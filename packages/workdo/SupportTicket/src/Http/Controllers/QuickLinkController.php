<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\QuickLink;
use Workdo\SupportTicket\Http\Requests\StoreQuickLinkRequest;
use Workdo\SupportTicket\Http\Requests\UpdateQuickLinkRequest;
use Workdo\SupportTicket\Events\CreateQuickLink;
use Workdo\SupportTicket\Events\UpdateQuickLink;
use Workdo\SupportTicket\Events\DestroyQuickLink;

class QuickLinkController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-quick-links')) {
            $quickLinks = QuickLink::where('created_by', creatorId())->orderBy('order')->get();

            return Inertia::render('SupportTicket/SystemSetup/QuickLinks/Index', [
                'quickLinks' => $quickLinks
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreQuickLinkRequest $request)
    {
        if (Auth::user()->can('create-support-ticket-quick-links')) {
            $validated = $request->validated();
            $maxOrder = QuickLink::where('created_by', creatorId())->max('order') ?? 0;

            $quickLink = new QuickLink();
            $quickLink->title = $validated['title'];
            $quickLink->icon = $validated['icon'];
            $quickLink->link = $validated['link'];
            $quickLink->order = $maxOrder + 1;
            $quickLink->creator_id = Auth::id();
            $quickLink->created_by = creatorId();
            $quickLink->save();

            CreateQuickLink::dispatch($request, $quickLink);

            return redirect()->back()->with('success', __('The quick link has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateQuickLinkRequest $request, $id)
    {
        if (Auth::user()->can('edit-support-ticket-quick-links')) {
            $quickLink = QuickLink::where('created_by', creatorId())->find($id);
            $quickLink->update($request->validated());

            UpdateQuickLink::dispatch($request, $quickLink);

            return redirect()->back()->with('success', __('The quick link details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-support-ticket-quick-links')) {
            $quickLink = QuickLink::where('created_by', creatorId())->find($id);
            
            DestroyQuickLink::dispatch($quickLink);
            
            $quickLink->delete();

            return redirect()->back()->with('success', __('The quick link has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}