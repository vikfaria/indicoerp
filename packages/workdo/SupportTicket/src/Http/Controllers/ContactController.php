<?php

namespace Workdo\SupportTicket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\Contact;
use Workdo\SupportTicket\Http\Requests\StoreContactRequest;
use Workdo\SupportTicket\Events\CreateContact;
use Workdo\SupportTicket\Events\DestroyContact;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-contact')) {
            $query = Contact::where(function ($q) {
                if (Auth::user()->can('manage-any-contact')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-contact')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            }

            // Sorting functionality
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            if (in_array($sortField, ['first_name', 'last_name', 'email', 'subject', 'created_at'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $contacts = $query->paginate($perPage)->withQueryString();

            return Inertia::render('SupportTicket/Contact/Index', [
                'contacts' => $contacts
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreContactRequest $request)
    {
        $contact = new Contact();
        $contact->name = $request->firstName . ' ' . $request->lastName;
        $contact->email = $request->email;
        $contact->subject = $request->subject;
        $contact->message = $request->message;
        $contact->creator_id = Auth::id();
        $contact->created_by = creatorId();
        $contact->save();

        CreateContact::dispatch($request, $contact);

        return redirect()->back()->with('success', __('Contact message has been sent successfully.'));
    }

    public function show(Contact $contact)
    {
        try {
            if (Auth::user()->can('view-contact')) {
                // Check if user has permission to view this specific contact
                if (Auth::user()->can('manage-any-contact') || 
                    (Auth::user()->can('manage-own-contact') && $contact->creator_id == Auth::id()) ||
                    $contact->created_by == creatorId()) {
                    
                    return response()->json([
                        'contact' => $contact
                    ]);
                } else {
                    return response()->json(['error' => __('Permission denied')], 403);
                }
            } else {
                return response()->json(['error' => __('Permission denied')], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => __('Contact not found')], 404);
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-contact')) {
            $contact = Contact::find($id);
            DestroyContact::dispatch($contact);
            $contact->delete();
            return back()->with('success', __('The contact has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}