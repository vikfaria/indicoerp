<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\HospitalManagement\Events\CreateHospitalDoctor;
use Workdo\HospitalManagement\Models\HospitalSpecialization;
use Workdo\Telegram\Services\SendMsg;

class CreateHospitalDoctorLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHospitalDoctor $event)
    {
        $doctor = $event->hospitaldoctor;
        $specialization = HospitalSpecialization::find($doctor->hospital_specialization_id);
        if (company_setting('Telegram New Doctor')  == 'on') {

            if(!empty($specialization))
            {
                $uArr = [
                    'doctor_name'    => $doctor->user->name,
                    'specialization' => $specialization->name
                ];
                SendMsg::SendMsgs($uArr , 'New Doctor');
            }
        }
    }
}
