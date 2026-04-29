<?php

namespace Workdo\ProductService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\ProductService\Events\CreateProductServiceUnit;
use Workdo\ProductService\Events\DestroyProductServiceUnit;
use Workdo\ProductService\Events\UpdateProductServiceUnit;
use Workdo\ProductService\Models\ProductServiceUnit;

class UnitController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-product-service-units')){
            $units = ProductServiceUnit::select('id', 'unit_name', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-product-service-units')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-product-service-units')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('ProductService/SystemSetup/Units/Index', [
                'units' => $units,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function store(Request $request)
    {
        if(Auth::user()->can('create-product-service-units')){
            $validated = $request->validate([
                'unit_name' => 'required|string|max:255',
            ]);

            $unit = new ProductServiceUnit();
            $unit->unit_name = $validated['unit_name'];
            $unit->creator_id = Auth::id();
            $unit->created_by = creatorId();
            $unit->save();

            // Dispatch event for packages to handle their fields
            CreateProductServiceUnit::dispatch($request, $unit);

            return redirect()->route('product-service.units.index')->with('success', __('The unit has been created successfully.'));
        }
        return redirect()->route('product-service.units.index')->with('error', __('Permission denied'));
    }

    public function update(Request $request, ProductServiceUnit $unit)
    {
        if(Auth::user()->can('edit-product-service-units')){
            $validated = $request->validate([
                'unit_name' => 'required|string|max:255',
            ]);

            $unit->unit_name = $validated['unit_name'];
            $unit->save();

            // Dispatch event for packages to handle their fields
            UpdateProductServiceUnit::dispatch($request, $unit);

            return redirect()->route('product-service.units.index')->with('success', __('The unit details are updated successfully.'));
        }
        return redirect()->route('product-service.units.index')->with('error', __('Permission denied'));
    }

    public function destroy(ProductServiceUnit $unit)
    {
        if(Auth::user()->can('delete-product-service-units')){

            // Dispatch event for packages to handle their fields
            DestroyProductServiceUnit::dispatch($unit);

            $unit->delete();

            return redirect()->route('product-service.units.index')->with('success', __('The unit has been deleted.'));
        }
        return redirect()->route('product-service.units.index')->with('error', __('Permission denied'));
    }
}
