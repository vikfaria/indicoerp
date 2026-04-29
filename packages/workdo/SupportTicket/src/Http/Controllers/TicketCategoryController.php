<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Http\Requests\StoreTicketCategoryRequest;
use Workdo\SupportTicket\Http\Requests\UpdateTicketCategoryRequest;

class TicketCategoryController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-ticket-categories')) {
            $user = Auth::user();
            $categories = TicketCategory::select('id', 'name', 'color', 'created_at')
                ->where(function ($q) use ($user) {
                    if ($user->can('manage-any-ticket-categories')) {
                        $q->where('created_by', creatorId());
                    }elseif ($user->can('manage-own-ticket-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('SupportTicket/SystemSetup/Categories/Index', [
                'categories' => $categories
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTicketCategoryRequest $request)
    {
        if (Auth::user()->can('create-ticket-categories')) {
            $ticketCategory = new TicketCategory();
            $ticketCategory->name = $request->name;
            $ticketCategory->color = $request->color;
            $ticketCategory->creator_id = Auth::id();
            $ticketCategory->created_by = creatorId();
            $ticketCategory->save();

            return redirect()->route('ticket-category.index')->with('success', __('The category has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTicketCategoryRequest $request, $category)
    {
        if (Auth::user()->can('edit-ticket-categories')) {
            $ticketCategory = TicketCategory::find($category);
            
            $ticketCategory->name = $request->name;
            $ticketCategory->color = $request->color;
            $ticketCategory->save();

            return back()->with('success', __('The category details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy($category)
    {
        try {
            if (Auth::user()->can('delete-ticket-categories')) {
                $ticketCategory = TicketCategory::find($category);
                $tickets = Ticket::where('category', $ticketCategory->id)->count();
                
                if ($tickets > 0) {
                    return back()->with('error', __('This category is used on tickets'));
                }

                $ticketCategory->delete();

                return back()->with('success', __('The category has been deleted.'));
            } else {
                return back()->with('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Category not found'));
        }
    }

    public function supportCategories()
    {
        return Inertia::render('SupportTicket/SystemSetup/SupportCategories/Index');
    }
}