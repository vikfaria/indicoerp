<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\Deal;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDealTaskSeeder extends Seeder
{
    public function run($userId): void
    {
        if (DealTask::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId))
        {
            // Only get deals that are NOT converted from leads (they already have tasks)
            $convertedDealIds = \Workdo\Lead\Models\Lead::where('created_by', $userId)
                ->where('is_converted', '>', 0)
                ->pluck('is_converted')
                ->toArray();

            $deals = Deal::where('created_by', $userId)
                ->whereNotIn('id', $convertedDealIds)
                ->get();

            if ($deals->isEmpty()) {
                return;
            }

            // Marketing Pipeline Deal Tasks (90 tasks)
            $marketingDealTasks = [
                'Launch brand awareness campaign', 'Coordinate creative asset development', 'Schedule content strategy meeting',
                'Prepare social media campaign plan', 'Conduct audience targeting analysis', 'Schedule influencer outreach coordination',
                'Send campaign performance report', 'Coordinate A/B testing setup', 'Schedule conversion optimization meeting',
                'Prepare email marketing automation', 'Conduct lead nurturing sequence setup', 'Schedule customer journey mapping',
                'Send marketing qualified lead report', 'Coordinate sales handoff process', 'Schedule lead scoring calibration',
                'Prepare marketing attribution analysis', 'Conduct ROI measurement setup', 'Schedule performance tracking meeting',
                'Send competitive intelligence report', 'Coordinate market research analysis', 'Schedule positioning strategy session',
                'Prepare product launch campaign', 'Conduct go-to-market planning', 'Schedule launch event coordination',
                'Send media kit preparation', 'Coordinate press release distribution', 'Schedule journalist briefing meeting',
                'Prepare thought leadership content', 'Conduct expert interview coordination', 'Schedule webinar series planning',
                'Send content calendar development', 'Coordinate editorial planning session', 'Schedule SEO optimization meeting',
                'Prepare website conversion optimization', 'Conduct user experience analysis', 'Schedule landing page testing',
                'Send marketing automation setup', 'Coordinate CRM integration planning', 'Schedule data synchronization meeting',
                'Prepare customer segmentation analysis', 'Conduct persona development session', 'Schedule targeting refinement meeting',
                'Send personalization strategy plan', 'Coordinate dynamic content setup', 'Schedule behavioral tracking implementation',
                'Prepare marketing technology stack', 'Conduct tool integration planning', 'Schedule platform optimization meeting',
                'Send performance dashboard setup', 'Coordinate reporting automation', 'Schedule analytics review session',
                'Prepare customer acquisition strategy', 'Conduct channel optimization analysis', 'Schedule budget allocation meeting',
                'Send campaign budget proposal', 'Coordinate resource planning session', 'Schedule team capacity meeting',
                'Prepare vendor selection process', 'Conduct agency evaluation review', 'Schedule partnership negotiation meeting',
                'Send collaboration agreement draft', 'Coordinate joint campaign planning', 'Schedule co-marketing strategy session',
                'Prepare event marketing campaign', 'Conduct trade show planning', 'Schedule booth design coordination',
                'Send sponsorship proposal development', 'Coordinate speaking opportunity pursuit', 'Schedule networking event planning',
                'Prepare customer retention campaign', 'Conduct loyalty program development', 'Schedule engagement strategy meeting',
                'Send referral program launch', 'Coordinate advocacy program setup', 'Schedule community building session',
                'Prepare customer success story', 'Conduct testimonial collection campaign', 'Schedule case study development',
                'Send video content production', 'Coordinate multimedia campaign creation', 'Schedule creative review meeting',
                'Prepare interactive content strategy', 'Conduct engagement optimization', 'Schedule user-generated content campaign',
                'Send influencer partnership proposal', 'Coordinate brand ambassador program', 'Schedule collaboration planning meeting',
                'Prepare market expansion strategy', 'Conduct geographic targeting analysis', 'Schedule localization planning session',
                'Send international campaign proposal', 'Coordinate cross-cultural adaptation', 'Schedule global strategy meeting',
                'Prepare seasonal campaign planning', 'Conduct holiday marketing strategy', 'Schedule promotional calendar development',
                'Send campaign performance optimization', 'Coordinate continuous improvement', 'Schedule strategy refinement meeting'
            ];

            // Lead Qualification Pipeline Deal Tasks (90 tasks)
            $qualificationDealTasks = [
                'Complete deal qualification assessment', 'Verify client budget authorization', 'Schedule decision maker meeting',
                'Conduct technical feasibility review', 'Prepare implementation scope document', 'Schedule resource requirement analysis',
                'Send detailed project proposal', 'Coordinate stakeholder approval process', 'Schedule contract negotiation session',
                'Prepare legal terms review', 'Conduct compliance verification check', 'Schedule risk assessment meeting',
                'Send security clearance documentation', 'Coordinate background verification process', 'Schedule due diligence review',
                'Prepare financial capability assessment', 'Conduct credit worthiness evaluation', 'Schedule payment terms negotiation',
                'Send insurance requirement verification', 'Coordinate liability coverage review', 'Schedule indemnification discussion',
                'Prepare service level agreement', 'Conduct performance metrics definition', 'Schedule quality assurance planning',
                'Send delivery timeline confirmation', 'Coordinate milestone planning session', 'Schedule progress tracking setup',
                'Prepare change management process', 'Conduct scope modification procedures', 'Schedule variation order protocol',
                'Send communication plan establishment', 'Coordinate reporting structure setup', 'Schedule status update meetings',
                'Prepare escalation procedure document', 'Conduct issue resolution process', 'Schedule conflict management planning',
                'Send training requirement analysis', 'Coordinate skill development planning', 'Schedule knowledge transfer session',
                'Prepare documentation standard setup', 'Conduct version control establishment', 'Schedule document management meeting',
                'Send intellectual property agreement', 'Coordinate confidentiality setup', 'Schedule non-disclosure signing',
                'Prepare data protection compliance', 'Conduct privacy policy review', 'Schedule GDPR compliance meeting',
                'Send regulatory requirement check', 'Coordinate industry standard compliance', 'Schedule certification planning',
                'Prepare audit trail establishment', 'Conduct record keeping setup', 'Schedule compliance monitoring meeting',
                'Send vendor management setup', 'Coordinate subcontractor approval', 'Schedule third-party evaluation',
                'Prepare quality control process', 'Conduct testing procedure setup', 'Schedule validation planning meeting',
                'Send acceptance criteria definition', 'Coordinate sign-off process setup', 'Schedule approval workflow meeting',
                'Prepare handover procedure document', 'Conduct transition planning session', 'Schedule go-live preparation meeting',
                'Send post-implementation support plan', 'Coordinate maintenance schedule setup', 'Schedule ongoing support meeting',
                'Prepare warranty terms definition', 'Conduct guarantee period setup', 'Schedule service commitment meeting',
                'Send renewal option documentation', 'Coordinate extension planning session', 'Schedule future opportunity meeting',
                'Prepare relationship management plan', 'Conduct partnership development', 'Schedule strategic alignment meeting',
                'Send collaboration framework setup', 'Coordinate joint planning session', 'Schedule mutual benefit meeting',
                'Prepare success measurement criteria', 'Conduct KPI establishment session', 'Schedule performance review meeting',
                'Send continuous improvement plan', 'Coordinate optimization process setup', 'Schedule enhancement planning meeting',
                'Prepare innovation opportunity analysis', 'Conduct technology advancement review', 'Schedule future development meeting',
                'Send market evolution assessment', 'Coordinate trend analysis session', 'Schedule strategic planning meeting',
                'Prepare competitive positioning review', 'Conduct differentiation analysis', 'Schedule value proposition meeting',
                'Send customer satisfaction survey', 'Coordinate feedback collection process', 'Schedule improvement planning meeting',
                'Prepare lessons learned documentation', 'Conduct best practice identification', 'Schedule knowledge sharing meeting',
                'Send process optimization report', 'Coordinate efficiency improvement', 'Schedule methodology refinement meeting'
            ];

             // Sales Pipeline Deal Tasks (90 tasks)
            $salesDealTasks = [
                'Finalize contract terms and conditions', 'Prepare implementation project plan', 'Schedule client onboarding session',
                'Conduct technical requirements review', 'Prepare system integration documentation', 'Schedule stakeholder kickoff meeting',
                'Send project timeline and milestones', 'Coordinate resource allocation planning', 'Schedule training needs assessment',
                'Prepare user access and permissions', 'Conduct security compliance review', 'Schedule data migration planning',
                'Coordinate vendor integration setup', 'Prepare go-live readiness checklist', 'Schedule system testing session',
                'Conduct user acceptance testing', 'Prepare production deployment plan', 'Schedule launch coordination meeting',
                'Send post-implementation support plan', 'Coordinate success metrics tracking', 'Schedule quarterly review meeting',
                'Prepare expansion opportunity analysis', 'Conduct customer satisfaction survey', 'Schedule renewal discussion meeting',
                'Send contract amendment proposal', 'Coordinate additional service setup', 'Schedule strategic planning session',
                'Prepare ROI analysis report', 'Conduct performance optimization review', 'Schedule enhancement planning meeting',
                'Send upgrade recommendation proposal', 'Coordinate integration enhancement setup', 'Schedule advanced training session',
                'Prepare compliance audit documentation', 'Conduct security assessment review', 'Schedule risk mitigation planning',
                'Send disaster recovery plan', 'Coordinate backup system setup', 'Schedule business continuity testing',
                'Prepare vendor management documentation', 'Conduct service level review', 'Schedule contract negotiation meeting',
                'Send pricing optimization proposal', 'Coordinate cost reduction analysis', 'Schedule budget planning session',
                'Prepare financial reporting setup', 'Conduct audit trail configuration', 'Schedule compliance verification meeting',
                'Send regulatory update notification', 'Coordinate policy implementation', 'Schedule staff training coordination',
                'Prepare knowledge transfer documentation', 'Conduct handover planning session', 'Schedule transition management meeting',
                'Send change management communication', 'Coordinate stakeholder alignment', 'Schedule project closure meeting',
                'Prepare lessons learned documentation', 'Conduct post-project review', 'Schedule success celebration event',
                'Send client testimonial request', 'Coordinate reference case development', 'Schedule partnership discussion meeting',
                'Prepare strategic alliance proposal', 'Conduct market expansion analysis', 'Schedule innovation planning session',
                'Send technology roadmap update', 'Coordinate future enhancement planning', 'Schedule long-term strategy meeting',
                'Prepare competitive analysis report', 'Conduct market positioning review', 'Schedule brand alignment meeting',
                'Send marketing collaboration proposal', 'Coordinate joint venture planning', 'Schedule co-marketing session',
                'Prepare thought leadership content', 'Conduct industry analysis review', 'Schedule expert panel participation',
                'Send conference speaking proposal', 'Coordinate webinar planning session', 'Schedule content creation meeting',
                'Prepare case study documentation', 'Conduct success story development', 'Schedule media interview coordination',
                'Send press release preparation', 'Coordinate public relations campaign', 'Schedule analyst briefing meeting',
                'Prepare investor presentation materials', 'Conduct board meeting preparation', 'Schedule executive briefing session',
                'Send quarterly business review', 'Coordinate performance dashboard setup', 'Schedule metrics analysis meeting',
                'Prepare optimization recommendation report', 'Conduct efficiency improvement review', 'Schedule process enhancement meeting',
                'Send automation opportunity analysis', 'Coordinate workflow optimization', 'Schedule digital transformation planning'
            ];

            $allDealTasks = array_merge($salesDealTasks, $marketingDealTasks, $qualificationDealTasks);
            $priorities = ['Low', 'Medium', 'High'];
            $taskIndex = 0;

            foreach ($deals as $dealIndex => $deal) {
                $pipelineName = $deal->pipeline->name ?? 'Sales';
                $stageName = strtolower($deal->stage->name ?? 'initial contact');
                $dealDate = Carbon::parse($deal->created_at);

                // Generate 1-3 tasks per deal
                $taskCount = rand(1, 3);

                $now = Carbon::now();
                $weekStart = $now->copy()->startOfWeek();
                $weekEnd = $now->copy()->endOfWeek();

                for ($i = 0; $i < $taskCount; $i++) {
                    // Task dates after deal creation date
                    $taskDate = $dealDate->copy()->addDays(rand(1, 7))->addDays($i);

                    // Determine status based on deal stage and date logic
                    $status = 'On Going';

                    if ($dealDate->gt($weekEnd)) {
                        // Future deals - tasks are ongoing
                        $status = 'On Going';
                    } elseif ($dealDate->between($now, $weekEnd)) {
                        // Current week deals - mixed status
                        $status = rand(0, 1) ? 'Completed' : 'On Going';
                    } elseif ($dealDate->lt($weekStart->copy()->subWeek())) {
                        // Past deals - mostly completed
                        $status = rand(0, 2) ? 'Completed' : 'On Going';
                    } elseif ($dealDate->between($weekStart, $now)) {
                        // Recent deals - likely ongoing
                        $status = rand(0, 1) ? 'On Going' : 'Completed';
                    }

                    // Priority based on deal stage and pipeline
                    $priority = 'Medium';

                    // High priority: Early critical stages
                    if (in_array($stageName, ['initial contact', 'qualification', 'proposal', 'negotiation'])) {
                        $priority = 'High';
                    }
                    // Low priority: Final/closed stages
                    elseif (in_array($stageName, ['won', 'lost', 'closed', 'completed'])) {
                        $priority = 'Low';
                    }
                    // Medium priority: Active middle stages

                    // Adjust priority based on deal value
                    if ($deal->price > 100000) {
                        $priority = 'High';
                    } elseif ($deal->price < 25000) {
                        $priority = 'Low';
                    }

                    DealTask::create([
                        'deal_id' => $deal->id,
                        'name' => $allDealTasks[$taskIndex % count($allDealTasks)],
                        'date' => $taskDate->format('Y-m-d'),
                        'time' => sprintf('%02d:%02d:%02d', rand(0, 23), rand(0, 59),rand(0, 59)),
                        'priority' => $priority,
                        'status' => $status,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ]);

                    $taskIndex++;
                }
            }
        }
    }
}
