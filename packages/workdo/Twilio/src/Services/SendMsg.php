<?php

namespace Workdo\Twilio\Services;

use App\Models\Notification;
use App\Models\NotificationTemplateLang;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SendMsg
{
    public static function SendMsgs($mobile_no, array $uArr, string $action, $id = null)
    {
        if (!empty($id)) {
            $usr = User::find($id);
        } elseif (Auth::check()) {
            $usr = Auth::user();
        } else {
            return false;
        }

        // Check if Twilio module is active
        $moduleActive = module_is_active('Twilio', $usr->id);

        if ($moduleActive) {

            $company_settings = getCompanyAllSetting($usr->id);

            $twilio_notification_is = isset($company_settings['twilio_notification_is']) ? $company_settings['twilio_notification_is'] : 'off';

            $template = Notification::where('action', $action)->where('type', 'Twilio')->first();
            $content  = NotificationTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();
            if ($content == null) {
                $content = NotificationTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', 'en')->first();
            }

            $msg = self::replaceVariable($content->content, $uArr, $usr->id);

            $twilio_sid   = isset($company_settings['twilio_sid']) ? $company_settings['twilio_sid'] : null;
            $twilio_token = isset($company_settings['twilio_token']) ? $company_settings['twilio_token'] : null;
            $twilio_from  = isset($company_settings['twilio_from']) ? $company_settings['twilio_from'] : null;

            if (($twilio_notification_is == 'on') && (!empty($twilio_sid)) && (!empty($twilio_token)) && (!empty($twilio_from))) {
                try {
                    // Get user phone number
                    $to_phone = $mobile_no ?? null;

                    if (!empty($to_phone)) {

                        $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilio_sid}/Messages.json";

                        $data = [
                            'From' => $twilio_from,
                            'To'   => $to_phone,
                            'Body' => $msg
                        ];


                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                        curl_setopt($ch, CURLOPT_USERPWD, $twilio_sid . ':' . $twilio_token);

                        $headers   = array();
                        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                        $result = curl_exec($ch);
                        if (curl_errno($ch)) {
                            return 'Error:' . curl_error($ch);
                        }
                        curl_close($ch);
                        return $result;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }

    public static function replaceVariable($content, $obj, $id = null)
    {
        $arrVariable = [
            '{user_name}',
            '{company_name}',
            '{name}',
            '{date}',
            '{time}',
            '{status}',
            '{appointment_name}',
            '{business_name}',
            '{component_name}',
            '{location_name}',
            '{wo_name}',
            '{part_name}',
            '{lead_name}',
            '{old_stage}',
            '{new_stage}',
            '{deal_name}',
            '{purchase_id}',
            '{warehouse_name}',
            '{quotation_id}',
            '{sales_order_id}',
            '{sales_invoice_id}',
            '{amount}',
            '{payment_type}',
            '{meeting_name}',
            '{project_name}',
            '{task_name}',
            '{bug_name}',
            '{old_status}',
            '{new_status}',
            '{supplier_name}',
            '{location}',
            '{assets}',
            '{contract_number}',
            '{submodule_name}',
            '{module_name}',
            '{teacher_name}',
            '{student_name}',
            '{parent_name}',
            '{class_name}',
            '{homework_title}',
            '{subject_name}',
            '{team_name}',
            '{machine_name}',
            '{specialization}',
            '{doctor_name}',
            '{patient_name}',
            '{type}',
            '{note_type}',
            '{article_type}',
            '{book_name}',
            '{challenge}',
            '{position}',
            '{module}',
            '{asset}',
            '{announcement_name}',
            '{branch_name}',
            '{start_date}',
            '{award_name}',
            '{end_date}',
            '{invoice_id}',
            '{event_name}',
            '{month}'
        ];

        $arrValue = [
            'user_name'         => '-',
            'company_name'      => '-',
            'name'              => '-',
            'date'              => '-',
            'time'              => '-',
            'status'            => '-',
            'appointment_name'  => '-',
            'business_name'     => '-',
            'component_name'    => '-',
            'location_name'     => '-',
            'wo_name'           => '-',
            'part_name'         => '-',
            'lead_name'         => '-',
            'old_stage'         => '-',
            'new_stage'         => '-',
            'deal_name'         => '-',
            'purchase_id'       => '-',
            'warehouse_name'    => '-',
            'quotation_id'      => '-',
            'sales_order_id'    => '-',
            'sales_invoice_id'  => '-',
            'amount'            => '-',
            'payment_type'      => '-',
            'meeting_name'      => '-',
            'project_name'      => '-',
            'task_name'         => '-',
            'bug_name'          => '-',
            'old_status'        => '-',
            'new_status'        => '-',
            'supplier_name'     => '-',
            'location'          => '-',
            'assets'            => '-',
            'contract_number'   => '-',
            'submodule_name'    => '-',
            'module_name'       => '-',
            'teacher_name'      => '-',
            'student_name'      => '-',
            'parent_name'       => '-',
            'class_name'        => '-',
            'homework_title'    => '-',
            'subject_name'      => '-',
            'team_name'         => '-',
            'machine_name'      => '-',
            'specialization'    => '-',
            'doctor_name'       => '-',
            'patient_name'      => '-',
            'type'              => '-',
            'note_type'         => '-',
            'article_type'      => '-',
            'book_name'         => '-',
            'challenge'         => '-',
            'position'          => '-',
            'module'            => '-',
            'asset'             => '-',
            'announcement_name' => '-',
            'branch_name'       => '-',
            'start_date'        => '-',
            'award_name'        => '-',
            'end_date'          => '-',
            'invoice_id'        => '-',
            'event_name'        => '-',
            'month'             => '-'
        ];

        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }

        if (!empty($id)) {
            $user = User::find($id);
        } elseif (Auth::check()) {
            $user = Auth::user();
        }

        $arrValue['company_name'] = $user->name ?? '--';

        return str_replace($arrVariable, array_values($arrValue), $content);
    }
}
