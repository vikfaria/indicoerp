<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\CleaningManagement\Events\CreateCleaningBooking;
use Workdo\Slack\Services\SendMsg;

class CreateCleaningBookingLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCleaningBooking $event)
    {
        $booking = $event->cleaningBooking;
        $user = User::find($booking->user_id);

        if (company_setting('Slack New Cleaning Booking') == 'on') {
            $uArr = [
                'user_name' => $booking->customer_name != null ? $booking->customer_name : $user->name ?? '',
            ];

            SendMsg::SendMsgs($uArr, 'New Cleaning Booking');
        }
    }
}