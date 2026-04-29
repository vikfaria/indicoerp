<?php

namespace Workdo\Lead\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Lead\Models\DealTask;
use Inertia\Inertia;
use Workdo\Lead\Http\Requests\StoreDealTaskRequest;
use Workdo\Lead\Http\Requests\UpdateDealTaskRequest;
use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\DealActivityLog;
use Workdo\Lead\Events\CreateDealTask;
use Workdo\Lead\Events\UpdateDealTask;
use Workdo\Lead\Events\DestroyDealTask;

class DealTaskController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-deal-tasks')){
            $tasks = DealTask::with(['deal'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-deal-tasks')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-deal-tasks')) {
                        $q->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereIn('deal_id', function($dealQ) {
                                     $dealQ->select('deal_id')
                                           ->from('user_deals')
                                           ->where('user_id', Auth::id());
                                 });
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('priority'), fn($q) => $q->where('priority', request('priority')))
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Lead/DealTasks/Index', [
                'tasks' => $tasks,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreDealTaskRequest $request)
    {
        if(Auth::user()->can('create-deal-tasks')){
            $usr = Auth::user();
            $validated = $request->validated();                  
            $deal       = Deal::find($validated['deal_id']);
            $clients    = ClientDeal::select('client_id')->where('deal_id', '=', $validated['deal_id'])->get()->pluck('client_id')->toArray();
            $deal_users = $deal->users->pluck('id')->toArray();
            $usrs       = User::whereIN('id', array_merge($deal_users, $clients))->get()->pluck('email', 'id')->toArray();
            if ($deal->created_by == creatorId()) {

                $dealTask             = new DealTask();
                $dealTask->deal_id    = $validated['deal_id'];
                $dealTask->name       = $validated['name'];
                $dealTask->date       = $validated['date'];
                $dealTask->time       = $validated['time'];
                $dealTask->priority   = $validated['priority'];
                $dealTask->status     = $validated['status'];
                $dealTask->created_by = creatorId();
                $dealTask->creator_id = Auth::id();
                $dealTask->save();

                CreateDealTask::dispatch($request, $deal, $dealTask);

                DealActivityLog::create([
                    'user_id' => $usr->id,
                    'deal_id' => $validated['deal_id'],
                    'log_type' => 'Create Task',
                    'remark' => json_encode(['title' => $dealTask->name]),
                ]);
                if (!empty(company_setting('New Task')) && company_setting('New Task')  == 'on') {
                    $tArr = [
                        'deal_name' => $deal->name,
                        'deal_pipeline' => $deal->pipeline->name,
                        'deal_stage' => $deal->stage->name,
                        'deal_status' => $deal->status,
                        'deal_price' => $deal->price,
                        'task_name' => $dealTask->name,
                        'task_priority' => is_numeric($dealTask->priority) ? (DealTask::$priorities[$dealTask->priority] ?? $dealTask->priority) : $dealTask->priority,
                        'task_status' => is_numeric($dealTask->status) ? (DealTask::$status[$dealTask->status] ?? $dealTask->status) : $dealTask->status,
                    ];

                    // Send Email
                    $resp = EmailTemplate::sendEmailTemplate('New Task', $usrs, $tArr);
                }
                return back()->with('success', __('The task has been created successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));                
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDealTaskRequest $request, DealTask $task)
    {
        if(Auth::user()->can('edit-deal-tasks')){
            
            $validated = $request->validated();

            $task->name     = $validated['name'];
            $task->date     = $validated['date'];
            $task->time     = $validated['time'];
            $task->priority = $validated['priority'];
            $task->status   = $validated['status'];
            $task->save();

            UpdateDealTask::dispatch($request, $task);

            return back()->with('success', __('The task details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(DealTask $task)
    {
        if(Auth::user()->can('delete-deal-tasks')){
           
            DestroyDealTask::dispatch($task);
            $task->delete();
            return back()->with('success', __('The task has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}