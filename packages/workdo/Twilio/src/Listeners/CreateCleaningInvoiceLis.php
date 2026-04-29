<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CleaningManagement\Events\CreateCleaningInvoice;
use Workdo\CleaningManagement\Models\CleaningInspection;
use Workdo\Twilio\Services\SendMsg;

class CreateCleaningInvoiceLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateCleaningInvoice $event)
    {
        if (company_setting('Twilio New Cleaning Invoice') == 'on') {

            $invoice = $event->cleaningInvoice;

            $inspection = CleaningInspection::find($invoice->inspection_id);
            $booking    = $inspection->booking;

            if ($booking->type == 'Client') {
                $client = User::where('id', $inspection->booking->user_id)->select('name', 'mobile_no')->first();
            } else {
                $client = (object) [
                    'name'      => $booking->customer_name ?? null,
                    'mobile_no' => $booking->phone ?? null,
                ];
            }

            if (!empty($client->mobile_no)) {
                $uArr = [
                    'user_name' => $client->name
                ];

                SendMsg::SendMsgs($client->mobile_no, $uArr, 'New Cleaning Invoice');
            }
        }
    }
}
