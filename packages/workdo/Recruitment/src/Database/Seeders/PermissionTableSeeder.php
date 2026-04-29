<?php

namespace Workdo\Recruitment\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            ['name' => 'manage-recruitment', 'module' => 'recruitment', 'label' => 'Manage Recruitment'],
            ['name' => 'manage-recruitment-dashboard', 'module' => 'recruitment', 'label' => 'Manage Recruitment Dashboard'],
            ['name' => 'manage-recruitment-system-setup', 'module' => 'recruitment', 'label' => 'Manage System Setup'],

            // JobLocation management
            ['name' => 'manage-job-locations', 'module' => 'job-locations', 'label' => 'Manage Job Locations'],
            ['name' => 'manage-any-job-locations', 'module' => 'job-locations', 'label' => 'Manage All Job Locations'],
            ['name' => 'manage-own-job-locations', 'module' => 'job-locations', 'label' => 'Manage Own Job Locations'],
            ['name' => 'view-job-locations', 'module' => 'job-locations', 'label' => 'View Job Locations'],
            ['name' => 'create-job-locations', 'module' => 'job-locations', 'label' => 'Create Job Locations'],
            ['name' => 'edit-job-locations', 'module' => 'job-locations', 'label' => 'Edit Job Locations'],
            ['name' => 'delete-job-locations', 'module' => 'job-locations', 'label' => 'Delete Job Locations'],

            // CustomQuestion management
            ['name' => 'manage-custom-questions', 'module' => 'custom-questions', 'label' => 'Manage Custom Questions'],
            ['name' => 'manage-any-custom-questions', 'module' => 'custom-questions', 'label' => 'Manage All Custom Questions'],
            ['name' => 'manage-own-custom-questions', 'module' => 'custom-questions', 'label' => 'Manage Own Custom Questions'],
            ['name' => 'view-custom-questions', 'module' => 'custom-questions', 'label' => 'View Custom Questions'],
            ['name' => 'create-custom-questions', 'module' => 'custom-questions', 'label' => 'Create Custom Questions'],
            ['name' => 'edit-custom-questions', 'module' => 'custom-questions', 'label' => 'Edit Custom Questions'],
            ['name' => 'delete-custom-questions', 'module' => 'custom-questions', 'label' => 'Delete Custom Questions'],

            // JobPosting management
            ['name' => 'manage-job-postings', 'module' => 'job-postings', 'label' => 'Manage Job Postings'],
            ['name' => 'manage-any-job-postings', 'module' => 'job-postings', 'label' => 'Manage All Job Postings'],
            ['name' => 'manage-own-job-postings', 'module' => 'job-postings', 'label' => 'Manage Own Job Postings'],
            ['name' => 'publish-job-postings', 'module' => 'job-postings', 'label' => 'Publish Job Postings'],
            ['name' => 'view-job-postings', 'module' => 'job-postings', 'label' => 'View Job Postings'],
            ['name' => 'create-job-postings', 'module' => 'job-postings', 'label' => 'Create Job Postings'],
            ['name' => 'edit-job-postings', 'module' => 'job-postings', 'label' => 'Edit Job Postings'],
            ['name' => 'delete-job-postings', 'module' => 'job-postings', 'label' => 'Delete Job Postings'],

            // Candidate management
            ['name' => 'manage-candidates', 'module' => 'candidates', 'label' => 'Manage Candidates'],
            ['name' => 'manage-any-candidates', 'module' => 'candidates', 'label' => 'Manage All Candidates'],
            ['name' => 'manage-own-candidates', 'module' => 'candidates', 'label' => 'Manage Own Candidates'],
            ['name' => 'view-candidates', 'module' => 'candidates', 'label' => 'View Candidates'],
            ['name' => 'create-candidates', 'module' => 'candidates', 'label' => 'Create Candidates'],
            ['name' => 'edit-candidates', 'module' => 'candidates', 'label' => 'Edit Candidates'],
            ['name' => 'delete-candidates', 'module' => 'candidates', 'label' => 'Delete Candidates'],

            // InterviewRound management
            ['name' => 'manage-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Manage Interview Rounds'],
            ['name' => 'manage-any-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Manage All Interview Rounds'],
            ['name' => 'manage-own-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Manage Own Interview Rounds'],
            ['name' => 'view-interview-rounds', 'module' => 'interview-rounds', 'label' => 'View Interview Rounds'],
            ['name' => 'create-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Create Interview Rounds'],
            ['name' => 'edit-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Edit Interview Rounds'],
            ['name' => 'delete-interview-rounds', 'module' => 'interview-rounds', 'label' => 'Delete Interview Rounds'],

            // Interview management
            ['name' => 'manage-interviews', 'module' => 'interviews', 'label' => 'Manage Interviews'],
            ['name' => 'manage-any-interviews', 'module' => 'interviews', 'label' => 'Manage All Interviews'],
            ['name' => 'manage-own-interviews', 'module' => 'interviews', 'label' => 'Manage Own Interviews'],
            ['name' => 'view-interviews', 'module' => 'interviews', 'label' => 'View Interviews'],
            ['name' => 'create-interviews', 'module' => 'interviews', 'label' => 'Create Interviews'],
            ['name' => 'edit-interviews', 'module' => 'interviews', 'label' => 'Edit Interviews'],
            ['name' => 'delete-interviews', 'module' => 'interviews', 'label' => 'Delete Interviews'],

            // InterviewFeedback management
            ['name' => 'manage-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Manage Interview Feedbacks'],
            ['name' => 'manage-any-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Manage All Interview Feedbacks'],
            ['name' => 'manage-own-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Manage Own Interview Feedbacks'],
            ['name' => 'view-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'View Interview Feedbacks'],
            ['name' => 'create-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Create Interview Feedbacks'],
            ['name' => 'edit-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Edit Interview Feedbacks'],
            ['name' => 'delete-interview-feedbacks', 'module' => 'interview-feedbacks', 'label' => 'Delete Interview Feedbacks'],

            // CandidateAssessment management
            ['name' => 'manage-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Manage Candidate Assessments'],
            ['name' => 'manage-any-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Manage All Candidate Assessments'],
            ['name' => 'manage-own-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Manage Own Candidate Assessments'],
            ['name' => 'view-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'View Candidate Assessments'],
            ['name' => 'create-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Create Candidate Assessments'],
            ['name' => 'edit-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Edit Candidate Assessments'],
            ['name' => 'delete-candidate-assessments', 'module' => 'candidate-assessments', 'label' => 'Delete Candidate Assessments'],

            // Offer management
            ['name' => 'manage-offers', 'module' => 'offers', 'label' => 'Manage Offers'],
            ['name' => 'manage-any-offers', 'module' => 'offers', 'label' => 'Manage All Offers'],
            ['name' => 'manage-own-offers', 'module' => 'offers', 'label' => 'Manage Own Offers'],
            ['name' => 'view-offers', 'module' => 'offers', 'label' => 'View Offers'],
            ['name' => 'create-offers', 'module' => 'offers', 'label' => 'Create Offers'],
            ['name' => 'edit-offers', 'module' => 'offers', 'label' => 'Edit Offers'],
            ['name' => 'approve-offers', 'module' => 'offers', 'label' => 'Approve Offers'],
            ['name' => 'send-offer-emails', 'module' => 'offers', 'label' => 'Send Offer Emails'],
            ['name' => 'download-offer-letters', 'module' => 'offers', 'label' => 'Download Offer Letters'],
            ['name' => 'convert-offers-to-employees', 'module' => 'offers', 'label' => 'Convert Offers to Employees'],
            ['name' => 'view-offer-employees', 'module' => 'offers', 'label' => 'View Offer Employee Details'],
            ['name' => 'delete-offers', 'module' => 'offers', 'label' => 'Delete Offers'],

            // ChecklistItem management
            ['name' => 'manage-checklist-items', 'module' => 'checklist-items', 'label' => 'Manage Checklist Items'],
            ['name' => 'manage-any-checklist-items', 'module' => 'checklist-items', 'label' => 'Manage All Checklist Items'],
            ['name' => 'manage-own-checklist-items', 'module' => 'checklist-items', 'label' => 'Manage Own Checklist Items'],
            ['name' => 'view-checklist-items', 'module' => 'checklist-items', 'label' => 'View Checklist Items'],
            ['name' => 'create-checklist-items', 'module' => 'checklist-items', 'label' => 'Create Checklist Items'],
            ['name' => 'edit-checklist-items', 'module' => 'checklist-items', 'label' => 'Edit Checklist Items'],
            ['name' => 'delete-checklist-items', 'module' => 'checklist-items', 'label' => 'Delete Checklist Items'],

            // CandidateOnboarding management
            ['name' => 'manage-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Manage Candidate Onboardings'],
            ['name' => 'manage-any-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Manage All Candidate Onboardings'],
            ['name' => 'manage-own-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Manage Own Candidate Onboardings'],
            ['name' => 'view-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'View Candidate Onboardings'],
            ['name' => 'create-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Create Candidate Onboardings'],
            ['name' => 'edit-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Edit Candidate Onboardings'],
            ['name' => 'delete-candidate-onboardings', 'module' => 'candidate-onboardings', 'label' => 'Delete Candidate Onboardings'],



            // JobType management
            ['name' => 'manage-job-types', 'module' => 'job-types', 'label' => 'Manage Job Types'],
            ['name' => 'manage-any-job-types', 'module' => 'job-types', 'label' => 'Manage All Job Types'],
            ['name' => 'manage-own-job-types', 'module' => 'job-types', 'label' => 'Manage Own Job Types'],
            ['name' => 'create-job-types', 'module' => 'job-types', 'label' => 'Create Job Types'],
            ['name' => 'edit-job-types', 'module' => 'job-types', 'label' => 'Edit Job Types'],
            ['name' => 'delete-job-types', 'module' => 'job-types', 'label' => 'Delete Job Types'],

            // CandidateSources management
            ['name' => 'manage-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Manage Candidate Sources'],
            ['name' => 'manage-any-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Manage All Candidate Sources'],
            ['name' => 'manage-own-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Manage Own Candidate Sources'],
            ['name' => 'create-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Create Candidate Sources'],
            ['name' => 'edit-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Edit Candidate Sources'],
            ['name' => 'delete-candidate-sources', 'module' => 'candidate-sources', 'label' => 'Delete Candidate Sources'],

            // InterviewType management
            ['name' => 'manage-interview-types', 'module' => 'interview-types', 'label' => 'Manage Interview Types'],
            ['name' => 'manage-any-interview-types', 'module' => 'interview-types', 'label' => 'Manage All Interview Types'],
            ['name' => 'manage-own-interview-types', 'module' => 'interview-types', 'label' => 'Manage Own Interview Types'],
            ['name' => 'create-interview-types', 'module' => 'interview-types', 'label' => 'Create Interview Types'],
            ['name' => 'edit-interview-types', 'module' => 'interview-types', 'label' => 'Edit Interview Types'],
            ['name' => 'delete-interview-types', 'module' => 'interview-types', 'label' => 'Delete Interview Types'],

            // OnboardingChecklist management
            ['name' => 'manage-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Manage Onboarding Checklists'],
            ['name' => 'manage-any-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Manage All Onboarding Checklists'],
            ['name' => 'manage-own-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Manage Own Onboarding Checklists'],
            ['name' => 'view-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'View Onboarding Checklists'],
            ['name' => 'create-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Create Onboarding Checklists'],
            ['name' => 'edit-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Edit Onboarding Checklists'],
            ['name' => 'delete-onboarding-checklists', 'module' => 'onboarding-checklists', 'label' => 'Delete Onboarding Checklists'],

            // Recruitment Settings
            ['name' => 'manage-recruitment-brand-settings', 'module' => 'setting', 'label' => 'Manage Brand Settings'],
            ['name' => 'manage-about-company', 'module' => 'setting', 'label' => 'Manage About Company'],
            ['name' => 'manage-application-tips', 'module' => 'setting', 'label' => 'Manage Application Tips'],
            ['name' => 'manage-what-happens-next', 'module' => 'setting', 'label' => 'Manage What Happens Next Section'],
            ['name' => 'manage-need-help', 'module' => 'setting', 'label' => 'Manage Need Help Section'],
            ['name' => 'manage-tracking-faq', 'module' => 'setting', 'label' => 'Manage Tracking FAQ'],
            ['name' => 'manage-offer-letter-template', 'module' => 'setting', 'label' => 'Manage Offer Letter Template'],
            ['name' => 'manage-recruitment-dashboard-welcome-card', 'module' => 'setting', 'label' => 'Manage Dashboard Welcome Card'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Recruitment',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}