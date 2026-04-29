<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\DealCall;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\UserDeal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDealCallSeeder extends Seeder
{
    public function run($userId): void
    {
        if (DealCall::whereHas('deal', function($query) use ($userId) {
            $query->where('created_by', $userId);
        })->exists()) {
            return;
        }
        if (!empty($userId)) {
            // Only get deals that are NOT converted from leads (they already have DealCalls)
            $convertedDealIds = \Workdo\Lead\Models\Lead::where('created_by', $userId)
                ->where('is_converted', '>', 0)
                ->pluck('is_converted')
                ->toArray();

            $deals = Deal::where('created_by', $userId)
                ->whereNotIn('id', $convertedDealIds)
                ->with(['pipeline', 'stage', 'userDeals'])
                ->get();

            if ($deals->isEmpty()) {
                return;
            }

            // Pipeline and stage-specific call subjects (using DealStage names)
            $callSubjects = [
                'Marketing' => [
                    'Campaign Launch' => ['Campaign Strategy Discussion', 'Creative Brief Review Call', 'Launch Timeline Coordination', 'Target Audience Analysis', 'Channel Selection Meeting', 'Budget Allocation Review', 'Creative Asset Planning', 'Performance Metrics Setup', 'Launch Sequence Planning', 'Risk Mitigation Discussion'],
                    'Lead Generation' => ['Lead Quality Assessment', 'Conversion Rate Review', 'Targeting Optimization Call', 'Source Performance Analysis', 'Cost Per Lead Review', 'Funnel Optimization Meeting', 'A/B Testing Strategy', 'Lead Scoring Calibration', 'Attribution Model Review', 'Campaign Performance Analysis'],
                    'Nurturing' => ['Content Strategy Discussion', 'Engagement Analysis Call', 'Nurture Sequence Review', 'Personalization Strategy Meeting', 'Automation Workflow Planning', 'Segmentation Strategy Review', 'Email Performance Analysis', 'Content Calendar Planning', 'Behavioral Trigger Setup', 'Lead Progression Tracking'],
                    'Qualification' => ['Lead Scoring Review', 'MQL Assessment Call', 'Handoff Preparation Meeting', 'Qualification Criteria Review', 'Sales Readiness Assessment', 'Lead Intelligence Gathering', 'Prospect Research Review', 'Engagement History Analysis', 'Buying Signal Identification', 'Handoff Documentation Prep'],
                    'Handoff' => ['Sales Handoff Call', 'Lead Transfer Meeting', 'Qualification Summary Review', 'Account Background Briefing', 'Opportunity Assessment Call', 'Sales Strategy Discussion', 'Next Steps Planning', 'Follow-up Schedule Coordination', 'Success Metrics Alignment', 'Feedback Loop Establishment']
                ],
                'Lead Qualification' => [
                    'Initial Contact' => ['Lead Verification Call', 'Initial Assessment Meeting', 'Contact Information Validation', 'Interest Level Confirmation', 'Basic Qualification Check', 'Company Profile Review', 'Decision Timeline Discussion', 'Budget Range Inquiry', 'Authority Level Assessment', 'Next Steps Planning'],
                    'Needs Assessment' => ['Requirements Analysis Call', 'Pain Point Discovery Session', 'Solution Fit Discussion', 'Current Process Review', 'Challenge Identification Meeting', 'Goal Setting Discussion', 'Success Criteria Definition', 'Constraint Analysis Call', 'Priority Assessment Meeting', 'Impact Evaluation Session'],
                    'Solution Fit' => ['Technical Compatibility Review', 'Feature Alignment Call', 'Integration Assessment', 'Scalability Discussion', 'Performance Requirements Review', 'Security Compliance Check', 'Customization Needs Analysis', 'Implementation Feasibility Call', 'Resource Requirements Assessment', 'Timeline Compatibility Review'],
                    'Proposal Sent' => ['Proposal Clarification Call', 'Questions and Answers Session', 'Feedback Collection Meeting', 'Modification Discussion Call', 'Scope Adjustment Review', 'Pricing Clarification Meeting', 'Timeline Adjustment Discussion', 'Terms Negotiation Call', 'Implementation Planning Review', 'Decision Timeline Confirmation'],
                    'Decision' => ['Final Decision Call', 'Approval Status Update', 'Next Steps Planning Session', 'Contract Preparation Meeting', 'Implementation Planning Call', 'Team Assignment Discussion', 'Success Metrics Agreement', 'Communication Plan Review', 'Risk Assessment Meeting', 'Go-Live Strategy Session']
                ],
                'Sales' => [
                    'Initial Contact' => ['Initial Discovery Call', 'Qualification Assessment Call', 'Requirements Gathering Session', 'Business Needs Analysis', 'Stakeholder Introduction Call', 'Current State Evaluation', 'Pain Point Identification Meeting', 'Solution Overview Discussion', 'Budget Range Exploration', 'Timeline Requirements Review'],
                    'Qualification' => ['Budget Confirmation Call', 'Decision Maker Identification', 'Timeline Discussion', 'Authority Verification Meeting', 'Procurement Process Review', 'Evaluation Criteria Discussion', 'Vendor Selection Timeline', 'Budget Approval Process', 'Implementation Planning Call', 'Resource Allocation Review'],
                    'Meeting' => ['Product Demonstration Call', 'Technical Requirements Review', 'Stakeholder Presentation', 'Feature Deep Dive Session', 'Integration Capabilities Demo', 'Security Assessment Meeting', 'Performance Benchmarking Call', 'Customization Options Review', 'Training Requirements Discussion', 'Support Structure Overview'],
                    'Proposal' => ['Proposal Review Meeting', 'Pricing Discussion Call', 'Contract Terms Negotiation', 'Service Level Agreement Review', 'Implementation Timeline Discussion', 'Payment Terms Negotiation', 'Scope Clarification Meeting', 'Risk Assessment Review', 'Warranty Terms Discussion', 'Change Management Planning'],
                    'Close' => ['Final Approval Call', 'Contract Signing Coordination', 'Implementation Planning Session', 'Project Kickoff Preparation', 'Team Assignment Meeting', 'Go-Live Planning Call', 'Success Metrics Definition', 'Milestone Review Session', 'Handover Process Discussion', 'Post-Implementation Support Planning']
                ]
            ];

            // Call descriptions based on pipeline and stage (15-25 words)
            $callDescriptions = [
                'Marketing' => [
                    'Campaign Launch' => [
                        'Discussed Q2 product launch strategy targeting enterprise clients with $50K marketing budget allocation.',
                        'Reviewed creative assets for digital campaign and established approval workflow with design team.',
                        'Coordinated 6-week launch timeline across LinkedIn, Google Ads, and email marketing channels.',
                        'Analyzed target audience segments including IT directors and confirmed messaging strategy for each.',
                        'Reviewed channel mix allocating 40% digital ads, 35% content marketing, 25% email campaigns.',
                        'Discussed $75K budget distribution and confirmed spend allocation across paid and organic channels.',
                        'Planned creative development including whitepapers, case studies, and video testimonials for campaign.',
                        'Established KPIs including 500 MQLs, 15% conversion rate, and $150 cost per lead.',
                        'Coordinated launch sequence starting with content marketing followed by paid advertising campaigns.',
                        'Reviewed potential risks including ad approval delays and confirmed backup creative options.'
                    ],
                    'Lead Generation' => [
                        'Analyzed current 12% conversion rate and identified opportunities to improve lead quality metrics.',
                        'Reviewed LinkedIn targeting parameters and optimized audience criteria for better lead generation performance.',
                        'Assessed Google Ads performance showing $180 cost per lead and identified optimization opportunities.',
                        'Evaluated email marketing generating 25% of leads and confirmed most effective subject lines.',
                        'Analyzed $200 average cost per lead across channels and confirmed budget efficiency improvements.',
                        'Reviewed conversion funnel showing 8% demo request rate and identified bottleneck areas.',
                        'Discussed A/B testing results showing 23% improvement with personalized landing page copy.',
                        'Assessed lead scoring accuracy at 78% and confirmed calibration adjustments for better qualification.',
                        'Reviewed attribution showing organic search contributing 35% of qualified leads this quarter.',
                        'Analyzed campaign trends indicating 40% increase in enterprise leads and confirmed scaling strategy.'
                    ],
                    'Nurturing' => [
                        'Developed 8-email nurture sequence targeting downloaded whitepaper leads with educational content focus.',
                        'Reviewed engagement metrics showing 28% open rate and refined subject lines for improvement.',
                        'Planned automated workflow triggering personalized content based on prospect industry and company size.',
                        'Discussed segmentation strategy separating SMB and enterprise prospects for targeted messaging approach.',
                        'Reviewed marketing automation workflows and confirmed trigger-based messaging for demo requests.',
                        'Analyzed audience segments including healthcare, finance, and technology for targeted content delivery.',
                        'Assessed email performance with 4.2% click-through rate and confirmed optimization strategies for engagement.',
                        'Planned monthly content calendar including case studies, product updates, and industry insights.',
                        'Reviewed behavioral triggers including website visits and confirmed automated follow-up email sequences.',
                        'Analyzed nurture effectiveness showing 18% progression to MQL status within 30-day period.'
                    ],
                    'Qualification' => [
                        'Reviewed lead scoring model weighting demo requests 50 points and email engagement 10.',
                        'Assessed 156 marketing qualified leads this month and established handoff criteria for sales.',
                        'Analyzed qualification accuracy at 82% and refined scoring model for improved sales conversion.',
                        'Discussed 75-point threshold for MQL status and confirmed criteria including budget and timeline.',
                        'Reviewed sales feedback indicating 65% of handed leads convert to opportunities within 30.',
                        'Analyzed lead intelligence including company size, industry, and technology stack for handoff preparation.',
                        'Assessed prospect research completeness including contact roles and confirmed background information quality.',
                        'Reviewed engagement history showing multiple touchpoints and confirmed behavioral qualification indicators for readiness.',
                        'Analyzed buying signals including pricing page visits and confirmed identification criteria for sales.',
                        'Discussed handoff documentation including lead source, engagement history, and qualification notes for team.'
                    ],
                    'Handoff' => [
                        'Coordinated handoff of 23 qualified leads to sales team with detailed background and engagement history.',
                        'Conducted weekly lead transfer meeting and provided qualification summary for each prospect opportunity.',
                        'Established SLA requiring sales follow-up within 24 hours and confirmed collaboration process for success.',
                        'Provided account intelligence including company background, technology stack, and key decision makers for outreach.',
                        'Discussed opportunity value assessment and confirmed lead prioritization based on company size and budget.',
                        'Reviewed recommended sales approach including personalized outreach and confirmed engagement strategy for qualified leads.',
                        'Planned follow-up coordination and confirmed 48-hour response timeline for lead progression tracking and updates.',
                        'Established weekly check-ins and confirmed timing for sales team engagement feedback sessions and optimization.',
                        'Aligned on conversion metrics and confirmed measurement criteria for handoff effectiveness evaluation and improvement.',
                        'Established feedback loop and confirmed monthly process optimization meetings for handoff improvement and refinement.'
                    ]
                ],
                'Lead Qualification' => [
                    'Initial Contact' => [
                        'Verified contact information for IT Director and confirmed interest in CRM modernization project.',
                        'Conducted initial qualification call to assess 200-employee company fit for enterprise solution.',
                        'Validated decision-maker status and established email as preferred communication method for follow-up.',
                        'Confirmed genuine interest in replacing legacy system and assessed Q3 evaluation timeline.',
                        'Reviewed basic qualification including $50K+ budget range and confirmed minimum requirements met.',
                        'Analyzed company profile showing $10M revenue and confirmed alignment with target customer.',
                        'Discussed 6-month decision timeline and confirmed realistic evaluation schedule fits business planning.',
                        'Explored $75K-150K budget range and confirmed CFO approval required for final investment.',
                        'Assessed CTO authority level and confirmed technical decision-making capability within IT department.',
                        'Planned discovery meeting next week and confirmed follow-up schedule for continued qualification.'
                    ],
                    'Needs Assessment' => [
                        'Analyzed current manual processes and identified automation opportunities saving 20 hours weekly.',
                        'Conducted needs assessment revealing data silos and confirmed integration requirements for solution.',
                        'Discovered critical reporting gaps and evaluated dashboard capabilities for executive visibility needs.',
                        'Reviewed current Excel-based tracking and confirmed areas requiring workflow automation and efficiency.',
                        'Identified customer data challenges and confirmed impact on sales productivity and revenue.',
                        'Discussed growth goals targeting 50% increase and confirmed scalability requirements for solution.',
                        'Analyzed success criteria including user adoption and confirmed measurement methodology for ROI.',
                        'Reviewed budget constraints under $100K and confirmed limitations affecting solution scope and.',
                        'Assessed high priority for Q4 implementation and confirmed urgency for addressing operational.',
                        'Evaluated 30% efficiency improvement potential and confirmed expected benefits from system deployment.'
                    ],
                    'Solution Fit' => [
                        'Reviewed API compatibility with existing ERP system and confirmed seamless integration capabilities.',
                        'Assessed feature alignment including custom fields and discussed workflow customization for specific.',
                        'Evaluated integration with Outlook and Slack and confirmed technical feasibility for implementation.',
                        'Analyzed scalability supporting 500+ users and confirmed solution capacity for projected company.',
                        'Reviewed performance handling 1M+ records and confirmed system capability for expected data.',
                        'Assessed SOC2 compliance requirements and confirmed solution meets healthcare industry security standards.',
                        'Analyzed mobile app functionality and confirmed flexibility for field sales team business.',
                        'Reviewed 90-day implementation timeline and confirmed realistic deployment schedule considering organizational.',
                        'Assessed IT team capacity and confirmed organizational readiness for solution deployment and.',
                        'Analyzed Q4 go-live target and confirmed alignment with business planning and budget.'
                    ],
                    'Proposal Sent' => [
                        'Clarified $85K proposal pricing and addressed questions about implementation services and ongoing.',
                        'Collected feedback on 4-phase approach and discussed timeline modifications for better organizational.',
                        'Reviewed proposal components including training and confirmed understanding of recommended solution and.',
                        'Discussed customization requirements and confirmed scope adjustments for improved solution fit and.',
                        'Reviewed implementation scope and confirmed changes including data migration and user training.',
                        'Clarified annual licensing at $35K and confirmed understanding of investment and payment.',
                        'Discussed 6-month timeline adjustment and confirmed realistic implementation schedule for organization and.',
                        'Reviewed contract terms including SLA and confirmed acceptable conditions for both parties.',
                        'Analyzed phased implementation approach and confirmed methodology for successful solution deployment and.',
                        'Confirmed 30-day decision timeline and established schedule for final evaluation and executive.'
                    ],
                    'Decision' => [
                        'Obtained executive approval for $85K investment and established implementation start date for January kickoff.',
                        'Confirmed board approval status and coordinated handoff to implementation team for project management and execution.',
                        'Planned kickoff activities and assigned dedicated project manager for continued client engagement and support.',
                        'Coordinated legal review and confirmed documentation requirements for contract execution and final signature process.',
                        'Planned implementation kickoff and confirmed project approach including timeline and resource allocation for success.',
                        'Discussed team assignments and confirmed client IT lead and internal resource allocation for project.',
                        'Established success metrics including user adoption and confirmed measurement criteria for project evaluation and ROI.',
                        'Reviewed weekly status meetings and confirmed reporting structure for project management and stakeholder communication.',
                        'Assessed potential delays and confirmed mitigation strategies for implementation challenges and project risk management.',
                        'Planned March go-live date and confirmed readiness criteria for production deployment and user training.'
                    ]
                ],
                'Sales' => [
                    'Initial Contact' => [
                        'Conducted discovery call to understand current CRM challenges and budget requirements for enterprise solution.',
                        'Qualified prospect authority and confirmed decision-making process for software procurement within organization.',
                        'Gathered business requirements and identified key stakeholders involved in technology evaluation process.',
                        'Analyzed existing workflow processes and identified specific areas requiring automation and efficiency improvements.',
                        'Established rapport with IT director and confirmed genuine interest in modernizing legacy systems.',
                        'Reviewed current technology stack and assessed integration complexity for proposed CRM solution.',
                        'Identified critical pain points affecting sales productivity and customer data management capabilities.',
                        'Discussed high-level solution approach and confirmed alignment with digital transformation objectives.',
                        'Explored budget parameters between $50K-200K and established realistic investment timeline for project.',
                        'Confirmed Q2 decision timeline and identified CFO and CTO as key stakeholders.'
                    ],
                    'Qualification' => [
                        'Confirmed $125K budget allocation and Q3 implementation timeline for CRM solution deployment.',
                        'Identified CEO as final decision maker and established technical evaluation criteria for vendor selection.',
                        'Discussed 6-month implementation timeline and confirmed internal IT team availability for project support.',
                        'Verified CFO purchasing authority and confirmed standard procurement process requires three vendor quotes.',
                        'Established vendor evaluation methodology focusing on integration capabilities and user adoption metrics.',
                        'Reviewed compliance requirements including SOC2 certification and GDPR data protection standards.',
                        'Discussed resource allocation requiring two full-time developers and confirmed team availability for project.',
                        'Validated 90-day implementation expectation and confirmed realistic deployment schedule considering organizational readiness.',
                        'Assessed change management capabilities and confirmed training budget for 50+ user adoption program.',
                        'Confirmed competitive evaluation against Salesforce and HubSpot with decision expected by month-end.'
                    ],
                    'Meeting' => [
                        'Delivered comprehensive product demo showcasing workflow automation and reporting capabilities to stakeholder team.',
                        'Reviewed API integration requirements and confirmed seamless connectivity with existing ERP system.',
                        'Presented ROI analysis showing 40% productivity improvement and addressed security compliance questions.',
                        'Conducted detailed feature walkthrough demonstrating lead scoring and automated email campaign functionality.',
                        'Reviewed mobile application capabilities and confirmed offline functionality for field sales team requirements.',
                        'Presented security architecture including encryption standards and addressed HIPAA compliance requirements for healthcare.',
                        'Demonstrated performance benchmarks handling 100K+ records and confirmed scalability for projected growth.',
                        'Showcased customization options including custom fields and workflow rules for specific business processes.',
                        'Reviewed training program structure and confirmed comprehensive onboarding plan for successful user adoption.',
                        'Presented 24/7 support structure and confirmed dedicated account manager for ongoing assistance.'
                    ],
                    'Proposal' => [
                        'Reviewed detailed $95K proposal including software licensing, implementation services, and training costs.',
                        'Negotiated contract terms including 99.9% uptime SLA and 4-hour response time guarantees.',
                        'Discussed flexible payment options including quarterly billing and confirmed preferred payment schedule.',
                        'Clarified service level commitments including dedicated support and guaranteed response times for issues.',
                        'Reviewed 4-phase implementation methodology and confirmed milestone-based delivery approach with checkpoints.',
                        'Negotiated payment terms allowing 30% upfront with remaining balance spread across implementation milestones.',
                        'Discussed project scope boundaries and confirmed deliverables including data migration and user training.',
                        'Reviewed risk mitigation strategies including backup plans and confirmed contingency procedures for delays.',
                        'Negotiated 2-year warranty terms and confirmed comprehensive post-implementation support coverage details.',
                        'Finalized change request procedures and confirmed process for handling scope modifications during implementation.'
                    ],
                    'Close' => [
                        'Obtained final executive approval for $125K investment and confirmed contract execution by Friday.',
                        'Coordinated contract signing with legal team and established January 15th project kickoff date.',
                        'Planned 4-phase implementation and assigned dedicated project manager and technical implementation specialist.',
                        'Confirmed project team including client IT lead and established weekly progress meetings schedule.',
                        'Scheduled go-live for March 30th and confirmed user acceptance testing criteria for production.',
                        'Established success metrics including user adoption rate and system performance benchmarks for evaluation.',
                        'Coordinated milestone reviews every two weeks and confirmed checkpoint schedule for progress tracking.',
                        'Planned knowledge transfer sessions and confirmed documentation requirements for internal IT team.',
                        'Established ongoing support relationship and confirmed quarterly business reviews for optimization opportunities.',
                        'Confirmed expansion discussion for marketing automation module scheduled for Q4 evaluation.'
                    ]
                ]
            ];

            // Call results based on outcome (20-25 words)
            $callResults = [
                'answered' => [
                    'Successfully connected with decision maker and gathered comprehensive requirements for next phase evaluation and budget confirmation.',
                    'Productive discussion completed with positive feedback and confirmed interest in moving forward with $125K investment proposal.',
                    'Detailed conversation held with key stakeholder resulting in approved next steps and Q3 implementation timeline confirmation.',
                    'Engaging call with prospect who expressed strong interest and provided additional contact information for technical team.',
                    'Successful connection established with qualified lead showing genuine interest in proposed solution and confirmed budget authority.',
                    'Meaningful dialogue conducted with decision maker confirming $85K budget and timeline for evaluation and vendor selection.',
                    'Positive interaction with engaged prospect who requested additional information and follow-up meeting with technical stakeholders.',
                    'Constructive conversation with qualified contact resulting in scheduled demonstration and stakeholder introduction for next week.',
                    'Effective call with interested prospect who confirmed requirements and agreed to proposal review with executive team.',
                    'Valuable discussion with decision maker who provided feedback and approved continuation of process with legal review.'
                ],
                'no_answer' => [
                    'No response received despite multiple connection attempts during scheduled call window, will try alternative contact methods.',
                    'Unable to reach contact at scheduled time, left detailed voicemail with callback request and alternative meeting options.',
                    'Call went unanswered, will attempt reconnection during alternative time slots this week and send follow-up email.',
                    'No pickup on scheduled call, sent follow-up email with alternative meeting times and confirmed continued interest.',
                    'Contact unavailable during planned call time, rescheduling for next business day and confirming availability via email.',
                    'Unable to connect as planned, left message requesting confirmation of continued interest and alternative contact methods.',
                    'No answer received, will try alternative contact methods and reschedule appointment for later this week.',
                    'Call attempt unsuccessful, following up with email to confirm availability and interest in continuing evaluation process.',
                    'Unable to reach during scheduled window, proposing alternative times for connection and confirming project timeline.',
                    'No response to call, sending calendar invitation for rescheduled meeting this week with agenda and objectives.'
                ],
                'busy' => [
                    'Contact was in meeting during scheduled call, agreed to reschedule for tomorrow afternoon with extended time.',
                    'Prospect unavailable due to conflicting priority, confirmed alternative time for detailed discussion about implementation requirements.',
                    'Brief connection made but contact was busy, scheduled follow-up call for next week with full agenda.',
                    'Unable to complete full discussion due to time constraints, rescheduled for extended session with technical team.',
                    'Contact had limited time available, agreed to comprehensive call later this week with decision makers present.',
                    'Interrupted by urgent matter, prospect requested reschedule for uninterrupted conversation time about budget and timeline.',
                    'Short connection established but contact was occupied, confirmed interest and rescheduled meeting for detailed proposal review.',
                    'Time conflict prevented full discussion, arranged dedicated time slot for next business day with stakeholder participation.',
                    'Contact was handling urgent issue, agreed to reconnect when schedule permits full attention to evaluation process.',
                    'Brief interaction due to competing priorities, established new time for comprehensive discussion about solution requirements.'
                ],
                'voicemail' => [
                    'Left comprehensive voicemail with key discussion points and requested callback within two days for proposal review.',
                    'Detailed message left explaining purpose and value proposition, provided multiple contact options for continued engagement.',
                    'Voicemail delivered with specific agenda items and requested confirmation of continued interest in Q3 implementation timeline.',
                    'Left informative message summarizing previous discussion and outlining next steps for consideration by executive team.',
                    'Comprehensive voicemail left with proposal highlights and requested feedback on key points within 48 hours.',
                    'Detailed message provided with timeline information and requested response regarding next meeting with technical stakeholders.',
                    'Left thorough voicemail explaining benefits and requested callback to schedule demonstration session with decision makers.',
                    'Informative message delivered with key details and multiple options for reconnection this week about budget.',
                    'Voicemail left with important updates and requested confirmation of decision timeline for vendor selection process.',
                    'Detailed message provided with next steps and requested response regarding continued engagement and evaluation criteria.'
                ]
            ];

            $callTypes = ['Inbound', 'Outbound'];
            $callResultTypes = ['answered', 'no_answer', 'busy', 'voicemail'];

            foreach ($deals as $deal) {
                $pipelineName = $deal->pipeline->name ?? 'Sales';
                $stageName = $deal->stage->name ?? 'Initial Contact';

                // Get assigned users for this deal
                $assignedUsers = $deal->userDeals->pluck('user_id')->toArray();
                if (empty($assignedUsers)) {
                    continue; // Skip deals with no assigned users
                }

                // Generate 1-2 calls per deal
                $callCount = rand(1, 2);

                for ($i = 0; $i < $callCount; $i++) {
                    // Select random assigned user for this call
                    $assignedUserId = $assignedUsers[array_rand($assignedUsers)];

                    // Get appropriate subjects for pipeline and stage
                    $subjects = $callSubjects[$pipelineName][$stageName] ?? $callSubjects['Sales']['Initial Contact'];
                    $subject = $subjects[array_rand($subjects)];

                    // Get appropriate descriptions for pipeline and stage
                    $descriptions = $callDescriptions[$pipelineName][$stageName] ?? $callDescriptions['Sales']['Initial Contact'];
                    $description = $descriptions[array_rand($descriptions)];

                    // Random call type
                    $callType = $callTypes[array_rand($callTypes)];

                    // Random call result type
                    $resultType = $callResultTypes[array_rand($callResultTypes)];

                    // Get appropriate call result
                    $callResult = $callResults[$resultType][array_rand($callResults[$resultType])];

                    // Generate realistic duration based on call result
                    if ($resultType === 'answered') {
                        $duration = sprintf('%02d:%02d:%02d', 0, rand(15, 45), rand(0, 59));
                    } elseif ($resultType === 'busy') {
                        $duration = sprintf('%02d:%02d:%02d', 0, rand(1, 5), rand(0, 59));
                    } else { // no_answer, voicemail
                        $duration = sprintf('%02d:%02d:%02d', 0, rand(0, 2), rand(0, 59));
                    }

                    // Call date within 30 days of deal creation
                    $dealCreated = Carbon::parse($deal->created_at);
                    $callDate = $dealCreated->copy()->addDays(rand(1, 30));

                    DealCall::create([
                        'deal_id' => $deal->id,
                        'subject' => $subject,
                        'call_type' => $callType,
                        'duration' => $duration,
                        'user_id' => $assignedUserId,
                        'description' => $description,
                        'call_result' => $callResult,
                    ]);
                }
            }
        }
    }
}
