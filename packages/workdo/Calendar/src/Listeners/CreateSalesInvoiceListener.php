<?php

namespace Workdo\Calendar\Listeners;

use App\Events\CreateSalesInvoice;
use Workdo\Calendar\Models\CalenderUtility;

class CreateSalesInvoiceListener
{
    public function handle(CreateSalesInvoice $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarSalesInvoice = $event->salesInvoice;
            $calendarRequest = $event->request;

            $type = 'sales_invoice';
            $calendarSalesInvoice->title = 'Sales Invoice - ' . $calendarSalesInvoice->invoice_number;
            $calendarSalesInvoice->start_date = $calendarRequest->due_date . ' 09:00:00';
            $calendarSalesInvoice->end_date = $calendarRequest->due_date . ' 17:00:00';

            CalenderUtility::addCalendarData($calendarSalesInvoice, $type, $calendarSalesInvoice->created_by);
        }
    }
}