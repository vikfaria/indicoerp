<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Services\JournalService;
use Workdo\ProductService\Models\ProductServiceItem;

class InventoryCostingFifoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_fifo_layers_drive_stock_valuation_and_cogs_on_posted_invoice_flow(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCounterpartyUser($company, 'client', 'Cliente FIFO');
        $vendor = $this->makeCounterpartyUser($company, 'vendor', 'Fornecedor FIFO');
        $warehouse = $this->makeWarehouse($company, 'WH-FIFO');

        $this->actingAs($company);
        AccountUtility::defaultdata($company->id);

        $product = $this->makeProduct($company, 'Produto FIFO', 99.00, 99.00);

        $purchaseOne = $this->makePurchaseInvoice($company, $vendor, $warehouse, 'PC-FIFO-001', '2026-06-01', 50.00);
        $this->makePurchaseItem($purchaseOne, $product, 10, 5.00);

        $purchaseTwo = $this->makePurchaseInvoice($company, $vendor, $warehouse, 'PC-FIFO-002', '2026-06-02', 80.00);
        $this->makePurchaseItem($purchaseTwo, $product, 10, 8.00);

        app(InventoryCostingService::class)->recordPurchase(
            $company->id,
            $product->id,
            10,
            5.00,
            '2026-06-01',
            'purchase_invoice',
            $purchaseOne->id,
            (string) $warehouse->id,
            false
        );

        app(InventoryCostingService::class)->recordPurchase(
            $company->id,
            $product->id,
            10,
            8.00,
            '2026-06-02',
            'purchase_invoice',
            $purchaseTwo->id,
            (string) $warehouse->id,
            false
        );

        $salesInvoice = $this->makeSalesInvoice($company, $customer, $warehouse, 'FT-FIFO-001', '2026-06-03', 240.00);
        $this->makeSalesItem($salesInvoice, $product, 12, 20.00);

        app(InventoryCostingService::class)->recordSale(
            $company->id,
            $product->id,
            12,
            '2026-06-03',
            'sales_invoice',
            $salesInvoice->id,
            (string) $warehouse->id,
            false
        );

        $valuation = app(InventoryCostingService::class)->getStockValuation($company->id, null, (string) $warehouse->id);
        $position = app(InventoryCostingService::class)->getCurrentPosition($company->id, $product->id, (string) $warehouse->id);

        $this->assertSame(8.0, round((float) $position['quantity'], 2));
        $this->assertSame(64.0, round((float) $position['value'], 2));
        $this->assertSame(64.0, round((float) $valuation['total_value'], 2));
        $this->assertSame(1, (int) $valuation['product_count']);

        $layers = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $layers);
        $this->assertSame(0.0, round((float) $layers[0]->remaining_quantity, 2));
        $this->assertTrue((bool) $layers[0]->is_exhausted);
        $this->assertSame(8.0, round((float) $layers[1]->remaining_quantity, 2));
        $this->assertFalse((bool) $layers[1]->is_exhausted);

        $movements = StockMovement::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $movements);
        $this->assertSame('purchase', (string) $movements[0]->movement_type);
        $this->assertSame('purchase', (string) $movements[1]->movement_type);
        $this->assertSame('sale', (string) $movements[2]->movement_type);
        $this->assertSame(-66.0, round((float) $movements[2]->total_cost, 2));

        $salesInvoice = $salesInvoice->refresh();
        $cogsJournal = app(JournalService::class)->createSalesCOGSJournal($salesInvoice);

        $this->assertNotNull($cogsJournal);
        $this->assertSame(66.0, round((float) $cogsJournal->total_debit, 2));
        $this->assertSame(66.0, round((float) $cogsJournal->total_credit, 2));
    }

    public function test_sale_is_blocked_when_fifo_stock_is_insufficient(): void
    {
        $company = $this->makeCompany();
        $warehouse = $this->makeWarehouse($company, 'WH-NEG');

        $this->actingAs($company);
        AccountUtility::defaultdata($company->id);

        $product = $this->makeProduct($company, 'Produto sem stock', 99.00, 99.00);

        app(InventoryCostingService::class)->recordPurchase(
            $company->id,
            $product->id,
            2,
            5.00,
            '2026-06-01',
            'purchase_invoice',
            1,
            (string) $warehouse->id,
            false
        );

        $this->expectException(ValidationException::class);

        app(InventoryCostingService::class)->recordSale(
            $company->id,
            $product->id,
            5,
            '2026-06-02',
            'sales_invoice',
            99,
            (string) $warehouse->id,
            false
        );
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeCounterpartyUser(User $company, string $type, string $name): User
    {
        return User::factory()->create([
            'type' => $type,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeWarehouse(User $company, string $name): Warehouse
    {
        return Warehouse::query()->create([
            'name' => $name,
            'address' => 'Maputo',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '841111111',
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company, string $name, float $salePrice, float $purchasePrice): ProductServiceItem
    {
        return ProductServiceItem::query()->create([
            'name' => $name,
            'sku' => strtoupper(str_replace(' ', '-', $name)) . '-SKU',
            'category_id' => null,
            'description' => $name,
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'unit' => null,
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makePurchaseInvoice(User $company, User $vendor, Warehouse $warehouse, string $number, string $date, float $totalAmount): PurchaseInvoice
    {
        return PurchaseInvoice::query()->create([
            'invoice_number' => $number,
            'invoice_date' => $date,
            'due_date' => $date,
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => $totalAmount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'validated',
        ]);
    }

    private function makePurchaseItem(PurchaseInvoice $invoice, ProductServiceItem $product, int $quantity, float $unitPrice): PurchaseInvoiceItem
    {
        return PurchaseInvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'STD',
            'tax_exemption_reason' => null,
        ]);
    }

    private function makeSalesInvoice(User $company, User $customer, Warehouse $warehouse, string $number, string $date, float $totalAmount): SalesInvoice
    {
        return SalesInvoice::query()->create([
            'invoice_number' => $number,
            'invoice_date' => $date,
            'due_date' => $date,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => $totalAmount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);
    }

    private function makeSalesItem(SalesInvoice $invoice, ProductServiceItem $product, int $quantity, float $unitPrice): SalesInvoiceItem
    {
        return SalesInvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'STD',
            'tax_exemption_reason' => null,
        ]);
    }
}
