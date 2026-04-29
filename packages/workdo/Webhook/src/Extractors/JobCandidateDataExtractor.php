<?php

namespace Workdo\Webhook\Extractors;

class JobCandidateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->candidate)) return [];

        $candidate = $event->candidate;

        return [
            'job_candidate' => [
                'id' => $candidate->id,
                'job' => $candidate->job_posting?->title ?? null,
                'candidate_source' => $candidate->candidate_source?->name ?? null,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'dob' => $candidate->dob,
                'country' => $candidate->country,
                'state' => $candidate->state,
                'city' => $candidate->city,
                'current_company' => $candidate->current_company,
                'current_position' => $candidate->current_position,
                'experience_years' => $candidate->experience_years,
                'current_salary' => $candidate->current_salary,
                'expected_salary' => $candidate->expected_salary,
                'notice_period' => $candidate->notice_period,
                'skills' => $candidate->skills,
                'education' => $candidate->education,
                'portfolio_url' => $candidate->portfolio_url,
                'linkedin_url' => $candidate->linkedin_url,
                'application_date' => $candidate->application_date,
                'tracking_id' => $candidate->tracking_id,
                'created_at' => $candidate->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
