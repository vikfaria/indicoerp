<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\EmailTemplate;
use Workdo\SupportTicket\Events\CreateTicket;
use Workdo\SupportTicket\Events\CreateTicketConversion;
use Workdo\SupportTicket\Events\DestroyTicket;
use Workdo\SupportTicket\Events\UpdateTicket;
use Workdo\SupportTicket\Http\Requests\StoreSupportTicketRequest;
use Workdo\SupportTicket\Http\Requests\UpdateSupportTicketRequest;
use Workdo\SupportTicket\Http\Requests\StoreConversionRequest;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\Conversion;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Models\SupportTicketCustomPage;
use Workdo\SupportTicket\Models\TicketField;


class SupportTicketController extends Controller
{
    private function getSettings()
    {
        $allSettings = SupportTicketSetting::getAllByCompany(creatorId());
        $privacyPolicy = $allSettings['privacy_policy'] ?? null;
        $termsConditions = $allSettings['terms_conditions'] ?? null;
        
        $privacyEnabled = false;
        $termsEnabled = false;
        
        if ($privacyPolicy) {
            $privacyData = json_decode($privacyPolicy, true);
            $privacyEnabled = $privacyData['enabled'] ?? false;
        }
        
        if ($termsConditions) {
            $termsData = json_decode($termsConditions, true);
            $termsEnabled = $termsData['enabled'] ?? false;
        }
        
        return [
            'faq_is_on' => company_setting('faq_is_on') ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on') ?? 'off',
            'privacy_policy_enabled' => $privacyEnabled,
            'terms_conditions_enabled' => $termsEnabled
        ];
    }

    private function getBrandSettings()
    {
        $query = SupportTicketSetting::whereIn('key', ['logo_dark', 'favicon', 'title_text', 'footer_text', 'terms_conditions', 'privacy_policy'])
            ->where('created_by', creatorId());
        
        $settings = $query->pluck('value', 'key')->toArray();
        $companyAllSetting = getCompanyAllSetting(creatorId());
        
        // Get all custom pages that are enabled for footer
        $customPages = SupportTicketCustomPage::where('created_by', creatorId())
            ->where('enable_page_footer', 'on')
            ->get(['slug', 'title']);
        
        $privacyEnabled = $customPages->where('slug', 'privacy-policy')->isNotEmpty();
        $termsEnabled = $customPages->where('slug', 'terms-conditions')->isNotEmpty();
        
        return [
            'logo_dark' => $settings['logo_dark'] ?? asset('packages/workdo/SupportTicket/src/Resources/assets/images/logo.png'),
            'favicon' => $settings['favicon'] ?? '/packages/workdo/SupportTicket/src/Resources/assets/images/favicon.png',
            'titleText' => $settings['title_text'] ?? 'Support Ticket System',
            'footerText' => $settings['footer_text'] ?? '© ' . date('Y') . ' Support System. All rights reserved.',
            'companyAllSetting' => $companyAllSetting,
            'companyId' => creatorId(),
            'termsEnabled' => $termsEnabled,
            'privacyEnabled' => $privacyEnabled,
            'customPages' => $customPages->map(function($page) {
                return [
                    'slug' => $page->slug,
                    'name' => $page->title
                ];
            })->toArray()
        ];
    }
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-support-tickets')) {
            $query = Ticket::with(['tcategory']);
            
            // Company admin or other roles
            $query->where(function ($q) {
                if (Auth::user()->can('manage-any-support-tickets')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-support-tickets')) {
                    $q->where(function($q)  {
                        $q->where('user_id', Auth::id())
                        ->orWhere('creator_id', Auth::id());
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
            
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_id', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->get('category'));
            }

            if ($request->filled('account_type')) {
                $query->where('account_type', $request->get('account_type'));
            }

            if ($request->filled('sort')) {
                $sortField = $request->get('sort');
                $sortDirection = $request->get('direction', 'asc');
                
                $allowedSortFields = ['ticket_id', 'name', 'email', 'subject', 'status', 'created_at'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = $request->get('per_page', 10);
            $tickets = $query->paginate($perPage);

            $tickets->getCollection()->transform(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'encrypted_id' => Crypt::encrypt($ticket->id),
                    'ticket_id' => $ticket->ticket_id,
                    'name' => $ticket->name,
                    'email' => $ticket->email,
                    'account_type' => $ticket->account_type,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'category' => [
                        'name' => $ticket->tcategory->name ?? 'No Category',
                        'color' => $ticket->tcategory->color ?? '#6B7280'
                    ],
                    'created_at' => $ticket->created_at->toISOString(),
                    'updated_at' => $ticket->updated_at->toISOString(),
                ];
            });

            $categories = TicketCategory::where(function ($q) {
                    if (Auth::user()->can('manage-any-knowledge-base')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->get();

            return Inertia::render('SupportTicket/Tickets/Index', [
                'tickets' => $tickets,
                'categories' => $categories,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-support-tickets')) {
            $categories = TicketCategory::where(function ($q) {
                if (Auth::user()->can('manage-any-knowledge-base')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->get();
            $staff = User::where('created_by', creatorId())
                ->where('type', 'staff')
                ->select('id', 'name', 'email')
                ->get();
            $clients = User::where('created_by', creatorId())
                ->where('type', 'client')
                ->select('id', 'name', 'email')
                ->get();
            $vendors = User::where('created_by', creatorId())
                ->where('type', 'vendor')
                ->select('id', 'name', 'email')
                ->get();

            // Get all fields (default + custom) ordered by 'order' field
            $allFields = TicketField::where('created_by', creatorId())
                ->where('status', true)
                ->orderBy('order')
                ->get();
                
            if ($allFields->count() < 1) {
                TicketField::defaultdata(creatorId());
                $allFields = TicketField::where('created_by', creatorId())
                    ->where('status', true)
                    ->orderBy('order')
                    ->get();
            }
            
            // Get custom fields (custom_id > 6) ordered by 'order' field
            $customFields = TicketField::where('created_by', creatorId())
                ->where('custom_id', '>', '6')
                ->where('status', true)
                ->orderBy('order')
                ->get();

            return Inertia::render('SupportTicket/Tickets/Create', [
                'categories' => $categories,
                'staff' => $staff,
                'clients' => $clients,
                'vendors' => $vendors,
                'allFields' => $allFields,
                'customFields' => $customFields
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreSupportTicketRequest $request)
    {
        if (Auth::user()->can('create-support-tickets')) {
            $validated = $request->validated();
            
            $validated['ticket_id'] = time();
            $validated['creator_id'] = Auth::id();
            $validated['created_by'] = creatorId();
            
            // Set default status if not provided
            if (empty($validated['status'])) {
                $validated['status'] = 'In Progress';
            }

            if ($validated['account_type'] == 'staff' || $validated['account_type'] == 'client' || $validated['account_type'] == 'vendor') {
                $user = User::find($validated['user_id']);
                if ($user) {
                    $validated['name'] = $user->name;
                    $validated['email'] = $user->email;
                } else {
                    return redirect()->back()->with('error', __('User not found'));
                }
            }

            if (!empty($validated['attachments'])) {
                $attachmentPaths = is_array($validated['attachments']) ? $validated['attachments'] : json_decode($validated['attachments'], true);
                if (is_array($attachmentPaths)) {
                    $attachments = [];
                    foreach ($attachmentPaths as $filePath) {
                        if (!empty($filePath) && $filePath !== 'logo_dark' && $filePath !== 'favicon') {
                            $filename = basename($filePath);
                            $attachments[] = [
                                'name' => $filename,
                                'path' => $filename
                            ];
                        }
                    }
                    $validated['attachments'] = json_encode($attachments);
                } else {
                    $validated['attachments'] = '[]';
                }
            } else {
                $validated['attachments'] = '[]';
            }

            $ticket = Ticket::create($validated);
            
            // Save custom field data
            if ($request->has('fields') && !empty($request->fields)) {
                TicketField::saveData($ticket, $request->fields);
            }

            CreateTicket::dispatch($request, $ticket);

            // Send email notification for new ticket
            if (!empty(company_setting('New Ticket')) && company_setting('New Ticket') == true) {
                $uArr = [
                    'ticket_name' => $ticket->name,
                    'email' => $ticket->email,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_url' => route('support-ticket.show', [Auth::user()->slug, Crypt::encrypt($ticket->id)])
                ];
                try {
                    EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $uArr);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', __('Ticket not found'));
                }
            }

            return redirect()->route('support-tickets.index')
                ->with('success', __('The ticket has been created successfully.'));
        } else {
            return redirect()->route('support-tickets.index')->with('error', __('Permission denied'));
        }
    }

   

    public function edit($id)
    {
        if(Auth::user()->can('edit-support-tickets')){
            $ticket = Ticket::with(['tcategory', 'conversions'])->find($id);
            
            if (!$ticket) {
                return redirect()->route('support-tickets.index')->with('error', __('Ticket not found'));
            }

                $categories = TicketCategory::where('created_by', creatorId())->get();
                $staff = User::where('created_by', creatorId())
                    ->where('type', 'staff')
                    ->select('id', 'name', 'email')
                    ->get();
                $clients = User::where('created_by', creatorId())
                    ->where('type', 'client')
                    ->select('id', 'name', 'email')
                    ->get();
                $vendors = User::where('created_by', creatorId())
                    ->where('type', 'vendor')
                    ->select('id', 'name', 'email')
                    ->get();

                // Get all fields (default + custom) ordered by 'order' field
                $allFields = TicketField::where('created_by', creatorId())
                    ->where('status', true)
                    ->orderBy('order')
                    ->get();
                    
                if ($allFields->count() < 1) {
                    TicketField::defaultdata(creatorId());
                    $allFields = TicketField::where('created_by', creatorId())
                        ->where('status', true)
                        ->orderBy('order')
                        ->get();
                }
                
                // Get custom fields (custom_id > 6) ordered by 'order' field
                $customFields = TicketField::where('created_by', creatorId())
                    ->where('custom_id', '>', '6')
                    ->where('status', true)
                    ->orderBy('order')
                    ->get();

            // Get existing field data
            $existingFieldData = TicketField::getData($ticket);
            
            $ticketData = [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'name' => $ticket->name,
                'email' => $ticket->email,
                'user_id' => $ticket->user_id,
                'account_type' => $ticket->account_type ?: 'custom',
                'category' => $ticket->category,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'description' => $ticket->description,
                'note' => $ticket->note,
                'attachments' => json_decode($ticket->attachments, true) ?? [],
                'fields' => $existingFieldData,
                'category_info' => $ticket->tcategory ? [
                    'id' => $ticket->tcategory->id,
                    'name' => $ticket->tcategory->name,
                    'color' => $ticket->tcategory->color
                ] : null,
                'conversions' => $ticket->conversions->map(function ($conversation) {
                    return [
                        'id' => $conversation->id,
                        'ticket_id' => $conversation->ticket_id,
                        'description' => $conversation->description,
                        'sender' => $conversation->sender,
                        'attachments' => is_array($conversation->attachments) ? $conversation->attachments : (json_decode($conversation->attachments, true) ?? []),
                        'created_at' => $conversation->created_at ? $conversation->created_at->toISOString() : null,
                        'replyBy' => ['name' => $conversation->replyBy() ? $conversation->replyBy()->name : 'User'],
                    ];
                }),
                'created_at' => $ticket->created_at ? $ticket->created_at->toISOString() : null,
                'updated_at' => $ticket->updated_at ? $ticket->updated_at->toISOString() : null,
            ];

            return Inertia::render('SupportTicket/Tickets/EditReply', [
                'ticket' => $ticketData,
                'categories' => $categories,
                'staff' => $staff,
                'clients' => $clients,
                'vendors' => $vendors,
                'allFields' => $allFields,
                'customFields' => $customFields,
                'slug' => Auth::user()->slug
            ]);
        }
        return redirect()->route('support-tickets.index')->with('error', __('Permission denied'));
    }

    public function update(UpdateSupportTicketRequest $request, $id)
    {
        if(Auth::user()->can('edit-support-tickets')){
            $ticket = Ticket::find($id);
            
            if (!$ticket) {
                return redirect()->route('support-tickets.index')->with('error', __('Ticket not found'));
            }
            
            $validated = $request->validated();

                if (isset($validated['account_type']) && $validated['account_type'] !== 'custom' && !empty($validated['user_id'])) {
                    $user = User::find($validated['user_id']);
                    if ($user) {
                        $validated['name'] = $user->name;
                        $validated['email'] = $user->email;
                        $validated['user_id'] = $user->id;
                    }
                } else {
                    $validated['user_id'] = null;
                }

                if (isset($validated['attachments']) && !empty($validated['attachments'])) {
                    $attachmentPaths = json_decode($validated['attachments'], true);
                    if (is_array($attachmentPaths)) {
                        $attachments = [];
                        foreach ($attachmentPaths as $filePath) {
                            if (!empty($filePath)) {
                                $filename = basename($filePath);
                                $attachments[] = [
                                    'name' => $filename,
                                    'path' => $filename
                                ];
                            }
                        }
                        $validated['attachments'] = json_encode($attachments);
                    }
                }

                $ticket->update($validated);
                
                // Save custom field data
                if ($request->has('fields') && !empty($request->fields)) {
                    TicketField::saveData($ticket, $request->fields);
                }

                UpdateTicket::dispatch($request, $ticket);

            return redirect()->back()->with('success', __('The ticket details are updated successfully.'));
        }
        return redirect()->route('support-tickets.index')->with('error', __('Permission denied'));
    }

    public function destroy($id)
    {
        if(Auth::user()->can('delete-support-tickets')){
            $ticket = Ticket::find($id);
            
            if (!$ticket) {
                return redirect()->route('support-tickets.index')->with('error', __('Ticket not found'));
            }
            
            Conversion::where('ticket_id', $ticket->id)->delete();

                $attachments = json_decode($ticket->attachments, true) ?? [];
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete(str_replace('/storage/', '', $attachment['path']));
                    }
                }

                Storage::disk('public')->deleteDirectory('tickets/' . $ticket->ticket_id);

                DestroyTicket::dispatch($ticket);

                $ticket->delete();

            return back()->with('success', __('The ticket has been deleted.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function destroyAttachment(Ticket $ticket, $attachmentIndex)
    {
        if(Auth::user()->can('edit-support-tickets')){
                $attachments = is_array($ticket->attachments) ? $ticket->attachments : (json_decode($ticket->attachments, true) ?? []);
                
                if (isset($attachments[$attachmentIndex])) {
                    $attachment = $attachments[$attachmentIndex];
                    if (isset($attachment['path'])) {
                        Storage::disk(name: 'public')->delete(str_replace('/storage/', '', $attachment['path']));
                    }

                    unset($attachments[$attachmentIndex]);
                    $ticket->attachments = json_encode(array_values($attachments));
                    $ticket->save();

                    return redirect()->back()->with('success', __('Attachment deleted successfully.'));
                }

            return redirect()->back()->with('error', __('Attachment not found.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function getUserData(Request $request)
    {
        $user = User::find($request->user_id);
        
        if ($user && $user->created_by === creatorId()) {
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        return response()->json(['error' => 'User not found'], 404);
    }

    public function storeNote(Request $request, $id)
    {
        if (Auth::user()->can('edit-support-tickets')) {
            $request->validate([
                'note' => 'required|string'
            ]);

            $ticket = Ticket::find($id);
            
            if (!$ticket) {
                return back()->with('error', __('Ticket not found'));
            }

            $ticket->note = $request->note;
            $ticket->save();

            return redirect()->back()->with('success', __('Note saved successfully'));
        }
        
        return back()->with('error', __('Permission denied'));
    }

    public function storeCustomFields(Request $request)
    {
        return redirect()->back()->with('success', __('Settings saved successfully'));
    }

    public function storeconverison(StoreConversionRequest $request, $ticketId)
    {
        if (Auth::user()->can('edit-support-tickets')) {

            $ticket = Ticket::find($ticketId);
            
            if (!$ticket) {
                return redirect()->back()->with('error', __('Ticket not found'));
            }

            $attachments = [];
            
            if (!empty($request->attachments)) {
                $attachmentPaths = is_array($request->attachments) ? $request->attachments : json_decode($request->attachments, true);
                if (is_array($attachmentPaths)) {
                    foreach ($attachmentPaths as $filePath) {
                        if (!empty($filePath) && $filePath !== 'logo_dark' && $filePath !== 'favicon') {
                            $filename = basename($filePath);
                            $attachments[] = [
                                'name' => $filename,
                                'path' => $filename
                            ];
                        }
                    }
                }
            }

            $conversion = new Conversion();
            $conversion->ticket_id = $ticket->id;
            $conversion->description = $request->description;
            $conversion->sender = Auth::id();
            $conversion->attachments = json_encode($attachments);
            $conversion->creator_id = Auth::id();
            $conversion->created_by = creatorId();
            $conversion->save();

            
            if ($request->has('status') && in_array($request->status, ['open', 'In Progress', 'Closed', 'On Hold'])) {
                $ticket->status = $request->status;
                $ticket->save();
            }
            CreateTicketConversion::dispatch($request, $ticket, $conversion);

             // Send email notification for reply
            if (!empty(company_setting('New Ticket Reply')) && company_setting('New Ticket Reply') == true) {
                $uArr = [
                    'ticket_name' => $ticket->name,
                    'ticket_id' => $ticket->ticket_id,
                    'email' => $ticket->email,
                    'reply_description' => $request->description,
                ];
                try {
                    EmailTemplate::sendEmailTemplate('New Ticket Reply', [$ticket->email], $uArr);
                } catch (\Exception $e) {
                   return redirect()->back()->with('error', __('Ticket not found'));
                }
            }

            return redirect()->back()->with('success', __('Reply added successfully'));
        }
        
        return redirect()->back()->with('error', __('Permission denied'));
    }

    public function editReply($ticketId, $conversionId)
    {
        if (Auth::user()->can('edit-support-tickets')) {
            $conversion = Conversion::where('ticket_id', $ticketId)
                ->where('id', $conversionId)
                ->where('sender', Auth::id())
                ->first();
            
            if (!$conversion) {
                return response()->json(['error' => 'Reply not found or unauthorized'], 404);
            }

            return response()->json([
                'id' => $conversion->id,
                'description' => $conversion->description,
                'attachments' => json_decode($conversion->attachments, true) ?? []
            ]);
        }
        
        return response()->json(['error' => 'Permission denied'], 403);
    }

    public function updateReply(StoreConversionRequest $request, $ticketId, $conversionId)
    {
        if (Auth::user()->can('edit-support-tickets')) {

            $conversion = Conversion::where('ticket_id', $ticketId)
                ->where('id', $conversionId)
                ->where('sender', Auth::id())
                ->first();
            
            if (!$conversion) {
                return response()->json(['error' => 'Reply not found or unauthorized'], 404);
            }

            $attachments = [];
            if ($request->hasFile('attachments')) {
                $files = is_array($request->file('attachments')) ? $request->file('attachments') : [$request->file('attachments')];
                foreach ($files as $file) {
                    $filenameWithExt = $file->getClientOriginalName();
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    
                    $upload = upload_file($request, 'attachments', $fileNameToStore, 'support_tickets');
                    
                    if (isset($upload['flag']) && $upload['flag'] == 1 && isset($upload['url'])) {
                        $attachments[] = [
                            'name' => $filenameWithExt,
                            'path' => $upload['url']
                        ];
                    }
                }
            }

            $conversion->update([
                'description' => $request->description,
                'attachments' => json_encode($attachments)
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Reply updated successfully'),
                'conversion' => [
                    'id' => $conversion->id,
                    'description' => $conversion->description,
                    'attachments' => json_decode($conversion->attachments, true) ?? [],
                    'updated_at' => $conversion->updated_at->toISOString()
                ]
            ]);
        }
        
        return response()->json(['error' => 'Permission denied'], 403);
    }

    public function deleteReply($ticketId, $conversionId)
    {
        if (Auth::user()->can('delete-support-tickets')) {
            $conversion = Conversion::where('ticket_id', $ticketId)
                ->where('id', $conversionId)
                ->where('sender', Auth::id())
                ->first();
            
            if (!$conversion) {
                return response()->json(['error' => 'Reply not found or unauthorized'], 404);
            }

            // Delete attachments if any
            $attachments = json_decode($conversion->attachments, true) ?? [];
            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $attachment['path']));
                }
            }

            $conversion->delete();

            return response()->json([
                'success' => true,
                'message' => __('Reply deleted successfully')
            ]);
        }
        
        return response()->json(['error' => 'Permission denied'], 403);
    }

    public function downloadAttachment(Request $request)
    {
        $filePath = $request->input('path');
        
        if (!$filePath) {
            return response()->json(['error' => 'File path required'], 400);
        }

        $fileUrl = $filePath;

        if (!str_starts_with($fileUrl, 'http')) {
            if (!str_contains($fileUrl, 'storage/media/')) {
                $fileUrl = 'storage/media/' . ltrim($fileUrl, '/');
            }
            $fileUrl = url($fileUrl);
        }

        return response()->json([
            'status' => true,
            'file_url' => $fileUrl,
            'message' => 'Download ready'
        ]);
    }

    public function grid($status = '')
    {
        if (Auth::user()->can('manage-support-tickets')) {
            $user = Auth::user();
            $tickets = Ticket::with(['tcategory'])
                ->select([
                    'tickets.*',
                    'ticket_categories.name as category_name',
                    'ticket_categories.color',
                ])
                ->join('ticket_categories', 'ticket_categories.id', '=', 'tickets.category');
                
            // Role-based filtering for grid view
            if (in_array($user->type, ['staff', 'client', 'vendor'])) {
                $tickets->where(function($q) use ($user) {
                    $q->where('tickets.user_id', $user->id)
                      ->orWhere('tickets.creator_id', $user->id);
                });
            } else {
                $tickets->where('tickets.created_by', creatorId());
            }

            if ($status == 'in-progress') {
                $tickets->where('status', '=', 'In Progress');
            } elseif ($status == 'on-hold') {
                $tickets->where('status', '=', 'On Hold');
            } elseif ($status == 'closed') {
                $tickets->where('status', '=', 'Closed');
            }

            $tickets = $tickets->orderBy('id', 'desc')
                ->when(request('search'), function($q, $search) {
                    $q->where('subject', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%');
                })
                ->paginate(min(request('per_page', 12), 100))
                ->withQueryString();

            return Inertia::render('SupportTicket/Tickets/Grid', [
                'tickets' => $tickets,
                'status' => $status
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}