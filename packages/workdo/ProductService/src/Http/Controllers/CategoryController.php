<?php

namespace Workdo\ProductService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\ProductService\Events\CreateProductServiceCategory;
use Workdo\ProductService\Events\DestroyProductServiceCategory;
use Workdo\ProductService\Events\UpdateProductServiceCategory;
use Workdo\ProductService\Models\ProductServiceCategory;

class CategoryController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-product-service-categories')){
            $categories = ProductServiceCategory::select('id', 'name','color', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-product-service-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-product-service-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('ProductService/SystemSetup/Categories/Index', [
                'categories' => $categories,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if(Auth::user()->can('create-product-service-categories')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            $category = new ProductServiceCategory();
            $category->name = $validated['name'];
            $category->color = $validated['color'];
            $category->creator_id = Auth::id();
            $category->created_by = creatorId();
            $category->save();

            // Dispatch event for packages to handle their fields
            CreateProductServiceCategory::dispatch($request, $category);

            return redirect()->route('product-service.item-categories.index')->with('success', __('The category has been created successfully.'));
        }
        return redirect()->route('product-service.item-categories.index')->with('error', __('Permission denied'));
    }

    public function update(Request $request, ProductServiceCategory $itemCategory)
    {
        if(Auth::user()->can('edit-product-service-categories')){
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string|max:7',
            ]);

            $itemCategory->name = $validated['name'];
            $itemCategory->color = $validated['color'];
            $itemCategory->save();

             // Dispatch event for packages to handle their fields
            UpdateProductServiceCategory::dispatch($request, $itemCategory);

            return redirect()->route('product-service.item-categories.index')->with('success', __('The category details are updated successfully.'));
        }
        return redirect()->route('product-service.item-categories.index')->with('error', __('Permission denied'));
    }

    public function destroy(ProductServiceCategory $itemCategory)
    {
        if(Auth::user()->can('delete-product-service-categories')){

            DestroyProductServiceCategory::dispatch($itemCategory);

            $itemCategory->delete();

            return redirect()->route('product-service.item-categories.index')->with('success', __('The category has been deleted.'));
        }
        return redirect()->route('product-service.item-categories.index')->with('error', __('Permission denied'));
    }
}
