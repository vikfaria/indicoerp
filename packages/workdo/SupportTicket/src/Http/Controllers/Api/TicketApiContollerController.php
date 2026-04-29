<?php

namespace Workdo\SupportTicket\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\SupportTicket\Models\Conversion;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\TicketField;

class TicketApiContollerController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $tickets = Ticket::with('tcategory')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-support-tickets')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-support-tickets')) {
                        $q->where(function ($q) {
                            $q->where('user_id', Auth::id())
                                ->orWhere('creator_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $tickets->getCollection()->transform(function ($ticket) {
                return [
                    'id'            => $ticket->id,
                    'ticket_id'     => $ticket->ticket_id,
                    'name'          => $ticket->name,
                    'email'         => $ticket->email,
                    'account_type'  => $ticket->account_type,
                    'subject'       => $ticket->subject,
                    'status'        => $ticket->status,
                    'description'   => strip_tags($ticket->description),
                    'note'          => $ticket->note,
                    'category_name' => $ticket->tcategory->name ?? 'No Category',
                    'color'         => $ticket->tcategory->color ?? '#6B7280',
                ];
            });

            return $this->paginatedResponse($tickets, 'Tickets retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }


    public function getRequestData()
    {
        try {
            $ticket_categories = TicketCategory::select('id', 'name')->where('created_by', creatorId())->get();

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

            $knowledges_categories = KnowledgeBaseCategory::select('id', 'title')->where('created_by', creatorId())->get();

            $data                          = [];
            $data['ticket_categories']     = $ticket_categories;
            $data['staff']                 = $staff;
            $data['client']                = $clients;
            $data['vendor']                = $vendors;
            $data['knowledges_categories'] = $knowledges_categories;

            return $this->successResponse($data, 'Data retrived successfully');
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'something went wrong!!!']);
        }
    }

    public function store(Request $request)
    {
        try {
            if (Auth::user()->can('create-support-tickets')) {
                $validator = Validator::make($request->all(), [
                    'account_type' => 'required|string|in:custom,staff,client,vendor',
                    'email'        => 'required|string|email|max:255',
                    'category'     => 'required|exists:ticket_categories,id',
                    'subject'      => 'required|string|max:255',
                    'description'  => 'required|string',
                    'status'       => 'required|string|in:In Progress,On Hold,Closed',
                    'attachments'  => 'nullable|array',
                ]);
                $validator->sometimes('name', 'required|string|max:255', function ($input) {
                    return $input->account_type === 'custom';
                });

                $validator->sometimes('user_id', 'required|exists:users,id', function ($input) {
                    return $input->account_type !== 'custom';
                });
                
                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $validated               = $validator->validated();
                $validated['ticket_id']  = time();
                $validated['creator_id'] = Auth::id();
                $validated['created_by'] = creatorId();

                // Set default status if not provided
                if (empty($validated['status'])) {
                    $validated['status'] = 'In Progress';
                }

                if ($validated['account_type'] == 'staff' || $validated['account_type'] == 'client' || $validated['account_type'] == 'vendor') {
                    $user = User::find($validated['user_id']);
                    if ($user) {
                        $validated['name']  = $user->name;
                        $validated['email'] = $user->email;
                    } else {
                        return redirect()->back()->with('error', __('User not found'));
                    }
                }

                $attachments = [];

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $originalName = $file->getClientOriginalName();

                            $tempRequest = new Request();
                            $tempRequest->files->set('attachment', $file);

                            if (($upload = upload_file($tempRequest, 'attachment', $originalName, ''))['flag'] == 0) {
                                return $this->errorResponse($upload['msg']);
                            }

                            $attachments[] = [
                                'name' => $originalName,
                                'path' => $originalName,
                            ];
                        }
                    }
                }

                $validated['attachments'] = json_encode($attachments);

                $ticket = Ticket::create($validated);

                if ($request->has('fields') && !empty($request->fields)) {
                    TicketField::saveData($ticket, $request->fields);
                }

                if (!empty(company_setting('New Ticket')) && company_setting('New Ticket') == true) {
                    $uArr = [
                        'ticket_name' => $ticket->name,
                        'email'       => $ticket->email,
                        'ticket_id'   => $ticket->ticket_id,
                    ];
                    try {
                        EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $uArr);
                    } catch (\Exception $e) {
                        // Log error but don't fail the request
                    }
                }
                return $this->successResponse('', 'Ticket created successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (Auth::user()->can('edit-support-tickets')) {

                $validator = Validator::make($request->all(), [
                    'name'         => 'required|string|max:255',
                    'email'        => 'required|string|email|max:255',
                    'category'     => 'required|exists:ticket_categories,id',
                    'subject'      => 'required|string|max:255',
                    'description'  => 'required|string',
                    'status'       => 'nullable|in:In Progress,On Hold,Closed',
                    'account_type' => 'required|in:staff,client,vendor,custom',
                    'user_id'      => 'required_unless:account_type,custom|exists:users,id',
                    'attachments'  => 'nullable|array',
                    'fields'       => 'nullable|array'
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $ticket = Ticket::find($id);
                if (!$ticket) {
                    return $this->errorResponse('Ticket not found', null, 404);
                }

                $validated = $validator->validated();

                if (in_array($validated['account_type'], ['staff', 'client', 'vendor'])) {
                    $user = User::find($validated['user_id']);
                    if ($user) {
                        $validated['name']  = $user->name;
                        $validated['email'] = $user->email;
                    } else {
                        return $this->errorResponse('User not found', null, 404);
                    }
                } else {
                    $validated['user_id'] = null;
                }

                $ticket->update($validated);

                if ($request->has('fields') && !empty($request->fields)) {
                    TicketField::saveData($ticket, $request->fields);
                }

                return $this->successResponse('', 'Ticket updated successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function destroy($id)
    {
        try {
            if (Auth::user()->can('delete-support-tickets')) {

                $ticket = Ticket::find($id);

                if (!$ticket) {
                    return $this->errorResponse('Ticket not found', null, 404);
                }
                Conversion::where('ticket_id', $ticket->id)->delete();

                $attachments = json_decode($ticket->attachments, true) ?? [];

                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete('media/' . $attachment['path']);
                    }
                }

                Storage::disk('public')->deleteDirectory('tickets/' . $ticket->ticket_id);

                $ticket->delete();

                return $this->successResponse('', 'Ticket deleted successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function storeNote(Request $request, $id)
    {
        try {
            if (Auth::user()->can('edit-support-tickets')) {

                $validator = Validator::make($request->all(), [
                    'note' => 'required|string'
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $ticket = Ticket::find($id);

                if (!$ticket) {
                    return $this->errorResponse('Ticket not found', 404);
                }

                $ticket->note = $request->note;
                $ticket->save();

                return $this->successResponse('', 'Note saved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function addReply(Request $request, $id)
    {
        try {
            if (Auth::user()->can('edit-support-tickets')) {

                $validator = Validator::make($request->all(), [
                    'description' => 'required|string|max:10000',
                    'files.*'     => 'nullable|file|max:10240',
                    'attachments' => 'nullable',
                    'status'      => 'nullable|in:open,In Progress,Closed,On Hold'
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $ticket = Ticket::find($id);

                if (!$ticket) {
                    return redirect()->back()->with('error', __('Ticket not found'));
                }
                $attachments = [];

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $originalName = $file->getClientOriginalName();

                            $tempRequest = new Request();
                            $tempRequest->files->set('attachment', $file);

                            if (($upload = upload_file($tempRequest, 'attachment', $originalName, ''))['flag'] == 0) {
                                return $this->errorResponse($upload['msg']);
                            }

                            $attachments[] = [
                                'name' => $originalName,
                                'path' => $originalName,
                            ];
                        }
                    }
                }

                $conversion              = new Conversion();
                $conversion->ticket_id   = $ticket->id;
                $conversion->description = $request->description;
                $conversion->sender      = Auth::id();
                $conversion->attachments = json_encode($attachments);
                $conversion->creator_id  = Auth::id();
                $conversion->created_by  = creatorId();
                $conversion->save();


                if ($request->has('status') && in_array($request->status, ['open', 'In Progress', 'Closed', 'On Hold'])) {
                    $ticket->status = $request->status;
                    $ticket->save();
                }

                // Send email notification for reply
                if (!empty(company_setting('New Ticket Reply')) && company_setting('New Ticket Reply') == true) {
                    $uArr = [
                        'ticket_name'       => $ticket->name,
                        'ticket_id'         => $ticket->ticket_id,
                        'email'             => $ticket->email,
                        'reply_description' => $request->description,
                    ];
                    try {
                        EmailTemplate::sendEmailTemplate('New Ticket Reply', [$ticket->email], $uArr);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', __('Ticket not found'));
                    }
                }

                return $this->successResponse(['id' => $conversion->id], 'Reply added successfully', 201);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
