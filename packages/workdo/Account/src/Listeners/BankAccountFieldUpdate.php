<?php

namespace Workdo\Account\Listeners;

class BankAccountFieldUpdate
{
    public function handle($event)
    {
        if(Module_is_active('Account'))
        {
            $request = $event->request;
            // Dynamically get the model from event
            foreach (get_object_vars($event) as $property => $value) {
                if ($value instanceof \Illuminate\Database\Eloquent\Model && $request->has('bank_account_id')) {
                    $value->bank_account_id = $request->input('bank_account_id');
                    $value->save();
                    break;
                }
            }
        }
    }
}
