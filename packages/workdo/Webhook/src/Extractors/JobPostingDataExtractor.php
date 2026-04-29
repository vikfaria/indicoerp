<?php

namespace Workdo\Webhook\Extractors;

class JobPostingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->jobposting)) return [];

        $jobposting = $event->jobposting;

        return [
            'jobposting' => [
                'id' => $jobposting->id,
                'title' => $jobposting->title,
                'department' => $jobposting->department?->department_name ?? null,
                'job_requisition' => $jobposting->requisition?->title ?? null,
                'min_experience' => $jobposting->min_experience,
                'max_experience' => $jobposting->max_experience,
                'min_salary' => $jobposting->min_salary,
                'max_salary' => $jobposting->max_salary,
                'skills' => $jobposting->skills,
                'application_deadline' => $jobposting->application_deadline,
                'posting_code' => $jobposting->posting_code,
                'created_at' => $jobposting->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}