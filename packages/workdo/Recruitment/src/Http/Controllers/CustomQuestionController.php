<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Http\Requests\StoreCustomQuestionRequest;
use Workdo\Recruitment\Http\Requests\UpdateCustomQuestionRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Events\CreateCustomQuestion;
use Workdo\Recruitment\Events\UpdateCustomQuestion;
use Workdo\Recruitment\Events\DestroyCustomQuestion;


class CustomQuestionController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-custom-questions')){
            $customquestions = CustomQuestion::query()

                ->where(function($q) {
                    if(Auth::user()->can('manage-any-custom-questions')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-custom-questions')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('question'), function($q) {
                    $q->where(function($query) {
                    $query->where('question', 'like', '%' . request('question') . '%');
                    });
                })
                ->when(request('type') !== null && request('type') !== '', fn($q) => $q->where('type', request('type')))
                ->when(request('is_active') !== null && request('is_active') !== '', fn($q) => $q->where('is_active', request('is_active')))
                ->when(request('is_required') !== null && request('is_required') !== '', fn($q) => $q->where('is_required', request('is_required')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/CustomQuestions/Index', [
                'customquestions' => $customquestions,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomQuestionRequest $request)
    {
        if(Auth::user()->can('create-custom-questions')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $customquestion = new CustomQuestion();
            $customquestion->question = $validated['question'];
            $customquestion->type = $validated['type'];
            $customquestion->options = $validated['options'];
            $customquestion->is_required = $validated['is_required'];
            $customquestion->is_active = $validated['is_active'];
            $customquestion->sort_order = $validated['sort_order'];
            $customquestion->is_active = $validated['is_active'];
            $customquestion->creator_id = Auth::id();
            $customquestion->created_by = creatorId();
            $customquestion->save();

            CreateCustomQuestion::dispatch($request, $customquestion);

            return redirect()->route('recruitment.custom-questions.index')->with('success', __('The custom question has been created successfully.'));
        }
        else{
            return redirect()->route('recruitment.custom-questions.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCustomQuestionRequest $request, CustomQuestion $customquestion)
    {
        if(Auth::user()->can('edit-custom-questions')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $customquestion->question = $validated['question'];
            $customquestion->type = $validated['type'];
            $customquestion->options = $validated['options'];
            $customquestion->is_required = $validated['is_required'];
            $customquestion->is_active = $validated['is_active'];
            $customquestion->sort_order = $validated['sort_order'];
            $customquestion->is_active = $validated['is_active'];
            $customquestion->save();

            UpdateCustomQuestion::dispatch($request, $customquestion);

            return redirect()->back()->with('success', __('The custom question details are updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.custom-questions.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(CustomQuestion $customquestion)
    {
        if(Auth::user()->can('delete-custom-questions')){
            DestroyCustomQuestion::dispatch($customquestion);
            $customquestion->delete();

            return redirect()->back()->with('success', __('The custom question has been deleted.'));
        }
        else{
            return redirect()->route('recruitment.custom-questions.index')->with('error', __('Permission denied'));
        }
    }
}