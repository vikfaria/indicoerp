<?php

namespace Workdo\FormBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Models\FormResponse;
use Workdo\FormBuilder\Events\ViewForm;
use Workdo\FormBuilder\Services\FormConversionService;

class PublicFormController extends Controller
{
    public function show($code)
    {
        $form = Form::where('code', $code)->where('is_active', true)->first();
        
        if (!$form) {
            abort(404, 'Form not found or inactive');
        }

        try {
            $form->load('fields');

            // Ensure options are arrays for frontend
            $form->fields->transform(function ($field) {
                if ($field->options && is_string($field->options)) {
                    $field->options = json_decode($field->options, true) ?: [];
                }
                return $field;
            });

            return Inertia::render('FormBuilder/Public/Form', [
                'form' => $form,
            ])->with([
                'flash' => session()->only(['success', 'error'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $code], 404);
        }
    }

    public function submit(Request $request, $code)
    {
        $form = Form::with(['fields', 'conversion'])->where('code', $code)->where('is_active', true)->firstOrFail();

        $rules = [];
        $messages = [];

        foreach ($form->fields as $field) {
            $fieldName = 'field_' . $field->id;
            $fieldRules = [];

            if ($field->required) {
                $fieldRules[] = 'required';
                $messages["{$fieldName}.required"] = __('The :field field is required.', ['field' => strtolower($field->label)]);
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field->type) {
                case 'email':
                    $fieldRules[] = 'email';
                    $messages["{$fieldName}.email"] = __('The :field must be a valid email address.', ['field' => strtolower($field->label)]);
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    $messages["{$fieldName}.numeric"] = __('The :field must be a number.', ['field' => strtolower($field->label)]);
                    break;
                case 'tel':
                    $fieldRules[] = 'string';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    $messages["{$fieldName}.url"] = __('The :field must be a valid URL.', ['field' => strtolower($field->label)]);
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    $messages["{$fieldName}.date"] = __('The :field must be a valid date.', ['field' => strtolower($field->label)]);
                    break;
                case 'time':
                    $fieldRules[] = 'date_format:H:i';
                    $messages["{$fieldName}.date_format"] = __('The :field must be a valid time.', ['field' => strtolower($field->label)]);
                    break;
                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
                case 'select':
                case 'radio':
                    if (!empty($field->options)) {
                        $options = is_string($field->options) ? json_decode($field->options, true) : $field->options;
                        $fieldRules[] = 'in:' . implode(',', $options);
                    }
                    break;
                case 'text':
                case 'textarea':
                case 'password':
                default:
                    $fieldRules[] = 'string';
                    break;
            }

            $rules[$fieldName] = $fieldRules;
        }

        $validated = $request->validate($rules, $messages);

        // Check if form is completely empty (prevent blank submissions)
        $hasData = false;
        $responseData = [];
        foreach ($form->fields as $field) {
            $fieldName = 'field_' . $field->id;
            if (array_key_exists($fieldName, $validated)) {
                $value = $validated[$fieldName];
                $responseData[$field->id] = $value;

                // Check if field has meaningful data
                if ($field->type === 'checkbox') {
                    if ($value === true || $value === '1' || $value === 1) {
                        $hasData = true;
                    }
                } else {
                    if (!empty($value) && trim($value) !== '') {
                        $hasData = true;
                    }
                }
            }
        }

        if (!$hasData) {
            return redirect()->back()->withErrors(['form' => __('Please fill at least one field before submitting the form.')]);
        }
        $response = new FormResponse();
        $response->form_id = $form->id;
        $response->response_data = $responseData;
        $response->save();

        ViewForm::dispatch($form, $response);

        // Process conversion if enabled
        try {
            $conversionService = new FormConversionService();
            $conversionService->processConversion($form, $response);
        } catch (\Exception $e) {
        }

        return redirect()->back()->with('success', __('The form has been submitted successfully.'));
    }
}
