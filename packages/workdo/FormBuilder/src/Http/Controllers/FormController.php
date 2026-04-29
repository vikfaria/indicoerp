<?php

namespace Workdo\FormBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Events\CreateForm;
use Workdo\FormBuilder\Events\UpdateForm;
use Workdo\FormBuilder\Events\DestroyForm;
use Workdo\FormBuilder\Events\FormConverted;
use Workdo\FormBuilder\Http\Requests\StoreFormRequest;
use Workdo\FormBuilder\Http\Requests\UpdateFormRequest;
use Workdo\FormBuilder\Http\Requests\FormFieldRequest;
use Workdo\FormBuilder\Http\Requests\StoreFormConversionRequest;
use Workdo\FormBuilder\Models\FormConversion;

class FormController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-formbuilder')) {
            $forms = Form::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-formbuilder-form')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->withCount(['responses', 'fields'])
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('is_active') !== null, fn($q) => $q->where('is_active', request('is_active')))
                ->orderBy(request('sort', 'created_at'), request('direction', 'desc'))
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('FormBuilder/Forms/Index', [
                'forms' => $forms,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-formbuilder-form')) {
            return Inertia::render('FormBuilder/Forms/Create');
        } else {
            return redirect()->route('formbuilder.forms.index')->with('error', __('Permission denied'));
        }
    }

    public function store(StoreFormRequest $request)
    {
        if (Auth::user()->can('create-formbuilder-form')) {

            $validated = $request->validated();
            $form = new Form();
            $form->name = $validated['name'];
            $form->code = Form::generateCode();
            $form->is_active = $request->boolean('is_active', true);
            $form->default_layout = $validated['default_layout'] ?? 'single';
            $form->creator_id = Auth::id();
            $form->created_by = creatorId();
            $form->save();

            // Save form fields
            if (isset($validated['fields']) && is_array($validated['fields'])) {
                foreach ($validated['fields'] as $fieldData) {
                    $form->fields()->create([
                        'label' => $fieldData['label'],
                        'type' => $fieldData['type'],
                        'required' => $fieldData['required'] ?? false,
                        'placeholder' => $fieldData['placeholder'] ?? null,
                        'options' => isset($fieldData['options']) ? json_encode($fieldData['options']) : null,
                        'order' => $fieldData['order'] ?? 0,
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId(),
                    ]);
                }
            }

            CreateForm::dispatch($request, $form);

            return redirect()->route('formbuilder.forms.index')->with('success', __('The form has been created successfully.'));
        } else {
            return redirect()->route('formbuilder.forms.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateFormRequest $request, Form $form)
    {
        if (Auth::user()->can('edit-formbuilder-form')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $validated = $request->validated();

            $form->name = $validated['name'];
            $form->is_active = $request->boolean('is_active', true);
            $form->default_layout = $validated['default_layout'] ?? 'single';
            $form->save();

            // Update form fields
            if (isset($validated['fields']) && is_array($validated['fields'])) {
                foreach ($validated['fields'] as $fieldData) {
                    $form->fields()->updateOrInsert(
                        [
                            'form_id'   => $form->id,
                            'id'        => $fieldData['id'] ?? null,
                        ],
                        [
                            'label'         => $fieldData['label'],
                            'type'          => $fieldData['type'],
                            'required'      => $fieldData['required'] ?? false,
                            'placeholder'   => $fieldData['placeholder'] ?? null,
                            'options'       => isset($fieldData['options']) && is_array($fieldData['options']) ? json_encode($fieldData['options']) : null,
                            'order'         => $fieldData['order'] ?? 0,
                            'creator_id'    => Auth::id(),
                            'created_by'    => creatorId(),
                        ]
                    );
                }
            }

            UpdateForm::dispatch($request, $form);

            return redirect()->route('formbuilder.forms.index')->with('success', __('The form details are updated successfully.'));
        } else {
            return redirect()->route('formbuilder.forms.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Form $form)
    {
        if (Auth::user()->can('delete-formbuilder-form')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            DestroyForm::dispatch($form);
            $form->delete();

            return back()->with('success', __('The form has been deleted.'));
        } else {
            return redirect()->route('formbuilder.forms.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(Form $form)
    {
        if (Auth::user()->can('edit-formbuilder-form')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $form->load('fields');

            $form->fields->transform(function ($field) {
                if ($field->options && is_string($field->options)) {
                    $field->options = json_decode($field->options, true) ?: [];
                }
                return $field;
            });

            return Inertia::render('FormBuilder/Forms/Edit', [
                'form' => $form,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }


    public function responses(Form $form)
    {
        if (Auth::user()->can('view-formbuilder-responses')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $form->load('fields');

            $responses = $form->responses()
                ->when(request('search'), function ($q) {
                    $search = request('search');
                    $q->where('response_data', 'like', '%' . $search . '%');
                })
                ->latest()
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('FormBuilder/Forms/Responses', [
                'form' => $form,
                'responses' => $responses,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroyResponse(Form $form, $responseId)
    {
        if (Auth::user()->can('delete-formbuilder-responses')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $response = $form->responses()->findOrFail($responseId);
            $response->delete();

            return back()->with('success', __('The response has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateFields(FormFieldRequest $request, Form $form)
    {
        if (Auth::user()->can('edit-formbuilder-form-fields')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $validated = $request->validated();

            $form->fields()->delete();

            foreach ($validated['fields'] as $fieldData) {
                $form->fields()->create([
                    'label' => $fieldData['label'],
                    'type' => $fieldData['type'],
                    'required' => $fieldData['required'] ?? false,
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'options' => isset($fieldData['options']) ? json_encode($fieldData['options']) : null,
                    'order' => $fieldData['order'] ?? 0,
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId(),
                ]);
            }

            return back()->with('success', __('The form fields are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroyField(Form $form, $fieldId)
    {
        if (Auth::user()->can('delete-formbuilder-form-fields')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return response()->json(['error' => __('Form not found.')], 404);
            }

            $field = $form->fields()->findOrFail($fieldId);
            $field->delete();

            return response()->json(['success' => __('Field deleted successfully.')]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function updateConversion(StoreFormConversionRequest $request, Form $form)
    {
        if (Auth::user()->can('manage-formbuilder-conversions')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $validated = $request->validated();

            $conversion = $form->conversion ?? new FormConversion();
            $conversion->form_id = $form->id;
            $conversion->module_name = $validated['module_name'];
            $conversion->submodule_name = $validated['submodule_name'];
            $conversion->is_active = $request->boolean('is_active', false);
            $conversion->field_mappings = $validated['field_mappings'];
            $conversion->creator_id = Auth::id();
            $conversion->created_by = creatorId();
            $conversion->save();

            FormConverted::dispatch($request, $form, $conversion);

            return back()->with('success', __('The form conversion settings are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getConversionData(Form $form)
    {
        if (Auth::user()->can('manage-formbuilder-conversions')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return response()->json(['error' => 'Form not found'], 404);
            }

            $conversion = $form->conversion;
            $availableModules = FormConversion::getAvailableModules();

            return response()->json([
                'conversion' => $conversion,
                'available_modules' => $availableModules,
                'form_fields' => $form->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'label' => $field->label,
                        'type' => $field->type,
                    ];
                }),
                'users' => User::where('created_by', creatorId())->select('id', 'name')->get(),
                'lead_pipelines' => Module_is_active('Lead') && class_exists('\Workdo\Lead\Models\Pipeline') ? \Workdo\Lead\Models\Pipeline::where('created_by', creatorId())->select('id', 'name')->get() : [],
                'deal_pipelines' => Module_is_active('Lead') && class_exists('\Workdo\Lead\Models\DealPipeline') ? \Workdo\Lead\Models\DealPipeline::where('created_by', creatorId())->select('id', 'name')->get() : [],
                'clients' => Module_is_active('Lead') && class_exists('\Workdo\Lead\Models\User') ? \Workdo\Lead\Models\User::where('type', 'client')->where('created_by', creatorId())->select('id', 'name')->get() : [],
                'accounts' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\Account') ? \Workdo\Sales\Models\Account::where('created_by', creatorId())->select('id', 'name')->get() : [],
                'opportunity_stages' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\OpportunitiesStage') ? \Workdo\Sales\Models\OpportunitiesStage::where('created_by', creatorId())->select('id', 'name')->get() : [],
                'contract_types' => Module_is_active('Contract') && class_exists('\Workdo\Contract\Models\ContractType') ? \Workdo\Contract\Models\ContractType::where('created_by', creatorId())->select('id', 'name')->get() : [],
                'books' => Module_is_active('InternalKnowledge') && class_exists('\Workdo\Internalknowledge\Models\Book') ? \Workdo\Internalknowledge\Models\Book::where('created_by', creatorId())->select('id', 'title as name')->get() : [],
            ]);
        } else {
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }

    public function conversion(Form $form)
    {
        if (Auth::user()->can('manage-formbuilder-conversions')) {
            $form = Form::where(function ($q) {
                if (Auth::user()->can('manage-any-formbuilder-form')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-formbuilder-form')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })->find($form->id);

            if (!$form) {
                return redirect()->route('formbuilder.forms.index')->with('error', __('Form not found.'));
            }

            $conversion = $form->conversion;
            $availableModules = FormConversion::getAvailableModules();

            return Inertia::render('FormBuilder/Forms/Conversion', [
                'form' => $form,
                'conversion' => $conversion,
                'available_modules' => $availableModules,
                'form_fields' => $form->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'label' => $field->label,
                        'type' => $field->type,
                    ];
                }),
            ]);
        } else {
            return redirect()->route('formbuilder.forms.index')->with('error', __('Permission denied'));
        }
    }
}
