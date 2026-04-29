<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\Conversion;
use Workdo\SupportTicket\Events\ReplyTicket;
use Workdo\SupportTicket\Http\Requests\StoreConversationRequest;
use Workdo\SupportTicket\Http\Requests\ReplyEmailRequest;

class ConversationController extends Controller
{
    private function getUser($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        return $user->id;

    }
    public function store(StoreConversationRequest $request, $slug, $ticketId)
    {
        $user_id = $this->getUser($slug);
        
        try {
            $decryptedTicketId = Crypt::decrypt($ticketId);
            $ticket = Ticket::find($decryptedTicketId);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Ticket not found'));
        }


        $attachments = [];
        
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            
            foreach ($files as $index => $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '_' . $index . '.' . $extension;

                $tempRequest = new Request();
                $tempRequest->files->set('attachment', $file);
                
                $upload = upload_file($tempRequest, 'attachment', $fileNameToStore, 'tickets');

                if (isset($upload['flag']) && $upload['flag'] == 1 && isset($upload['url'])) {
                    $attachments[] = [
                        'name' => $filenameWithExt,
                        'path' => $upload['url']
                    ];
                } else {
                    return redirect()->back()->with('error', isset($upload['msg']) ? $upload['msg'] : __('File upload failed'));
                }
            }
        }

        $conversationData = [
            'ticket_id' => $ticket->id,
            'description' => $request->description,
            'sender' => 'admin',
            'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            'creator_id' => $user_id,
            'created_by' => $user_id
        ];
    
        $conversation = Conversion::create($conversationData);

        ReplyTicket::dispatch($request, $conversation, $ticket);

        // Send email notification for reply
        if (!empty(company_setting('New Ticket Reply', $user_id)) && company_setting('New Ticket Reply', $user_id) == true) {
            $uArr = [
                'ticket_name' => $ticket->name,
                'ticket_id' => $ticket->ticket_id,
                'email' => $ticket->email,
                'reply_description' => $request->description,
            ];
            EmailTemplate::sendEmailTemplate('New Ticket Reply', [$ticket->email], $uArr, $user_id);
           
        }

        return redirect()->back()->with('success', __('Reply sent successfully'));
    }

    public function sendEmail(Request $request, $slug, $ticketId)
    {
        $user_id = $this->getUser($slug);
        $ticket = Ticket::find($ticketId);
        
        if (!empty(company_setting('New Ticket',$user_id)) && company_setting('New Ticket', $user_id) == true) {
            $uArr = [
                'ticket_name' => $ticket->name,
                'email' => $ticket->email,
                'ticket_id' => $ticket->ticket_id,
                'ticket_url' => route('support-ticket.show', [$slug, $ticket->id]),
            ];

            try {
                EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $uArr);
                return response()->json(['success' => true, 'message' => __('Email sent successfully')]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
        
        return response()->json(['success' => false, 'message' => __('Email template not enabled')], 400);
    }

    public function replyEmail(ReplyEmailRequest $request, $slug, $ticketId)
    {
        $user_id = $this->getUser($slug);
        $ticket = Ticket::find($ticketId);
        
        if (!empty(company_setting('New Ticket Reply',$user_id)) && company_setting('New Ticket Reply',$user_id) == true) {
            $uArr = [
                'ticket_name' => $ticket->name,
                'ticket_id' => $ticket->ticket_id,
                'email' => $ticket->email,
                'reply_description' => $request->reply_description,
            ];

            try {
                EmailTemplate::sendEmailTemplate('New Ticket Reply', [$ticket->email], $uArr, $user_id);
                return response()->json(['success' => true, 'message' => __('Reply email sent successfully')]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
        
        return response()->json(['success' => false, 'message' => __('Email template not enabled')], 400);
    }
}