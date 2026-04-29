<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Slack\Services\SendMsg;
use Workdo\Spreadsheet\Events\CreateSpreadsheet;

class CreateSpreadsheetLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSpreadsheet $event)
    {
        $spreadsheets = $event->spreadsheet;
        $user = User::find($spreadsheets->created_by);
        
        if (company_setting('Slack New Spreadsheet') == 'on') {
            $uArr = [
                'user_name' => $user->name
            ];

            SendMsg::SendMsgs($uArr, 'New Spreadsheet');
        }
    }
}