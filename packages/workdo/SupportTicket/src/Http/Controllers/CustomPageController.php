<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\SupportTicketCustomPage;
use Workdo\SupportTicket\Http\Requests\StoreCustomPageRequest;
use Workdo\SupportTicket\Http\Requests\UpdateCustomPageRequest;
use Workdo\SupportTicket\Events\CreateCustomPage;
use Workdo\SupportTicket\Events\UpdateCustomPage;
use Workdo\SupportTicket\Events\DestroyCustomPage;

class CustomPageController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-support-ticket-custom-pages')) {
            $customPages = SupportTicketCustomPage::where('created_by', creatorId())->get();

            $actualUserSlug = 'default';
            if (Auth::user()->type == 'company') {
                $actualUserSlug = Auth::user()->slug ?? 'default';
            } else {
                $createdByUser = User::find(creatorId());
                if ($createdByUser) {
                    $actualUserSlug = $createdByUser->slug ?? 'default';
                }
            }

            return Inertia::render('SupportTicket/SystemSetup/CustomPages/Index', [
                'customPages' => $customPages,
                'actualUserSlug' => $actualUserSlug,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomPageRequest $request)
    {
        if (Auth::user()->can('create-support-ticket-custom-pages')) {
            $customPage = new SupportTicketCustomPage();
            $customPage->title = $request->title;
            $customPage->slug = $request->slug ?: Str::slug($request->title);
            $customPage->contents = $request->contents;
            $customPage->description = $request->description;
            $customPage->enable_page_footer = $request->enable_page_footer ?? 'off';
            $customPage->creator_id = Auth::id();
            $customPage->created_by = creatorId();
            $customPage->save();

            CreateCustomPage::dispatch($request, $customPage);

            return redirect()->route('support-ticket.custom-pages.index')->with('success', __('The custom page has been created successfully.'));
        } else {
            return redirect()->route('support-ticket.custom-pages.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCustomPageRequest $request, $id)
    {
        if (Auth::user()->can('edit-support-ticket-custom-pages')) {
            $supportTicketCustomPage = SupportTicketCustomPage::where('id', $id)
                ->where('created_by', creatorId())
                ->first();

            $supportTicketCustomPage->title = $request->title;
            $supportTicketCustomPage->slug = $request->slug ?: Str::slug($request->title);
            $supportTicketCustomPage->contents = $request->contents;
            $supportTicketCustomPage->description = $request->description;
            $supportTicketCustomPage->enable_page_footer = $request->enable_page_footer ?? 'off';
            $supportTicketCustomPage->save();

            UpdateCustomPage::dispatch($request, $supportTicketCustomPage);

            return redirect()->route('support-ticket.custom-pages.index')->with('success', __('The custom page details are updated successfully.'));
        } else {
            return redirect()->route('support-ticket.custom-pages.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-support-ticket-custom-pages')) {
            $supportTicketCustomPage = SupportTicketCustomPage::where('id', $id)
                ->where('created_by', creatorId())
                ->firstOrFail();

            DestroyCustomPage::dispatch($supportTicketCustomPage);
            $supportTicketCustomPage->delete();

            return redirect()->route('support-ticket.custom-pages.index')->with('success', __('The custom page has been deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}