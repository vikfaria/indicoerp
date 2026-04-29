<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type_id',
        'status',
        'creator_id',
        'created_by',
    ];

    public static function defaultdata($company_id = null)
    {
        $hrRolePermissions = [
            'manage-dashboard',
            'manage-media',
            'manage-own-media',
            'create-media',
            'download-media',
            'delete-media',
            'manage-media-directories',
            'manage-own-media-directories',
            'create-media-directories',
            'edit-media-directories',
            'delete-media-directories',

            'manage-profile',
            'edit-profile',
            'change-password-profile',

            'manage-messenger',
            'send-messages',
            'view-messages',
            'toggle-favorite-messages',
            'toggle-pinned-messages',

            // Recruitment
            'manage-recruitment',
            'manage-recruitment-dashboard',

            // Job Locations
            'manage-job-locations',
            'manage-any-job-locations',
            'manage-own-job-locations',
            'view-job-locations',
            'create-job-locations',
            'edit-job-locations',
            'delete-job-locations',

            // Custom Questions
            'manage-custom-questions',
            'manage-any-custom-questions',
            'manage-own-custom-questions',
            'view-custom-questions',
            'create-custom-questions',
            'edit-custom-questions',
            'delete-custom-questions',

            // Job Postings
            'manage-job-postings',
            'manage-any-job-postings',
            'manage-own-job-postings',
            'publish-job-postings',
            'view-job-postings',
            'create-job-postings',
            'edit-job-postings',
            'delete-job-postings',

            // Candidates
            'manage-candidates',
            'manage-any-candidates',
            'manage-own-candidates',
            'view-candidates',
            'create-candidates',
            'edit-candidates',
            'delete-candidates',

            // Interview Rounds
            'manage-interview-rounds',
            'manage-any-interview-rounds',
            'manage-own-interview-rounds',
            'view-interview-rounds',
            'create-interview-rounds',
            'edit-interview-rounds',
            'delete-interview-rounds',

            // Interviews
            'manage-interviews',
            'manage-any-interviews',
            'manage-own-interviews',
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'delete-interviews',

            // Interview Feedbacks
            'manage-interview-feedbacks',
            'manage-any-interview-feedbacks',
            'manage-own-interview-feedbacks',
            'view-interview-feedbacks',
            'create-interview-feedbacks',
            'edit-interview-feedbacks',
            'delete-interview-feedbacks',

            // Candidate Assessments
            'manage-candidate-assessments',
            'manage-any-candidate-assessments',
            'manage-own-candidate-assessments',
            'view-candidate-assessments',
            'create-candidate-assessments',
            'edit-candidate-assessments',
            'delete-candidate-assessments',

            // Offers
            'manage-offers',
            'manage-any-offers',
            'manage-own-offers',
            'view-offers',
            'create-offers',
            'edit-offers',
            'approve-offers',
            'send-offer-emails',
            'download-offer-letters',
            'convert-offers-to-employees',
            'view-offer-employees',
            'delete-offers',

            // Onboarding Checklists
            'manage-checklist-items',
            'manage-any-checklist-items',
            'manage-own-checklist-items',
            'view-checklist-items',
            'create-checklist-items',
            'edit-checklist-items',
            'delete-checklist-items',

            // Candidate Onboardings
            'manage-candidate-onboardings',
            'manage-any-candidate-onboardings',
            'manage-own-candidate-onboardings',
            'view-candidate-onboardings',
            'create-candidate-onboardings',
            'edit-candidate-onboardings',
            'delete-candidate-onboardings',

            // Job Types
            'manage-job-types',
            'manage-any-job-types',
            'manage-own-job-types',
            'create-job-types',
            'edit-job-types',
            'delete-job-types',

            // Candidate Sources
            'manage-candidate-sources',
            'manage-any-candidate-sources',
            'manage-own-candidate-sources',
            'create-candidate-sources',
            'edit-candidate-sources',
            'delete-candidate-sources',

            // Interview Types
            'manage-interview-types',
            'manage-any-interview-types',
            'manage-own-interview-types',
            'create-interview-types',
            'edit-interview-types',
            'delete-interview-types',

            // Onboarding Checklists
            'manage-onboarding-checklists',
            'manage-any-onboarding-checklists',
            'manage-own-onboarding-checklists',
            'view-onboarding-checklists',
            'create-onboarding-checklists',
            'edit-onboarding-checklists',
            'delete-onboarding-checklists',

            // Recruitment Settings
            'manage-recruitment-brand-settings',
            'manage-about-company',
            'manage-application-tips',
            'manage-what-happens-next',
            'manage-need-help',
            'manage-tracking-faq',
            'manage-offer-letter-template',
            'manage-recruitment-dashboard-welcome-card',
        ];

        if (!empty($company_id)) {
            $hrRole = Role::where('name', 'hr')->where('created_by', $company_id)->where('guard_name', 'web')->first();
            if (empty($hrRole)) {
                $hrRole = new Role();
                $hrRole->name = 'hr';
                $hrRole->guard_name = 'web';
                $hrRole->label = 'Hr';
                $hrRole->editable = 0;
                $hrRole->created_by = $company_id;
                $hrRole->save();

                foreach ($hrRolePermissions as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission) && !$hrRole->hasPermissionTo($permission_v)) {
                        $hrRole->givePermissionTo($permission);
                    }
                }
            } else {
                foreach ($hrRolePermissions as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission) && !$hrRole->hasPermissionTo($permission_v)) {
                        $hrRole->givePermissionTo($permission);
                    }
                }
            }

            OfferLetter::defaultOfferLetter($company_id);
        }
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            // Dashboard
            'manage-dashboard',
            'manage-recruitment',
            'manage-recruitment-dashboard',

            // Job Interviews
            'manage-interviews',
            'manage-own-interviews',
            'view-interviews',

            // Candidates Assessments
            'manage-candidate-assessments',
            'manage-own-candidate-assessments',
            'view-candidate-assessments',

            // Interview Feedbacks
            'manage-interview-feedbacks',
            'manage-own-interview-feedbacks',
            'view-interview-feedbacks',

            // Candidate Onboardings
            'manage-candidate-onboardings',
            'manage-own-candidate-onboardings',
            'view-candidate-onboardings',
        ];

        $hr_permission = [
            'manage-dashboard',
            'manage-media',
            'manage-own-media',
            'create-media',
            'download-media',
            'delete-media',
            'manage-media-directories',
            'manage-own-media-directories',
            'create-media-directories',
            'edit-media-directories',
            'delete-media-directories',

            'manage-profile',
            'edit-profile',
            'change-password-profile',

            'manage-messenger',
            'send-messages',
            'view-messages',
            'toggle-favorite-messages',
            'toggle-pinned-messages',

            // Dashboard
            'manage-dashboard',

            // Recruitment
            'manage-recruitment',
            'manage-recruitment-dashboard',
            'manage-recruitment-system-setup',

            // Job Locations
            'manage-job-locations',
            'manage-any-job-locations',
            'manage-own-job-locations',
            'view-job-locations',
            'create-job-locations',
            'edit-job-locations',
            'delete-job-locations',

            // Custom Questions
            'manage-custom-questions',
            'manage-any-custom-questions',
            'manage-own-custom-questions',
            'view-custom-questions',
            'create-custom-questions',
            'edit-custom-questions',
            'delete-custom-questions',

            // Job Postings
            'manage-job-postings',
            'manage-any-job-postings',
            'manage-own-job-postings',
            'publish-job-postings',
            'view-job-postings',
            'create-job-postings',
            'edit-job-postings',
            'delete-job-postings',

            // Candidates
            'manage-candidates',
            'manage-any-candidates',
            'manage-own-candidates',
            'view-candidates',
            'create-candidates',
            'edit-candidates',
            'delete-candidates',

            // Interview Rounds
            'manage-interview-rounds',
            'manage-any-interview-rounds',
            'manage-own-interview-rounds',
            'view-interview-rounds',
            'create-interview-rounds',
            'edit-interview-rounds',
            'delete-interview-rounds',

            // Interviews
            'manage-interviews',
            'manage-any-interviews',
            'manage-own-interviews',
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'delete-interviews',

            // Interview Feedbacks
            'manage-interview-feedbacks',
            'manage-any-interview-feedbacks',
            'manage-own-interview-feedbacks',
            'view-interview-feedbacks',
            'create-interview-feedbacks',
            'edit-interview-feedbacks',
            'delete-interview-feedbacks',

            // Candidate Assessments
            'manage-candidate-assessments',
            'manage-any-candidate-assessments',
            'manage-own-candidate-assessments',
            'view-candidate-assessments',
            'create-candidate-assessments',
            'edit-candidate-assessments',
            'delete-candidate-assessments',

            // Offers
            'manage-offers',
            'manage-any-offers',
            'manage-own-offers',
            'view-offers',
            'create-offers',
            'edit-offers',
            'approve-offers',
            'send-offer-emails',
            'download-offer-letters',
            'convert-offers-to-employees',
            'view-offer-employees',
            'delete-offers',

            // Onboarding Checklists
            'manage-checklist-items',
            'manage-any-checklist-items',
            'manage-own-checklist-items',
            'view-checklist-items',
            'create-checklist-items',
            'edit-checklist-items',
            'delete-checklist-items',

            // Candidate Onboardings
            'manage-candidate-onboardings',
            'manage-any-candidate-onboardings',
            'manage-own-candidate-onboardings',
            'view-candidate-onboardings',
            'create-candidate-onboardings',
            'edit-candidate-onboardings',
            'delete-candidate-onboardings',

            // Job Types
            'manage-job-types',
            'manage-any-job-types',
            'manage-own-job-types',
            'create-job-types',
            'edit-job-types',
            'delete-job-types',

            // Candidate Sources
            'manage-candidate-sources',
            'manage-any-candidate-sources',
            'manage-own-candidate-sources',
            'create-candidate-sources',
            'edit-candidate-sources',
            'delete-candidate-sources',

            // Interview Types
            'manage-interview-types',
            'manage-any-interview-types',
            'manage-own-interview-types',
            'create-interview-types',
            'edit-interview-types',
            'delete-interview-types',

            // Onboarding Checklists
            'manage-onboarding-checklists',
            'manage-any-onboarding-checklists',
            'manage-own-onboarding-checklists',
            'view-onboarding-checklists',
            'create-onboarding-checklists',
            'edit-onboarding-checklists',
            'delete-onboarding-checklists',

            // Recruitment Settings
            'manage-recruitment-brand-settings',
            'manage-about-company',
            'manage-application-tips',
            'manage-what-happens-next',
            'manage-need-help',
            'manage-tracking-faq',
            'manage-offer-letter-template',
            'manage-recruitment-dashboard-welcome-card',
        ];

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            foreach ($staff_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }

        if ($rolename == 'hr') {
            $roles_v = Role::where('name', 'hr')->where('id', $role_id)->first();
            foreach ($hr_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }
    }
}
