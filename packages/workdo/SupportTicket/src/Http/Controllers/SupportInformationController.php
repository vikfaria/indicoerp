<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Http\Requests\StoreSupportInformationRequest;
use Workdo\SupportTicket\Events\CreateSupportInformation;

class SupportInformationController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-support-information')) {
            $supportInfo = SupportTicketSetting::where('key', 'support_information')->where('created_by', creatorId())->first();
            $data = $supportInfo ? json_decode($supportInfo->value, true) : null;

            return Inertia::render('SupportTicket/SystemSetup/support-information', [
                'supportInformation' => $data
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreSupportInformationRequest $request)
    {
        if (Auth::user()->can('edit-support-ticket-support-information')) {
            $supportInfo = SupportTicketSetting::updateOrCreate(
                ['key' => 'support_information', 'created_by' => creatorId()],
                ['value' => json_encode($request->validated())]
            );

            CreateSupportInformation::dispatch($request, $supportInfo);

            return redirect()->back()->with('success', __('The support information details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}