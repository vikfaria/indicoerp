<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Loan;
use Workdo\Hrm\Models\LoanType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Http\Requests\StoreLoanRequest;
use Workdo\Hrm\Http\Requests\UpdateLoanRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Events\CreateLoan;
use Workdo\Hrm\Events\UpdateLoan;
use Workdo\Hrm\Events\DestroyLoan;

class LoanController extends Controller
{
    public function store(StoreLoanRequest $request)
    {
        if (Auth::user()->can('create-loans')) {
            $validated = $request->validated();

            $employee = Employee::query()
                ->where('id', (int) $validated['employee_id'])
                ->where('created_by', creatorId())
                ->first();

            if ($employee) {
                // Check if employee already has a loan
                $existingLoan = Loan::query()
                    ->active()
                    ->where('created_by', creatorId())
                    ->where('employee_id', $employee->user_id)
                    ->where('loan_type_id', $validated['loan_type_id'])
                    ->first();

                if ($existingLoan) {
                    return redirect()->back()->with('error', __('Employee already has a loan.'));
                }

                $loan = new Loan();
                $loan->title = $validated['title'];
                $loan->employee_id = $employee->user_id;
                $loan->loan_type_id = $validated['loan_type_id'];
                $loan->type = $validated['type'];
                $loan->amount = $validated['amount'];
                $loan->start_date = $validated['start_date'];
                $loan->end_date = $validated['end_date'];
                $loan->reason = $validated['reason'];
                $loan->creator_id = Auth::id();
                $loan->created_by = creatorId();
                $loan->save();

                CreateLoan::dispatch($request, $loan);

                return redirect()->back()->with('success', __('The loan has been created successfully.'))->with('timestamp', time());
            } else {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateLoanRequest $request, Loan $loan)
    {
        if (Auth::user()->can('edit-loans')) {
            if (!$this->canAccessLoan($loan)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($loan->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled loan cannot be edited.'));
            }

            $validated = $request->validated();

            // Check if another employee already has a loan (excluding current loan)
            $existingLoan = Loan::query()
                ->active()
                ->where('created_by', creatorId())
                ->where('employee_id', $loan->employee_id)
                ->where('loan_type_id', $validated['loan_type_id'])
                ->where('id', '!=', $loan->id)
                ->first();

            if ($existingLoan) {
                return redirect()->back()->with('error', __('Employee already has a loan.'));
            }

            $loan->title = $validated['title'];
            $loan->loan_type_id = $validated['loan_type_id'];
            $loan->type = $validated['type'];
            $loan->amount = $validated['amount'];
            $loan->start_date = $validated['start_date'];
            $loan->end_date = $validated['end_date'];
            $loan->reason = $validated['reason'];
            $loan->save();

            UpdateLoan::dispatch($request, $loan);

            return redirect()->back()->with('success', __('The loan has been updated successfully.'))->with('timestamp', time());
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Loan $loan, Employee $employee)
    {
        if (Auth::user()->can('delete-loans')) {
            if (!$this->canAccessLoan($loan)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($loan->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Loan is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyLoan::dispatch($loan);
            $loan->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('The loan has been cancelled.'))->with('timestamp', time());
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessLoan(Loan $loan): bool
    {
        return (int) $loan->created_by === (int) creatorId();
    }
}
