<?php

namespace Workdo\Lead\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\User;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Http\Requests\StoreLeadRequest;
use Workdo\Lead\Http\Requests\UpdateLeadRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\UserLead;
use Workdo\Lead\Models\Label;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Http\Request;
use Workdo\Lead\Http\Requests\AssignUsersRequest;
use Workdo\Lead\Http\Requests\StoreLeadCallRequest;
use Workdo\Lead\Http\Requests\UpdateLeadCallRequest;
use Workdo\Lead\Models\LeadActivityLog;
use Workdo\Lead\Models\LeadCall;
use Workdo\Lead\Models\LeadDiscussion;
use Workdo\Lead\Models\LeadEmail;
use Workdo\Lead\Models\LeadFile;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Http\Requests\ConvertToDealRequest;
use Workdo\Lead\Http\Requests\StoreLeadEmailRequest;
use Workdo\Lead\Http\Requests\StoreLeadDiscussionRequest;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\DealDiscussion;
use Workdo\Lead\Models\DealFile;
use Workdo\Lead\Models\DealCall;
use Workdo\Lead\Models\DealEmail;
use Workdo\Lead\Models\ClientDeal;
use Spatie\Permission\Models\Role;
use Workdo\Lead\Models\UserDeal;
use Workdo\Lead\Events\CreateLead;
use Workdo\Lead\Events\UpdateLead;
use Workdo\Lead\Events\DestroyLead;
use Workdo\Lead\Events\LeadMoved;
use Workdo\Lead\Events\LeadAddUser;
use Workdo\Lead\Events\DestroyUserLead;
use Workdo\Lead\Events\LeadAddProduct;
use Workdo\Lead\Events\DestroyLeadProduct;
use Workdo\Lead\Events\LeadUploadFile;
use Workdo\Lead\Events\DestroyLeadFile;
use Workdo\Lead\Events\LeadSourceUpdate;
use Workdo\Lead\Events\DestroyLeadSource;
use Workdo\Lead\Events\LeadAddDiscussion;
use Workdo\Lead\Events\LeadAddCall;
use Workdo\Lead\Events\LeadCallUpdate;
use Workdo\Lead\Events\DestroyLeadCall;
use Workdo\Lead\Events\LeadAddEmail;
use Workdo\Lead\Events\LeadConvertDeal;
use Workdo\Lead\Models\Source;
use App\Events\CreateUser;
class LeadController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-leads')) {
            // Get user's default pipeline or first available pipeline
            $usr = Auth::user();
            $defaultPipelineId = null;
            
            if ($usr->default_pipeline) {
                $pipeline = Pipeline::where('created_by', creatorId())
                    ->where('id', $usr->default_pipeline)
                    ->first();
                if ($pipeline) {
                    $defaultPipelineId = $pipeline->id;
                }
            }
            
            if (!$defaultPipelineId) {
                $pipeline = Pipeline::where('created_by', creatorId())->first();
                $defaultPipelineId = $pipeline ? $pipeline->id : null;
            }
            
            $leads = Lead::with(['stage', 'user', 'userLeads.user'])
                ->withCount(['tasks', 'complete_tasks'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-leads')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-leads')) {
                        $q->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereHas('userLeads', function($leadQ) {
                                     $leadQ->where('user_id', Auth::id());
                                 });
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%' . request('name') . '%');
                        $query->orWhere('email', 'like', '%' . request('name') . '%');
                        $query->orWhere('subject', 'like', '%' . request('name') . '%');
                    });
                })
                ->when(request('is_active') !== null && request('is_active') !== '', fn($q) => $q->where('is_active', request('is_active') === '1' ? 1 : 0))
                ->when(request('user_id') && request('user_id') !== '', fn($q) => $q->where('user_id', request('user_id')))
                ->when(request('pipeline_id') && request('pipeline_id') !== '', fn($q) => $q->where('pipeline_id', request('pipeline_id')), function($q) use ($defaultPipelineId) {
                    // If no pipeline_id in request, use default pipeline
                    if ($defaultPipelineId) {
                        $q->where('pipeline_id', $defaultPipelineId);
                    }
                })
                ->when(request('stage_id') && request('stage_id') !== '', fn($q) => $q->where('stage_id', request('stage_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = User::where('created_by', '=', creatorId())
                ->emp([],['vendor'])
                ->select('id', 'name')
                ->get();

            $pipelines = Pipeline::where('created_by', creatorId())->select('id', 'name')->get();
            $stages = LeadStage::where('created_by', creatorId())->select('id', 'name', 'pipeline_id')->get();
            $labels = Label::with('pipeline')->where('created_by', creatorId())->select('id', 'name', 'color', 'pipeline_id')->get();
            $sources = Source::where('created_by', creatorId())->get(['id', 'name']);
            $products = module_is_active('ProductService') ? ProductServiceItem::where('created_by', creatorId())->get(['id', 'name']) : [];
            return Inertia::render('Lead/Leads/Index', [
                'leads' => $leads,
                'users' => $users,
                'pipelines' => $pipelines,
                'stages' => $stages,
                'labels' => $labels,
                'sources' => $sources,
                'products' => $products,
                'currentPipelineId' => request('pipeline_id') ?: $defaultPipelineId,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLeadRequest $request)
    {
        if (Auth::user()->can('create-leads')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $usr = Auth::user();
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
                return redirect()->route('lead.leads.index')->with('error', __('Please create pipeline.'));
            }
            if (empty($stage)) {
                return redirect()->route('lead.leads.index')->with('error', __('Please create stage for this pipeline.'));
            } else {
                $lead                 = new Lead();
                $lead->name           = $request->name;
                $lead->email          = $request->email;
                $lead->subject        = $request->subject;
                $lead->user_id        = $request->user_id;
                $lead->pipeline_id    = $pipeline->id;
                $lead->stage_id       = $stage->id;
                $lead->phone          = $request->phone;
                $lead->date           = $request->date;
                $lead->creator_id     = Auth::id();
                $lead->created_by     = creatorId();
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
            CreateLead::dispatch($request, $lead);

            $resp = ['is_success' => true, 'error' => ''];
            if (!empty(company_setting('Lead Assigned')) && company_setting('Lead Assigned')  == true) {
                $lArr    = [
                    'lead_name' => $lead->name,
                    'lead_email' => $lead->email,
                    'lead_pipeline' => $pipeline->name,
                    'lead_stage' => $stage->name,
                ];
                $usrEmail = User::find($request->user_id);

                // Send Email
                $resp = EmailTemplate::sendEmailTemplate('Lead Assigned', [$usrEmail->id => $usrEmail->email], $lArr);
            }
            return redirect()->route('lead.leads.index')->with('success', __('The lead has been created successfully.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->route('lead.leads.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Lead $lead)
    {
        try {
            if (Auth::user()->can('view-leads') && $lead->created_by == creatorId()) {
                    $lead = Lead::with([
                        'stage',
                        'pipeline',
                        'user',
                        'userLeads' => function ($query) {
                            $query->with('user:id,name,avatar');
                        },
                        'tasks',
                        'emails',
                        'discussions.creator:id,name',
                        'files',
                        'calls',
                        'activities.user:id,name'
                    ])->find($lead->id);
                    $deal = null;
                    if ($lead->is_converted) {
                        $deal = Deal::where('id', '=', $lead->is_converted)->first();
                    }
                    return Inertia::render('Lead/Leads/Show/Index', [
                        'lead' => $lead,
                        'deal' => $deal ? [
                            'id' => $deal->id,
                            'is_active' => $deal->status === 'Active'
                        ] : null
                    ]);
            } else {
                return redirect()->route('lead.leads.index')->with('error', __('Access denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Lead not found'));
        }
    }

    public function edit(Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {

            if ($lead->created_by == creatorId()) {
                $lead = Lead::find($lead->id);

                $pipelines = Pipeline::where('created_by', '=', creatorId())->get()->pluck('name', 'id');
                $pipelines->prepend(__('Select Pipeline'), '');

                $sources = Source::where('created_by', '=', creatorId())->get()->pluck('name', 'id');

                if (module_is_active('ProductService')) {
                    $products = ProductServiceItem::where('created_by', '=', creatorId())->get()->pluck('name', 'id');
                }

                $users = User::where('created_by', '=', creatorId())->emp([],['vendor'])->get()->pluck('name', 'id');
                $users->prepend(__('Select User'), null);

                $lead->sources = explode(',', $lead->sources ?? '');
                $lead->products = explode(',', $lead->products ?? '');

                return response()->json([
                    'lead' => $lead,
                    'pipelines' => $pipelines,
                    'sources' => $sources,
                    'products' => $products ?? [],
                    'users' => $users
                ]);
            }
        }
        return response()->json(['error' => 'Permission denied'], 403);
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        try {
            if (Auth::user()->can('edit-leads')) {
                $validated = $request->validated();
                $validated['is_active'] = $request->boolean('is_active', true);


                $lead->name        = $validated['name'];
                $lead->email       = $validated['email'];
                $lead->subject     = $validated['subject'];
                $lead->user_id     = $validated['user_id'];
                $lead->phone       = $validated['phone'];
                $lead->date        = $validated['date'];
                $lead->pipeline_id = $validated['pipeline_id'] ?? $lead->pipeline_id;
                $lead->stage_id    = $validated['stage_id'] ?? $lead->stage_id;
                $lead->sources     = is_array($validated['sources']) ? (empty($validated['sources']) ? null : implode(',', array_filter($validated['sources']))) : ($validated['sources'] ?? $lead->sources);
                $lead->products    = is_array($validated['products']) ? (empty($validated['products']) ? null : implode(',', array_filter($validated['products']))) : ($validated['products'] ?? $lead->products);
                $lead->notes       = $validated['notes'] ?? $lead->notes;
                $lead->labels      = $request->input('labels', $lead->labels);
                $lead->save();

                UpdateLead::dispatch($request, $lead);

                return back()->with('success', __('The lead details are updated successfully.'));
            } else {
                return back()->with('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Lead not found'));
        }
    }

    public function destroy(Lead $lead)
    {
        try {
            if (Auth::user()->can('delete-leads')) {
                DestroyLead::dispatch($lead);

                LeadActivityLog::where('lead_id', '=', $lead->id)->delete();

                $lead->delete();

                return back()->with('success', __('The lead has been deleted.'));
            } else {
                return back()->with('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Lead not found'));
        }
    }

    public function getStagesByPipeline($pipelineId)
    {
        $stages = LeadStage::where('pipeline_id', $pipelineId)
            ->where('created_by', creatorId())
            ->select('id', 'name')
            ->get();

        return response()->json($stages);
    }

    public function updateLabels(Request $request, $id)
    {
        if (Auth::user()->can('edit-leads')) {
            $leads = Lead::find($id);
            $creatorId = creatorId();

            if ($leads->created_by == $creatorId) {
                if ($request->labels) {
                    $leads->labels = is_array($request->labels) ? implode(',', $request->labels) : $request->labels;
                } else {
                    $leads->labels = $request->labels;
                }
                $leads->save();

                return redirect()->route('lead.leads.index')->with('success', __('The label details are updated successfully.'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function getAvailableUsers(Lead $lead)
    {
        if (Auth::user()->can('view-leads')) {
            $users = User::where('created_by', '=', creatorId())
                ->emp([],['vendor'])
                ->whereNotIn('id', function ($q) use ($lead) {
                    $q->select('user_id')->from('user_leads')->where('lead_id', '=', $lead->id);
                })
                ->select('id', 'name', 'avatar')
                ->get();

            return response()->json($users);
        }else{
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }

    public function assignUsers(AssignUsersRequest $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            foreach ($request->user_ids as $userId) {
                UserLead::firstOrCreate([
                    'user_id' => $userId,
                    'lead_id' => $lead->id,
                ]);
                LeadAddUser::dispatch($request, $lead);
            }

            return back()->with('success', __('The users have been assigned successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeUser(Lead $lead, $userId)
    {
        if (Auth::user()->can('edit-leads')) {
            UserLead::where('lead_id', $lead->id)
                ->where('user_id', $userId)
                ->delete();

            DestroyUserLead::dispatch($lead);

            return back()->with('success', __('The user has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getAvailableProducts(Lead $lead)
    {
        if (Auth::user()->can('view-leads')) {
            $products = ProductServiceItem::where('created_by', '=', creatorId())
                ->select('id', 'name')
                ->get();

            return response()->json($products);
        }else{
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }

    public function assignProducts(Request $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $usr        = Auth::user();
            $existingIds = $lead->products ? explode(',', $lead->products) : [];
            $newIds = array_merge($existingIds, $request->product_ids);
            $uniqueIds = array_unique(array_filter($newIds));
            $lead->products = implode(',', $uniqueIds);
            $lead->save();
            LeadAddProduct::dispatch($request, $lead);
            $productIds = explode(',', $lead->products);
            $objProduct = ProductServiceItem::whereIN('id', $productIds)->get()->pluck('name', 'id')->toArray();

            LeadActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'lead_id' => $lead->id,
                    'log_type' => 'Add Product',
                    'remark' => json_encode(['title' => implode(",", $objProduct)]),
                ]
            );

            return back()->with('success', __('The products have been assigned successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeProduct(Lead $lead, $productId)
    {
        if (Auth::user()->can('edit-leads')) {
            $products = explode(',', $lead->products);
            $products = array_filter($products, fn($id) => $id != $productId);
            $lead->products = implode(',', $products);
            $lead->save();
            DestroyLeadProduct::dispatch($lead);

            return back()->with('success', __('The product has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getAvailableSources(Lead $lead)
    {
        if (Auth::user()->can('view-leads')) {
            $sources = Source::where('created_by', creatorId())
                ->select('id', 'name')
                ->get();

            return response()->json($sources);
        }else{
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }

    public function assignSources(Request $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $usr        = Auth::user();
            $existingIds = $lead->sources ? explode(',', $lead->sources) : [];
            $newIds = array_merge($existingIds, $request->source_ids);
            $uniqueIds = array_unique(array_filter($newIds));
            $lead->sources = implode(',', $uniqueIds);
            $lead->save();
            LeadSourceUpdate::dispatch($request, $lead);
            LeadActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'lead_id' => $lead->id,
                    'log_type' => 'Update Sources',
                    'remark' => json_encode(['title' => 'Update Sources']),
                ]
            );
            return back()->with('success', __('The sources have been assigned successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeSource(Lead $lead, $sourceId)
    {
        if (Auth::user()->can('edit-leads')) {
            $sources = explode(',', $lead->sources);
            $sources = array_filter($sources, fn($id) => $id != $sourceId);
            $lead->sources = implode(',', $sources);
            $lead->save();
            DestroyLeadSource::dispatch($lead);

            return back()->with('success', __('The source has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeEmail(StoreLeadEmailRequest $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $validated = $request->validated();

            $lead_email = LeadEmail::create([
                'lead_id' => $lead->id,
                'to' => $validated['to'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
            ]);
            LeadAddEmail::dispatch($request, $lead, $lead_email);
            LeadActivityLog::create(
                [
                    'user_id' => Auth::user()->id,
                    'lead_id' => $lead->id,
                    'log_type' => 'Create Lead Email',
                    'remark' => json_encode(['title' => 'Create new Lead Email']),
                ]
            );
            if (!empty(company_setting('Lead Emails')) && company_setting('Lead Emails')  == true) {
                $lead_users[] = $request->to;
                $lArr = [
                    'lead_name' => $lead->name,
                    'lead_email_subject' => $request->subject,
                    'lead_email_description' => $request->description,
                ];

                // Send Email
                $resp = EmailTemplate::sendEmailTemplate('Lead Emails', $lead_users, $lArr);
            }
            return back()->with('success', __('The email has been created successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeDiscussion(StoreLeadDiscussionRequest $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $validated = $request->validated();

            LeadDiscussion::create([
                'lead_id' => $lead->id,
                'comment' => $validated['message'],
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);
            LeadAddDiscussion::dispatch($request, $lead);

            return back()->with('success', __('The discussion has been created successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeFile(Request $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $additionalImages = $request->input('additional_images', []);

            foreach ($additionalImages as $filePath) {
                $fileName = basename($filePath);
                LeadFile::create([
                    'lead_id' => $lead->id,
                    'file_name' => $fileName,
                    'file_path' => $fileName,
                ]);
                LeadUploadFile::dispatch($request, $lead);
            }

            LeadActivityLog::create([
                'user_id' => Auth::user()->id,
                'lead_id' => $lead->id,
                'log_type' => 'Upload File',
                'remark' => json_encode(['files_count' => count($additionalImages)]),
            ]);

            return back()->with('success', __('Files have been uploaded successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteFile(Lead $lead, $fileId)
    {
        if (Auth::user()->can('edit-leads')) {
            $file = LeadFile::where('id', $fileId)->where('lead_id', $lead->id)->first();
            if ($file) {
                DestroyLeadFile::dispatch($lead);
                \Storage::disk('public')->delete($file->file_path);
                $file->delete();
            }

            return back()->with('success', __('The file has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function callStore(StoreLeadCallRequest $request)
    {
        if (Auth::user()->can('edit-leads')) {
            $validated = $request->validated();
            $usr  = Auth::user();
            $lead = Lead::find($request->lead_id);
            $call              = new LeadCall();
            $call->lead_id     = $request->lead_id;
            $call->subject     = $request->subject;
            $call->call_type   = $request->call_type;
            $call->duration    = $request->duration;
            $call->user_id     = $request->assignee;
            $call->description = $request->description;
            $call->call_result = $request->call_result;
            $call->save();
            LeadAddCall::dispatch($request, $lead);

            LeadActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'lead_id' => $request->lead_id,
                    'log_type' => 'Create Lead Call',
                    'remark' => json_encode(['title' => 'Create new Lead Call']),
                ]
            );
            return back()->with('success', __('The call has been created successfully.'))->with('status', 'calls');
        } else {
            return back()->with('error', __('Permission denied'))->with('status', 'calls');
        }
    }

    public function callUpdate(UpdateLeadCallRequest $request, $callId)
    {
        if (Auth::user()->can('edit-leads')) {
            $validated = $request->validated();

            $call = LeadCall::find($callId);
            $call->subject     = $request->subject;
            $call->call_type   = $request->call_type;
            $call->duration    = $request->duration;
            $call->user_id     = $request->assignee;
            $call->description = $request->description;
            $call->call_result = $request->calluserNames_result;
            $call->save();
            LeadCallUpdate::dispatch($request, $call);

            return back()->with('success', __('The call details are updated successfully.'))->with('status', 'calls');
        } else {
            return back()->with('error', __('Permission denied'))->with('status', 'calls');
        }
    }

    public function callDestroy($callId)
    {
        if (Auth::user()->can('edit-leads')) {
            $call = LeadCall::find($callId);
            DestroyLeadCall::dispatch($call);
            $call->delete();

            return back()->with('success', __('The call has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function order(Request $request)
    {
        try {
            if (Auth::user()->can('lead-move')) {
                $post       = $request->all();
                $lead       = Lead::find($post['lead_id']);
                $lead_users = $lead->userLeads()->with('user')->get()->pluck('user.email', 'user.id')->toArray();

                if ($lead->stage_id != $post['stage_id']) {

                    $newStage = LeadStage::find($post['stage_id']);

                    LeadActivityLog::create(
                        [
                            'user_id' => Auth::user()->id,
                            'lead_id' => $lead->id,
                            'log_type' => 'Move',
                            'remark' => json_encode(
                                [
                                    'title' => $lead->name,
                                    'old_status' => $lead->stage->name,
                                    'new_status' => $newStage->name,
                                ]
                            ),
                        ]
                    );

                    if (!empty(company_setting('Lead Moved')) && company_setting('Lead Moved')  == 'on') {
                        $lArr = [
                            'lead_name' => $lead->name,
                            'lead_email' => $lead->email,
                            'lead_pipeline' => $lead->pipeline->name ?? '',
                            'lead_stage' => $lead->stage->name,
                            'lead_old_stage' => $lead->stage->name,
                            'lead_new_stage' => $newStage->name,
                        ];
                        // Send Email
                        EmailTemplate::sendEmailTemplate('Lead Moved', $lead_users, $lArr);
                    }
                }

                foreach ($post['order'] as $key => $item) {

                    $leads = Lead::where('id', $item)->update(['order' => $key, 'stage_id' => $post['stage_id']]);
                }
                LeadMoved::dispatch($request, $lead);
                return back()->with('success', __('The lead moved successfully.'));
            } else {
                return back()->with('error', __('Permission denied.'));
            }
        } catch (\Throwable $th) {
            return back()->with('error', __('Something went wrong.'));
        }
    }

    public function getExistingClients()
    {
        if (Auth::user()->can('view-leads')) {
            $clients = User::where('type', 'client')
                ->where('created_by', creatorId())
                ->select('id', 'name', 'email')
                ->get();

            return response()->json($clients);
        }else{
            return response()->json([]);
        }
    }

    public function convertToDeal(ConvertToDealRequest $request, Lead $lead)
    {
        if (Auth::user()->can('edit-leads')) {
            $validated = $request->validated();

            $creatorId = creatorId();

            if ($request->client_check == 'exist') {
                $client = User::where('type', 'client')
                    ->where('email', $request->clients)
                    ->where('created_by', $creatorId)
                    ->first();

                if (!$client) {
                    return back()->with('error', __('The client is not available.'));
                }
            } else {
                $checkUser = canCreateUser();
                if (!$checkUser['can_create']) {
                    return redirect()->route('users.index')->with('error', $checkUser['message']);
                }

                $role = Role::where('name', 'client')->where('created_by', $creatorId)->first();
                $enableEmailVerification = admin_setting('enableEmailVerification');

                $client = User::create([
                    'name' => $request->client_name,
                    'email' => $request->client_email,
                    'password' => \Hash::make($request->client_password),
                    'email_verified_at' => $enableEmailVerification === 'on' ? null : now(),
                    'type' => 'client',
                    'lang' => company_setting('defaultLanguage') ?? 'en',
                    'creator_id' => Auth::id(),
                    'created_by' => $creatorId,
                ]);
                $client->assignRole($role);

                // Dispatch event for packages to handle their fields
                CreateUser::dispatch($request, $client);                

                if ($enableEmailVerification === 'on') {
                    // Apply dynamic mail configuration
                    SetConfigEmail(creatorId());
                    $client->sendEmailVerificationNotification();
                }
            }

            $cArr = [
                'email' => $request->client_email,
                'password' => $request->client_password,
            ];

            // Send Email to client if they are new created.
            EmailTemplate::sendEmailTemplate('New User', [$client->id => $client->email], $cArr);

            $stage = DealStage::where('pipeline_id', $lead->pipeline_id)->first();
            if (!$stage) {
                return back()->with('error', __('Please create stage for this pipeline.'));
            }
            $deal              = new Deal();
            $deal->name        = $request->name;
            $deal->price       = $request->price ?? 0;
            $deal->pipeline_id = $lead->pipeline_id;
            $deal->stage_id    = $stage->id;
            $deal->sources     = in_array('sources', $request->is_transfer ?? []) ? $lead->sources : null;
            $deal->products    = in_array('products', $request->is_transfer ?? []) ? $lead->products : null;
            $deal->notes       = in_array('notes', $request->is_transfer ?? []) ? $lead->notes : null;
            $deal->labels      = $lead->labels;
            $deal->status      = 'Active';
            $deal->creator_id  = Auth::id();
            $deal->created_by  = $lead->created_by;
            $deal->save();

            ClientDeal::create([
                'deal_id' => $deal->id,
                'client_id' => $client->id,
            ]);

            $lead->load(['tasks', 'userLeads', 'discussions', 'files', 'calls', 'emails']);

            if ($lead->tasks) {
                foreach ($lead->tasks as $task) {
                    DealTask::create([
                        'deal_id' => $deal->id,
                        'name' => $task->name,
                        'date' => $task->date,
                        'time' => $task->time,
                        'priority' => $task->priority,
                        'status' => $task->status,
                    ]);
                }
            }

            if (!empty(company_setting('Deal Assigned')) && company_setting('Deal Assigned')  == true) {

                // Send Mail
                $pipeline = Pipeline::find($lead->pipeline_id);
                $dArr     = [
                    'deal_name' => $deal->name,
                    'deal_pipeline' => $pipeline->name,
                    'deal_stage' => $stage->name,
                    'deal_status' => $deal->status,
                    'deal_price' => $deal->price,
                ];
                EmailTemplate::sendEmailTemplate('Deal Assigned', [$client->id => $client->email], $dArr);
            }

            // Transfer users
            if ($lead->userLeads) {
                foreach ($lead->userLeads as $userLead) {
                    UserDeal::create([
                        'user_id' => $userLead->user_id,
                        'deal_id' => $deal->id,
                    ]);
                }
            }

            // Transfer discussions
            if (in_array('discussion', $request->is_transfer ?? []) && $lead->discussions) {
                foreach ($lead->discussions as $discussion) {
                    DealDiscussion::create([
                        'deal_id' => $deal->id,
                        'comment' => $discussion->comment,
                        'creator_id' => $discussion->creator_id,
                        'created_by' => $discussion->created_by,
                    ]);
                }
            }

            // Transfer files
            if (in_array('files', $request->is_transfer ?? []) && $lead->files) {
                foreach ($lead->files as $file) {
                    DealFile::create([
                        'deal_id' => $deal->id,
                        'file_name' => $file->file_name,
                        'file_path' => $file->file_path,
                    ]);
                }
            }

            // Transfer calls
            if (in_array('calls', $request->is_transfer ?? []) && $lead->calls) {
                foreach ($lead->calls as $call) {
                    DealCall::create([
                        'deal_id' => $deal->id,
                        'subject' => $call->subject,
                        'call_type' => $call->call_type,
                        'duration' => $call->duration,
                        'user_id' => $call->user_id,
                        'description' => $call->description,
                        'call_result' => $call->call_result,
                    ]);
                }
            }

            // Transfer emails
            if (in_array('emails', $request->is_transfer ?? []) && $lead->emails) {
                foreach ($lead->emails as $email) {
                    DealEmail::create([
                        'deal_id' => $deal->id,
                        'to' => $email->to,
                        'subject' => $email->subject,
                        'description' => $email->description,
                    ]);
                }
            }

            $lead->is_converted = $deal->id;
            $lead->save();

            LeadConvertDeal::dispatch($request, $lead);

            return back()->with('success', __('The lead has been converted into a deal successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
