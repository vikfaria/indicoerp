<?php

namespace Workdo\LandingPage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use App\Models\AddOn;
use App\Models\User;
use Workdo\LandingPage\Models\MarketplaceSetting;
use Workdo\LandingPage\Models\LandingPageSetting;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = AddOn::where('is_enable', true)->whereNotIn('module', User::$superadmin_activated_module);
        $packages = $query->get();
        
        // Find the package matching the given slug
        $matchedPackage = $packages->firstWhere('package_name', $request->slug);

        // If no package found, show 404 page
        if (!$matchedPackage) {
            $landingPageSettings = LandingPageSetting::first();
            return Inertia::render('LandingPage/MarketplaceNotFound', [
                'landingPageSettings' => $landingPageSettings
            ]);
        }

        // Use the "name" field (module name style)
        $moduleName = $matchedPackage->module;

        $settings = MarketplaceSetting::where('module', $moduleName)->first();
        $landingPageSettings = LandingPageSetting::first();
        
        return Inertia::render('LandingPage/Marketplace', [
            'packages' => $packages,
            'settings' => $settings,
            'landingPageSettings' => $landingPageSettings
        ]);
    }

    public function settings(Request $request)
    {
        if(Auth::user()->can('manage-marketplace-settings')){
            $module = $request->get('module');
            $settings = null;
            
            if ($module) {
                $settings = MarketplaceSetting::where('module', $module)->first();
            }
            
            $activeModules = AddOn::where('is_enable', true)->get(['name', 'module', 'package_name'])->map(function($addon) {
                return [
                    'module' => $addon->module,
                    'name' => $addon->name,
                    'version' => $addon->version ?? '1.0.0',
                    'package_name' => $addon->package_name
                ];
            });
        
            return Inertia::render('LandingPage/marketplace/Settings', [
                'settings' => $settings ?: [
                    'module' => $module,
                    'title' => 'Marketplace',
                    'subtitle' => 'Discover powerful modules for your business',
                    'config_sections' => []
                ],
                'activeModules' => $activeModules,
                'selectedModule' => $module
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeSettings(Request $request)
    {
        if(Auth::user()->can('manage-marketplace-settings')){
            $validated = $request->validate([
                'module' => 'required|string',
                'title' => 'nullable|string|max:255',
                'subtitle' => 'nullable|string',
                'config_sections' => 'nullable|array'
            ]);

            // Handle image paths - store only filename
            if (isset($validated['config_sections']['sections'])) {
                $this->processImagePaths($validated['config_sections']['sections']);
            }

            MarketplaceSetting::updateOrCreate(['module' => $validated['module']], $validated);
        
            return back()->with('success', 'Marketplace settings saved successfully');
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function processImagePaths(&$sections)
    {
        foreach ($sections as $sectionKey => &$sectionData) {
            if (is_array($sectionData)) {
                // Handle hero image
                if (isset($sectionData['image'])) {
                    $sectionData['image'] = $this->processImagePath($sectionData['image']);
                }
                
                // Handle screenshots images array
                if (isset($sectionData['images']) && is_array($sectionData['images'])) {
                    $sectionData['images'] = array_map([$this, 'processImagePath'], $sectionData['images']);
                }
                
                // Handle dedication subSections screenshots
                if (isset($sectionData['subSections']) && is_array($sectionData['subSections'])) {
                    foreach ($sectionData['subSections'] as &$subSection) {
                        if (isset($subSection['screenshot'])) {
                            $subSection['screenshot'] = $this->processImagePath($subSection['screenshot']);
                        }
                    }
                }
            }
        }
    }

    private function processImagePath($imagePath)
    {
        if (strpos($imagePath, 'packages/workdo') !== false) {
            return $imagePath;
        }
        return basename($imagePath);
    }


}