<?php

namespace Workdo\Lead\Http\Controllers;

use App\Models\EmailTemplate;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Http\Requests\StoreDealRequest;
use Workdo\Lead\Http\Requests\UpdateDealRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Lead\Models\Pipeline;
use App\Models\User;
use Illuminate\Http\Request;
use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\Label;
use Workdo\Lead\Models\UserDeal;
use Workdo\Lead\Models\DealEmail;
use Workdo\Lead\Models\DealDiscussion;
use Workdo\Lead\Models\DealCall;
use Workdo\Lead\Models\DealFile;
use Workdo\Lead\Http\Requests\StoreDealCallRequest;
use Workdo\Lead\Http\Requests\UpdateDealCallRequest;
use Workdo\Lead\Http\Requests\StoreDealEmailRequest;
use Workdo\Lead\Http\Requests\StoreDealDiscussionRequest;
use Workdo\Lead\Models\ClientPermission;
use Workdo\Lead\Models\DealActivityLog;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\Lead;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Lead\Events\CreateDeal;
use Workdo\Lead\Events\UpdateDeal;
use Workdo\Lead\Events\DestroyDeal;
use Workdo\Lead\Events\DealMoved;
use Workdo\Lead\Events\DealAddUser;
use Workdo\Lead\Events\DestroyUserDeal;
use Workdo\Lead\Events\DealAddClient;
use Workdo\Lead\Events\DestroyDealClient;
use Workdo\Lead\Events\DealAddProduct;
use Workdo\Lead\Events\DestroyDealProduct;
use Workdo\Lead\Events\DealUploadFile;
use Workdo\Lead\Events\DestroyDealFile;
use Workdo\Lead\Events\DealSourceUpdate;
use Workdo\Lead\Events\DestroyDealSource;
use Workdo\Lead\Events\DealAddDiscussion;
use Workdo\Lead\Events\DealAddCall;
use Workdo\Lead\Events\DealCallUpdate;
use Workdo\Lead\Events\DestroyDealCall;
use Workdo\Lead\Events\DealAddEmail;
use Workdo\Lead\Models\Source;

class DealController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-deals')) {
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
            
            $deals = Deal::select('id', 'name', 'price', 'pipeline_id', 'stage_id', 'phone', 'status', 'sources', 'products', 'notes', 'labels', 'created_at')
                ->with(['pipeline:id,name', 'stage:id,name', 'creator:id,name', 'users:id,name,avatar'])
                ->withCount(['tasks', 'complete_tasks'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-deals')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-deals')) {
                        $q->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereHas('userDeals', function($dealQ) {
                                     $dealQ->where('user_id', Auth::id());
                                 });
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('pipeline_id') && request('pipeline_id') !== '', fn($q) => $q->where('pipeline_id', request('pipeline_id')), function($q) use ($defaultPipelineId) {
                    // If no pipeline_id in request, use default pipeline
                    if ($defaultPipelineId) {
                        $q->where('pipeline_id', $defaultPipelineId);
                    }
                })
                ->when(request('stage_id'), fn($q) => $q->where('stage_id', request('stage_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $pipelines = Pipeline::where('created_by', creatorId())->get(['id', 'name']);
            $stages = DealStage::where('created_by', creatorId())->get(['id', 'name', 'pipeline_id']);
            $users = User::where('created_by', creatorId())->where('type', 'client')->get(['id', 'name']);
            $sources = Source::where('created_by', creatorId())->get(['id', 'name']);
            $products = Module_is_active('ProductService') ? ProductServiceItem::where('created_by', creatorId())->get(['id', 'name']) : [];
            $labels = Label::with('pipeline')->where('created_by', creatorId())->select('id', 'name', 'color', 'pipeline_id')->get();

            return Inertia::render('Lead/Deals/Index', [
                'deals' => $deals,
                'pipelines' => $pipelines,
                'stages' => $stages,
                'users' => $users,
                'sources' => $sources,
                'products' => $products,
                'labels' => $labels,
                'currentPipelineId' => request('pipeline_id') ?: $defaultPipelineId,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }



    public function store(StoreDealRequest $request)
    {
        if (Auth::user()->can('create-deals')) {
            $validated = $request->validated();
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
            $stage = DealStage::where('pipeline_id', '=', $pipeline->id)->first();
            if (empty($stage)) {
                return redirect()->route('lead.deals.index')->with('error', __('Please create stage for this pipeline.'));
            } else {

                $deal              = new Deal();
                $deal->name        = $validated['name'];
                $deal->price       = $validated['price'] ?? 0;
                $deal->pipeline_id = $pipeline->id;
                $deal->stage_id    = $stage->id;
                $deal->phone       = $validated['phone'];
                $deal->status      = 'Active';
                $deal->creator_id  = Auth::id();
                $deal->created_by  = creatorId();
                $deal->save();

                $clients = User::whereIN('id', array_filter($validated['clients']))->get()->pluck('email', 'id')->toArray();
                foreach (array_keys($clients) as $client) {
                    ClientDeal::create(
                        [
                            'deal_id' => $deal->id,
                            'client_id' => $client,
                        ]
                    );
                }

                // Create user deals
                if (Auth::user()->type == 'company') {
                    $usrDeals = [
                        creatorId()
                    ];
                } else {
                    $usrDeals = [
                        creatorId(),
                        Auth::user()->id,
                    ];
                }

                foreach ($usrDeals as $usrDeal) {
                    UserDeal::create(
                        [
                            'user_id' => $usrDeal,
                            'deal_id' => $deal->id,
                        ]
                    );
                }
                // Dispatch event for packages to handle their fields
                CreateDeal::dispatch($request, $deal);

                if (!empty(company_setting('Deal Assigned')) && company_setting('Deal Assigned')  == 'on') {
                    $dArr = [
                        'deal_name' => !empty($deal->name) ? $deal->name : '',
                        'deal_pipeline' => $pipeline->name,
                        'deal_stage' => $stage->name,
                        'deal_status' => $deal->status,
                        'deal_price' =>  $deal->price,
                    ];
                    // Send Mail
                    $resp = EmailTemplate::sendEmailTemplate('Deal Assigned', $clients, $dArr);
                }
                $resp = null;
                $resp['is_success'] = true;
                return redirect()->route('lead.deals.index')->with('success', __('The deal has been created successfully.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
        } else {
            return redirect()->route('lead.deals.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Deal $deal)
    {
        try {
            if (Auth::user()->can('view-deals')) {
                if ($deal->created_by == creatorId()) {
                    $deal = Deal::with([
                        'pipeline',
                        'stage',
                        'creator',
                        'tasks',
                        'userDeals' => function ($query) {
                            $query->with('user:id,name,avatar');
                        },
                        'emails',
                        'discussions.creator:id,name',
                        'calls',
                        'files',
                        'activities.user:id,name',
                        'clientDeals.client:id,name,avatar'
                    ])->find($deal->id);

                    $assignedUserIds = $deal->userDeals->pluck('user_id')->toArray();
                    $availableUsers = User::where('created_by', creatorId())
                        ->whereNotIn('id', $assignedUserIds)
                        ->get(['id', 'name']);
                    $availableProducts = module_is_active('ProductService') ? ProductServiceItem::where('created_by', creatorId())->get(['id', 'name']) : [];
                    $availableSources = \Workdo\Lead\Models\Source::where('created_by', creatorId())->get(['id', 'name']);
                    $assignedClientIds = $deal->clientDeals->pluck('client_id')->toArray();
                    $availableClients = User::where('created_by', creatorId())
                        ->where('type', 'client')
                        ->whereNotIn('id', $assignedClientIds)
                        ->get(['id', 'name']);
                    return Inertia::render('Lead/Deals/Show', [
                        'deal' => $deal,
                        'availableUsers' => $availableUsers,
                        'availableProducts' => $availableProducts,
                        'availableSources' => $availableSources,
                        'availableClients' => $availableClients,
                    ]);
                }else{
                    return back()->with('error', __('Permission denied'));
                }
            } else {
                return back()->with('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Deal not found'));
        }
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $validated = $request->validated();
            $deal->name        = $validated['name'];
            $deal->price       = $validated['price'];
            $deal->pipeline_id = $validated['pipeline_id'];
            $deal->stage_id    = $validated['stage_id'];
            $deal->phone       = $validated['phone'];
            $deal->sources     = isset($validated['sources']) && !empty($validated['sources']) ? array_filter($validated['sources']) : null;
            $deal->products    = isset($validated['products']) && !empty($validated['products']) ? array_filter($validated['products']) : null;
            $deal->notes       = $validated['notes'] ?? '';
            $deal->save();

            // Dispatch event for packages to handle their fields
            UpdateDeal::dispatch($request, $deal);

            return back()->with('success', __('The deal details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Deal $deal)
    {
        try {
            if (Auth::user()->can('delete-deals')) {
                if ($deal->created_by == creatorId()) {

                    DestroyDeal::dispatch($deal);

                    DealDiscussion::where('deal_id', '=', $deal->id)->delete();
                    $dealfiles = DealFile::where('deal_id', '=', $deal->id)->get();
                    foreach ($dealfiles as $dealfile) {

                        delete_file($dealfile->file_path);
                        $dealfile->delete();
                    }
                    ClientDeal::where('deal_id', '=', $deal->id)->delete();
                    UserDeal::where('deal_id', '=', $deal->id)->delete();
                    DealTask::where('deal_id', '=', $deal->id)->delete();
                    DealActivityLog::where('deal_id', '=', $deal->id)->delete();
                    ClientPermission::where('deal_id', '=', $deal->id)->delete();
                    $lead = Lead::where(['is_converted' => $deal->id])->update(['is_converted' => 0]);

                    $deal->delete();
                    return back()->with('success', __('The deal has been deleted.'));
                }
            } else {
                return back()->with('error', __('Permission denied'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Deal not found'));
        }
    }

    public function updateLabels(Request $request, $id)
    {
        if (Auth::user()->can('edit-deals')) {
            $deal = Deal::find($id);
            if ($deal->created_by == creatorId()) {
                if ($request->labels) {
                    $deal->labels = is_array($request->labels) ? implode(',', $request->labels) : $request->labels;
                } else {
                    $deal->labels = $request->labels;
                }
                $deal->save();
                return redirect()->route('lead.deals.index')->with('success', __('The label details are updated successfully.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function assignUsers(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $userIds = $request->input('user_ids', []);
            $users = User::whereIN('id', array_filter($userIds))->get()->pluck('email', 'id')->toArray();

            foreach (array_keys($users) as $userId) {
                UserDeal::create([
                    'deal_id' => $deal->id,
                    'user_id' => $userId
                ]);
                DealAddUser::dispatch($request, $deal);
            }
            if (!empty(company_setting('Deal Assigned')) && company_setting('Deal Assigned')  == 'on') {
                $dArr = [
                    'deal_name' => $deal->name,
                    'deal_pipeline' => $deal->pipeline->name,
                    'deal_stage' => $deal->stage->name,
                    'deal_status' => $deal->status,
                    'deal_price' => $deal->price,
                ];
                // Send Email
                $resp = EmailTemplate::sendEmailTemplate('Deal Assigned', $users, $dArr);
            }
            if (!empty($users) && !empty($userIds)) {
                return back()->with('success', __('Users have been updated successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            } else {
                return back()->with('error', __('Please select valid user.'));
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeUser(Deal $deal, User $user)
    {
        if (Auth::user()->can('edit-deals')) {
            DestroyUserDeal::dispatch($deal);
            UserDeal::where('deal_id', $deal->id)->where('user_id', $user->id)->delete();
            return back()->with('success', __('The user has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function assignProducts(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $usr = Auth::user();

            $existingIds = is_array($deal->products) ? $deal->products : [];
            $newIds = array_merge($existingIds, $request->product_ids);
            $uniqueIds = array_unique(array_filter($newIds));
            $deal->products = $uniqueIds;
            $deal->save();
            DealAddProduct::dispatch($request, $deal);
            $objProduct = [];
            if(Module_is_active('ProductService')){
                $objProduct = ProductServiceItem::whereIN('id', $uniqueIds)->get()->pluck('name', 'id')->toArray();
            }

            DealActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'deal_id' => $deal->id,
                    'log_type' => 'Add Product',
                    'remark' => json_encode(['title' => implode(",", $objProduct)]),
                ]
            );
            return back()->with('success', __('The products have been assigned successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeProduct(Deal $deal, $productId)
    {
        if (Auth::user()->can('edit-deals')) {
            $products = $deal->products ?: [];
            $products = array_filter($products, fn($id) => $id != $productId);
            $deal->products = array_values($products);
            $deal->save();
            DestroyDealProduct::dispatch($deal);
            return back()->with('success', __('The product has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function assignSources(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $usr = Auth::user();

            $existingIds = is_array($deal->sources) ? $deal->sources : [];
            $newIds = array_merge($existingIds, $request->source_ids);
            $uniqueIds = array_unique(array_filter($newIds));
            $deal->sources = $uniqueIds;
            $deal->save();
            DealSourceUpdate::dispatch($request, $deal);
            DealActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'deal_id' => $deal->id,
                    'log_type' => 'Update Sources',
                    'remark' => json_encode(['title' => 'Update Sources']),
                ]
            );
            return back()->with('success', __('The sources have been assigned successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeSource(Deal $deal, $sourceId)
    {
        if (Auth::user()->can('edit-deals')) {
            $sources = $deal->sources ?: [];
            $sources = array_filter($sources, fn($id) => $id != $sourceId);
            $deal->sources = array_values($sources);
            $deal->save();
            DestroyDealSource::dispatch($deal);
            return back()->with('success', __('The source has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeEmail(StoreDealEmailRequest $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $usr = Auth::user();
            $validated = $request->validated();

            $deal_email = DealEmail::create([
                'deal_id' => $deal->id,
                'to' => $validated['to'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
            ]);
            DealAddEmail::dispatch($request, $deal, $deal_email);
            if (!empty(company_setting('Deal Emails')) && company_setting('Deal Emails')  == true) {
                $lead_users[] = $validated['to'];
                $lArr = [
                    'deal_name' => $deal->name,
                    'deal_email_subject' => $validated['subject'],
                    'deal_email_description' => $validated['description'],
                ];

                // Send Email
                $resp = EmailTemplate::sendEmailTemplate('Deal Emails', $lead_users, $lArr);
            }
            DealActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'deal_id' => $deal->id,
                    'log_type' => 'Create Deal Email',
                    'remark' => json_encode(['title' => 'Create new Deal Email']),
                ]
            );

            return back()->with('success', __('The email has been created successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeDiscussion(StoreDealDiscussionRequest $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $validated = $request->validated();

            DealDiscussion::create([
                'deal_id' => $deal->id,
                'comment' => $validated['message'],
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);
            DealAddDiscussion::dispatch($request, $deal);

            return back()->with('success', __('The discussion has been created successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function assignClients(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $clientIds = $request->input('client_ids', []);
            $clients = User::whereIN('id', array_filter($clientIds))->get()->pluck('email', 'id')->toArray();

            foreach (array_keys($clients) as $clientId) {
                ClientDeal::firstOrCreate([
                    'deal_id' => $deal->id,
                    'client_id' => $clientId
                ]);
                DealAddClient::dispatch($request, $deal);
            }

            if (!empty($clients) && !empty($clientIds)) {
                return back()->with('success', __('The clients have been assigned successfully.'));
            } else {
                return back()->with('error', __('Please select valid client.'));
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function removeClient(Deal $deal, User $client)
    {
        if (Auth::user()->can('edit-deals')) {
            ClientDeal::where('deal_id', $deal->id)->where('client_id', $client->id)->delete();
            DestroyDealClient::dispatch($deal);
            return back()->with('success', __('The client has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function callStore(StoreDealCallRequest $request)
    {
        if (Auth::user()->can('edit-deals')) {
            $usr = Auth::user();
            $deal = Deal::find($request->deal_id);

            $call              = new DealCall();
            $call->deal_id     = $request->deal_id;
            $call->subject     = $request->subject;
            $call->call_type   = $request->call_type;
            $call->duration    = $request->duration;
            $call->user_id     = $request->assignee;
            $call->description = $request->description;
            $call->call_result = $request->call_result;
            $call->save();
            DealAddCall::dispatch($request, $deal);

            DealActivityLog::create([
                'user_id' => $usr->id,
                'deal_id' => $request->deal_id,
                'log_type' => 'Create Deal Call',
                'remark' => json_encode(['title' => 'Create new Deal Call']),
            ]);

            return back()->with('success', __('The call has been created successfully.'))->with('status', 'calls');
        } else {
            return back()->with('error', __('Permission denied'))->with('status', 'calls');
        }
    }

    public function callUpdate(UpdateDealCallRequest $request, $callId)
    {
        if (Auth::user()->can('edit-deals')) {
            $call = DealCall::find($callId);
            $call->subject     = $request->subject;
            $call->call_type   = $request->call_type;
            $call->duration    = $request->duration;
            $call->user_id     = $request->assignee;
            $call->description = $request->description;
            $call->call_result = $request->call_result;
            $call->save();
            DealCallUpdate::dispatch($request, $call);

            return back()->with('success', __('The call details are updated successfully.'))->with('status', 'calls');
        } else {
            return back()->with('error', __('Permission denied'))->with('status', 'calls');
        }
    }

    public function callDestroy($callId)
    {
        if (Auth::user()->can('edit-deals')) {
            $call = DealCall::find($callId);
            DestroyDealCall::dispatch($call);
            $call->delete();

            return back()->with('success', __('The call has been deleted.'))->with('status', 'calls');
        } else {
            return back()->with('error', __('Permission denied'))->with('status', 'calls');
        }
    }

    public function storeFile(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $additionalImages = $request->input('additional_images', []);

            foreach ($additionalImages as $filePath) {
                $fileName = basename($filePath);
                DealFile::create([
                    'deal_id' => $deal->id,
                    'file_name' => $fileName,
                    'file_path' => $fileName,
                ]);
                DealUploadFile::dispatch($request, $deal);
            }

            DealActivityLog::create([
                'user_id' => Auth::user()->id,
                'deal_id' => $deal->id,
                'log_type' => 'Upload File',
                'remark' => json_encode(['files_count' => count($additionalImages)]),
            ]);

            return back()->with('success', __('Files have been uploaded successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteFile(Deal $deal, $fileId)
    {
        if (Auth::user()->can('edit-deals')) {
            $file = DealFile::where('id', $fileId)->where('deal_id', $deal->id)->first();
            if ($file) {
                DestroyDealFile::dispatch($deal);
                \Storage::disk('public')->delete($file->file_path);
                $file->delete();
            }

            return back()->with('success', __('The file has been deleted.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function order(Request $request)
    {
        try {
            if (Auth::user()->can('deal-move')) {
                $usr = Auth::user();
                $post = $request->all();
                $deal = Deal::find($post['deal_id']);
                $clients    = ClientDeal::select('client_id')->where('deal_id', '=', $deal->id)->get()->pluck('client_id')->toArray();
                $deal_users = $deal->users->pluck('id')->toArray();
                $usrs       = User::whereIN('id', array_merge($deal_users, $clients))->get()->pluck('email', 'id')->toArray();

                if ($deal->stage_id != $post['stage_id']) {
                    $newStage = DealStage::find($post['stage_id']);

                    DealActivityLog::create([
                        'user_id' => Auth::user()->id,
                        'deal_id' => $deal->id,
                        'log_type' => 'Move',
                        'remark' => json_encode([
                            'title' => $deal->name,
                            'old_status' => $deal->stage->name,
                            'new_status' => $newStage->name,
                        ]),
                    ]);
                }
                if (!empty(company_setting('Deal Moved')) && company_setting('Deal Moved')  == 'on') {
                    $dArr = [
                        'deal_name' => $deal->name,
                        'deal_pipeline' => $deal->pipeline->name,
                        'deal_stage' => $deal->stage->name,
                        'deal_status' => $deal->status,
                        'deal_price' => $deal->price,
                        'deal_old_stage' => $deal->stage->name,
                        'deal_new_stage' => $newStage->name,
                    ];

                    // Send Email
                    $resp =  EmailTemplate::sendEmailTemplate('Deal Moved', $usrs, $dArr);
                }
                foreach ($post['order'] as $key => $item) {
                    $deal           = Deal::find($item);
                    $deal->order    = $key;
                    $deal->stage_id = $post['stage_id'];
                    $deal->save();
                }
                DealMoved::dispatch($request, $deal);
                return back()->with('success', __('The deal moved successfully.'));
            } else {
                return back()->with('error', __('Permission denied.'));
            }
        } catch (\Throwable $th) {
            return back()->with('error', __('Something went wrong.'));
        }
    }

    public function changeStatus(Request $request, Deal $deal)
    {
        if (Auth::user()->can('edit-deals')) {
            $deal->status = $request->deal_status;
            $deal->save();
            return back()->with('success', __('The deal status updated successfully.'));
        }else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
