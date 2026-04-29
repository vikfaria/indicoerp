<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Http\Requests\StoreCtaSectionRequest;
use Workdo\SupportTicket\Events\CreateCtaSection;

class CtaSectionController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-cta-sections')) {
            $ctaSections = SupportTicketSetting::where('key', 'cta_sections')->where('created_by', creatorId())->first();
            $data = $ctaSections ? json_decode($ctaSections->value, true) : null;

            return Inertia::render('SupportTicket/SystemSetup/cta-section', [
                'ctaSections' => $data
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCtaSectionRequest $request)
    {
        if (Auth::user()->can('edit-support-ticket-cta-sections') || Auth::user()->can('create-support-ticket-cta-sections')) {
            $ctaSection = SupportTicketSetting::updateOrCreate(
                ['key' => 'cta_sections', 'created_by' => creatorId()],
                [
                    'value' => json_encode($request->validated()),
                    'creator_id' => Auth::id()
                ]
            );

            CreateCtaSection::dispatch($request, $ctaSection);

            return redirect()->back()->with('success', __('The CTA section details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}