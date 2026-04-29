<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CleaningManagement\Events\CreateCleaningBooking;
use Workdo\Twilio\Services\SendMsg;

class CreateCleaningBookingLis
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
    public function handle(CreateCleaningBooking $event)
    {
        if (company_setting('Twilio New Cleaning Booking') == 'on') {

            $booking = $event->cleaningBooking;

            $user = User::find($booking->user_id);

            if (!empty($booking) && !empty($user) && !empty($user->mobile_no)) {

                $uArr = [
                    'user_name' => $booking->customer_name != null ? $booking->customer_name : $user->name ?? '-',
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Cleaning Booking');
            }
        }
    }
}
