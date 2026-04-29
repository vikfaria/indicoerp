<?php

namespace Workdo\Calendar\Listeners;

use App\Events\CreatePurchaseInvoice;
use Workdo\Calendar\Models\CalenderUtility;

class CreatePurchaseInvoiceListener
{
    public function handle(CreatePurchaseInvoice $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarPurchaseInvoice = $event->purchaseInvoice;
            $calendarRequest = $event->request;

            $type = 'purchase_invoice';
            $calendarPurchaseInvoice->title = 'Purchase Invoice - ' . $calendarPurchaseInvoice->invoice_number;
            $calendarPurchaseInvoice->start_date = $calendarRequest->due_date . ' 09:00:00';
            $calendarPurchaseInvoice->end_date = $calendarRequest->due_date . ' 17:00:00';

            CalenderUtility::addCalendarData($calendarPurchaseInvoice, $type, $calendarPurchaseInvoice->created_by);
        }
    }
}