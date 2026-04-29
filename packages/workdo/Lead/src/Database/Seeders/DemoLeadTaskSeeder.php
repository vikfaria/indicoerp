<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\LeadTask;
use Workdo\Lead\Models\Lead;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoLeadTaskSeeder extends Seeder
{
    public function run($userId): void
    {
        if (LeadTask::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId))
        {
            $leads = Lead::where('created_by', $userId)->get();

            if ($leads->isEmpty()) {
                return;
            }

            // Marketing Pipeline (90 tasks)
            $marketingTasks = [
                'Send welcome email to new prospect', 'Schedule initial discovery call', 'Add to email nurture sequence',
                'Research company background and needs', 'Send product information packet', 'Follow up on email engagement',
                'Schedule product demonstration call', 'Send case study materials', 'Connect on LinkedIn platform',
                'Share industry insights report', 'Schedule needs assessment meeting', 'Send pricing information sheet',
                'Follow up on demo feedback', 'Schedule stakeholder introduction call', 'Send proposal draft document',
                'Conduct competitive analysis research', 'Schedule technical requirements review', 'Send implementation timeline overview',
                'Follow up on proposal questions', 'Schedule contract discussion meeting', 'Send reference customer contacts',
                'Conduct ROI analysis presentation', 'Schedule final decision meeting', 'Send contract terms document',
                'Follow up on contract review', 'Schedule onboarding kickoff meeting', 'Send welcome package materials',
                'Conduct post-conversion check-in call', 'Schedule success metrics review', 'Send customer satisfaction survey',
                'Research prospect social media activity', 'Send personalized video message', 'Schedule coffee meeting invitation',
                'Share relevant blog articles', 'Send webinar invitation link', 'Follow up on webinar attendance',
                'Schedule one-on-one consultation call', 'Send industry benchmark report', 'Connect with decision makers',
                'Share customer success stories', 'Schedule group presentation meeting', 'Send detailed feature comparison',
                'Follow up on feature questions', 'Schedule pilot program discussion', 'Send pilot program proposal',
                'Conduct pilot program kickoff', 'Schedule pilot review meeting', 'Send pilot results summary',
                'Follow up on pilot feedback', 'Schedule expansion discussion meeting', 'Send expansion proposal document',
                'Research additional use cases', 'Send integration possibilities overview', 'Schedule technical integration call',
                'Follow up on integration requirements', 'Schedule implementation planning meeting', 'Send project timeline document',
                'Conduct stakeholder alignment meeting', 'Schedule budget approval discussion', 'Send budget justification materials',
                'Follow up on budget questions', 'Schedule final approval meeting', 'Send contract execution documents',
                'Research market trends analysis', 'Send competitive landscape report', 'Schedule strategic planning session',
                'Follow up on strategic initiatives', 'Schedule quarterly business review', 'Send performance metrics dashboard',
                'Conduct customer advocacy discussion', 'Schedule reference call participation', 'Send testimonial request form',
                'Follow up on testimonial submission', 'Schedule case study interview', 'Send case study draft',
                'Research expansion opportunities analysis', 'Send upsell proposal document', 'Schedule renewal discussion meeting',
                'Follow up on renewal terms', 'Schedule contract extension meeting', 'Send loyalty program invitation',
                'Conduct satisfaction survey call', 'Schedule feedback collection session', 'Send improvement recommendations report',
                'Follow up on recommendation implementation', 'Schedule success celebration meeting', 'Send achievement recognition certificate',
                'Research partnership opportunities analysis', 'Send partnership proposal document', 'Schedule partnership discussion meeting',
                'Follow up on partnership terms', 'Schedule joint venture planning', 'Send collaboration agreement draft',
                'Conduct market research survey', 'Schedule focus group session', 'Send research findings summary',
                'Follow up on research insights', 'Schedule product development meeting', 'Send feature request documentation'
            ];

            // Lead Qualification Pipeline (90 tasks)
            $qualificationTasks = [
                'Review lead contact information thoroughly', 'Verify company details and background', 'Check lead source attribution',
                'Assess initial interest level indicators', 'Validate contact information accuracy', 'Research company size and structure',
                'Evaluate budget potential indicators', 'Check decision maker identification', 'Assess timeline requirements urgency',
                'Conduct initial qualification screening call', 'Verify business needs alignment', 'Check solution fit assessment',
                'Evaluate technical requirements compatibility', 'Assess implementation readiness level', 'Check compliance requirements alignment',
                'Conduct detailed needs assessment interview', 'Verify pain points identification', 'Check current solution evaluation',
                'Assess change management readiness', 'Evaluate stakeholder buy-in level', 'Check project approval process',
                'Conduct budget authority verification call', 'Verify decision making process', 'Check procurement requirements understanding',
                'Assess competitive evaluation status', 'Evaluate vendor selection criteria', 'Check evaluation timeline constraints',
                'Conduct technical fit assessment meeting', 'Verify integration requirements compatibility', 'Check security requirements alignment',
                'Assess scalability needs evaluation', 'Evaluate performance requirements fit', 'Check customization needs assessment',
                'Conduct stakeholder alignment meeting', 'Verify champion identification process', 'Check internal advocacy development',
                'Assess political landscape navigation', 'Evaluate change resistance factors', 'Check communication strategy alignment',
                'Conduct risk assessment evaluation', 'Verify mitigation strategies development', 'Check contingency planning requirements',
                'Assess project success criteria', 'Evaluate measurement methodology alignment', 'Check reporting requirements compatibility',
                'Conduct final qualification review meeting', 'Verify all criteria satisfaction', 'Check qualification score calculation',
                'Assess recommendation development process', 'Evaluate next steps planning', 'Check handoff documentation preparation',
                'Document qualification decision rationale', 'Prepare qualification summary report', 'Update lead scoring metrics',
                'Schedule approval committee presentation', 'Prepare executive summary document', 'Conduct final recommendation meeting',
                'Follow up on approval decision', 'Schedule next phase planning', 'Send approved lead handoff',
                'Research industry specific requirements', 'Verify regulatory compliance needs', 'Check certification requirements alignment',
                'Assess data security requirements', 'Evaluate privacy compliance needs', 'Check audit trail requirements',
                'Conduct reference check verification', 'Verify customer testimonial validation', 'Check case study relevance',
                'Assess proof of concept requirements', 'Evaluate pilot program feasibility', 'Check trial period parameters',
                'Conduct competitive analysis comparison', 'Verify differentiation factors identification', 'Check value proposition alignment',
                'Assess pricing model compatibility', 'Evaluate contract terms acceptability', 'Check negotiation parameters identification',
                'Conduct implementation timeline assessment', 'Verify resource availability confirmation', 'Check project milestone alignment',
                'Assess training requirements evaluation', 'Evaluate support needs identification', 'Check maintenance requirements compatibility',
                'Conduct post-implementation planning review', 'Verify success metrics definition', 'Check ongoing relationship expectations',
                'Assess expansion potential evaluation', 'Evaluate long-term partnership viability', 'Check strategic alignment assessment',
                'Document lessons learned analysis', 'Prepare process improvement recommendations', 'Update qualification methodology refinements'
            ];

            // Sales Pipeline (90 tasks) - if Sales pipeline exists
            $salesTasks = [
                'Initial contact call with prospect', 'Send introduction email and company overview', 'Schedule discovery meeting appointment',
                'Research prospect business requirements thoroughly', 'Prepare customized presentation materials', 'Conduct needs assessment interview',
                'Send detailed proposal document', 'Follow up on proposal feedback', 'Schedule product demonstration session',
                'Prepare technical specifications document', 'Conduct stakeholder presentation meeting', 'Send pricing quotation details',
                'Follow up on pricing questions', 'Schedule contract negotiation meeting', 'Prepare contract terms document',
                'Conduct final negotiation session', 'Send revised contract proposal', 'Follow up on contract review',
                'Schedule contract signing appointment', 'Prepare onboarding documentation package', 'Conduct project kickoff meeting',
                'Send welcome and next steps', 'Schedule implementation planning session', 'Prepare project timeline document',
                'Conduct team introduction meeting', 'Send training materials package', 'Schedule training session appointment',
                'Follow up on training completion', 'Schedule go-live planning meeting', 'Prepare go-live checklist document',
                'Conduct system testing session', 'Send testing results summary', 'Schedule final approval meeting',
                'Follow up on final approval', 'Schedule launch celebration event', 'Send launch announcement communication',
                'Conduct post-launch review meeting', 'Send success metrics report', 'Schedule quarterly business review',
                'Follow up on satisfaction feedback', 'Schedule expansion discussion meeting', 'Send upsell proposal document',
                'Research additional opportunities thoroughly', 'Prepare expansion business case', 'Conduct ROI analysis presentation',
                'Send competitive analysis report', 'Schedule strategic planning session', 'Follow up on strategic initiatives',
                'Conduct partnership discussion meeting', 'Send partnership proposal document', 'Schedule joint planning session',
                'Follow up on partnership terms', 'Schedule collaboration kickoff meeting', 'Send collaboration agreement draft',
                'Conduct market analysis research', 'Send industry insights report', 'Schedule thought leadership session',
                'Follow up on market trends', 'Schedule innovation discussion meeting', 'Send innovation proposal document',
                'Research technology advancement opportunities', 'Prepare technology roadmap presentation', 'Conduct future planning session',
                'Send future state vision', 'Schedule transformation planning meeting', 'Follow up on transformation roadmap',
                'Conduct change management session', 'Send change communication plan', 'Schedule stakeholder alignment meeting',
                'Follow up on stakeholder feedback', 'Schedule success celebration event', 'Send achievement recognition letter',
                'Conduct lessons learned session', 'Send best practices documentation', 'Schedule knowledge transfer meeting',
                'Follow up on knowledge retention', 'Schedule continuous improvement session', 'Send improvement recommendations report',
                'Research optimization opportunities analysis', 'Prepare optimization business case', 'Conduct efficiency review meeting',
                'Send efficiency metrics report', 'Schedule performance review session', 'Follow up on performance improvements',
                'Conduct customer advocacy discussion', 'Send testimonial request form', 'Schedule reference call participation',
                'Follow up on reference materials', 'Schedule case study interview', 'Send case study draft document',
                'Research renewal opportunities analysis', 'Prepare renewal proposal document', 'Conduct renewal discussion meeting',
                'Follow up on renewal terms', 'Schedule contract extension meeting', 'Send loyalty program invitation'
            ];

            $allTasks = array_merge($salesTasks, $marketingTasks, $qualificationTasks);
            $priorities = ['Low', 'Medium', 'High'];
            $taskIndex = 0;

            foreach ($leads as $leadIndex => $lead) {
                $pipelineName = $lead->pipeline->name ?? 'Marketing';
                $stageName = strtolower($lead->stage->name ?? 'prospect');
                $leadDate = Carbon::parse($lead->date);

                // Generate 1-3 tasks per lead
                $taskCount = rand(1, 3);

                $now = Carbon::now();
                $weekStart = $now->copy()->startOfWeek();
                $weekEnd = $now->copy()->endOfWeek();

                for ($i = 0; $i < $taskCount; $i++) {
                    // Task dates around lead follow-up date
                    $taskDate = $leadDate->copy()->subDays(rand(1, 3))->addDays($i);

                    // Determine status based on lead follow-up date logic
                    $status = 'On Going';

                    if ($leadDate->gt($weekEnd)) {
                        // More future (beyond next week) - task is ongoing
                        $status = 'On Going';
                    } elseif ($leadDate->between($now, $weekEnd)) {
                        // Current week future - likely completed recently
                        $status = rand(0, 1) ? 'Complete' : 'On Going';
                    } elseif ($leadDate->lt($weekStart->copy()->subWeek())) {
                        // More past - always completed
                        $status = 'Complete';
                    } elseif ($leadDate->between($weekStart, $now)) {
                        // Current week past - might still be ongoing
                        $status = rand(0, 1) ? 'On Going' : 'Complete';
                    }

                    // Priority based on all 3 pipeline stages (Sales, Marketing, Lead Qualification)
                    $priority = 'Medium';
                    // High priority: Early and critical stages
                    if (in_array($stageName, ['draft', 'prospect', 'unqualified', 'qualified'])) {
                        $priority = 'High';
                    }
                    // Low priority: Final/closed stages
                    elseif (in_array($stageName, ['accepted', 'declined', 'converted', 'approved', 'rejected'])) {
                        $priority = 'Low';
                    }
                    // Medium priority: Active middle stages (sent, open, revised, contacted, in review, engaged)

                    LeadTask::create([
                        'lead_id' => $lead->id,
                        'name' => $allTasks[$taskIndex % count($allTasks)],
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
