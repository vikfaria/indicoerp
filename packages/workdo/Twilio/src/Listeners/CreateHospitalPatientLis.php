<?php

namespace Workdo\Twilio\Listeners;

use Workdo\HospitalManagement\Events\CreateHospitalPatient;
use Workdo\Twilio\Services\SendMsg;

class CreateHospitalPatientLis
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
    public function handle(CreateHospitalPatient $event)
    {
        if (company_setting('Twilio New Patient') == 'on') {

            $patient = $event->hospitalpatient;

            if (!empty($patient) && !empty($patient->mobile_no)) {
                $uArr = [
                    'patient_name' => $patient->name
                ];

                SendMsg::SendMsgs($patient->mobile_no, $uArr, 'New Patient');
            }
        }
    }
}
