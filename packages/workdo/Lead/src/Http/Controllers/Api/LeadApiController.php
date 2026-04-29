<?php

namespace Workdo\Lead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Workdo\Lead\Models\Label;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\LeadActivityLog;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\Source;
use Workdo\Lead\Models\UserLead;
use Workdo\ProductService\Models\ProductServiceItem;

class LeadApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (Auth::user()->can('manage-leads')) {
                $validator = Validator::make($request->all(), [
                    'pipeline_id' => 'required|integer|exists:pipelines,id'
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }
                $pipelineId = $request->pipeline_id;

                $leadStages = LeadStage::where('pipeline_id', $pipelineId)
                    ->where('created_by', creatorId())
                    ->get()
                    ->map(function ($stage) {
                        return (object) [
                            'id'    => $stage->id,
                            'name'  => $stage->name,
                            'order' => $stage->order,
                        ];
                    });

                foreach ($leadStages as $key => $stage) {
                    $lead = Lead::where('created_by', creatorId())
                        ->where('pipeline_id', $pipelineId)
                        ->where('stage_id', $stage->id)
                        ->with(['userLeads.user', 'tasks']);

                    $lead = $lead->where(function ($q) {
                        if (Auth::user()->can('manage-any-leads')) {
                            $q->where('created_by', creatorId());
                        } elseif (Auth::user()->can('manage-own-leads')) {
                            $q->where(function ($subQ) {
                                $subQ->where('creator_id', Auth::id())
                                    ->orWhereHas('userLeads', function ($leadQ) {
                                        $leadQ->where('user_id', Auth::id());
                                    });
                            });
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    });
                    $lead = $lead->get();

                    $stage->leads = $lead->map(function ($lead) use ($key, $leadStages) {
                        $labelIds = $lead->labels ? explode(',', $lead->labels) : [];
                        $labels   = Label::whereIn('id', $labelIds)
                            ->where('created_by', creatorId())
                            ->get()
                            ->map(function ($label) {
                                return [
                                    'id'    => $label->id,
                                    'name'  => $label->name,
                                    'color' => $label->color,
                                ];
                            });
                        $users = $lead->userLeads->map(function ($userLead) {
                            return [
                                'id'     => $userLead->user->id,
                                'name'   => $userLead->user->name,
                                'avatar' => $userLead->user->avatar ? getImageUrlPrefix() . '/' . $userLead->user->avatar : getImageUrlPrefix() . '/' . 'avatar.png',
                            ];
                        });

                        return [
                            'id'             => $lead->id,
                            'name'           => $lead->name,
                            'order'          => $lead->order,
                            'email'          => $lead->email,
                            'subject'        => $lead->subject,
                            'phone'          => $lead->phone,
                            'follow_up_date' => $lead->date->format('Y-m-d'),
                            'previous_stage' => isset($leadStages[$key - 1]) ? $leadStages[$key - 1]->id : 0,
                            'current_stage'  => $leadStages[$key]->id,
                            'next_stage'     => isset($leadStages[$key + 1]) ? $leadStages[$key + 1]->id : 0,
                            'total_tasks'    => $lead->tasks->where('status', 'Complete')->count() . '/' . $lead->tasks->count(),
                            'total_products' => !empty($lead->products) ? count(explode(',', $lead->products)) : 0,
                            'total_sources'  => !empty($lead->sources) ? count(explode(',', $lead->sources)) : 0,
                            'labels'         => $labels,
                            'users'          => $users
                        ];
                    });
                }

                return $this->successResponse($leadStages, 'Lead retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function leadCreateAndUpdate(Request $request)
    {
        try {
            if ($request->lead_id) {
                return $this->updateLead($request);
            } else {
                return $this->createLead($request);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function createLead(Request $request)
    {
        try {
            if (Auth::user()->can('create-leads')) {
                $validator = Validator::make($request->all(), [
                    'name'    => 'required',
                    'email'   => 'required|email',
                    'subject' => 'required',
                    'phone'   => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
                    'date'    => 'required|date',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $usr       = Auth::user();
                $pipelines = Pipeline::where('created_by', '=', creatorId());
                if ($usr->default_pipeline) {
                    $pipeline = $pipelines->where('id', '=', $usr->default_pipeline)->first();
                    if (!$pipeline) {
                        $pipeline = $pipelines->first();
                    }
                } else {
                    $pipeline = $pipelines->first();
                }
                if (!empty($pipeline)) {
                    $stage = LeadStage::where('pipeline_id', '=', $pipeline->id)->first();
                } else {
                    return $this->errorResponse('Please create pipeline');
                }
                if (empty($stage)) {
                    return $this->errorResponse('Please create stage for this pipeline');
                } else {

                    $lead              = new Lead();
                    $lead->name        = $request->name;
                    $lead->email       = $request->email;
                    $lead->subject     = $request->subject;
                    $lead->user_id     = $request->user_id;
                    $lead->pipeline_id = $pipeline->id;
                    $lead->stage_id    = $stage->id;
                    $lead->phone       = $request->phone;
                    $lead->date        = $request->date;
                    $lead->creator_id  = Auth::id();
                    $lead->created_by  = creatorId();
                    $lead->save();

                    if (Auth::user()->type == 'company') {
                        $usrLeads = [
                            $usr->id,
                            $request->user_id,
                        ];
                    } else {
                        $usrLeads = [
                            creatorId(),
                            $request->user_id,
                        ];
                    }

                    $usrLeads = array_unique(array_filter($usrLeads));

                    foreach ($usrLeads as $usrLead) {
                        UserLead::firstOrCreate(
                            [
                                'user_id' => $usrLead,
                                'lead_id' => $lead->id,
                            ]
                        );
                    }
                }
                return $this->successResponse('', 'Lead created successfully');
            } else {
                return $this->errorResponse('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function updateLead(Request $request)
    {
        try {
            if (Auth::user()->can('edit-leads')) {

                $validator = Validator::make($request->all(), [
                    'lead_id'     => 'required|integer|exists:leads,id',
                    'name'        => 'required|max:100',
                    'email'       => 'required|email',
                    'subject'     => 'required|max:200',
                    'date'        => 'required|date',
                    'user_id'     => 'nullable|integer',
                    'pipeline_id' => 'required|integer',
                    'stage_id'    => 'nullable|integer',
                    'sources'     => 'nullable|max:100',
                    'products'    => 'nullable|max:100',
                    'notes'       => 'nullable|max:1000',
                    'phone'       => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $lead = Lead::where('created_by', creatorId())->where('id', $request->lead_id)->first();

                if (!$lead) {
                    return $this->errorResponse('Lead not found');
                }

                $lead->name        = $request->name;
                $lead->email       = $request->email;
                $lead->subject     = $request->subject;
                $lead->user_id     = $request->user_id;
                $lead->phone       = $request->phone;
                $lead->date        = $request->date;
                $lead->pipeline_id = $request->pipeline_id ?? $lead->pipeline_id;
                $lead->stage_id    = $request->stage_id ?? $lead->stage_id;
                $lead->sources     = $request->sources ?? $lead->sources;
                $lead->products    = $request->products ?? $lead->products;
                $lead->notes       = $request->notes ?? $lead->notes;
                $lead->labels      = $request->input('labels', $lead->labels);
                $lead->save();

                return $this->successResponse('', 'Lead updated successfully');
            } else {
                return $this->errorResponse('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function leadStageUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'lead_id'  => 'required|exists:leads,id',
                'stage_id' => 'required|exists:lead_stages,id',
                'order'    => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $lead = Lead::find($request->lead_id);

            if ($lead->stage_id != $request->stage_id) {
                $newStage = LeadStage::find($request->stage_id);

                LeadActivityLog::create([
                    'user_id'  => Auth::user()->id,
                    'lead_id'  => $lead->id,
                    'log_type' => 'Move',
                    'remark'   => json_encode([
                        'title'      => $lead->name,
                        'old_status' => $lead->stage->name,
                        'new_status' => $newStage->name,
                    ]),
                ]);
            }

            Lead::where('id', $request->lead_id)->update(['order' => $request->order, 'stage_id' => $request->stage_id]);

            return $this->successResponse('', 'Lead moved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function leadDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pipeline_id' => 'required|integer|exists:pipelines,id',
            'lead_id'     => 'required|integer|exists:leads,id'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $pipelineId = $request->pipeline_id;
        $leadId     = $request->lead_id;


        try {

            $lead = Lead::where('created_by', '=', creatorId())->where('pipeline_id', $pipelineId)->where('id', $leadId)->first();

            $stageCnt = LeadStage::where('pipeline_id', '=', $lead->pipeline_id)->where('created_by', '=', $lead->created_by)->get();
            $i        = 0;
            foreach ($stageCnt as $stage) {
                $i++;
                if ($stage->id == $lead->stage_id) {
                    break;
                }
            }
            $precentage = number_format(($i * 100) / count($stageCnt));

            $data = [
                'id'             => $lead->id,
                'name'           => $lead->name,
                'email'          => $lead->email,
                'subject'        => $lead->subject,
                'pipeline_id'    => $lead->pipeline_id,
                'pipeline_name'  => $lead->pipeline->name,
                'stage_id'       => $lead->stage_id,
                'stage_name'     => $lead->stage->name,
                'order'          => $lead->order,
                'phone'          => $lead->phone,
                'created_at'     => $lead->created_at->format('Y-m-d'),
                'follow_up_date' => $lead->date->format('Y-m-d'),
                'percentage'     => $precentage . '%',
                'tasks_list'     => $lead->tasks->map(function ($task) {
                    return [
                        'id'       => $task->id,
                        'name'     => $task->name,
                        'date'     => $task->date->format('Y-m-d'),
                        'time'     => \Carbon\Carbon::parse($task->time)->format('H:i:s'),
                        'priority' => $task->priority,
                        'status'   => $task->status,
                    ];
                }),

                'lead_activity'     => $lead->activities->map(function ($activity) {
                    return [
                        'id'     => $activity->id,
                        'remark' => strip_tags($activity->getLeadRemark()),
                        'time'   => $activity->created_at->diffForHumans(),
                    ];
                }),
            ];

            return $this->successResponse($data, 'Lead retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function destroy(Request $request)
    {
        try {
            if (Auth::user()->can('delete-leads')) {
                $validator = Validator::make($request->all(), [
                    'lead_id' => 'required|exists:leads,id',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $lead = Lead::findOrFail($request->lead_id);

                if ($lead->created_by != creatorId()) {
                    return $this->errorResponse('Lead Not Found', 404);
                }

                LeadActivityLog::where('lead_id', '=', $lead->id)->delete();

                $lead->delete();

                return $this->successResponse('', 'Lead Deleted successfully');
            } else {
                return $this->errorResponse('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function getUsers()
    {
        try {
            $users = User::where('created_by', '=', creatorId())
                ->emp([], ['vendor'])
                ->select('id', 'name')
                ->get();

            return $this->successResponse($users, 'Users retrived successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function getRequestData()
    {
        try {
            $sources = Source::where('created_by', '=', creatorId())->select('id', 'name')->get();
            if (module_is_active('ProductService')) {
                $products = ProductServiceItem::where('created_by', '=', creatorId())->select('id', 'name')->get();
            }
            $data = [
                'sources'  => $sources,
                'products' => $products ?? [],
            ];
            return $this->successResponse($data, 'Sources retrived successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
