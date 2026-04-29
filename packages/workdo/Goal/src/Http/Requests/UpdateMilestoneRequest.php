<?php

namespace Workdo\Goal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalMilestone;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal_id' => 'required|exists:goals,id',
            'milestone_name' => 'required|max:255',
            'milestone_description' => 'nullable',
            'target_amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $goalId = $this->input('goal_id');
                    $milestone = $this->route('milestone');
                    if ($goalId && $milestone) {
                        $goal = Goal::find($goalId);
                        if ($goal) {
                            $existingTotal = GoalMilestone::where('goal_id', $goalId)
                                                        ->where('id', '!=', $milestone->id)
                                                        ->sum('target_amount');
                            if (($existingTotal + $value) > $goal->target_amount) {
                                $fail('Total milestone amounts cannot exceed goal target amount of ' . $goal->target_amount);
                            }
                        }
                    }
                }
            ],
            'target_date' => 'required|date',
            'status' => 'required|in:pending,achieved,overdue',
            'achieved_amount' => 'nullable|numeric|min:0',
        ];
    }
}
