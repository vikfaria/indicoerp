<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Feedback\Events\CreateTemplate;
use Workdo\Feedback\Models\TemplateModule;
use Workdo\Twilio\Services\SendMsg;

class CreateTemplateLis
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
    public function handle(CreateTemplate $event)
    {
        if (company_setting('Twilio New Template') == 'on') {

            $template = $event->template;

            $module = TemplateModule::find($template->module);
            $to     = \Auth::user()->mobile_no;

            if (!empty($module) && !empty($to)) {
                $uArr = [
                    'submodule_name' => $module->submodule,
                    'module_name'    => $module->module,
                ];
            }

            SendMsg::SendMsgs($to, $uArr, 'New Template');
        }
    }
}
