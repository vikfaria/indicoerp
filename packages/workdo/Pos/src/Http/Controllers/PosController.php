<?php

namespace Workdo\Pos\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Workdo\ProductService\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Workdo\Pos\Events\CreatePos;
use Workdo\ProductService\Models\ProductServiceCategory;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Pos\Models\Pos;
use Workdo\Pos\Models\PosItem;
use Workdo\Pos\Http\Requests\StorePosRequest;
use Workdo\Pos\Events\CreatePosSale;
use Workdo\Pos\Models\PosPayment;
use Workdo\ProductService\Models\ProductServiceTax;

class PosController extends Controller
{
    private function canAccessSale(Pos $sale): bool
    {
        if ($sale->created_by != creatorId()) {
            return false;
        }

        if (in_array(Auth::user()->type, ['company', 'superadmin'])) {
            return true;
        }

        return $sale->customer_id == Auth::id() || $sale->creator_id == Auth::id();
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-pos-orders')){
            $query = Pos::with(['customer:id,name,email', 'warehouse:id,name', 'payment:pos_id,discount,amount,discount_amount'])
                ->withCount('items')
                ->where('created_by', creatorId());

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('sale_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($customerQuery) use ($search) {
                          $customerQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('warehouse', function($warehouseQuery) use ($search) {
                          $warehouseQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('customer')) {
                $customer = $request->get('customer');
                $query->whereHas('customer', function($customerQuery) use ($customer) {
                    $customerQuery->where('name', 'like', "%{$customer}%");
                });
            }

            if ($request->filled('warehouse')) {
                $warehouse = $request->get('warehouse');
                $query->whereHas('warehouse', function($warehouseQuery) use ($warehouse) {
                    $warehouseQuery->where('name', 'like', "%{$warehouse}%");
                });
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            if (in_array($sortField, ['sale_number', 'total', 'created_at'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->latest();
            }

            $perPage = $request->get('per_page', 10);
            $sales = $query->paginate($perPage)->withQueryString();

            $sales->getCollection()->transform(function($sale) {
                $sale->total = $sale->payment ? $sale->payment->discount_amount : 0;
                return $sale;
            });

            return Inertia::render('Pos/PosOrder/Index', [
                'sales' => $sales,
            ]);
        }else{
            return redirect()->route('warehouses.index')->with('error', __('Permission denied'));

        }
    }

    public function create()
    {
        if(Auth::user()->can('create-pos')){
            $customers = User::whereHas('roles', function($query) {
                $query->where('name', 'client');
            })->where('created_by', creatorId())->select('id', 'name', 'email')->get();

            $warehouses = Warehouse::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'name', 'address')
                ->get();

            $categories = ProductServiceCategory::where('created_by', creatorId())
                ->select('id', 'name', 'color')
                ->get();
            return Inertia::render('Pos/Pos/Create', [
                'customers' => $customers,
                'warehouses' => $warehouses,
                'categories' => $categories,
            ]);
        }else{
            return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function getProducts(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $categoryId = $request->get('category_id');

        if (!$warehouseId) {
            return response()->json([]);
        }

        $query = ProductServiceItem::select('id', 'name', 'sku', 'sale_price', 'category_id', 'image', 'tax_ids')
            ->with(['category:id,name', 'warehouseStocks' => function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0);
            }])
            ->whereHas('warehouseStocks', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0);
            })
            ->where('created_by', creatorId())
            ->where('is_active', true)
            ->where('type', '!=', 'service');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->get()
            ->map(function($product) {
                $warehouseStock = $product->warehouseStocks->first();
                $taxes = [];
                if ($product->tax_ids && is_array($product->tax_ids) && !empty($product->tax_ids)) {
                    $taxes = \Workdo\ProductService\Models\ProductServiceTax::whereIn('id', $product->tax_ids)
                        ->where('created_by', creatorId())
                        ->get(['id', 'tax_name', 'rate'])
                        ->map(function($tax) {
                            return [
                                'id' => $tax->id,
                                'name' => $tax->tax_name,
                                'rate' => $tax->rate
                            ];
                        })
                        ->toArray();
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->sale_price,
                    'stock' => $warehouseStock ? $warehouseStock->quantity : 0,
                    'category' => $product->category ? $product->category->name : null,
                    'image' => $product->image,
                    'tax_ids_debug' => $product->tax_ids,
                    'taxes' => $taxes
                ];
            });

        return response()->json($products);
    }

    public function store(StorePosRequest $request)
    {
        if(Auth::user()->can('create-pos')){
            $validated = $request->validated();

            $saleNumber = Pos::generateSaleNumber();
            $sale = new Pos();
            $sale->sale_number = $saleNumber;
            $sale->customer_id = $validated['customer_id'] ?? null;
            $sale->warehouse_id = $validated['warehouse_id'];
            $sale->pos_date = $validated['pos_date'] ?? now()->toDateString();
            $sale->creator_id = Auth::id();
            $sale->created_by = creatorId();
            $sale->save();

            $finalAmount = 0;
            foreach ($validated['items'] as $item) {
                $product = ProductServiceItem::where('id', $item['id'])
                    ->where('created_by', creatorId())
                    ->where('is_active', true)
                    ->where('type', 'product')
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => __('One or more selected products are invalid.'),
                    ]);
                }

                $subtotal = $item['quantity'] * $item['price'];

                $taxAmount = 0;
                $taxIds = null;
                if ($product && $product->tax_ids && is_array($product->tax_ids) && !empty($product->tax_ids)) {
                    $taxIds = $product->tax_ids;
                    $taxes = ProductServiceTax::whereIn('id', $taxIds)
                        ->where('created_by', creatorId())
                        ->get();

                    foreach ($taxes as $tax) {
                        $taxAmount += $subtotal * ($tax->rate / 100);
                    }
                }

                $totalAmount = $subtotal + $taxAmount;
                $finalAmount+= $totalAmount;
                $saleItem = new PosItem();
                $saleItem->pos_id = $sale->id;
                $saleItem->product_id = $item['id'];
                $saleItem->quantity = $item['quantity'];
                $saleItem->price = $item['price'];
                $saleItem->tax_ids = $taxIds;
                $saleItem->subtotal = $subtotal;
                $saleItem->tax_amount = $taxAmount;
                $saleItem->total_amount = $totalAmount;
                $saleItem->creator_id = Auth::id();
                $saleItem->created_by = creatorId();
                $saleItem->save();

            }

            $posPayment = new PosPayment();
            $posPayment->pos_id = $sale->id;
            $posPayment->discount = $validated['discount'];
            $posPayment->amount = $finalAmount;
            $posPayment->discount_amount = $finalAmount-$validated['discount'];
            $posPayment->creator_id = Auth::id();
            $posPayment->created_by = creatorId();
            $posPayment->save();

            try {
                CreatePos::dispatch($request, $sale);
            } catch (\Throwable $th) {
                
            }

            return back()->with('success', __('The POS sale has been created successfully.'));
        }else{
            return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Pos $sale)
    {
        if(Auth::user()->can('view-pos-orders') && $this->canAccessSale($sale)){
            $sale->load([
                'customer:id,name,email',
                'warehouse:id,name',
                'items:id,pos_id,product_id,quantity,price,subtotal,tax_ids,tax_amount,total_amount',
                'items.product:id,name,sku',
                'payment:pos_id,discount,amount,discount_amount'
            ]);
            $totals = PosItem::where('pos_id', $sale->id)
                ->selectRaw('SUM(subtotal) as subtotal, SUM(tax_amount) as tax_amount, SUM(total_amount) as total_amount')
                ->first();

            $sale->subtotal = $totals->subtotal ?? 0;
            $sale->tax_amount = $totals->tax_amount ?? 0;
            $sale->discount_amount = $sale->payment ? $sale->payment->discount : 0;
            $sale->total_amount = $sale->payment ? $sale->payment->discount_amount : 0;

            $sale->items->each(function($item) {
                $taxes = [];
                if ($item->tax_ids && is_array($item->tax_ids)) {
                    $taxes = ProductServiceTax::whereIn('id', $item->tax_ids)
                        ->where('created_by', creatorId())
                        ->get(['id', 'tax_name', 'rate'])
                        ->toArray();
                }
                $item->taxes = $taxes;
            });

            return Inertia::render('Pos/PosOrder/Show', [
                'sale' => $sale,
            ]);
        }else{
           return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function barcode()
    {
        if(Auth::user()->can('manage-pos-barcodes')){
            $warehouses = Warehouse::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'name')
                ->get();

            return Inertia::render('Pos/Barcode/Index', [
                'warehouses' => $warehouses,
            ]);
        }else{
           return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function printBarcode(Request $request, $sale = null)
    {
        if(Auth::user()->can('manage-pos-barcodes')){
            return Inertia::render('Pos/Barcode/Print', [
                'products' => json_decode($request->get('products'), true),
                'copies' => json_decode($request->get('copies'), true),
            ]);
        }else{
           return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function print(Pos $sale)
    {
        if(Auth::user()->can('view-pos-orders') && $this->canAccessSale($sale)){
            $sale->load([
                'customer:id,name,email',
                'warehouse:id,name',
                'items:id,pos_id,product_id,quantity,price,subtotal,tax_ids,tax_amount,total_amount',
                'items.product:id,name,sku',
                'payment:pos_id,discount,amount,discount_amount'
            ]);

            $totals = PosItem::where('pos_id', $sale->id)
                ->selectRaw('SUM(subtotal) as subtotal, SUM(tax_amount) as tax_amount, SUM(total_amount) as total_amount')
                ->first();

            $sale->subtotal = $totals->subtotal ?? 0;
            $sale->tax_amount = $totals->tax_amount ?? 0;
            $sale->discount_amount = $sale->payment ? $sale->payment->discount : 0;
            $sale->total_amount = $sale->payment ? $sale->payment->discount_amount : 0;

            $sale->items->each(function($item) {
                $taxes = [];
                if ($item->tax_ids && is_array($item->tax_ids)) {
                    $taxes = ProductServiceTax::whereIn('id', $item->tax_ids)
                        ->where('created_by', creatorId())
                        ->get(['id', 'tax_name', 'rate'])
                        ->toArray();
                }
                $item->taxes = $taxes;
            });

            return Inertia::render('Pos/PosOrder/Print', [
                'sale' => $sale,
            ]);
        }else{
           return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }
    public function getNextPosNumber()
    {
        return response()->json([
            'pos_number' => Pos::generateSaleNumber()
        ]);
    }
}
