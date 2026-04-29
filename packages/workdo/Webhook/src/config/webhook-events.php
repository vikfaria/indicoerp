<?php

return [
    'events' => [
        'App\Events\CreateUser' => [
            [
                'action' => 'New User',
                'module' => 'general',
                'type' => 'super admin',
                'extractor' => 'Workdo\Webhook\Extractors\UserDataExtractor'
            ],
            [
                'action' => 'New User',
                'module' => 'general',
                'type' => 'company',
                'extractor' => 'Workdo\Webhook\Extractors\UserDataExtractor'
            ]
        ],

        // event use pending
        // 'App\Events\CreateSubscriber' => [
        //     'action' => 'New Subscriber',
        //     'module' => 'general',
        //     'type' => 'super admin',
        //     'extractor' => 'Workdo\Webhook\Extractors\SubscriberDataExtractor'
        // ],

        'App\Events\CreateSalesInvoice' => [
            'action' => 'New Sales Invoice',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\SalesInvoiceDataExtractor'
        ],
        'App\Events\PostSalesInvoice' => [
            'action' => 'Sales Invoice Status Updated',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\PostSalesInvoiceDataExtractor'
        ],
        'App\Events\CreateSalesProposal' => [
            'action' => 'New Sales Proposal',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\SalesProposalDataExtractor'
        ],
        'App\Events\AcceptSalesProposal' => [
            'action' => 'Sales Proposal Status Updated',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\AcceptSalesProposalDataExtractor'
        ],
        'App\Events\CreatePurchaseInvoice' => [
            'action' => 'New Purchase Invoice',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\PurchaseInvoiceDataExtractor'
        ],
        'App\Events\CreateWarehouse' => [
            'action' => 'New Warehouse',
            'module' => 'general',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\WarehouseDataExtractor'
        ],
        // Add other package wise event and data in this only, and create "Extractors" proper no need to do anything else

        'Workdo\Account\Events\CreateCustomer' => [
            'action' => 'New Customer',
            'module' => 'Account',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\CustomerDataExtractor'
        ],
        'Workdo\Account\Events\CreateVendor' => [
            'action' => 'New Vendor',
            'module' => 'Account',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\VendorDataExtractor'
        ],
        'Workdo\Account\Events\CreateRevenue' => [
            'action' => 'New Revenue',
            'module' => 'Account',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\RevenueDataExtractor'
        ],
        'Workdo\Recruitment\Events\CreateJobPosting' => [
            'action' => 'New Job Posting',
            'module' => 'Recruitment',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\JobPostingDataExtractor'
        ],
        'Workdo\Recruitment\Events\CreateCandidate' => [
            'action' => 'New Job Candidate',
            'module' => 'Recruitment',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\JobCandidateDataExtractor'
        ],
        'Workdo\Recruitment\Events\CreateInterview' => [
            'action' => 'New Job Interview Schedule',
            'module' => 'Recruitment',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\JobInterviewScheduleDataExtractor'
        ],
        'Workdo\Recruitment\Events\ConvertOfferToEmployee' => [
            'action' => 'New Convert To Employee',
            'module' => 'Recruitment',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\ConvertToEmployeeDataExtractor'
        ],
        'Workdo\Training\Events\CreateTraining' => [
            'action' => 'New Training',
            'module' => 'Training',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\TrainingDataExtractor'
        ],
        'Workdo\Training\Events\CreateTrainer' => [
            'action' => 'New Trainer',
            'module' => 'Training',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\TrainerDataExtractor'
        ],
        'Workdo\ZoomMeeting\Events\CreateZoomMeeting' => [
            'action' => 'New Zoom Meeting',
            'module' => 'ZoomMeeting',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\ZoomMeetingDataExtractor'
        ],
        'Workdo\Taskly\Events\CreateProject' => [
            'action' => 'New Project',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\ProjectDataExtractor'
        ],
        'Workdo\Taskly\Events\CreateProjectMilestone' => [
            'action' => 'New Milestone',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\MilestoneDataExtractor'
        ],
        'Workdo\Taskly\Events\CreateProjectTask' => [
            'action' => 'New Task',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\TaskDataExtractor'
        ],
        'Workdo\Taskly\Events\UpdateTaskStage' => [
            'action' => 'Task Stage Update',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\TaskStageUpdateDataExtractor'
        ],
        'Workdo\Taskly\Events\CreateTaskComment' => [
            'action' => 'New Task Comment',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\TaskCommentDataExtractor'
        ],
        'Workdo\Taskly\Events\CreateProjectBug' => [
            'action' => 'New Bug',
            'module' => 'Taskly',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\BugDataExtractor'
        ],
        'Workdo\Lead\Events\CreateLead' => [
            'action' => 'New Lead',
            'module' => 'Lead',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\LeadDataExtractor'
        ],
        'Workdo\Lead\Events\CreateDeal' => [
            'action' => 'New Deal',
            'module' => 'Lead',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\DealDataExtractor'
        ],
        'Workdo\Lead\Events\LeadMoved' => [
            'action' => 'Lead Moved',
            'module' => 'Lead',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\LeadMovedDataExtractor'
        ],
        'Workdo\Lead\Events\DealMoved' => [
            'action' => 'Deal Moved',
            'module' => 'Lead',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\DealMovedDataExtractor'
        ],
        'Workdo\Lead\Events\LeadConvertDeal' => [
            'action' => 'Convert To Deal',
            'module' => 'Lead',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\ConvertToDealDataExtractor'
        ],
        'Workdo\Contract\Events\CreateContract' => [
            'action' => 'New Contract',
            'module' => 'Contract',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\ContractDataExtractor'
        ],
        'Workdo\Hrm\Events\CreateAward' => [
            'action' => 'New Award',
            'module' => 'Hrm',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\HrmAwardDataExtractor'
        ],
        'Workdo\Hrm\Events\CreateAnnouncement' => [
            'action' => 'New Announcement',
            'module' => 'Hrm',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\HrmAnnouncementDataExtractor'
        ],
        'Workdo\Hrm\Events\CreateHoliday' => [
            'action' => 'New Holidays',
            'module' => 'Hrm',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\HrmHolidayDataExtractor'
        ],
        'Workdo\Hrm\Events\CreateEvent' => [
            'action' => 'New Event',
            'module' => 'Hrm',
            'type' => 'company',
            'extractor' => 'Workdo\Webhook\Extractors\HrmEventDataExtractor'
        ],
    ]
];
