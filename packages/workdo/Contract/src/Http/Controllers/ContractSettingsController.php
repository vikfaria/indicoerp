<?php

namespace Workdo\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractSettingsController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->can('manage-contract-settings')) {
            $validator = Validator::make($request->all(), [
                'settings.contract_prefix' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first());
            }

            $settings = $request->input('settings', []);
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId());
                }

                return back()->with("success", __('Contract settings updated successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', __('Failed to update contract settings: ') . $e->getMessage());
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}