<?php

namespace Workdo\Contract\Listeners;

use App\Events\DefaultData;
use Workdo\Contract\Models\ContractUtility;

class DataDefault
{
    public function __construct()
    {
        //
    }

    public function handle(DefaultData $event)
    {
        $company_id = $event->company_id;
        $user_module = $event->user_module ? explode(',', $event->user_module) : [];
        if (!empty($user_module)) {
            if (in_array("Contract", $user_module)) {
                ContractUtility::defaultdata($company_id);
            }
        }
    }
}
