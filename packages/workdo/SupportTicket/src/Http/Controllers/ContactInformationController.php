<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Http\Requests\StoreContactInformationRequest;
use Workdo\SupportTicket\Events\CreateContactInformation;

class ContactInformationController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-contact-information')) {
            $contactInfo = SupportTicketSetting::where('key', 'contact_information')
                ->where('created_by', creatorId())
                ->first();

            $data = $contactInfo ? json_decode($contactInfo->value, true) : null;

            return Inertia::render('SupportTicket/SystemSetup/ContactInformation/Index', [
                'contactInformation' => $data
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreContactInformationRequest $request)
    {
        if (Auth::user()->can('create-support-ticket-contact-information')) {
            $data = $request->only(['map_embed_url', 'address', 'phone', 'email', 'image']);

            $contactInfo = SupportTicketSetting::updateOrCreate(
                [
                    'key' => 'contact_information',
                    'created_by' => creatorId()
                ],
                [
                    'value' => json_encode($data)
                ]
            );

            CreateContactInformation::dispatch($request, $contactInfo);

            return redirect()->back()->with('success', __('The contact information details are updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}