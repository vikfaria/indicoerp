<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\Offer;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\JobPosting;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Department;

class DemoOfferSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Offer::where('created_by', $userId)->exists()) {
            return;
        }

        $positions = [
            'Senior Software Engineer', 'Product Manager', 'UX Designer', 'Data Scientist',
            'DevOps Engineer', 'Marketing Manager', 'Sales Representative', 'HR Specialist',
            'Financial Analyst', 'Business Analyst', 'Frontend Developer', 'Backend Developer'
        ];

        $benefits = [
            'Health insurance, dental coverage, 401k matching, flexible work hours',
            'Medical benefits, life insurance, stock options, remote work flexibility',
            'Comprehensive health plan, retirement savings, professional development budget',
            'Health & wellness benefits, equity participation, unlimited PTO policy',
            'Full medical coverage, dental & vision, performance bonuses, gym membership',
            'Healthcare benefits, retirement plan, education reimbursement, flexible schedule'
        ];

        $declineReasons = [
            'Accepted another offer with better compensation package',
            'Decided to stay with current employer after counter-offer',
            'Salary expectations not met despite negotiations',
            'Location requirements do not align with personal needs',
            'Career growth opportunities not sufficient for long-term goals',
            'Company culture and values do not match personal preferences'
        ];

        $statuses = [0, 1, 2, 3, 4, 5]; // 0=Draft, 1=Sent, 2=Accepted, 3=Negotiating, 4=Declined, 5=Expired

        // Get eligible candidates (with passed assessments OR Strong Hire/Hire feedback)
        $eligibleCandidates = Candidate::where('created_by', $userId)
            ->where(function ($query) {
                $query->whereHas('candidateAssessments', function ($q) {
                    $q->where('pass_fail_status', '0'); // Pass status
                })->orWhereHas('interviewFeedbacks', function ($q) {
                    $q->whereIn('recommendation', ['0', '1']); // Strong Hire & Hire
                });
            })
            ->get();

        if ($eligibleCandidates->isEmpty()) {
            return;
        }

        // Get departments from HRM module
        $departments = Department::where('created_by', $userId)->pluck('id')->toArray();
        if (empty($departments)) {
            return;
        }

        $jobs = JobPosting::where('created_by', $userId)->where('is_published', 1)->where('status', 'active')->pluck('id')->toArray();
        $approvers = User::emp()->where('created_by', $userId)->pluck('id')->toArray();
        $employees = Employee::where('created_by', $userId)->pluck('id')->toArray();

        if (empty($jobs) || empty($approvers)) {
            return;
        }

        for ($i = 0; $i < 15; $i++) {
            $candidate = $eligibleCandidates->random();
            $job = fake()->randomElement($jobs);
            $approver = fake()->randomElement($approvers);

            if (!$candidate || !$job || !$approver) {
                continue;
            }

            $offerDate = fake()->dateTimeBetween('-2 months', 'now');
            $startDate = fake()->dateTimeBetween($offerDate, '+1 month');
            $expirationDate = fake()->dateTimeBetween($offerDate, '+2 weeks');

            $status = fake()->randomElement($statuses);
            $baseSalary = fake()->numberBetween(50000, 150000);
            $bonus = $status === 2 ? fake()->numberBetween(5000, 25000) : null; // Accepted

            $responseDate = null;
            $declineReason = null;

            if (in_array($status, [2, 3, 4])) { // Accepted, Negotiating, Declined
                $responseDate = fake()->dateTimeBetween($offerDate, 'now');
                if ($status === 4) { // Declined
                    $declineReason = fake()->randomElement($declineReasons);
                }
            }

            // Set realistic approval status
            $approvalStatus = 'pending';
            $approvedBy = null;

            if ($status !== 0) { // Not draft
                $approvalStatus = fake()->randomElement(['pending', 'approved']);
                if ($approvalStatus === 'approved') {
                    $approvedBy = $approver;
                }
            }

            // Set conversion to employee data (only for accepted offers)
            $convertedToEmployee = false;
            $employeeId = null;

            if ($status === 2 && $approvalStatus === 'approved') { // Accepted and approved
                $convertedToEmployee = fake()->boolean(70); // 70% chance of conversion
                if ($convertedToEmployee && !empty($employees)) {
                    $employeeId = fake()->randomElement($employees);
                }
            }

            Offer::create([
                'candidate_id' => $candidate->id,
                'job_id' => $job,
                'offer_date' => $offerDate->format('Y-m-d'),
                'position' => fake()->randomElement($positions),
                'department_id' => fake()->randomElement($departments),
                'salary' => $baseSalary,
                'bonus' => $bonus,
                'equity' => $status === 2 ? fake()->randomFloat(2, 0.1, 2.0) . '%' : null,
                'benefits' => fake()->randomElement($benefits),
                'start_date' => $startDate->format('Y-m-d'),
                'expiration_date' => $expirationDate->format('Y-m-d'),
                'offer_letter_path' => $status !== 0 ? 'offer_letter_' . fake()->uuid() . '.pdf' : null,
                'status' => $status,
                'response_date' => $responseDate?->format('Y-m-d'),
                'decline_reason' => $declineReason,
                'converted_to_employee' => $convertedToEmployee,
                'employee_id' => $employeeId,
                'approved_by' => $approvedBy,
                'approval_status' => $approvalStatus,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
