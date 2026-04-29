<?php

namespace Workdo\Slack\Services;

use App\Models\Notification;
use App\Models\NotificationTemplateLang;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SendMsg
{
    public static function SendMsgs(array $uArr, string $action,$id = null)
    {
        // Check if Slack module is active
        if (Module_is_active('Slack', $id)) {

            if (!empty($id)) {
                $usr = User::find($id);
            } else {
                $usr = Auth::user();
            }

            $company_settings = getCompanyAllSetting($id);

            $slack_notification_is = isset($company_settings['slack_notification_is']) ? $company_settings['slack_notification_is'] : 'off';

            $template = Notification::where('action', $action)->where('type','Slack')->first();
            $content = NotificationTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();
            if($content == null)
            {
                $content = NotificationTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', 'en')->first();
            }

            $msg = self::replaceVariable($content->content, $uArr, $id);

            $slack_webhook = isset($company_settings['slack_webhook_url']) ? $company_settings['slack_webhook_url'] : null;

            if (($slack_notification_is =='on') && (!empty($slack_webhook)))
            {
                try
                {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $slack_webhook);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $msg]));

                    $headers = array();
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        return 'Error:' . curl_error($ch);
                    }
                    curl_close($ch);

                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }

    public static function replaceVariable($content, $obj ,$id = null)
    {
        $arrVariable = [
            '{user_name}',
            '{company_name}',
            '{invoice_id}',
            '{bill_id}',

            '{amount}',
            '{vender_name}',

            '{appointment_name}',
            '{date}',
            '{time}',
            '{business_name}',
            '{status}',

            '{component_name}',
            '{wo_name}',
            '{part_name}',
            '{location_name}',
            '{contract_number}',
            '{name}',

            '{month}',
            '{award_name}',
            '{event_name}',
            '{branch_name}',
            '{start_date}',
            '{end_date}',
            '{purpose_of_visit}',
            '{place_of_visit}',
            '{announcement_name}',

            '{lead_name}',
            '{old_stage}',
            '{new_stage}',
            '{deal_name}',

            '{purchase_id}',
            '{job_name}',
            '{application}',
            '{retainer_id}',
            '{start_time}',
            '{end_time}',
            '{quotation_id}',
            '{sales_order_id}',
            '{sales_invoice_id}',

            '{payment_type}',
            '{meeting_name}',
            '{ticket_name}',
            '{project_name}',
            '{task_name}',
            '{bug_name}',
            '{contact_name}',

            '{coupon_name}',
            '{discount}',
            '{booking_number}',
            "{price}",
            "{portfolio_name}",
            "{portfolio_category}",
            '{module}',

            '{supplier_name}',
            '{location}',
            '{assets}',
            '{asset}',
            '{program_name}',
            '{order_number}',

            '{insurance_provider}',
            '{old_status}',
            '{new_status}',
            '{store_name}',
            '{course_name}',

            '{student_name}',
            '{blog_name}',
            '{page_name}',
            '{warehouse_name}',

            '{fleet_name}',
            '{process_name}',
            '{hours}',
            '{cycle_name}',
            '{office_name}',
            '{department}',
            '{season_name}',
            '{season}',
            '{crop_name}',
            '{activity}',
            '{cultivation}',
            '{activity_name}',
            '{service_name}',
            '{cultivation_name}',
            '{farmer_name}',

            '{submodule_name}',
            '{module_name}',
            '{tour_name}',
            '{days}',
            '{agent_name}',
            '{journalist_name}',
            '{information}',
            '{newspaper_name}',
            '{advertidsement}',

            '{teacher_name}',
            '{parent_name}',
            '{class_name}',
            '{services}',
            '{team_name}',

            '{property_name}',
            '{unit_name}',
            '{vehicle_name}',
            '{child_name}',
            '{product_name}',
            '{consignment_name}',
            '{commission}',
            '{machine_name}',
            '{doctor_name}',
            '{patient_name}',
            '{specialization}',
            '{homework_title}',
            '{subject_name}',

            '{employee_name}',
            '{note_type}',
            '{article_type}',
            '{book_name}',
            '{position}',
            '{challenge}',
            '{type}',
        ];
        $arrValue    = [
            'user_name' => '-',
            'company_name' => '-',
            'invoice_id' => '-',
            'bill_id'=>'-',

            'amount'=>'-',
            'vender_name'=>'-',

            'appointment_name'=>'-',
            'date'=>'-',
            'time'=>'-',
            'business_name'=>'-',
            'status'=>'',

            'component_name'=> '-',
            'wo_name'=>'-',
            'part_name'=>'-',
            'location_name'=>'-',
            'contract_number'=>'-',
            'name'=>'-',

            'month'=>'-',
            'award_name'=>'-',
            'event_name'=>'-',
            'branch_name'=>'-',
            'start_date'=>'-',
            'end_date'=>'-',
            'purpose_of_visit'=>'-',
            'place_of_visit'=>'-',
            'announcement_name'=>'-',

            'lead_name' => '-',
            'old_stage'=>'-',
            'new_stage' => '-',
            'deal_name'=>'-',

            'purchase_id'=>'-',
            'job_name'=>'-',
            'application'=>'-',
            'retainer_id' => '-',
            'start_time'=>'-',
            'end_time'=>'-',
            'quotation_id'=>'-',
            'sales_order_id'=>'-',
            'sales_invoice_id'=>'-',

            'payment_type'=>'-',
            'meeting_name'=>'-',
            'ticket_name'=>'-',
            'project_name'=>'-',
            'task_name'=>'-',
            'bug_name'=>'-',
            'contact_name'=>'-',

            'coupon_name'=>'-',
            'discount'=>'-',
            'booking_number'=>'-',
            'price'=>'-',
            'portfolio_name'=>'-',
            'portfolio_category'=>'-',
            'module'=>'-',

            'supplier_name'=>'-',
            'location'=>'-',
            'assets'=>'-',
            'asset'=>'-',
            'program_name'=>'-',
            'order_number'=>'-',

            'insurance_provider'=>'-',
            'old_status'=>'-',
            'new_status'=>'-',
            'store_name'=>'-',
            'course_name'=>'-',

            'student_name' => '-',
            'blog_name' => '-',
            'page_name' => '-',
            'warehouse_name'=>'-',

            'fleet_name'=>'-',
            'process_name'=>'-',
            'hours'=>'-',
            'cycle_name'=>'-',
            'office_name'=>'-',
            'department'=>'-',
            'season_name'=>'-',
            'season'=>'-',
            'crop_name'=>'-',
            'activity'=>'-',
            'cultivation'=>'-',
            'activity_name'=>'-',
            'service_name'=>'-',
            'cultivation_name'=>'-',
            'farmer_name'=>'-',

            'submodule_name'=>'-',
            'module_name'=>'-',
            'tour_name'=>'-',
            'days' => '-',
            'agent_name' => '-',
            'journalist_name' => '-',
            'information'=>'-',
            'newspaper_name'=>'-',
            'advertidsement' => '-',

            'teacher_name' => '-',
            'parent_name' => '-',
            'class_name' => '-',
            'services' => '-',
            'team_name' => '-',

            'property_name'=>'-',
            'unit_name' => '-',
            'vehicle_name' => '-',
            'child_name' => '-',
            'product_name' => '-',
            'consignment_name' => '-',
            'commission'=>'-',
            'machine_name' => '-',
            'doctor_name'=>'-',
            'patient_name'=>'-',
            'specialization'=>'-',
            'homework_title'=>'-',
            'subject_name'=>'-',

            'employee_name'=>'-',
            'note_type'=>'-',
            'article_type'=>'-',
            'book_name'=>'-',
            'position'=> '-',
            'challenge'=>'-',
            'type'=>'-',
        ];

        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }

        if (!empty($id)) {
            $user = User::find($id);
        } else {
            $user = Auth::user();
        }

        $arrValue['company_name'] = $user->name;

        return str_replace($arrVariable, array_values($arrValue), $content);
    }
}
