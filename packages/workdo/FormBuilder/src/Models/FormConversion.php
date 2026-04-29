<?php

namespace Workdo\FormBuilder\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FormConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'module_name',
        'submodule_name',
        'is_active',
        'field_mappings',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'field_mappings' => 'array',
    ];



    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get available modules for conversion
     */
    public static function getAvailableModules()
    {
        $user = User::where('created_by', creatorId())
            ->when(!Auth::user()->can('manage-any-users'), function ($q) {
                if (Auth::user()->can('manage-own-users')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        $modules = [
            'Taskly' => [
                'Project' => [
                    'project_name' => ['label' => 'Project Name', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'start_date' => ['label' => 'Start Date', 'type' => 'date'],
                    'end_date' => ['label' => 'End Date', 'type' => 'date'],
                    'budget' => ['label' => 'Budget', 'type' => 'number'],
                    'users_id' => [
                        'label' => 'Team Members',
                        'type' => 'select',
                        'options' =>  $user->emp()->select('id', 'name')->get(),
                    ],
                ],
            ],
            'Lead' => [
                'Lead' => [
                    'name' => ['label' => 'Name', 'type' => 'text'],
                    'email' => ['label' => 'Email', 'type' => 'email'],
                    'subject' => ['label' => 'Subject', 'type' => 'text'],
                    'phone' => ['label' => 'Phone No', 'type' => 'tel'],
                    'date' => ['label' => 'Follow Up Date', 'type' => 'date'],
                    'user_id' => [
                        'label' => 'Assigned User',
                        'type' => 'select',
                        'options' => $user->emp([], ['vendor'])->select('id', 'name')->get()
                    ],
                    'pipeline_id' => [
                        'label' => 'Pipeline',
                        'type' => 'select',
                        'options' => Module_is_active('Lead') && class_exists('\Workdo\Lead\Models\Pipeline') ? \Workdo\Lead\Models\Pipeline::where('created_by', creatorId())->select('id', 'name')->get() : []
                    ],
                ],
                'Deal' => [
                    'name' => ['label' => 'Deal Name', 'type' => 'text'],
                    'price' => ['label' => 'Price', 'type' => 'number'],
                    'phone' => ['label' => 'Phone Number', 'type' => 'tel'],
                    'clients' => [
                        'label' => 'Clients',
                        'type' => 'select',
                        'options' => \App\Models\User::where('type', 'client')->where('created_by', creatorId())->select('id', 'name')->get()
                    ],
                    'pipeline_id' => [
                        'label' => 'Pipeline',
                        'type' => 'select',
                        'options' => Module_is_active('Lead') && class_exists('\Workdo\Lead\Models\Pipeline') ? \Workdo\Lead\Models\Pipeline::where('created_by', creatorId())->select('id', 'name')->get() : []
                    ],
                ],
            ],
            'Sales' => [
                'Contact' => [
                    'name' => ['label' => 'Name', 'type' => 'text'],
                    'email' => ['label' => 'Email', 'type' => 'email'],
                    'phone' => ['label' => 'Phone Number', 'type' => 'tel'],
                    'address' => ['label' => 'Address', 'type' => 'textarea'],
                    'city' => ['label' => 'City', 'type' => 'text'],
                    'state' => ['label' => 'State', 'type' => 'text'],
                    'postal_code' => ['label' => 'Postal Code', 'type' => 'text'],
                    'country' => ['label' => 'Country', 'type' => 'text'],
                    'account_id' => [
                        'label' => 'Account',
                        'type' => 'select',
                        'options' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\SalesAccount') ? \Workdo\Sales\Models\SalesAccount::where('created_by', creatorId())->where('is_active', true)
                            ->when(!Auth::user()->can('manage-any-sales-accounts'), function ($q) {
                                if (Auth::user()->can('manage-own-sales-accounts')) {
                                    $q->where(function ($query) {
                                        $query->where('creator_id', Auth::id())->orWhere('assign_user_id', Auth::id());
                                    });
                                } else {
                                    $q->whereRaw('1 = 0');
                                }
                            })->select('id', 'name')->get() : []
                    ],
                    'assign_user_id' => [
                        'label' => 'Assigned User',
                        'type' => 'select',
                        'options' =>  $user->emp()->select('id', 'name')->get(),
                    ],
                ],
                'Opportunity' => [
                    'name' => ['label' => 'Name', 'type' => 'text'],
                    'amount' => ['label' => 'Amount', 'type' => 'number'],
                    'probability' => ['label' => 'Probability (%)', 'type' => 'number'],
                    'close_date' => ['label' => 'Close Date', 'type' => 'date'],
                    'account_id' => [
                        'label' => 'Account',
                        'type' => 'select',
                        'options' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\SalesAccount') ? \Workdo\Sales\Models\SalesAccount::where('created_by', creatorId())->where('is_active', true)
                            ->when(!Auth::user()->can('manage-any-sales-accounts'), function ($q) {
                                if (Auth::user()->can('manage-own-sales-accounts')) {
                                    $q->where(function ($query) {
                                        $query->where('creator_id', Auth::id())->orWhere('assign_user_id', Auth::id());
                                    });
                                } else {
                                    $q->whereRaw('1 = 0');
                                }
                            })->select('id', 'name')->get() : []
                    ],
                    'contact_id' => [
                        'label' => 'Contact',
                        'type' => 'select',
                        'options' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\SalesContact') ? \Workdo\Sales\Models\SalesContact::where('created_by', creatorId())->where('is_active', true)
                            ->when(!Auth::user()->can('manage-any-sales-contacts'), function ($q) {
                                if (Auth::user()->can('manage-own-sales-contacts')) {
                                    $q->where(function ($query) {
                                        $query->where('creator_id', Auth::id())->orWhere('assign_user_id', Auth::id());
                                    });
                                } else {
                                    $q->whereRaw('1 = 0');
                                }
                            })->select('id', 'name')->get() : []
                    ],
                    'assign_user_id' => [
                        'label' => 'Assigned User',
                        'type' => 'select',
                        'options' =>  $user->emp()->select('id', 'name')->get(),
                    ],
                    'stage_id' => [
                        'label' => 'Opportunity Stage',
                        'type' => 'select',
                        'options' => Module_is_active('Sales') && class_exists('\Workdo\Sales\Models\SalesOpportunityStage') ? \Workdo\Sales\Models\SalesOpportunityStage::where('created_by', creatorId())
                            ->when(!Auth::user()->can('manage-any-sales-opportunity-stages'), function ($q) {
                                if (Auth::user()->can('manage-own-sales-opportunity-stages')) {
                                    $q->where('creator_id', Auth::id());
                                } else {
                                    $q->whereRaw('1 = 0');
                                }
                            })
                            ->where('is_active', true)
                            ->orderBy('order')
                            ->select('id', 'name')
                            ->get() : []
                    ],
                ],
            ],
            'Contract' => [
                'Contract' => [
                    'subject' => ['label' => 'Subject', 'type' => 'text'],
                    'value' => ['label' => 'Value', 'type' => 'number'],
                    'start_date' => ['label' => 'Start Date', 'type' => 'date'],
                    'end_date' => ['label' => 'End Date', 'type' => 'date'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'status' => [
                        'label' => 'Status',
                        'type' => 'select',
                        'options' => [
                            ['id' => 'pending', 'name' => 'Pending'],
                            ['id' => 'active', 'name' => 'Active'],
                            ['id' => 'expired', 'name' => 'Expired'],
                            ['id' => 'cancelled', 'name' => 'Cancelled'],
                        ]
                    ],
                    'user_id' => [
                        'label' => 'Assigned User',
                        'type' => 'select',
                        'options' => $user->select('id', 'name')->get()
                    ],
                    'type_id' => [
                        'label' => 'Contract Type',
                        'type' => 'select',
                        'options' => Module_is_active('Contract') && class_exists('\Workdo\Contract\Models\ContractType') ? \Workdo\Contract\Models\ContractType::where('created_by', creatorId())->where('is_active', true)->select('id', 'name')->get() : []
                    ],
                ],
            ],
            'Internalknowledge' => [
                'Book' => [
                    'title' => ['label' => 'Title', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'users' => [
                        'label' => 'Assigned Users',
                        'type' => 'select',
                        'multiple' => true,
                        'options' => $user->emp()->select('id', 'name')->get()
                    ],
                ],
                'Article' => [
                    'title' => ['label' => 'Title', 'type' => 'text'],
                    'description' => ['label' => 'Overview', 'type' => 'textarea'],
                    'internalknowledge_book_id' => [
                        'label' => 'Book',
                        'type' => 'select',
                        'options' => Module_is_active('Internalknowledge') && class_exists('\Workdo\Internalknowledge\Models\InternalknowledgeBook') ? \Workdo\Internalknowledge\Models\InternalknowledgeBook::where(function ($q) {
                            if (Auth::user()->can('manage-any-internalknowledge-books')) {
                                $q->where('created_by', creatorId());
                            } elseif (Auth::user()->can('manage-own-internalknowledge-books')) {
                                $q->where('created_by', creatorId())
                                    ->where(function ($query) {
                                        $query->where('creator_id', Auth::id())
                                            ->orWhereJsonContains('users', (string) Auth::id());
                                    });
                            } else {
                                $q->where('created_by', creatorId())
                                    ->whereJsonContains('users', (string) Auth::id());
                            }
                        })->select('id', 'title as name')->get() : []
                    ],
                    'type' => [
                        'label' => 'Type',
                        'type' => 'select',
                        'options' => [
                            ['id' => 'Document', 'name' => 'Document'],
                            ['id' => 'Mind Map', 'name' => 'Mind Map'],
                        ]
                    ],
                ],
            ],
            'Notes' => [
                'Note' => [
                    'title' => ['label' => 'Title', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'color' => [
                        'label' => 'Color',
                        'type' => 'select',
                        'options' => [
                            ['id' => 'bg-primary', 'name' => 'Primary'],
                            ['id' => 'bg-secondary', 'name' => 'Secondary'],
                            ['id' => 'bg-success', 'name' => 'Success'],
                            ['id' => 'bg-danger', 'name' => 'Danger'],
                            ['id' => 'bg-warning', 'name' => 'Warning'],
                            ['id' => 'bg-info', 'name' => 'Info']
                        ],
                    ],
                    'type' => [
                        'label' => 'Type',
                        'type' => 'select',
                        'options' => [
                            ['id' => '0', 'name' => 'Personal'],
                            ['id' => '1', 'name' => 'Shared'],
                        ]
                    ],
                ],
            ],
            'CMMS' => [
                'Location' => [
                    'name' => ['label' => 'Name', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'type' => 'textarea'],
                ],
            ],
            'MachineRepairManagement' => [
                'Machine' => [
                    'machine_name' => ['label' => 'Machine Name', 'type' => 'text'],
                    'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text'],
                    'model' => ['label' => 'Model', 'type' => 'text'],
                    'installation_date' => ['label' => 'Installation Date', 'type' => 'date'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'is_enabled' => [
                        'label' => 'Status',
                        'type' => 'select',
                        'options' => [
                            ['id' => '1', 'name' => 'Enabled'],
                            ['id' => '0', 'name' => 'Disabled'],
                        ]
                    ],
                ],
            ],
        ];

        // Filter out modules that are not active or user doesn't have permission
        $filteredModules = [];
        foreach ($modules as $moduleName => $submodules) {
            if (Module_is_active($moduleName)) {
                // Check module-specific permissions
                $hasPermission = true;
                switch ($moduleName) {
                    case 'Contract':
                        $hasPermission = Auth::user()->can('create-contracts');
                        break;
                    case 'Lead':
                        $hasPermission = Auth::user()->can('create-leads') || Auth::user()->can('create-deals');
                        break;
                    case 'Sales':
                        $hasPermission = Auth::user()->can('create-sales-contacts') || Auth::user()->can('create-sales-opportunities');
                        break;
                    case 'Taskly':
                        $hasPermission = Auth::user()->can('create-project');
                        break;
                    case 'Internalknowledge':
                        $hasPermission = Auth::user()->can('create-internalknowledge-books') || Auth::user()->can('create-internalknowledge-articles');
                        break;
                    case 'Notes':
                        $hasPermission = Auth::user()->can('create-notes');
                        break;
                    case 'CMMS':
                        $hasPermission = Auth::user()->can('create-cmms-locations');
                        break;
                    case 'MachineRepairManagement':
                        $hasPermission = Auth::user()->can('create-machines');
                        break;
                }
                
                if ($hasPermission) {
                    $filteredModules[$moduleName] = $submodules;
                }
            }
        }

        return $filteredModules;
    }
}
