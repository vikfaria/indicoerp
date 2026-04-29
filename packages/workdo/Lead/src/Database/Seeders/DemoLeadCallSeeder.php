<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\LeadCall;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\UserLead;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoLeadCallSeeder extends Seeder
{
    public function run($userId): void
    {
        if (LeadCall::whereHas('lead', function($query) use ($userId) {
            $query->where('created_by', $userId);
        })->exists()) {
            return;
        }
        if (!empty($userId)) {
            $leads = Lead::where('created_by', $userId)->with(['pipeline', 'stage', 'userLeads'])->get();

            if ($leads->isEmpty()) {
                return;
            }

            // Pipeline and stage-specific call subjects (10 each)
            $callSubjects = [
                'Marketing' => [
                    'Prospect' => ['Lead Qualification Call', 'Interest Assessment Meeting', 'Initial Outreach Call', 'Marketing Response Follow-up', 'Campaign Engagement Discussion', 'Prospect Evaluation Call', 'Interest Verification Meeting', 'Outreach Follow-up Call', 'Engagement Analysis Discussion', 'Qualification Assessment Call'],
                    'Contacted' => ['Follow-up Engagement Call', 'Content Discussion Meeting', 'Nurture Sequence Call', 'Educational Resource Review', 'Value Proposition Presentation', 'Engagement Follow-up Call', 'Content Strategy Meeting', 'Nurture Planning Call', 'Resource Discussion Call', 'Value Assessment Meeting'],
                    'Engaged' => ['Engagement Analysis Call', 'Content Feedback Session', 'Interest Level Assessment', 'Behavioral Analysis Discussion', 'Progression Evaluation Meeting', 'Engagement Review Call', 'Content Performance Meeting', 'Interest Confirmation Call', 'Behavior Assessment Discussion', 'Progress Evaluation Call'],
                    'Qualified' => ['Marketing Qualification Call', 'Handoff Preparation Meeting', 'Sales Readiness Assessment', 'Lead Intelligence Gathering', 'Transfer Coordination Call', 'Qualification Review Meeting', 'Handoff Planning Call', 'Readiness Assessment Discussion', 'Intelligence Review Call', 'Transfer Planning Meeting'],
                    'Converted' => ['Conversion Confirmation Call', 'Handoff Completion Meeting', 'Sales Team Introduction', 'Account Transfer Discussion', 'Success Metrics Review', 'Conversion Review Call', 'Handoff Follow-up Meeting', 'Team Coordination Call', 'Transfer Completion Discussion', 'Success Analysis Meeting']
                ],
                'Lead Qualification' => [
                    'Unqualified' => ['Initial Assessment Call', 'Basic Qualification Check', 'Interest Verification Meeting', 'Preliminary Evaluation Session', 'Screening Assessment Call', 'Assessment Review Meeting', 'Qualification Discussion Call', 'Verification Follow-up Call', 'Evaluation Planning Session', 'Screening Review Call'],
                    'In Review' => ['Detailed Review Meeting', 'Qualification Analysis Call', 'Assessment Progress Discussion', 'Evaluation Criteria Review', 'Status Update Meeting', 'Review Progress Call', 'Analysis Discussion Meeting', 'Progress Assessment Call', 'Criteria Review Discussion', 'Status Follow-up Meeting'],
                    'Qualified' => ['Qualification Confirmation Call', 'Approval Preparation Meeting', 'Final Assessment Review', 'Qualification Documentation Call', 'Handoff Preparation Session', 'Confirmation Follow-up Call', 'Preparation Planning Meeting', 'Assessment Completion Call', 'Documentation Review Discussion', 'Handoff Planning Call'],
                    'Approved' => ['Approval Notification Call', 'Next Steps Planning Meeting', 'Process Advancement Discussion', 'Success Confirmation Call', 'Progression Planning Session', 'Approval Follow-up Call', 'Planning Coordination Meeting', 'Advancement Planning Call', 'Success Review Discussion', 'Progress Planning Meeting'],
                    'Rejected' => ['Rejection Explanation Call', 'Feedback Discussion Meeting', 'Alternative Options Review', 'Future Opportunity Assessment', 'Relationship Maintenance Call', 'Rejection Follow-up Call', 'Feedback Analysis Meeting', 'Options Planning Call', 'Opportunity Review Discussion', 'Maintenance Planning Call']
                ],
                'Sales' => [
                    'Draft' => ['Initial Draft Call', 'Document Preparation Call', 'Content Planning Session', 'Draft Review Meeting', 'Initial Setup Call', 'Outline Discussion Call', 'Structure Planning Meeting', 'Content Strategy Call', 'Draft Coordination Meeting', 'Preparation Planning Call'],
                    'Sent' => ['Delivery Confirmation Call', 'Receipt Verification Call', 'Follow-up Contact Call', 'Acknowledgment Discussion', 'Status Update Call', 'Transmission Confirmation Call', 'Delivery Status Meeting', 'Receipt Follow-up Call', 'Confirmation Discussion Call', 'Delivery Tracking Call'],
                    'Open' => ['Opening Discussion Call', 'Initial Review Call', 'Engagement Assessment Call', 'Interest Confirmation Call', 'Response Follow-up Call', 'Opening Analysis Meeting', 'Initial Feedback Call', 'Review Coordination Call', 'Engagement Follow-up Call', 'Opening Status Discussion'],
                    'Revised' => ['Revision Discussion Call', 'Feedback Collection Call', 'Modification Planning Call', 'Update Coordination Call', 'Change Review Meeting', 'Revision Planning Session', 'Feedback Analysis Call', 'Modification Discussion Call', 'Update Planning Meeting', 'Change Coordination Call'],
                    'Declined' => ['Decline Discussion Call', 'Feedback Collection Call', 'Alternative Options Call', 'Future Opportunity Call', 'Relationship Maintenance Call', 'Decline Analysis Meeting', 'Feedback Review Call', 'Alternative Planning Call', 'Future Strategy Discussion', 'Relationship Follow-up Call'],
                    'Accepted' => ['Acceptance Confirmation Call', 'Next Steps Planning Call', 'Implementation Discussion Call', 'Success Celebration Call', 'Progression Planning Call', 'Acceptance Follow-up Meeting', 'Implementation Planning Call', 'Success Review Discussion', 'Progress Coordination Call', 'Next Phase Planning Meeting']
                ]
            ];

            // Call descriptions (15-25 words, 10 each)
            $callDescriptions = [
                'Marketing' => [
                    'Prospect' => [
                        'Conducted initial qualification call to assess company fit for marketing solution and automation platform.',
                        'Verified contact information and confirmed interest in marketing automation platform and lead generation tools.',
                        'Analyzed current marketing challenges and identified areas requiring automation improvements and optimization strategies.',
                        'Discussed marketing goals and confirmed alignment with solution capabilities and expected business outcomes.',
                        'Established communication preferences and confirmed follow-up schedule for engagement and relationship development.',
                        'Coordinated prospect assessment and confirmed company profile alignment with marketing solution capabilities.',
                        'Reviewed prospect qualifications and established interest level for marketing automation and lead generation.',
                        'Discussed prospect requirements and confirmed solution fit for marketing challenges and business objectives.',
                        'Analyzed prospect profile and established qualification criteria for marketing solution and platform evaluation.',
                        'Confirmed prospect interest and coordinated next steps for marketing solution assessment and evaluation.'
                    ],
                    'Contacted' => [
                        'Follow-up call to discuss marketing automation benefits and comprehensive implementation approach for business.',
                        'Reviewed content marketing strategy and confirmed interest in educational resources and lead nurturing programs.',
                        'Discussed email marketing challenges and identified opportunities for improvement and automation enhancement strategies.',
                        'Analyzed current lead generation process and confirmed areas requiring optimization and efficiency improvements.',
                        'Established nurture sequence preferences and confirmed content delivery schedule for engagement and conversion.',
                        'Coordinated follow-up discussion and reviewed marketing automation benefits for lead generation and nurturing.',
                        'Reviewed contact engagement and confirmed interest in marketing solution capabilities and implementation approach.',
                        'Discussed contact requirements and established timeline for marketing automation evaluation and decision process.',
                        'Analyzed contact feedback and confirmed alignment with marketing solution features and business objectives.',
                        'Confirmed contact interest and coordinated next steps for marketing automation assessment and planning.'
                    ],
                    'Engaged' => [
                        'Analyzed engagement metrics and discussed content performance with marketing team for optimization and improvement.',
                        'Reviewed behavioral data and confirmed interest level based on activity patterns and engagement history.',
                        'Discussed content preferences and confirmed alignment with marketing objectives and business development goals.',
                        'Evaluated engagement progression and confirmed readiness for next phase in marketing qualification process.',
                        'Analyzed interaction history and confirmed qualification criteria for advancement to sales team handoff.',
                        'Coordinated engagement analysis and reviewed content performance metrics for marketing optimization and strategy.',
                        'Reviewed engagement patterns and confirmed interest progression for marketing qualification and sales readiness.',
                        'Discussed engagement feedback and established next steps for marketing qualification and team coordination.',
                        'Analyzed engagement data and confirmed qualification criteria for progression to sales team handoff.',
                        'Confirmed engagement status and coordinated next phase planning for marketing qualification and advancement.'
                    ],
                    'Qualified' => [
                        'Conducted marketing qualification assessment and confirmed sales readiness criteria for team handoff and coordination.',
                        'Reviewed lead scoring results and confirmed qualification for sales handoff and opportunity development process.',
                        'Gathered account intelligence and prepared comprehensive handoff documentation for sales team and stakeholders.',
                        'Confirmed decision timeline and established sales team introduction schedule for qualified lead handoff.',
                        'Analyzed buying signals and confirmed readiness for sales engagement process and opportunity development.',
                        'Coordinated qualification assessment and confirmed sales readiness for team handoff and opportunity management.',
                        'Reviewed qualification criteria and established handoff timeline for sales team engagement and follow-up.',
                        'Discussed qualification status and confirmed readiness for sales team introduction and opportunity development.',
                        'Analyzed qualification metrics and established handoff process for sales team engagement and coordination.',
                        'Confirmed qualification completion and coordinated sales team handoff for opportunity development and management.'
                    ],
                    'Converted' => [
                        'Confirmed successful conversion to sales opportunity and coordinated comprehensive team handoff and transition.',
                        'Completed marketing qualification process and transferred lead to sales team for opportunity development.',
                        'Reviewed conversion metrics and confirmed successful progression through marketing funnel and qualification process.',
                        'Established ongoing collaboration between marketing and sales for account management and opportunity development.',
                        'Documented conversion success factors and confirmed process optimization opportunities for future improvement.',
                        'Coordinated conversion confirmation and established sales team handoff for opportunity management and development.',
                        'Reviewed conversion status and confirmed successful transition from marketing to sales team engagement.',
                        'Discussed conversion metrics and established ongoing collaboration for account management and opportunity development.',
                        'Analyzed conversion success and confirmed process optimization for future marketing and sales coordination.',
                        'Confirmed conversion completion and coordinated ongoing account management between marketing and sales teams.'
                    ]
                ],
                'Lead Qualification' => [
                    'Unqualified' => [
                        'Conducted initial assessment call to evaluate basic qualification criteria and company fit for solution.',
                        'Verified contact information and confirmed interest level in solution and business development opportunities.',
                        'Analyzed company profile and assessed fit for qualification process and business development activities.',
                        'Discussed preliminary requirements and confirmed evaluation criteria for qualification and assessment process.',
                        'Established baseline qualification metrics and confirmed assessment schedule for evaluation and review process.',
                        'Coordinated initial assessment and reviewed qualification criteria for company fit and solution alignment.',
                        'Reviewed unqualified status and confirmed assessment approach for future qualification and development opportunities.',
                        'Discussed assessment results and established criteria for future qualification and business development activities.',
                        'Analyzed qualification factors and confirmed approach for future assessment and qualification process improvement.',
                        'Confirmed assessment completion and coordinated future qualification strategy for business development and engagement.'
                    ],
                    'In Review' => [
                        'Conducted detailed qualification review and analyzed assessment criteria for comprehensive evaluation and decision.',
                        'Reviewed qualification progress and confirmed evaluation methodology for assessment and decision making process.',
                        'Analyzed assessment results and discussed qualification status with team for comprehensive review and evaluation.',
                        'Evaluated qualification criteria and confirmed progression requirements for assessment and advancement process.',
                        'Discussed review findings and established next steps for qualification process and decision making.',
                        'Coordinated review process and analyzed qualification criteria for comprehensive assessment and evaluation.',
                        'Reviewed assessment progress and confirmed evaluation methodology for qualification and decision making process.',
                        'Discussed review status and established timeline for qualification assessment and decision making process.',
                        'Analyzed review findings and confirmed next steps for qualification process and advancement evaluation.',
                        'Confirmed review completion and coordinated next phase planning for qualification assessment and decision.'
                    ],
                    'Qualified' => [
                        'Confirmed qualification status and prepared approval documentation for comprehensive review and decision process.',
                        'Reviewed qualification criteria and confirmed successful completion of assessment and evaluation process.',
                        'Analyzed qualification results and prepared handoff documentation for team coordination and next steps.',
                        'Confirmed qualification approval and established next steps for progression and advancement process coordination.',
                        'Documented qualification success and prepared transition to approval process for team coordination and management.',
                        'Coordinated qualification confirmation and established next phase planning for approval and advancement process.',
                        'Reviewed qualification completion and confirmed success criteria for approval and progression to next phase.',
                        'Discussed qualification status and established timeline for approval process and team coordination activities.',
                        'Analyzed qualification success and confirmed next steps for approval and advancement process coordination.',
                        'Confirmed qualification achievement and coordinated approval process for progression and team handoff activities.'
                    ],
                    'Approved' => [
                        'Confirmed qualification approval and coordinated next steps for process advancement and team coordination activities.',
                        'Reviewed approval criteria and confirmed successful qualification completion for progression and advancement process.',
                        'Established post-approval process and confirmed team assignment for continuation and ongoing management activities.',
                        'Confirmed approval status and coordinated handoff to implementation team for project management and execution.',
                        'Documented approval success and established ongoing engagement schedule for relationship management and coordination.',
                        'Coordinated approval confirmation and established next phase planning for implementation and team coordination.',
                        'Reviewed approval completion and confirmed success criteria for implementation and ongoing management activities.',
                        'Discussed approval status and established timeline for implementation and team coordination process.',
                        'Analyzed approval success and confirmed next steps for implementation and ongoing relationship management.',
                        'Confirmed approval achievement and coordinated implementation planning for project execution and team management.'
                    ],
                    'Rejected' => [
                        'Discussed qualification rejection and provided feedback on assessment results for future improvement and development.',
                        'Reviewed rejection criteria and confirmed areas for future improvement and qualification process enhancement.',
                        'Analyzed rejection factors and discussed alternative qualification approaches for future assessment and development.',
                        'Provided rejection feedback and established future engagement opportunities for relationship maintenance and development.',
                        'Documented rejection reasons and confirmed relationship maintenance strategy for future opportunities and engagement.',
                        'Coordinated rejection discussion and gathered feedback for future improvement and qualification process enhancement.',
                        'Reviewed rejection reasoning and confirmed alternative strategies for future qualification and business development.',
                        'Discussed rejection feedback and established process improvements for future qualification and assessment activities.',
                        'Analyzed rejection factors and confirmed relationship maintenance approach for future opportunities and engagement.',
                        'Confirmed rejection understanding and coordinated future engagement strategy for relationship development and opportunities.'
                    ]
                ],
                'Sales' => [
                    'Draft' => [
                        'Conducted initial draft preparation call to outline document structure and content requirements for proposal.',
                        'Discussed draft timeline and confirmed resource allocation for document preparation and comprehensive review process.',
                        'Reviewed draft specifications and established approval workflow for content development and stakeholder coordination.',
                        'Analyzed draft requirements and confirmed stakeholder involvement in preparation and detailed review activities.',
                        'Established draft quality standards and confirmed review criteria for content approval and finalization.',
                        'Coordinated draft planning session and confirmed content outline with team members and stakeholders.',
                        'Reviewed draft structure requirements and established timeline for content creation and approval workflow.',
                        'Discussed draft preparation methodology and confirmed resource availability for document development activities.',
                        'Analyzed content requirements and established draft framework for comprehensive document preparation process.',
                        'Confirmed draft planning approach and coordinated team assignments for efficient content development.'
                    ],
                    'Sent' => [
                        'Confirmed document delivery and established receipt acknowledgment process for comprehensive tracking and follow-up.',
                        'Verified successful transmission and confirmed recipient access to delivered document content and materials.',
                        'Discussed delivery timeline and confirmed next steps for document review and feedback collection.',
                        'Analyzed delivery metrics and confirmed successful completion of transmission process and recipient notification.',
                        'Established follow-up schedule and confirmed recipient engagement with delivered content materials and documentation.',
                        'Coordinated delivery confirmation and verified successful document transmission to all intended recipients.',
                        'Reviewed delivery status and confirmed receipt acknowledgment from key stakeholders and decision makers.',
                        'Discussed transmission completion and established timeline for recipient review and feedback collection.',
                        'Analyzed delivery success metrics and confirmed proper document access for all stakeholders.',
                        'Confirmed delivery completion and coordinated follow-up schedule for recipient engagement and response.'
                    ],
                    'Open' => [
                        'Confirmed document opening and discussed initial impressions with recipient for comprehensive feedback collection.',
                        'Analyzed opening metrics and confirmed recipient engagement with document content and structural elements.',
                        'Discussed initial review findings and established timeline for comprehensive evaluation and assessment process.',
                        'Reviewed opening feedback and confirmed understanding of document objectives and key requirements.',
                        'Established review schedule and confirmed next steps for detailed content analysis and evaluation.',
                        'Coordinated opening discussion and gathered initial feedback from recipient regarding document content.',
                        'Reviewed opening status and confirmed recipient understanding of document structure and objectives.',
                        'Discussed initial engagement metrics and established timeline for comprehensive document review process.',
                        'Analyzed opening feedback and confirmed recipient interest in proceeding with detailed evaluation.',
                        'Confirmed document access and coordinated next steps for thorough content review and assessment.'
                    ],
                    'Revised' => [
                        'Discussed revision requirements and confirmed modification timeline for document improvement and enhancement process.',
                        'Analyzed feedback and established revision priorities for content enhancement and comprehensive refinement activities.',
                        'Reviewed revision scope and confirmed resource allocation for document modification and improvement activities.',
                        'Discussed change requirements and established approval workflow for revised content review and finalization.',
                        'Confirmed revision timeline and established quality standards for improved document delivery and presentation.',
                        'Coordinated revision planning and confirmed modification approach for content enhancement and improvement.',
                        'Reviewed revision feedback and established priorities for document modification and quality enhancement.',
                        'Discussed revision methodology and confirmed resource availability for comprehensive content improvement activities.',
                        'Analyzed revision requirements and established workflow for efficient document modification and approval.',
                        'Confirmed revision approach and coordinated team assignments for effective content enhancement and refinement.'
                    ],
                    'Declined' => [
                        'Discussed decline reasons and gathered feedback for future improvement and ongoing relationship maintenance.',
                        'Analyzed decline factors and confirmed alternative approaches for future engagement and collaboration opportunities.',
                        'Reviewed decline feedback and established process improvements for future document submissions and presentations.',
                        'Discussed alternative options and confirmed continued relationship for future collaboration and engagement opportunities.',
                        'Established feedback loop and confirmed process optimization for improved future outcomes and success.',
                        'Coordinated decline discussion and gathered insights for future improvement and relationship development.',
                        'Reviewed decline reasoning and confirmed alternative strategies for future engagement and collaboration.',
                        'Discussed decline feedback and established process enhancements for improved future proposal submissions.',
                        'Analyzed decline factors and confirmed relationship maintenance approach for future opportunities.',
                        'Confirmed decline understanding and coordinated future engagement strategy for continued relationship building.'
                    ],
                    'Accepted' => [
                        'Confirmed acceptance status and established next steps for implementation and comprehensive progression planning.',
                        'Discussed acceptance terms and confirmed timeline for implementation and detailed delivery activities coordination.',
                        'Reviewed acceptance criteria and established success metrics for implementation and comprehensive evaluation process.',
                        'Analyzed acceptance feedback and confirmed satisfaction with document content and delivery methodology.',
                        'Established implementation schedule and confirmed resource allocation for successful project completion and delivery.',
                        'Coordinated acceptance confirmation and established next phase planning for implementation and execution.',
                        'Reviewed acceptance terms and confirmed timeline for project initiation and comprehensive delivery activities.',
                        'Discussed acceptance criteria and established success framework for implementation and evaluation process.',
                        'Analyzed acceptance feedback and confirmed satisfaction with proposal content and delivery approach.',
                        'Confirmed acceptance status and coordinated implementation planning for successful project execution and completion.'
                    ]
                ]
            ];

            // Call results (20-25 words)
            $callResults = [
                'answered' => [
                    'Successfully connected with decision maker and gathered comprehensive requirements for next phase evaluation and budget confirmation.',
                    'Productive discussion completed with positive feedback and confirmed interest in moving forward with proposed solution implementation.',
                    'Detailed conversation held with key stakeholder resulting in approved next steps and confirmed implementation timeline.',
                    'Engaging call with prospect who expressed strong interest and provided additional contact information for team.',
                    'Successful connection established with qualified lead showing genuine interest in proposed solution and confirmed authority.',
                    'Meaningful dialogue conducted with decision maker confirming budget and timeline for evaluation and selection.',
                    'Positive interaction with engaged prospect who requested additional information and follow-up meeting with stakeholders.',
                    'Constructive conversation with qualified contact resulting in scheduled demonstration and stakeholder introduction for evaluation.',
                    'Effective call with interested prospect who confirmed requirements and agreed to proposal review with team.',
                    'Valuable discussion with decision maker who provided feedback and approved continuation of evaluation process.'
                ],
                'no_answer' => [
                    'No response received despite multiple connection attempts during scheduled call window, will try alternative methods.',
                    'Unable to reach contact at scheduled time, left detailed voicemail with callback request and options.',
                    'Call went unanswered, will attempt reconnection during alternative time slots this week and email.',
                    'No pickup on scheduled call, sent follow-up email with alternative meeting times and confirmed interest.',
                    'Contact unavailable during planned call time, rescheduling for next business day and confirming via email.',
                    'Unable to connect as planned, left message requesting confirmation of continued interest and methods.',
                    'No answer received, will try alternative contact methods and reschedule appointment for this week.',
                    'Call attempt unsuccessful, following up with email to confirm availability and interest in process.',
                    'Unable to reach during scheduled window, proposing alternative times for connection and confirming timeline.',
                    'No response to call, sending calendar invitation for rescheduled meeting this week with agenda.'
                ],
                'busy' => [
                    'Contact was in meeting during scheduled call, agreed to reschedule for tomorrow afternoon with time.',
                    'Prospect unavailable due to conflicting priority, confirmed alternative time for detailed discussion about requirements.',
                    'Brief connection made but contact was busy, scheduled follow-up call for next week with agenda.',
                    'Unable to complete full discussion due to time constraints, rescheduled for extended session with team.',
                    'Contact had limited time available, agreed to comprehensive call later this week with makers present.',
                    'Interrupted by urgent matter, prospect requested reschedule for uninterrupted conversation time about budget.',
                    'Short connection established but contact was occupied, confirmed interest and rescheduled meeting for review.',
                    'Time conflict prevented full discussion, arranged dedicated time slot for next business day with participation.',
                    'Contact was handling urgent issue, agreed to reconnect when schedule permits full attention to process.',
                    'Brief interaction due to competing priorities, established new time for comprehensive discussion about requirements.'
                ],
                'voicemail' => [
                    'Left comprehensive voicemail with key discussion points and requested callback within two days for review.',
                    'Detailed message left explaining purpose and value proposition, provided multiple contact options for continued engagement.',
                    'Voicemail delivered with specific agenda items and requested confirmation of continued interest in timeline.',
                    'Left informative message summarizing previous discussion and outlining next steps for consideration by team.',
                    'Comprehensive voicemail left with proposal highlights and requested feedback on key points within hours.',
                    'Detailed message provided with timeline information and requested response regarding next meeting with stakeholders.',
                    'Left thorough voicemail explaining benefits and requested callback to schedule demonstration session with makers.',
                    'Informative message delivered with key details and multiple options for reconnection this week about budget.',
                    'Voicemail left with important updates and requested confirmation of decision timeline for selection process.',
                    'Detailed message provided with next steps and requested response regarding continued engagement and criteria.'
                ]
            ];

            $callTypes = ['Inbound', 'Outbound'];
            $callResultTypes = ['answered', 'no_answer', 'busy', 'voicemail'];

            foreach ($leads as $lead) {
                $pipelineName = $lead->pipeline->name ?? 'Sales';
                $stageName = $lead->stage->name ?? 'Initial Contact';

                // Get assigned users for this lead
                $assignedUsers = $lead->userLeads->pluck('user_id')->toArray();
                if (empty($assignedUsers)) {
                    continue; // Skip leads with no assigned users
                }

                // Generate 1-2 calls per lead
                $callCount = rand(1, 2);

                for ($i = 0; $i < $callCount; $i++) {
                    // Select random assigned user for this call
                    $assignedUserId = $assignedUsers[array_rand($assignedUsers)];

                    // Get appropriate subjects for pipeline and stage
                    $subjects = $callSubjects[$pipelineName][$stageName] ?? $callSubjects['Marketing']['Prospect'];
                    $subject = $subjects[array_rand($subjects)];

                    // Get appropriate descriptions for pipeline and stage
                    $descriptions = $callDescriptions[$pipelineName][$stageName] ?? $callDescriptions['Marketing']['Prospect'];
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

                    // Call date within 30 days of lead creation
                    $leadCreated = Carbon::parse($lead->created_at);
                    $callDate = $leadCreated->copy()->addDays(rand(1, 30));

                    LeadCall::create([
                        'lead_id' => $lead->id,
                        'subject' => $subject,
                        'call_type' => $callType,
                        'duration' => $duration,
                        'user_id' => $assignedUserId,
                        'description' => $description,
                        'call_result' => $callResult,
                        'created_at' => $callDate,
                    ]);
                }
            }
        }
    }
}
