<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\IpRestrict;
use Workdo\Hrm\Http\Requests\StoreIpRestrictRequest;
use Workdo\Hrm\Http\Requests\UpdateIpRestrictRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;


class IpRestrictController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-ip-restricts')){
            $iprestricts = IpRestrict::select('id', 'ip', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-ip-restricts')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-ip-restricts')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            $globalSettings = getCompanyAllSetting();
            $ipRestrictEnabled = $globalSettings['ip_restrict'] ?? 'off';

            return Inertia::render('Hrm/SystemSetup/IpRestricts/Index', [
                'iprestricts' => $iprestricts,
                'ipRestrictEnabled' => $ipRestrictEnabled,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreIpRestrictRequest $request)
    {
        if(Auth::user()->can('create-ip-restricts')){
            $validated = $request->validated();



            $iprestrict = new IpRestrict();
            $iprestrict->ip = $validated['ip'];

            $iprestrict->creator_id = Auth::id();
            $iprestrict->created_by = creatorId();
            $iprestrict->save();

            return redirect()->route('hrm.ip-restricts.index')->with('success', __('The ip restrict has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.ip-restricts.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateIpRestrictRequest $request, IpRestrict $iprestrict)
    {
        if(Auth::user()->can('edit-ip-restricts')){
            $validated = $request->validated();



            $iprestrict->ip = $validated['ip'];

            $iprestrict->save();

            return redirect()->route('hrm.ip-restricts.index')->with('success', __('The ip restrict details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.ip-restricts.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(IpRestrict $iprestrict)
    {
        if(Auth::user()->can('delete-ip-restricts')){
            $iprestrict->delete();

            return redirect()->route('hrm.ip-restricts.index')->with('success', __('The ip restrict has been deleted.'));
        }
        else{
            return redirect()->route('hrm.ip-restricts.index')->with('error', __('Permission denied'));
        }
    }

    public function toggleSetting(Request $request)
    {
        if(Auth::user()->can('manage-ip-restricts')){
            setSetting('ip_restrict', $request->enabled ? 'on' : 'off');
            return redirect()->back()->with('success',$request->enabled ? __('IP restrict enabled successfully.'): __('IP restrict disabled successfully.') );
        }
        else{
            return redirect()->route('hrm.ip-restricts.index')->with('error', __('Permission denied'));
        }
    }


}