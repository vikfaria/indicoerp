<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Http\Requests\StoreTitleSectionRequest;


class TitleSectionController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-title-sections')) {
            $titleSections = SupportTicketSetting::where('key', 'title_sections')->where('created_by', creatorId())->first();
            $data = $titleSections ? json_decode($titleSections->value, true) : null;

            return Inertia::render('SupportTicket/SystemSetup/title-section', [
                'titleSections' => $data
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTitleSectionRequest $request)
    {
        if (Auth::user()->can('manage-support-ticket-title-sections')) {
            $titleSection = SupportTicketSetting::updateOrCreate(
                ['key' => 'title_sections', 'created_by' => creatorId()],
                [
                    'value' => json_encode($request->validated())
                ]
            );

            return redirect()->back()->with('success', __('The title sections deatils are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}