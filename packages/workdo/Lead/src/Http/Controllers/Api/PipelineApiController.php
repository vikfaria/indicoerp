<?php

namespace Workdo\Lead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\Pipeline;

class PipelineApiController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            $pipelines = Pipeline::where('created_by', '=', creatorId())
                ->get()->map(function ($pipeline) {
                    $leadStages = LeadStage::where('pipeline_id', $pipeline->id)->get();
                    return [
                        'id'     => $pipeline->id,
                        'name'   => $pipeline->name,
                        'stages' => $leadStages->map(function ($stage) {
                            return [
                                'id'    => $stage->id,
                                'name'  => $stage->name,
                                'order' => $stage->order,
                            ];
                        }),
                    ];
                });

            return $this->successResponse($pipelines, 'Pipelines retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function pipelineCreateAndUpdate(Request $request)
    {
        try {
            if ($request->pipeline_id) {
                return $this->updatePipeline($request);
            } else {
                return $this->createPipeline($request);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function createPipeline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $lead_stages = [
            "Draft",
            "Sent",
            "Open",
            "Revised",
            "Declined",
            "Accepted",
        ];

        $deal_stages = [
            'Initial Contact',
            'Qualification',
            'Meeting',
            'Proposal',
            'Close',
        ];

        $pipeline               = new Pipeline();
        $pipeline->name         = $request->name;
        $pipeline->creator_id   = Auth::id();
        $pipeline->created_by   = creatorId();
        $pipeline->save();

        // Create Lead Stages
        foreach ($lead_stages as $index => $stage_name) {
            $leadStage = LeadStage::where('name', $stage_name)
                ->where('pipeline_id',$pipeline->id)
                ->where('created_by', creatorId())
                ->exists();

            if (empty($leadStage)) {
                $leadStage              = new LeadStage();
                $leadStage->name        = $stage_name;
                $leadStage->pipeline_id = $pipeline->id;
                $leadStage->order       = $index + 1;
                $leadStage->creator_id  = Auth::id();
                $leadStage->created_by  = creatorId();
                $leadStage->save();
            }
        }

        // Create Deal Stages
        foreach ($deal_stages as $index => $stage_name) {
            $dealStage = DealStage::where('name', $stage_name)
                ->where('pipeline_id',$pipeline->id)
                ->where('created_by', creatorId())
                ->exists();

            if (empty($dealStage)) {
                $dealStage              = new DealStage();
                $dealStage->name        = $stage_name;
                $dealStage->pipeline_id = $pipeline->id;
                $dealStage->order       = $index + 1;
                $dealStage->creator_id  = Auth::id();
                $dealStage->created_by  = creatorId();
                $dealStage->save();
            }
        }
        return $this->successResponse('', 'Pipeline created successfully');
    }

    private function updatePipeline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pipeline_id' => 'required|exists:pipelines,id',
            'name'        => 'required|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $pipeline = Pipeline::where('created_by', creatorId())
            ->where('id', $request->pipeline_id)
            ->first();

        if (!$pipeline) {
            return $this->errorResponse('Pipeline not found');
        }

        $pipeline->name = $request->name;
        $pipeline->save();

        return $this->successResponse('', 'Pipeline updated successfully');
    }
}
