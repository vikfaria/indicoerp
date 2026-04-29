<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class TicketField extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'placeholder', 'width', 'order', 'status', 'is_required', 'custom_id', 'creator_id', 'created_by'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'status' => 'boolean',
    ];

    public static $fieldTypes = [
        'text' => 'Text',
        'email' => 'Email',
        'number' => 'Number',
        'date' => 'Date',
        'textarea' => 'Textarea',
        'file' => 'File',
        'select' => 'Select',
    ];

    public static function defaultdata($company_id = null)
    {
        // Default Fields
        $field_array = [
            0 => [
                'name' => 'Name',
                'type' => 'text',
                'placeholder' => 'Enter Name',
                'width' => '6',
                'order' => 0,
                'custom_id' => '1',
            ],
            1 => [
                'name' => 'Email',
                'type' => 'email',
                'placeholder' => 'Enter Email',
                'width' => '6',
                'order' => 1,
                'custom_id' => '2',
            ],
            2 => [
                'name' => 'Category',
                'type' => 'text',
                'placeholder' => 'Select Category',
                'width' => '6',
                'order' => 2,
                'custom_id' => '3',
            ],
            3 => [
                'name' => 'Subject',
                'type' => 'text',
                'placeholder' => 'Enter Subject',
                'width' => '6',
                'order' => 3,
                'custom_id' => '4',
            ],
            4 => [
                'name' => 'Description',
                'type' => 'textarea',
                'placeholder' => 'Enter Description',
                'width' => '12',
                'order' => 4,
                'custom_id' => '5',
            ],
            5 => [
                'name' => 'Attachments',
                'type' => 'file',
                'placeholder' => 'You can select multiple files',
                'width' => '12',
                'order' => 5,
                'custom_id' => '6',
            ]
        ];

        if ($company_id) {
            $ticket_f = self::where('created_by', $company_id)->get();
            if (count($ticket_f) == 0) {
                foreach ($field_array as $field) {
                    self::create([
                        'name' => $field['name'],
                        'type' => $field['type'],
                        'placeholder' => $field['placeholder'],
                        'width' => $field['width'],
                        'order' => $field['order'],
                        'custom_id' => $field['custom_id'],
                        'is_required' => true,
                        'status' => true,
                        'creator_id' => $company_id,
                        'created_by' => $company_id,
                    ]);
                }
            }
        }
    }

    public static function saveData($obj, $data)
    {
        if (!empty($data) && count($data) > 0) {
            $RecordId = $obj->id;
            foreach ($data as $fieldId => $value) {
                if (!empty($fieldId) && !empty($value)) {
                    \DB::insert(
                        'insert into ticket_field_values (`record_id`, `field_id`,`value`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`),`updated_at` = VALUES(`updated_at`) ',
                        [
                            $RecordId,
                            $fieldId,
                            $value,
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }
        }
    }

    public static function getData($obj)
    {
        return \DB::table('ticket_field_values')->select(
            [
                'ticket_field_values.value',
                'ticket_fields.id',
            ]
        )->join('ticket_fields', 'ticket_field_values.field_id', '=', 'ticket_fields.id')->where('record_id', '=', $obj->id)->get()->pluck('value', 'id');
    }
}