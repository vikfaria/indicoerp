<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\ChecklistItem;
use Workdo\Recruitment\Http\Requests\StoreChecklistItemRequest;
use Workdo\Recruitment\Http\Requests\UpdateChecklistItemRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\OnboardingChecklist;
use Workdo\Recruitment\Events\CreateChecklistItem;
use Workdo\Recruitment\Events\UpdateChecklistItem;
use Workdo\Recruitment\Events\DestroyChecklistItem;

class ChecklistItemController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-checklist-items')){
            $checklistitems = ChecklistItem::query()
                ->with(['checklist'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-checklist-items')) {
                        $q->where('checklist_items.created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-checklist-items')) {
                        $q->where('checklist_items.creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('task_name'), function($q) {
                    $q->where(function($query) {
                        $query->where('checklist_items.task_name', 'like', '%' . request('task_name') . '%');
                        $query->orWhere('checklist_items.description', 'like', '%' . request('task_name') . '%');
                        $query->orWhere('checklist_items.assigned_to_role', 'like', '%' . request('task_name') . '%');
                        $query->orWhereHas('checklist', function($q) {
                            $q->where('name', 'like', '%' . request('task_name') . '%');
                        });
                    });
                })
                ->when(request('checklist_id') && request('checklist_id') !== 'all', fn($q) => $q->where('checklist_id', request('checklist_id')))
                ->when(request('category') !== null && request('category') !== '', fn($q) => $q->where('category', request('category')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status') === '1' ? 1 : 0))
                ->when(request('sort'), function($q) {
                    if (request('sort') === 'checklist.name') {
                        return $q->join('onboarding_checklists', 'checklist_items.checklist_id', '=', 'onboarding_checklists.id')
                                 ->orderBy('onboarding_checklists.name', request('direction', 'asc'))
                                 ->select('checklist_items.*');
                    }
                    return $q->orderBy(request('sort'), request('direction', 'asc'));
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/ChecklistItems/Index', [
                'checklistitems' => $checklistitems,
                'onboardingchecklists' => OnboardingChecklist::where('created_by', creatorId())->where('status', 1)->select('id', 'name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreChecklistItemRequest $request)
    {
        if(Auth::user()->can('create-checklist-items')){
            $validated = $request->validated();

            $validated['is_required'] = $request->boolean('is_required', false);
            $validated['status'] = $request->boolean('status', true);

            $checklistitem = new ChecklistItem();
            $checklistitem->checklist_id = $validated['checklist_id'];
            $checklistitem->task_name = $validated['task_name'];
            $checklistitem->description = $validated['description'];
            $checklistitem->category = $validated['category'];
            $checklistitem->assigned_to_role = $validated['assigned_to_role'];
            $checklistitem->due_day = $validated['due_day'];
            $checklistitem->is_required = $validated['is_required'];
            $checklistitem->status = $validated['status'];

            $checklistitem->creator_id = Auth::id();
            $checklistitem->created_by = creatorId();
            $checklistitem->save();

            CreateChecklistItem::dispatch($request, $checklistitem);

            return redirect()->route('recruitment.checklist-items.index')->with('success', __('The checklist item has been created successfully.'));
        }
        else{
            return redirect()->route('recruitment.checklist-items.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistitem)
    {
        if(Auth::user()->can('edit-checklist-items')){
            $validated = $request->validated();

            $validated['is_required'] = $request->boolean('is_required', false);
            $validated['status'] = $request->boolean('status', true);

            $checklistitem->checklist_id = $validated['checklist_id'];
            $checklistitem->task_name = $validated['task_name'];
            $checklistitem->description = $validated['description'];
            $checklistitem->category = $validated['category'];
            $checklistitem->assigned_to_role = $validated['assigned_to_role'];
            $checklistitem->due_day = $validated['due_day'];
            $checklistitem->is_required = $validated['is_required'];
            $checklistitem->status = $validated['status'];

            $checklistitem->save();

            UpdateChecklistItem::dispatch($request, $checklistitem);

            return redirect()->back()->with('success', __('The checklist item details are updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.checklist-items.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(ChecklistItem $checklistitem)
    {
        if(Auth::user()->can('delete-checklist-items')){
            DestroyChecklistItem::dispatch($checklistitem);
            $checklistitem->delete();

            return redirect()->back()->with('success', __('The checklist item has been deleted.'));
        }
        else{
            return redirect()->route('recruitment.checklist-items.index')->with('error', __('Permission denied'));
        }
    }




}
