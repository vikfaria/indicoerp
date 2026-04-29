<?php

use Workdo\Recruitment\Http\Controllers\CandidateOnboardingController;
use Workdo\Recruitment\Http\Controllers\ChecklistItemController;
use Workdo\Recruitment\Http\Controllers\OnboardingChecklistController;
use Workdo\Recruitment\Http\Controllers\OfferController;
use Workdo\Recruitment\Http\Controllers\CandidateAssessmentController;
use Workdo\Recruitment\Http\Controllers\InterviewFeedbackController;
use Workdo\Recruitment\Http\Controllers\InterviewController;
use Workdo\Recruitment\Http\Controllers\InterviewRoundController;
use Workdo\Recruitment\Http\Controllers\CandidateController;
use Workdo\Recruitment\Http\Controllers\JobPostingController;
use Workdo\Recruitment\Http\Controllers\CustomQuestionController;
use Workdo\Recruitment\Http\Controllers\JobLocationController;

use Workdo\Recruitment\Http\Controllers\InterviewTypeController;
use Workdo\Recruitment\Http\Controllers\CandidateSourcesController;
use Workdo\Recruitment\Http\Controllers\JobTypeController;

use Workdo\Recruitment\Http\Controllers\DashboardController as RecruitmentDashboardController;
use Workdo\Recruitment\Http\Controllers\RecruitmentSettingController;
use Workdo\Recruitment\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Workdo\Recruitment\Http\Middleware\RecruitmentSharedDataMiddleware;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Recruitment'])->group(function () {
    Route::get('/dashboard/recruitment', [RecruitmentDashboardController::class, 'index'])->name('recruitment.index');



    Route::prefix('recruitment/job-types')->name('recruitment.job-types.')->group(function () {
        Route::get('/', [JobTypeController::class, 'index'])->name('index');
        Route::post('/', [JobTypeController::class, 'store'])->name('store');
        Route::put('/{jobtype}', [JobTypeController::class, 'update'])->name('update');
        Route::delete('/{jobtype}', [JobTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/candidate-sources')->name('recruitment.candidate-sources.')->group(function () {
        Route::get('/', [CandidateSourcesController::class, 'index'])->name('index');
        Route::post('/', [CandidateSourcesController::class, 'store'])->name('store');
        Route::put('/{candidatesources}', [CandidateSourcesController::class, 'update'])->name('update');
        Route::delete('/{candidatesources}', [CandidateSourcesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/interview-types')->name('recruitment.interview-types.')->group(function () {
        Route::get('/', [InterviewTypeController::class, 'index'])->name('index');
        Route::post('/', [InterviewTypeController::class, 'store'])->name('store');
        Route::put('/{interviewtype}', [InterviewTypeController::class, 'update'])->name('update');
        Route::delete('/{interviewtype}', [InterviewTypeController::class, 'destroy'])->name('destroy');
    });

    Route::get('/recruitment/settings', [RecruitmentSettingController::class, 'index'])->name('recruitment.settings.index');
    Route::post('/recruitment/settings', [RecruitmentSettingController::class, 'update'])->name('recruitment.settings.update');

    Route::get('/recruitment/about-company', [RecruitmentSettingController::class, 'aboutCompany'])->name('recruitment.about-company.index');
    Route::post('/recruitment/about-company', [RecruitmentSettingController::class, 'updateAboutCompany'])->name('recruitment.about-company.update');

    Route::get('/recruitment/application-tips', [RecruitmentSettingController::class, 'applicationTips'])->name('recruitment.application-tips.index');
    Route::post('/recruitment/application-tips', [RecruitmentSettingController::class, 'updateApplicationTips'])->name('recruitment.application-tips.update');

    Route::get('/recruitment/what-happens-next', [RecruitmentSettingController::class, 'whatHappensNext'])->name('recruitment.what-happens-next.index');
    Route::post('/recruitment/what-happens-next', [RecruitmentSettingController::class, 'updateWhatHappensNext'])->name('recruitment.what-happens-next.update');

    Route::get('/recruitment/need-help', [RecruitmentSettingController::class, 'needHelp'])->name('recruitment.need-help.index');
    Route::post('/recruitment/need-help', [RecruitmentSettingController::class, 'updateNeedHelp'])->name('recruitment.need-help.update');

    Route::get('/recruitment/tracking-faq', [RecruitmentSettingController::class, 'trackingFaq'])->name('recruitment.tracking-faq.index');
    Route::post('/recruitment/tracking-faq', [RecruitmentSettingController::class, 'updateTrackingFaq'])->name('recruitment.tracking-faq.update');

    Route::get('/recruitment/offer-letter-template', [RecruitmentSettingController::class, 'offerLetterTemplate'])->name('recruitment.offer-letter-template.index');
    Route::post('/recruitment/offer-letter-template', [RecruitmentSettingController::class, 'updateOfferLetterTemplate'])->name('recruitment.offer-letter-template.update');
    Route::get('/recruitment/offer-letter-placeholders', [RecruitmentSettingController::class, 'getPlaceholders'])->name('recruitment.offer-letter-placeholders');

    Route::get('/recruitment/dashboard-welcome-card', [RecruitmentSettingController::class, 'dashboardWelcomeCard'])->name('recruitment.dashboard-welcome-card.index');
    Route::post('/recruitment/dashboard-welcome-card', [RecruitmentSettingController::class, 'updateDashboardWelcomeCard'])->name('recruitment.dashboard-welcome-card.update');



    Route::prefix('recruitment/job-locations')->name('recruitment.job-locations.')->group(function () {
        Route::get('/', [JobLocationController::class, 'index'])->name('index');
        Route::post('/', [JobLocationController::class, 'store'])->name('store');
        Route::put('/{joblocation}', [JobLocationController::class, 'update'])->name('update');
        Route::delete('/{joblocation}', [JobLocationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/custom-questions')->name('recruitment.custom-questions.')->group(function () {
        Route::get('/', [CustomQuestionController::class, 'index'])->name('index');
        Route::post('/', [CustomQuestionController::class, 'store'])->name('store');
        Route::put('/{customquestion}', [CustomQuestionController::class, 'update'])->name('update');
        Route::delete('/{customquestion}', [CustomQuestionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/job-postings')->name('recruitment.job-postings.')->group(function () {
        Route::get('/', [JobPostingController::class, 'index'])->name('index');
        Route::get('/create', [JobPostingController::class, 'create'])->name('create');
        Route::post('/', [JobPostingController::class, 'store'])->name('store');
        Route::get('/{jobposting}/edit', [JobPostingController::class, 'edit'])->name('edit');
        Route::put('/{jobposting}', [JobPostingController::class, 'update'])->name('update');
        Route::post('/{jobposting}/toggle-publish', [JobPostingController::class, 'togglePublish'])->name('toggle-publish');
        Route::delete('/{jobposting}', [JobPostingController::class, 'destroy'])->name('destroy');
        Route::get('/{jobposting}', [JobPostingController::class, 'show'])->name('show');
    });

    // Dependent dropdown routes
    Route::get('recruitment/job-postings/{jobPosting}/settings', [JobPostingController::class, 'getJobPostingSettings'])->name('recruitment.job-postings.settings');

    Route::prefix('recruitment/candidates')->name('recruitment.candidates.')->group(function () {
        Route::get('/', [CandidateController::class, 'index'])->name('index');
        Route::post('/', [CandidateController::class, 'store'])->name('store');
        Route::put('/{candidate}', [CandidateController::class, 'update'])->name('update');
        Route::patch('/{candidate}/update-status', [CandidateController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{candidate}', [CandidateController::class, 'destroy'])->name('destroy');
        Route::get('/{candidate}', [CandidateController::class, 'show'])->name('show');
    });

    // Dependent dropdown routes
    Route::get('recruitment/job-postings/{jobPosting}/custom-questions', [CandidateController::class, 'getCustomQuestionsByJobPosting'])->name('recruitment.job-postings.custom-questions');

    Route::prefix('recruitment/interview-rounds')->name('recruitment.interview-rounds.')->group(function () {
        Route::get('/', [InterviewRoundController::class, 'index'])->name('index');
        Route::post('/', [InterviewRoundController::class, 'store'])->name('store');
        Route::put('/{interviewround}', [InterviewRoundController::class, 'update'])->name('update');
        Route::delete('/{interviewround}', [InterviewRoundController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/interviews')->name('recruitment.interviews.')->group(function () {
        Route::get('/', [InterviewController::class, 'index'])->name('index');
        Route::post('/', [InterviewController::class, 'store'])->name('store');
        Route::put('/{interview}', [InterviewController::class, 'update'])->name('update');
        Route::delete('/{interview}', [InterviewController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes
    Route::get('recruitment/candidates/{candidate}/interview-rounds', [InterviewController::class, 'getInterviewRoundsByCandidate'])->name('recruitment.candidates.interview-rounds');
    Route::get('recruitment/candidates/{candidate}/job-location', [CandidateController::class, 'getCandidateJobLocation'])->name('recruitment.candidates.job-location');

    Route::prefix('recruitment/interview-feedbacks')->name('recruitment.interview-feedbacks.')->group(function () {
        Route::get('/', [InterviewFeedbackController::class, 'index'])->name('index');
        Route::post('/', [InterviewFeedbackController::class, 'store'])->name('store');
        Route::put('/{interviewfeedback}', [InterviewFeedbackController::class, 'update'])->name('update');
        Route::delete('/{interviewfeedback}', [InterviewFeedbackController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/candidate-assessments')->name('recruitment.candidate-assessments.')->group(function () {
        Route::get('/', [CandidateAssessmentController::class, 'index'])->name('index');
        Route::post('/', [CandidateAssessmentController::class, 'store'])->name('store');
        Route::put('/{candidateassessment}', [CandidateAssessmentController::class, 'update'])->name('update');
        Route::delete('/{candidateassessment}', [CandidateAssessmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/offers')->name('recruitment.offers.')->group(function () {
        Route::get('/', [OfferController::class, 'index'])->name('index');
        Route::get('/create', [OfferController::class, 'create'])->name('create');
        Route::post('/', [OfferController::class, 'store'])->name('store');
        Route::get('/{offer}/edit', [OfferController::class, 'edit'])->name('edit');
        Route::put('/{offer}', [OfferController::class, 'update'])->name('update');
        Route::post('/{offer}/approval-status', [OfferController::class, 'updateApprovalStatus'])->name('approval-status');
        Route::post('/{offer}/send-email', [OfferController::class, 'sendEmail'])->name('send-email');
        Route::get('/{offer}/convert-to-employee', [OfferController::class, 'showConvertToEmployeeForm'])->name('convert-to-employee');
        Route::post('/{offer}/convert-to-employee', [OfferController::class, 'convertToEmployee'])->name('convert-to-employee.store');
        Route::get('/download/{encryptedId}', [OfferController::class, 'downloadOfferLetter'])->name('download');
        Route::delete('/{offer}', [OfferController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes
    Route::get('recruitment/candidates/{candidate}/jobs', [OfferController::class, 'getJobsByCandidate'])->name('recruitment.candidates.jobs');

    Route::prefix('recruitment/onboarding-checklists')->name('recruitment.onboarding-checklists.')->group(function () {
        Route::get('/', [OnboardingChecklistController::class, 'index'])->name('index');
        Route::post('/', [OnboardingChecklistController::class, 'store'])->name('store');
        Route::put('/{onboardingchecklist}', [OnboardingChecklistController::class, 'update'])->name('update');
        Route::delete('/{onboardingchecklist}', [OnboardingChecklistController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/checklist-items')->name('recruitment.checklist-items.')->group(function () {
        Route::get('/', [ChecklistItemController::class, 'index'])->name('index');
        Route::post('/', [ChecklistItemController::class, 'store'])->name('store');
        Route::put('/{checklistitem}', [ChecklistItemController::class, 'update'])->name('update');
        Route::delete('/{checklistitem}', [ChecklistItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recruitment/candidate-onboardings')->name('recruitment.candidate-onboardings.')->group(function () {
        Route::get('/', [CandidateOnboardingController::class, 'index'])->name('index');
        Route::post('/', [CandidateOnboardingController::class, 'store'])->name('store');
        Route::get('/{candidateonboarding}/edit', [CandidateOnboardingController::class, 'edit'])->name('edit');
        Route::put('/{candidateonboarding}', [CandidateOnboardingController::class, 'update'])->name('update');
        Route::delete('/{candidateonboarding}', [CandidateOnboardingController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes
    Route::get('recruitment/candidates/{candidate}/checklists', [CandidateOnboardingController::class, 'getChecklistsByCandidate'])->name('recruitment.candidates.checklists');
});

// Public frontend routes with slug support (no authentication required)
Route::middleware(['web', RecruitmentSharedDataMiddleware::class])->group(function () {
    Route::prefix('{userSlug}/careers')->name('recruitment.frontend.careers.')->group(function () {
        Route::get('/', [FrontendController::class, 'jobListings'])->name('jobs.index');
        Route::get('/job/{id}', [FrontendController::class, 'jobDetails'])->name('jobs.show');
        Route::get('/job/{id}/apply', [FrontendController::class, 'jobApply'])->name('jobs.apply');
        Route::get('/job/{id}/terms', [FrontendController::class, 'jobTerms'])->name('jobs.terms');
        Route::post('/job/{id}/apply', [FrontendController::class, 'submitApplication'])->name('jobs.apply.submit');
        Route::get('/application-success/{trackingId?}', [FrontendController::class, 'applicationSuccess'])->name('application.success');
        Route::get('/track', [FrontendController::class, 'trackingForm'])->name('track.form');
        Route::post('/track/verify', [FrontendController::class, 'trackingVerify'])->name('track.verify');
        Route::get('/track/{trackingId}', [FrontendController::class, 'trackingDetails'])->name('track.details');
        Route::post('/offer/{offerId}/respond', [FrontendController::class, 'offerResponse'])->name('offer.respond');
    });

    // Public offer letter download route
    Route::get('/recruitment/offer-letter/download/{encryptedId}', [OfferController::class, 'downloadOfferLetter'])->name('recruitment.offers.public-download');
});
