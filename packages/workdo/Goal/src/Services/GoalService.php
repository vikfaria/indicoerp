<?php

namespace Workdo\Goal\Services;

use Illuminate\Support\Facades\Auth;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalContribution;
use Workdo\Goal\Models\GoalTracking;
use Workdo\Goal\Models\GoalMilestone;

class GoalService
{
    public function autoContributeFromJournalEntry($journalEntry) {
        $accountIds = $journalEntry->items->pluck('account_id')->unique();
        $linkedGoals = Goal::whereIn('account_id', $accountIds)
                                    ->where('status', 'active')
                                    ->get();
        foreach($linkedGoals as $goal) {
            $contributionAmount = $this->calculateContributionFromJournalEntry($goal, $journalEntry);
            if ($contributionAmount > 0) {
                $this->addGoalContribution($goal->id, [
                    'contribution_date' => $journalEntry->journal_date,
                    'contribution_amount' => $contributionAmount,
                    'contribution_type' => 'automatic',
                    'reference_type' => 'journal_entry',
                    'reference_id' => $journalEntry->id,
                    'notes' => "Auto-contribution from Journal Entry #{$journalEntry->id}"
                ]);
            }
        }
    }

    public function calculateContributionFromJournalEntry($goal, $journalEntry) {
        $goalAccount = $goal->account;
        $contributionAmount = 0;
        foreach($journalEntry->items as $item) {
            if ($item->account_id == $goal->account_id) {
                if ($goal->goal_type === 'savings' && $goalAccount->normal_balance === 'credit') {
                    $contributionAmount += $item->credit_amount - $item->debit_amount;
                }
                elseif ($goal->goal_type === 'debt_reduction' && $goalAccount->normal_balance === 'credit') {
                    $contributionAmount += $item->debit_amount - $item->credit_amount;
                }
                elseif ($goal->goal_type === 'expense_reduction' && $goalAccount->normal_balance === 'debit') {
                    $contributionAmount += $this->calculateExpenseReduction($goal, $item, $journalEntry);
                }
            }
        }

        return max(0, $contributionAmount);
    }

    public function calculateExpenseReduction($goal, $journalItem, $journalEntry) {
        $baselineAmount = $this->getExpenseBaseline($goal->account_id, $journalEntry->journal_date);
        $currentExpense = $journalItem->debit_amount - $journalItem->credit_amount;
        $reduction = $baselineAmount - $currentExpense;

        return max(0, $reduction);
    }

    public function getExpenseBaseline($accountId, $currentDate) {
        // Calculate baseline from last 3 months average
        $startDate = $currentDate->copy()->subMonths(3);
        $endDate = $currentDate->copy()->subDay();

        $avgExpense = JournalEntryItem::where('account_id', $accountId)
                    ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('journal_date', [$startDate, $endDate]);
                    })
                    ->selectRaw('AVG(debit_amount - credit_amount) as avg_amount')
                    ->value('avg_amount');
        return $avgExpense ?: 100; // Default baseline if no history
    }

    public function addGoalContribution($goalId, $contributionData) {
        $goal = Goal::find($goalId);

        // Calculate maximum allowed contribution to not exceed target
        $remainingAmount = $goal->target_amount - $goal->current_amount;
        $actualContribution = min($contributionData['contribution_amount'], $remainingAmount);

        // Only proceed if there's remaining capacity
        if ($actualContribution <= 0) {
            return null; // Goal already completed
        }

        $contribution = new GoalContribution();
        $contribution->goal_id = $goalId;
        $contribution->contribution_date = $contributionData['contribution_date'];
        $contribution->contribution_amount = $actualContribution;
        $contribution->contribution_type = $contributionData['contribution_type'];
        $contribution->reference_type = $contributionData['reference_type'] ?? 'manual';
        $contribution->reference_id = $contributionData['reference_id'] ?? null;
        $contribution->notes = $contributionData['notes'] ?? '';
        $contribution->creator_id = Auth::id();
        $contribution->created_by = creatorId();
        $contribution->save();

        $goal->current_amount = min($goal->target_amount, $goal->current_amount + $actualContribution);
        $goal->save();

        $this->updateGoalTracking($goalId);

        // Distribute actual contribution across milestones
        $this->distributeContributionToMilestones($goalId, $actualContribution);

        if ($goal->current_amount >= $goal->target_amount) {
            $goal->status = 'completed';
            $goal->save();
        }

        return $contribution->id;
    }

    public function updateGoalTracking($goalId) {
        $goal = Goal::find($goalId);
        $previousTracking = GoalTracking::where('goal_id', $goalId)
                                    ->orderBy('tracking_date', 'desc')
                                    ->first();

        $previousAmount = $previousTracking ? $previousTracking->current_amount : 0;
        $contributionAmount = $goal->current_amount - $previousAmount;
        // Calculate progress
        $progressPercentage = $goal->target_amount > 0
            ? ($goal->current_amount / $goal->target_amount) * 100
            : 0;

        // Calculate days remaining
        $daysRemaining = now()->diffInDays($goal->target_date, false);

        // Calculate projected completion date
        $projectedDate = $this->calculateProjectedCompletion($goal);

        // Simple on-track status
        $onTrackStatus = 'on_track';

        // Create tracking
        $tracking = new GoalTracking();
        $tracking->goal_id = $goalId;
        $tracking->tracking_date = now();
        $tracking->previous_amount = $previousAmount;
        $tracking->contribution_amount = $contributionAmount;
        $tracking->current_amount = $goal->current_amount;
        $tracking->progress_percentage = $progressPercentage;
        $tracking->days_remaining = $daysRemaining;
        $tracking->projected_completion_date = $projectedDate;
        $tracking->on_track_status = $onTrackStatus;
        $tracking->creator_id = Auth::id();
        $tracking->created_by = creatorId();
        $tracking->save();

        return $tracking->id;
    }

    public function calculateProjectedCompletion($goal) {
        $contributions = GoalContribution::where('goal_id', $goal->id)
                    ->where('contribution_date', '>=', $goal->start_date)
                    ->orderBy('contribution_date')
                    ->get();
        if ($contributions->count() < 2) {
            return $goal->target_date;
        }

        // Calculate average monthly contribution
        $totalContributions = $contributions->sum('contribution_amount');
        $monthsElapsed = $contributions->first()->contribution_date
                        ->diffInMonths($contributions->last()->contribution_date) ?: 1;

        $avgMonthlyContribution = $totalContributions / $monthsElapsed;

        if ($avgMonthlyContribution <= 0) {
            return null; // No progress being made
        }

        // Calculate remaining amount and months needed
        $remainingAmount = $goal->target_amount - $goal->current_amount;
        $monthsNeeded = $remainingAmount / $avgMonthlyContribution;

        return now()->addMonths(ceil($monthsNeeded));
    }

    public function checkMilestoneAchievements($goalId) {
        $goal = Goal::find($goalId);
        $allMilestones = GoalMilestone::where('goal_id', $goalId)->orderBy('id')->get();

        $remainingAmount = $goal->current_amount;

        foreach($allMilestones as $milestone) {
            if ($remainingAmount >= $milestone->target_amount) {
                $milestone->achieved_amount = $milestone->target_amount;
                $milestone->achieved_date = now();
                $milestone->status = 'achieved';
                $remainingAmount -= $milestone->target_amount;
            } elseif ($remainingAmount > 0) {
                $milestone->achieved_amount = $remainingAmount;
                $milestone->status = 'pending';
                $milestone->achieved_date = null;
                $remainingAmount = 0;
            } else {
                $milestone->achieved_amount = 0;
                $milestone->status = 'pending';
                $milestone->achieved_date = null;
            }
            $milestone->save();
        }
    }

    public function distributeContributionToMilestones($goalId, $contributionAmount) {
        $allMilestones = GoalMilestone::where('goal_id', $goalId)->orderBy('id')->get();

        if ($allMilestones->isEmpty()) {
            return;
        }

        $remainingAmount = $contributionAmount;

        foreach($allMilestones as $milestone) {
            if ($remainingAmount <= 0) {
                break;
            }

            $currentAchieved = $milestone->achieved_amount ?? 0;
            $remainingForMilestone = $milestone->target_amount - $currentAchieved;

            if ($remainingForMilestone > 0) {
                $amountToAdd = min($remainingAmount, $remainingForMilestone);
                $milestone->achieved_amount = $currentAchieved + $amountToAdd;
                $remainingAmount -= $amountToAdd;

                // Update status
                if ($milestone->achieved_amount >= $milestone->target_amount) {
                    $milestone->status = 'achieved';
                    if (!$milestone->achieved_date) {
                        $milestone->achieved_date = now();
                    }
                } else {
                    $milestone->status = 'pending';
                }

                $milestone->save();
            }
        }
    }
}
