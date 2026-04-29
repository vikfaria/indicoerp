<?php

namespace Workdo\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\RecruitmentSetting;
use Workdo\Recruitment\Models\OfferLetter;
use Workdo\Recruitment\Events\UpdateBrandSetting;
use Workdo\Recruitment\Events\UpdateAboutCompany;
use Workdo\Recruitment\Events\UpdateApplicationTips;
use Workdo\Recruitment\Events\UpdateWhatHappensNext;
use Workdo\Recruitment\Http\Requests\StoreDashboardWelcomeCardRequest;
use Workdo\Recruitment\Events\UpdateNeedHelp;
use Workdo\Recruitment\Events\UpdateTrackingFaq;
use Workdo\Recruitment\Events\UpdateOfferLetterTemplate;

class RecruitmentSettingController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-recruitment-brand-settings')) {
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->whereIn('key', ['logo_dark', 'favicon', 'title_text', 'footer_text'])
                ->pluck('value', 'key')
                ->toArray();

            $dashboardWelcomeCardSetting = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'dashboard_welcome_card')
                ->first();

            $dashboardWelcomeCard = $dashboardWelcomeCardSetting ? json_decode($dashboardWelcomeCardSetting->value, true) : [
                'card_title' => '',
                'card_description' => '',
                'button_text' => '',
                'button_icon' => ''
            ];

            return Inertia::render('Recruitment/SystemSetup/BrandSettings/Index', [
                'settings' => [
                    'logo_dark' => $settings['logo_dark'] ?? 'packages/workdo/Recruitment/src/Resources/images/logo.png',
                    'favicon' => $settings['favicon'] ?? 'packages/workdo/Recruitment/src/Resources/images/favicon.png',
                    'titleText' => $settings['title_text'] ?? '',
                    'footerText' => $settings['footer_text'] ?? ''
                ],
                'dashboardWelcomeCard' => $dashboardWelcomeCard
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request)
    {
        if (Auth::user()->can('manage-recruitment-brand-settings')) {
            $request->validate([
                'settings.logo_dark' => 'nullable|string',
                'settings.favicon' => 'nullable|string',
                'settings.titleText' => 'required|string|max:255',
                'settings.footerText' => 'required|string|max:500'
            ]);

            $settings = $request->input('settings', []);
            $settingsMap = [
                'logo_dark' => 'logo_dark',
                'favicon' => 'favicon',
                'titleText' => 'title_text',
                'footerText' => 'footer_text'
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMap[$key])) {
                    RecruitmentSetting::updateOrCreate(
                        ['key' => $settingsMap[$key], 'created_by' => creatorId()],
                        ['value' => $value, 'updated_at' => now()]
                    );
                }
            }

            UpdateBrandSetting::dispatch($request);

            return back()->with('success', __('The recruitment settings have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function aboutCompany()
    {
        if(Auth::user()->can('manage-about-company')){
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->whereIn('key', ['our_mission', 'company_size', 'industry'])
                ->pluck('value', 'key')
                ->toArray();

            return Inertia::render('Recruitment/SystemSetup/AboutCompany/Index', [
                'settings' => [
                    'ourMission' => $settings['our_mission'] ?? '',
                    'companySize' => $settings['company_size'] ?? '',
                    'industry' => $settings['industry'] ?? ''
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateAboutCompany(Request $request)
    {
        if(Auth::user()->can('manage-about-company')){
            $request->validate([
                'settings.ourMission' => 'required|string|max:100',
                'settings.companySize' => 'required|string|max:50',
                'settings.industry' => 'required|string|max:60'
            ]);

            $settings = $request->input('settings', []);
            $settingsMap = [
                'ourMission' => 'our_mission',
                'companySize' => 'company_size',
                'industry' => 'industry'
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMap[$key])) {
                    RecruitmentSetting::updateOrCreate(
                        ['key' => $settingsMap[$key], 'created_by' => creatorId()],
                        ['value' => $value, 'updated_at' => now()]
                    );
                }
            }

            UpdateAboutCompany::dispatch($request);

            return back()->with('success', __('The about company settings have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function applicationTips()
    {
        if(Auth::user()->can('manage-application-tips')){
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'application_tips')
                ->value('value');

            $tips = $settings ? json_decode($settings, true) : [['title' => '']];

            return Inertia::render('Recruitment/SystemSetup/ApplicationTips/Index', [
                'settings' => [
                    'tips' => $tips
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateApplicationTips(Request $request)
    {
        if(Auth::user()->can('manage-application-tips')){
            $request->validate([
                'settings.tips' => 'required|array|min:1',
                'settings.tips.*.title' => 'required|string|max:100'
            ]);

            $tips = $request->input('settings.tips', []);

            RecruitmentSetting::updateOrCreate(
                ['key' => 'application_tips', 'created_by' => creatorId()],
                ['value' => json_encode($tips), 'updated_at' => now()]
            );

            UpdateApplicationTips::dispatch($request);

            return back()->with('success', __('The application tips have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function whatHappensNext()
    {
        if(Auth::user()->can('manage-what-happens-next')){
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'what_happens_next')
                ->value('value');

            $steps = $settings ? json_decode($settings, true) : [['title' => '', 'description' => '', 'icon' => '']];

            return Inertia::render('Recruitment/SystemSetup/WhatHappensNext/Index', [
                'settings' => [
                    'steps' => $steps
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateWhatHappensNext(Request $request)
    {
        if(Auth::user()->can('manage-what-happens-next')){
            $request->validate([
                'settings.steps' => 'required|array|min:1',
                'settings.steps.*.title' => 'required|string|max:50',
                'settings.steps.*.description' => 'required|string|max:100',
                'settings.steps.*.icon' => 'required|string|max:30'
            ]);

            $steps = $request->input('settings.steps', []);

            RecruitmentSetting::updateOrCreate(
                ['key' => 'what_happens_next', 'created_by' => creatorId()],
                ['value' => json_encode($steps), 'updated_at' => now()]
            );

            UpdateWhatHappensNext::dispatch($request);

            return back()->with('success', __('What happens next settings have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function needHelp()
    {
        if(Auth::user()->can('manage-need-help')){
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->whereIn('key', ['need_help_description', 'need_help_email', 'need_help_phone'])
                ->pluck('value', 'key')
                ->toArray();

            return Inertia::render('Recruitment/SystemSetup/NeedHelp/Index', [
                'settings' => [
                    'description' => $settings['need_help_description'] ?? '',
                    'email' => $settings['need_help_email'] ?? '',
                    'phone' => $settings['need_help_phone'] ?? ''
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateNeedHelp(Request $request)
    {
        if(Auth::user()->can('manage-need-help')){
            $request->validate([
                'settings.description' => 'required|string|max:100',
                'settings.email' => 'required|email|max:50',
                'settings.phone' => 'required|string|max:20'
            ]);

            $settings = $request->input('settings', []);
            $settingsMap = [
                'description' => 'need_help_description',
                'email' => 'need_help_email',
                'phone' => 'need_help_phone'
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMap[$key])) {
                    RecruitmentSetting::updateOrCreate(
                        ['key' => $settingsMap[$key], 'created_by' => creatorId()],
                        ['value' => $value, 'updated_at' => now()]
                    );
                }
            }

            UpdateNeedHelp::dispatch($request);

            return back()->with('success', __('Need help settings have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function trackingFaq()
    {
        if(Auth::user()->can('manage-tracking-faq')){
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'tracking_faq')
                ->value('value');

            $faqs = $settings ? json_decode($settings, true) : [['question' => '', 'answer' => '']];

            return Inertia::render('Recruitment/SystemSetup/TrackingFaq/Index', [
                'settings' => [
                    'faqs' => $faqs
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateTrackingFaq(Request $request)
    {
        if(Auth::user()->can('manage-tracking-faq')){
            $request->validate([
                'settings.faqs' => 'required|array|min:1',
                'settings.faqs.*.question' => 'required|string|max:100',
                'settings.faqs.*.answer' => 'required|string|max:200'
            ]);

            $faqs = $request->input('settings.faqs', []);

            RecruitmentSetting::updateOrCreate(
                ['key' => 'tracking_faq', 'created_by' => creatorId()],
                ['value' => json_encode($faqs), 'updated_at' => now()]
            );

            UpdateTrackingFaq::dispatch($request);

            return back()->with('success', __('Tracking FAQ settings have been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function offerLetterTemplate()
    {
        if(Auth::user()->can('manage-offer-letter-template')){
            $templates = OfferLetter::where('created_by', creatorId())
                ->pluck('content', 'lang')
                ->toArray();

            if (empty($templates)) {
                OfferLetter::defaultOfferLetter(creatorId());
                $templates = OfferLetter::where('created_by', creatorId())
                    ->pluck('content', 'lang')
                    ->toArray();
            }

            return Inertia::render('Recruitment/SystemSetup/OfferLetterTemplate/Index', [
                'settings' => [
                    'templates' => $templates,
                    'languages' => OfferLetter::getLanguages()
                ]
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateOfferLetterTemplate(Request $request)
    {
        if(Auth::user()->can('manage-offer-letter-template')){
            $request->validate([
                'settings.templates' => 'required|array',
                'settings.templates.*' => 'required|string'
            ]);

            $templates = $request->input('settings.templates', []);

            foreach ($templates as $lang => $content) {
                OfferLetter::updateOrCreate(
                    ['lang' => $lang, 'created_by' => creatorId()],
                    ['content' => $content, 'creator_id' => Auth::id(), 'updated_at' => now()]
                );
            }

            UpdateOfferLetterTemplate::dispatch($request);

            return back()->with('success', __('Offer letter template has been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getPlaceholders()
    {
        return response()->json([
            'placeholders' => [
                '{applicant_name}' => 'Applicant Name',
                '{app_name}' => 'Application Name',
                '{company_name}' => 'Company Name',
                '{job_title}' => 'Job Title',
                '{job_type}' => 'Job Type',
                '{start_date}' => 'Start Date',
                '{workplace_location}' => 'Workplace Location',
                '{days_of_week}' => 'Days of Week',
                '{salary}' => 'Salary',
                '{salary_type}' => 'Salary Type',
                '{salary_duration}' => 'Salary Duration',
                '{next_pay_period}' => 'Next Pay Period',
                '{offer_expiration_date}' => 'Offer Expiration Date'
            ]
        ]);
    }

    public function dashboardWelcomeCard()
    {
        if (Auth::user()->can('manage-recruitment-dashboard-welcome-card')) {
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'dashboard_welcome_card')
                ->first();

            $dashboardWelcomeCard = $settings ? json_decode($settings->value, true) : [
                'card_title' => '',
                'card_description' => '',
                'button_text' => '',
                'button_icon' => ''
            ];

            return Inertia::render('Recruitment/SystemSetup/DashboardWelcomeCard/Index', [
                'dashboardWelcomeCard' => $dashboardWelcomeCard
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateDashboardWelcomeCard(StoreDashboardWelcomeCardRequest $request)
    {
        if (Auth::user()->can('manage-recruitment-dashboard-welcome-card')) {
            $validated = $request->validated();

            RecruitmentSetting::updateOrCreate(
                ['key' => 'dashboard_welcome_card', 'created_by' => creatorId()],
                ['value' => json_encode($validated)]
            );

            return back()->with('success', __('The dashboard welcome card has been updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
