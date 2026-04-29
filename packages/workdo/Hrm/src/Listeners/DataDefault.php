<?php

namespace Workdo\Hrm\Listeners;

use App\Events\DefaultData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Hrm\Models\HrmModel;

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
            if (in_array("Hrm", $user_module)) {
                HrmModel::defaultdata($company_id);
            }
        }
    }
}
