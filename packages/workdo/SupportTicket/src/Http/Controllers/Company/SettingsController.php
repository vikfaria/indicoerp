<?php

namespace Workdo\SupportTicket\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\SupportTicket\Models\TicketField;

class SettingsController extends Controller
{
    public function update(Request $request)
    {
        if (Auth::user()->can('edit-support-ticket-title-sections')) {
            try {
                $settings = $request->input('settings', []);
                $fields = $settings['fields'] ?? [];
                unset($settings['fields']);
                
                // Handle basic settings
                foreach ($settings as $key => $value) {
                    $settingValue = is_bool($value) ? ($value ? 'on' : 'off') : $value;
                    setSetting($key, $settingValue, creatorId());
                }

                // Handle fields
                if (!empty($fields)) {
                    $field_ids = TicketField::where('created_by', creatorId())
                        ->orderBy('order')
                        ->pluck('id')
                        ->toArray();

                    $order = 0;
                    foreach ($fields as $field) {
                        $fieldObj = new TicketField();

                        if (isset($field['id']) && !empty($field['id'])) {
                            $fieldObj = TicketField::find($field['id']);
                            if (($key = array_search($fieldObj->id, $field_ids)) !== false) {
                                unset($field_ids[$key]);
                            }
                        }

                        $fieldObj->name = $field['name'];
                        $fieldObj->placeholder = $field['placeholder'];
                        
                        if (isset($field['type']) && !empty($field['type'])) {
                            if (isset($fieldObj->id) && $fieldObj->id > 6) {
                                $fieldObj->type = $field['type'];
                            } elseif (!isset($fieldObj->id)) {
                                $fieldObj->type = $field['type'];
                            }
                        }
                        
                        $fieldObj->width = $field['width'] ?? '12';
                        $fieldObj->is_required = $field['is_required'] ?? true;
                        $fieldObj->created_by = creatorId();
                        $fieldObj->creator_id = Auth::id();
                        $fieldObj->order = $field['order'] ?? $order;
                        $fieldObj->status = true;
                        $fieldObj->save();
                        $order++;
                        
                        if (!$fieldObj->custom_id) {
                            $fieldObj->custom_id = $fieldObj->id;
                            $fieldObj->save();
                        }
                    }

                    // Delete removed fields
                    if (!empty($field_ids)) {
                        TicketField::whereIn('id', $field_ids)
                            ->where('status', 1)
                            ->delete();
                    }
                }

                return redirect()->back()->with('success', __('Support settings save successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update support settings: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function getFields()
    {
        if (Auth::user()->can('manage-support-ticket-settings')) {
            try {
                $fields = TicketField::where('created_by', creatorId())
                    ->where('status', true)
                    ->orderBy('order')
                    ->get();

                return response()->json(['fields' => $fields]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to get fields: ') . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }
}