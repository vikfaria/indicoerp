<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\DealEmail;
use Workdo\Lead\Models\DealDiscussion;
use Workdo\Lead\Models\LeadEmail;
use Workdo\Lead\Models\LeadDiscussion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoLeadEmailDiscussionSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $leads = Lead::where('created_by', $userId)->with(['pipeline', 'stage'])->get();
            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();

            if ($leads->isEmpty() || empty($users)) {
                return;
            }

            $this->seedLeadFiles($leads);

            // Lead email templates based on pipeline and stage
            $leadEmailTemplates = [
                'Marketing' => [
                    'Prospect' => [
                        'subjects' => ['Welcome to Our Newsletter', 'Exclusive Industry Insights', 'Free Resource Download', 'Market Research Report', 'Best Practices Guide', 'Webinar Invitation', 'Case Study Collection', 'Trend Analysis', 'Expert Interview', 'Solution Comparison'],
                        'descriptions' => [
                            'Thank you for subscribing to our newsletter. We will keep you updated with latest industry trends.',
                            'We have prepared exclusive industry insights that might be valuable for your business growth and planning.',
                            'Please find attached our comprehensive guide on digital transformation best practices for your industry sector.',
                            'Market research report shows emerging trends that could impact your business strategy and competitive position.',
                            'Best practices guide compiled from successful implementations across similar organizations in your industry.',
                            'Webinar invitation for next week covering advanced strategies for operational efficiency and cost reduction.',
                            'Case study collection showcases how organizations like yours achieved significant ROI with our solutions.',
                            'Trend analysis reveals key market shifts that forward-thinking companies are leveraging for growth.',
                            'Expert interview series features industry leaders discussing challenges and solutions in your sector.',
                            'Solution comparison guide helps evaluate different approaches to address your business challenges effectively.'
                        ]
                    ],
                    'Contacted' => [
                        'subjects' => ['Follow-up on Your Inquiry', 'Scheduling Our Discussion', 'Additional Information Request', 'Discovery Call Setup', 'Requirements Gathering', 'Solution Fit Assessment', 'Stakeholder Introduction', 'Timeline Discussion', 'Budget Planning', 'Next Steps'],
                        'descriptions' => [
                            'Thank you for reaching out to us. We would like to schedule a brief call to understand requirements.',
                            'I wanted to follow up on our previous conversation and see if you have questions about solutions.',
                            'Could you please provide more details about your current challenges so we can tailor our approach accordingly?',
                            'Discovery call setup to understand your business objectives and how our solutions can help achieve them.',
                            'Requirements gathering session will help us understand your specific needs and technical constraints.',
                            'Solution fit assessment to determine which of our offerings best aligns with your business goals.',
                            'Stakeholder introduction meeting to understand decision-making process and key evaluation criteria.',
                            'Timeline discussion needed to understand your implementation preferences and business deadlines.',
                            'Budget planning conversation to ensure our solutions align with your investment parameters and expectations.',
                            'Next steps outline will clarify the evaluation process and what information we need to proceed.'
                        ]
                    ],
                    'Engaged' => [
                        'subjects' => ['Demo Invitation', 'Case Study Sharing', 'Technical Consultation Offer', 'ROI Analysis', 'Implementation Planning', 'Reference Customer Call', 'Proof of Concept', 'Security Review', 'Integration Assessment', 'Pilot Program'],
                        'descriptions' => [
                            'Based on our discussion, I would like to invite you to a personalized demo of our platform.',
                            'I am sharing a relevant case study that shows how we helped similar company achieve efficiency improvement.',
                            'Our technical team is available for a consultation to discuss your specific integration requirements and needs.',
                            'ROI analysis prepared showing projected benefits and cost savings based on your current operational metrics.',
                            'Implementation planning session to discuss deployment approach, timeline, and resource requirements.',
                            'Reference customer call arranged with similar organization to discuss their experience and results achieved.',
                            'Proof of concept proposal to demonstrate our solution capabilities with your actual data and workflows.',
                            'Security review documentation available to address compliance requirements and data protection concerns.',
                            'Integration assessment completed showing compatibility with your existing systems and infrastructure.',
                            'Pilot program opportunity to test our solution with limited scope before full implementation commitment.'
                        ]
                    ],
                    'Qualified' => [
                        'subjects' => ['Proposal Preparation', 'Budget Discussion', 'Timeline Planning', 'Stakeholder Alignment', 'Contract Terms', 'Service Levels', 'Success Metrics', 'Risk Assessment', 'Change Management', 'Executive Presentation'],
                        'descriptions' => [
                            'We are preparing a detailed proposal based on your requirements. Could you confirm your preferred timeline?',
                            'To finalize our proposal, we need to discuss budget parameters and investment expectations for this project.',
                            'Let us schedule a meeting to discuss implementation timeline and project phases in detail with stakeholders.',
                            'Stakeholder alignment session to ensure all decision makers are informed and supportive of the initiative.',
                            'Contract terms discussion to address legal requirements, compliance needs, and procurement policies.',
                            'Service levels definition to establish support expectations, response times, and performance guarantees.',
                            'Success metrics identification to measure project outcomes and business value delivery effectively.',
                            'Risk assessment and mitigation planning to address potential challenges during implementation.',
                            'Change management strategy to ensure smooth user adoption and minimize business disruption.',
                            'Executive presentation scheduled to present business case and secure final approval for the project.'
                        ]
                    ],
                    'Converted' => [
                        'subjects' => ['Welcome to Our Platform', 'Onboarding Schedule', 'Success Team Introduction', 'Implementation Kickoff', 'Training Program', 'Data Migration', 'System Configuration', 'Go-Live Planning', 'Support Resources', 'Success Milestones'],
                        'descriptions' => [
                            'Welcome to our platform! We are excited to start this journey with you and ensure your success.',
                            'Your onboarding specialist will contact you shortly to schedule the implementation kickoff meeting with your team.',
                            'I would like to introduce you to your dedicated success team who will guide you through implementation.',
                            'Implementation kickoff meeting scheduled to review project plan, assign responsibilities, and establish communication protocols.',
                            'Training program designed specifically for your team to ensure effective platform adoption and utilization.',
                            'Data migration planning session to ensure seamless transfer of your existing information to our platform.',
                            'System configuration will be customized to match your business processes and workflow requirements.',
                            'Go-live planning includes testing phases, user acceptance, and rollback procedures for risk mitigation.',
                            'Support resources available 24/7 during implementation including dedicated technical and success managers.',
                            'Success milestones defined to track progress and ensure project delivers expected business value and outcomes.'
                        ]
                    ]
                ],
                'Lead Qualification' => [
                    'Unqualified' => [
                        'subjects' => ['Thank You for Your Interest', 'Alternative Solutions', 'Future Opportunities', 'Resource Recommendations', 'Partner Referrals', 'Market Updates', 'Industry Insights', 'Networking Opportunities', 'Educational Content', 'Stay Connected'],
                        'descriptions' => [
                            'Thank you for your interest in our solutions. While our current offering may not be perfect fit.',
                            'Although our enterprise solution might not align with your current needs, we have alternative options available.',
                            'We will keep your information on file and reach out when we have solutions that better match.',
                            'Resource recommendations for addressing your current challenges with existing tools and methodologies.',
                            'Partner referrals available for organizations that specialize in solutions more suited to your requirements.',
                            'Market updates will be shared when new solutions become available that better fit your needs.',
                            'Industry insights and best practices will continue to be shared through our newsletter and content.',
                            'Networking opportunities at industry events where you might connect with relevant solution providers.',
                            'Educational content and webinars that might help address your current business challenges effectively.',
                            'Stay connected through our professional network for future opportunities and business relationship building.'
                        ]
                    ],
                    'In Review' => [
                        'subjects' => ['Application Under Review', 'Additional Information Needed', 'Review Status Update', 'Documentation Request', 'Technical Assessment', 'Reference Verification', 'Compliance Check', 'Financial Review', 'Timeline Confirmation', 'Decision Pending'],
                        'descriptions' => [
                            'Your application is currently under review by our qualification team. We will update you within 48 hours.',
                            'To complete our review process, we need some additional information about your technical infrastructure and requirements.',
                            'We are making good progress on your qualification review and expect to have an update by end.',
                            'Documentation request for additional materials to support your qualification and evaluation process.',
                            'Technical assessment in progress to validate your infrastructure compatibility with our solution requirements.',
                            'Reference verification process initiated to confirm your business credentials and operational history.',
                            'Compliance check underway to ensure alignment with our partnership and engagement requirements.',
                            'Financial review being conducted to assess your organization stability and investment capacity.',
                            'Timeline confirmation needed to align our review process with your decision-making schedule.',
                            'Decision pending final review by our executive team. We expect to have results within one week.'
                        ]
                    ],
                    'Qualified' => [
                        'subjects' => ['Qualification Approved', 'Next Steps Discussion', 'Solution Presentation', 'Sales Team Introduction', 'Discovery Session', 'Proposal Timeline', 'Stakeholder Meeting', 'Technical Deep Dive', 'ROI Discussion', 'Implementation Planning'],
                        'descriptions' => [
                            'Congratulations! Your qualification has been approved. Let us discuss the next steps in our process.',
                            'Now that you are qualified, we would like to schedule a detailed presentation of our solution capabilities.',
                            'Our sales team will contact you shortly to discuss pricing options and implementation approach for your organization.',
                            'Sales team introduction meeting to assign dedicated account manager and establish communication protocols.',
                            'Discovery session scheduled to understand your specific requirements and business objectives in detail.',
                            'Proposal timeline discussion to align our preparation schedule with your evaluation and decision process.',
                            'Stakeholder meeting arrangement to ensure all decision makers are engaged in the evaluation process.',
                            'Technical deep dive session with our architects to validate solution fit and integration requirements.',
                            'ROI discussion to quantify expected benefits and establish success metrics for the implementation.',
                            'Implementation planning overview to discuss deployment approach, timeline, and resource requirements.'
                        ]
                    ],
                    'Approved' => [
                        'subjects' => ['Final Approval Confirmation', 'Contract Preparation', 'Implementation Planning', 'Legal Review', 'Project Kickoff', 'Team Assignment', 'Success Metrics', 'Training Schedule', 'Go-Live Planning', 'Partnership Launch'],
                        'descriptions' => [
                            'We are pleased to confirm your final approval. Our legal team will prepare the contract documents.',
                            'Contract preparation is underway. Please review the attached terms and conditions for your approval and signature.',
                            'Let us begin planning the implementation process and establish project timelines and milestones for successful deployment.',
                            'Legal review process initiated to finalize contract terms and ensure compliance with all requirements.',
                            'Project kickoff meeting scheduled to introduce teams and establish project governance and communication protocols.',
                            'Team assignment completed with dedicated project manager and technical resources allocated for your implementation.',
                            'Success metrics definition session to establish KPIs and measurement criteria for project outcomes.',
                            'Training schedule preparation to ensure your team is ready for system adoption and effective utilization.',
                            'Go-live planning session to discuss deployment strategy, testing phases, and rollback procedures.',
                            'Partnership launch celebration planned to mark the beginning of our long-term business relationship.'
                        ]
                    ],
                    'Rejected' => [
                        'subjects' => ['Application Status Update', 'Feedback and Recommendations', 'Future Consideration', 'Alternative Options', 'Partner Referrals', 'Improvement Areas', 'Reapplication Process', 'Market Changes', 'Relationship Maintenance', 'Professional Network'],
                        'descriptions' => [
                            'After careful review, we are unable to proceed at this time. We appreciate your interest in solutions.',
                            'We have provided detailed feedback on your application and recommendations for future consideration and improvement.',
                            'While we cannot move forward now, we encourage you to reapply in six months after addressing feedback.',
                            'Alternative options may be available through our partner network that better align with your current situation.',
                            'Partner referrals provided for organizations that might be better suited to address your specific requirements.',
                            'Improvement areas identified that could strengthen future applications and increase approval probability.',
                            'Reapplication process guidelines provided for when you are ready to pursue qualification again.',
                            'Market changes may create new opportunities in the future that better align with your profile.',
                            'Relationship maintenance through periodic check-ins to assess changes in your business situation.',
                            'Professional network connections available to help you find suitable solutions for your current needs.'
                        ]
                    ]
                ],
                'Sales' => [
                    'Draft' => [
                        'subjects' => ['Initial Inquiry Response', 'Information Request', 'Preliminary Discussion', 'Requirements Gathering', 'Solution Overview', 'Budget Discussion', 'Timeline Planning', 'Stakeholder Introduction', 'Discovery Session', 'Needs Assessment'],
                        'descriptions' => [
                            'Thank you for your inquiry about our enterprise solutions. We would like to schedule a preliminary discussion.',
                            'We have received your information request and our sales team will contact you within 24 hours.',
                            'I would like to schedule a brief call to understand your requirements and see how we can help.',
                            'We are gathering requirements for your project and would like to discuss your specific needs in detail.',
                            'Our solution overview presentation is ready. Let us schedule a meeting to demonstrate our capabilities.',
                            'Budget discussion is important for proper solution sizing. Could we schedule a call to discuss investment parameters?',
                            'Timeline planning session needed to align our implementation schedule with your business requirements and deadlines.',
                            'Stakeholder introduction meeting would help us understand decision-making process and key requirements from all parties.',
                            'Discovery session scheduled to deep dive into your current challenges and identify optimization opportunities.',
                            'Needs assessment questionnaire attached. Please complete and return so we can prepare tailored solution presentation.'
                        ]
                    ],
                    'Sent' => [
                        'subjects' => ['Proposal Sent', 'Quote Delivery', 'Solution Overview', 'Technical Specifications', 'Implementation Plan', 'Contract Terms', 'Pricing Details', 'Service Agreement', 'Project Timeline', 'Next Steps'],
                        'descriptions' => [
                            'Your customized proposal has been sent. Please review and let us know if you have any questions.',
                            'Quote delivery confirmation: Your detailed pricing information is attached for review.',
                            'Solution overview document has been prepared based on our discussion. Please review at your convenience.',
                            'Technical specifications document outlines all system requirements and integration details for your review.',
                            'Implementation plan attached showing project phases, milestones, and resource requirements for deployment.',
                            'Contract terms and conditions document sent for legal review. Please share with your procurement team.',
                            'Detailed pricing breakdown includes all components, services, and optional features for your consideration.',
                            'Service agreement draft outlines support levels, response times, and ongoing maintenance terms.',
                            'Project timeline document shows implementation phases and key milestones for successful deployment.',
                            'Next steps document outlines the approval process and what we need from your team to proceed.'
                        ]
                    ],
                    'Open' => [
                        'subjects' => ['Proposal Follow-up', 'Questions and Clarifications', 'Next Steps Discussion', 'Technical Review', 'Stakeholder Meeting', 'Budget Approval', 'Timeline Confirmation', 'Reference Check', 'Demo Request', 'Final Review'],
                        'descriptions' => [
                            'Following up on the proposal we sent last week. Do you have any questions or need clarifications?',
                            'We are available to answer any questions about our proposal and discuss implementation details.',
                            'Would you like to schedule a call to discuss next steps and address any concerns you might have?',
                            'Technical review meeting available to discuss integration requirements and system compatibility.',
                            'Stakeholder meeting can be arranged to present proposal to your executive team and decision makers.',
                            'Budget approval process support available. We can provide additional justification materials if needed.',
                            'Timeline confirmation needed to finalize implementation schedule and resource allocation planning.',
                            'Reference check calls can be arranged with existing clients in your industry for validation.',
                            'Live demo session available to showcase specific features relevant to your use case.',
                            'Final review meeting to address any remaining questions before moving to contract negotiation.'
                        ]
                    ],
                    'Revised' => [
                        'subjects' => ['Revised Proposal', 'Updated Quote', 'Modified Solution', 'Adjusted Timeline', 'Pricing Revision', 'Scope Changes', 'Contract Updates', 'Feature Modifications', 'Implementation Changes', 'Final Version'],
                        'descriptions' => [
                            'Based on your feedback, we have revised our proposal to better align with your requirements.',
                            'Updated quote reflecting the changes we discussed. Please review the modified terms and pricing.',
                            'Modified solution proposal addresses all your concerns and includes the requested enhancements.',
                            'Adjusted timeline accommodates your preferred implementation schedule and resource availability.',
                            'Pricing revision reflects the scope changes and additional features you requested for the project.',
                            'Scope changes have been incorporated based on stakeholder feedback and business requirements.',
                            'Contract updates include revised terms and conditions as discussed with your legal team.',
                            'Feature modifications ensure the solution perfectly matches your workflow and business processes.',
                            'Implementation approach has been changed to minimize business disruption during deployment.',
                            'Final version of proposal incorporates all feedback and is ready for executive approval.'
                        ]
                    ],
                    'Declined' => [
                        'subjects' => ['Thank You for Your Consideration', 'Future Opportunities', 'Feedback Request', 'Lessons Learned', 'Relationship Maintenance', 'Alternative Solutions', 'Market Updates', 'Industry Insights', 'Partnership Potential', 'Stay Connected'],
                        'descriptions' => [
                            'Thank you for considering our proposal. We appreciate the opportunity to present our solution.',
                            'While this opportunity did not work out, we would welcome future opportunities to work together.',
                            'We would appreciate any feedback on our proposal to help us improve our future offerings.',
                            'Lessons learned from this process will help us better serve similar organizations in the future.',
                            'We value the relationship built during this process and hope to stay connected for future needs.',
                            'Alternative solutions may become available that better fit your requirements and budget constraints.',
                            'We will keep you updated on new product releases and features that might interest your organization.',
                            'Industry insights and best practices will be shared through our newsletter and thought leadership content.',
                            'Partnership opportunities may arise where we can collaborate in different capacity for mutual benefit.',
                            'Please stay connected through our professional network for future business opportunities and updates.'
                        ]
                    ],
                    'Accepted' => [
                        'subjects' => ['Proposal Acceptance', 'Welcome Aboard', 'Implementation Planning', 'Project Kickoff', 'Team Introductions', 'Success Metrics', 'Training Schedule', 'Go-Live Planning', 'Support Setup', 'Partnership Launch'],
                        'descriptions' => [
                            'Thank you for accepting our proposal! We are excited to begin working with your team.',
                            'Welcome aboard! Our implementation team will contact you shortly to begin the onboarding process.',
                            'Implementation planning meeting scheduled for next week to discuss timeline and project phases.',
                            'Project kickoff meeting will introduce all team members and establish communication protocols.',
                            'Team introductions scheduled to ensure smooth collaboration between our organizations throughout implementation.',
                            'Success metrics and KPIs will be defined to measure project progress and business value delivery.',
                            'Training schedule prepared to ensure your team is fully prepared for system go-live and adoption.',
                            'Go-live planning session will finalize deployment strategy and rollback procedures if needed.',
                            'Support setup includes dedicated success manager and 24/7 technical support during transition.',
                            'Partnership launch celebration planned to mark the beginning of our long-term business relationship.'
                        ]
                    ]
                ]
            ];

            // Lead discussion templates (30-35 words each)
            $leadDiscussionTemplates = [
                'Marketing' => [
                    'Prospect' => [
                        'Initial contact established through website form submission. Lead shows interest in our marketing automation platform and requested information about pricing and features.',
                        'Downloaded our whitepaper on digital marketing trends. Appears to be researching solutions for their growing business and evaluating different platforms for implementation.',
                        'Attended our webinar on customer acquisition strategies. Asked relevant questions during Q&A session and showed genuine interest in our approach and methodology.',
                        'Subscribed to our newsletter and engaged with multiple email campaigns. Showing consistent interest in our content and demonstrating potential for conversion to qualified lead.',
                        'Market research report download indicates serious evaluation of industry solutions. Lead is gathering information for upcoming technology investment decision.',
                        'Best practices guide engagement shows interest in improving current marketing operations. Lead appears to be in early stages of solution evaluation.',
                        'Webinar attendance for advanced strategies session demonstrates commitment to learning. Lead asked specific questions about implementation and ROI expectations.',
                        'Case study collection download suggests lead is researching success stories. Interest in similar company implementations indicates qualified prospect potential.',
                        'Trend analysis engagement shows forward-thinking approach to business. Lead is staying informed about market developments and competitive advantages.',
                        'Expert interview series participation indicates thought leadership interest. Lead values industry insights and best practices for strategic decision making.'
                    ],
                    'Contacted' => [
                        'First phone call completed successfully. Lead confirmed they are evaluating marketing automation solutions for Q2 implementation and have allocated budget for investment.',
                        'Email exchange initiated with marketing director. Lead provided details about their current marketing challenges and team size for solution sizing and customization.',
                        'LinkedIn connection established with decision maker. Lead shared their company growth plans and marketing objectives for the upcoming fiscal year and expansion.',
                        'Follow-up call scheduled for next week. Lead requested information about our integration capabilities with their existing CRM system and marketing technology stack.',
                        'Discovery call setup completed with marketing team. Lead confirmed evaluation timeline and budget parameters for comprehensive solution assessment and selection.',
                        'Requirements gathering session scheduled to understand specific needs. Lead provided preliminary information about current processes and desired improvements.',
                        'Solution fit assessment planned to determine alignment with business goals. Lead expressed interest in seeing demonstration of key features and capabilities.',
                        'Stakeholder introduction meeting arranged to understand decision process. Lead identified key influencers and approval workflow for vendor selection.',
                        'Timeline discussion confirmed alignment with business planning cycle. Lead has flexibility in implementation schedule to ensure successful deployment.',
                        'Budget planning conversation revealed adequate investment capacity. Lead confirmed authority to make decision within established parameters and timeline.'
                    ],
                    'Engaged' => [
                        'Demo session completed successfully with marketing team. Lead was impressed with our campaign management features and analytics capabilities for their specific use case.',
                        'Technical discussion held with their IT team. Confirmed compatibility with their existing CRM system and validated integration approach for seamless data flow.',
                        'Case study presentation delivered to stakeholders. Lead found our retail client success story particularly relevant to their industry and business model for implementation.',
                        'Pricing discussion initiated with procurement team. Lead confirmed budget availability and decision-making timeline for final selection and contract negotiation process.',
                        'ROI analysis presentation showed compelling business case. Lead finance team approved investment level based on projected cost savings and revenue growth.',
                        'Implementation planning session outlined deployment approach and timeline. Lead confirmed resource availability and project team assignments for successful execution.',
                        'Reference customer call arranged with similar organization. Lead wants to validate implementation experience and results achieved with our solution.',
                        'Proof of concept proposal presented for limited pilot program. Lead interested in testing solution capabilities with actual data before full commitment.',
                        'Security review documentation provided to compliance team. Lead confirmed all regulatory requirements are met with enhanced security features.',
                        'Integration assessment completed showing seamless compatibility. Lead IT team approved technical approach and confirmed implementation feasibility.'
                    ],
                    'Qualified' => [
                        'Budget confirmed at fifty thousand annually. Decision maker identified and meeting scheduled for proposal presentation with executive team and key stakeholders for approval.',
                        'Technical requirements gathered comprehensively. Lead confirmed they need multi-channel campaign management and advanced analytics for their marketing operations and reporting needs.',
                        'Stakeholder meeting completed successfully. All key decision makers are aligned on moving forward with evaluation process and have committed timeline for final decision.',
                        'Reference call arranged with existing client. Lead wants to hear about implementation experience and results achieved to validate our solution for their organization.',
                        'Stakeholder alignment achieved across marketing and IT teams. All departments support solution selection and have committed resources for implementation.',
                        'Contract terms discussion completed with legal team. Standard terms acceptable with minor modifications for compliance with corporate procurement policies.',
                        'Service levels review demonstrates our commitment to success. Support structure and response times exceed current vendor standards and expectations.',
                        'Success metrics identification session defined measurable outcomes. Lead agreed to quarterly reviews and performance tracking for ROI validation.',
                        'Risk assessment completed with mitigation strategies approved. All potential challenges identified with contingency plans for successful implementation.',
                        'Executive presentation scheduled to present business case. Leadership team review planned for final approval and project authorization.'
                    ],
                    'Converted' => [
                        'Contract signed and implementation kickoff scheduled. Lead is excited to start using our platform and has assigned dedicated team for onboarding and training.',
                        'Onboarding process initiated with marketing team. Lead team assigned and training schedule confirmed for next month to ensure successful adoption and utilization.',
                        'Success metrics defined with marketing director. Lead agreed to quarterly reviews and performance tracking to measure ROI and campaign effectiveness for optimization.',
                        'Integration planning completed with IT team. Technical team ready to begin data migration process and system configuration for go-live within agreed timeline.',
                        'Implementation kickoff meeting completed with project team introductions. Communication protocols established and project charter approved by all stakeholders.',
                        'Training program customized for marketing team roles and responsibilities. Learning management system configured with role-based access and certification tracking.',
                        'Data migration planning session completed with technical teams. Migration approach validated and testing schedule established for quality assurance.',
                        'System configuration customized to match business processes. Workflow automation rules configured to optimize marketing operations and efficiency.',
                        'Go-live planning includes comprehensive testing and user acceptance. Rollback procedures established for risk mitigation during deployment phase.',
                        'Success milestones defined with quarterly business reviews scheduled. Partnership launched with dedicated account management and ongoing support commitment.'
                    ]
                ],
                'Lead Qualification' => [
                    'Unqualified' => [
                        'Initial assessment completed thoroughly. Lead does not meet our minimum requirements for enterprise solution due to company size and budget constraints for implementation.',
                        'Budget constraints identified during qualification. Lead cannot meet our minimum investment threshold at this time but expressed interest for future consideration when budget allows.',
                        'Timeline mismatch discovered during evaluation. Lead needs immediate solution but our implementation requires three-month minimum timeline for proper deployment and training.',
                        'Technical incompatibility confirmed with IT team. Lead legacy systems cannot support our integration requirements without significant infrastructure upgrades and additional investment.',
                        'Resource recommendations provided for addressing current challenges. Lead appreciates guidance and will consider alternative approaches that better fit their situation.',
                        'Partner referrals offered for organizations specializing in smaller implementations. Lead grateful for connections to more suitable solution providers.',
                        'Market updates promised when new solutions become available. Lead interested in staying informed about future offerings that might align better.',
                        'Industry insights sharing established through newsletter subscription. Lead values thought leadership content and best practices for business improvement.',
                        'Networking opportunities discussed for industry events and connections. Lead interested in professional development and industry relationship building.',
                        'Educational content access provided for current business challenges. Lead appreciates resources and will stay connected for future opportunities.'
                    ],
                    'In Review' => [
                        'Application submitted and under review by qualification team. Initial assessment shows potential fit based on company size, budget, and technical requirements for solution.',
                        'Documentation review in progress with evaluation team. Lead provided comprehensive information about their requirements, current systems, and implementation timeline for assessment.',
                        'Technical evaluation scheduled with architecture team. Our architects will assess their infrastructure compatibility and integration requirements for successful implementation and deployment.',
                        'Reference checks initiated with previous vendors. Contacting their current solution providers to understand their implementation history and technical capabilities for validation.',
                        'Documentation request sent for additional materials supporting qualification. Lead responsive and providing requested information promptly for evaluation process.',
                        'Technical assessment in progress with infrastructure compatibility review. Architecture team evaluating integration requirements and system compatibility for implementation.',
                        'Reference verification process underway to confirm business credentials. Previous vendor relationships and implementation history being validated for qualification.',
                        'Compliance check initiated to ensure alignment with partnership requirements. Regulatory and governance standards being reviewed for qualification approval.',
                        'Financial review conducted to assess organizational stability. Investment capacity and financial health evaluation completed for qualification decision.',
                        'Decision pending final review by executive qualification committee. All assessment criteria evaluated and recommendation prepared for leadership approval.'
                    ],
                    'Qualified' => [
                        'Qualification approved based on budget, authority, need, and timeline criteria. Moving to sales process with dedicated account manager for detailed solution presentation and proposal.',
                        'Technical fit confirmed by engineering team. Lead infrastructure can support our solution with minimal modifications and integration work required for successful deployment.',
                        'Decision maker access verified successfully. Lead has direct access to C-level executives for approval and can make final selection within established timeline for implementation.',
                        'Implementation timeline aligned with requirements. Lead can accommodate our standard four-month deployment schedule and has allocated necessary resources for project success.',
                        'Sales team introduction completed with dedicated account manager assigned. Lead briefed on next steps and evaluation process for solution presentation.',
                        'Discovery session scheduled to understand specific requirements in detail. Lead confirmed availability and stakeholder participation for comprehensive needs assessment.',
                        'Proposal timeline established aligning with business decision schedule. Lead committed to evaluation timeline and final selection within agreed timeframe.',
                        'Stakeholder meeting arranged to engage all decision makers. Lead confirmed executive participation and decision-making authority for vendor selection.',
                        'Technical deep dive session planned with architecture team. Lead IT team available for detailed integration and compatibility assessment.',
                        'Implementation planning overview scheduled to discuss deployment approach. Lead project team identified and resource allocation confirmed for success.'
                    ],
                    'Approved' => [
                        'Final approval granted by executive committee. Lead meets all criteria for enterprise engagement and has been prioritized for immediate sales process and solution presentation.',
                        'Legal review completed successfully without issues. No compliance issues identified for this engagement and contract terms have been pre-approved for expedited processing.',
                        'Risk assessment passed with high confidence. Lead company shows strong financial stability and growth trajectory making them ideal candidate for long-term partnership.',
                        'Strategic fit confirmed by leadership team. This engagement aligns with our target market and growth objectives making it priority opportunity for sales team.',
                        'Legal review process completed with contract terms pre-approved. Standard framework acceptable with expedited processing available for faster engagement.',
                        'Project kickoff meeting scheduled with implementation team assigned. Lead excited to begin engagement and has allocated dedicated resources for success.',
                        'Team assignment completed with dedicated project manager and technical resources. Lead implementation team identified and ready for project initiation.',
                        'Success metrics definition session planned to establish KPIs. Lead committed to measuring outcomes and tracking business value delivery.',
                        'Training schedule preparation underway for effective adoption. Lead team ready for comprehensive training program and certification process.',
                        'Partnership launch celebration planned to mark relationship beginning. Long-term strategic partnership established with mutual commitment to success.'
                    ],
                    'Rejected' => [
                        'Application rejected due to insufficient budget allocation for enterprise solution requirements. Lead cannot meet minimum investment threshold despite interest in our platform.',
                        'Technical incompatibility issues cannot be resolved within reasonable timeframe and budget constraints. Lead legacy systems require extensive upgrades for successful integration.',
                        'Decision-making authority unclear after evaluation. Lead cannot provide access to final decision makers and approval process is too complex for our sales cycle.',
                        'Timeline constraints prevent successful implementation. Lead needs faster deployment than we can provide while maintaining quality standards and proper training requirements.',
                        'Alternative options explored through partner network for better fit. Lead appreciates referrals to organizations more suited to their specific requirements.',
                        'Partner referrals provided for specialized solution providers. Lead grateful for connections to vendors with more appropriate offerings for their situation.',
                        'Improvement areas identified for future consideration and reapplication. Lead understands requirements and will address gaps for future qualification.',
                        'Reapplication process guidelines provided for future opportunity. Lead encouraged to reapply when business situation changes and requirements are met.',
                        'Market changes may create future opportunities for engagement. Lead will be contacted when new solutions or programs become available.',
                        'Professional network connections maintained for future opportunities. Lead appreciates relationship and will stay connected for potential future engagement.'
                    ]
                ],
                'Sales' => [
                    'Draft' => [
                        'Initial inquiry received through website contact form. Lead is interested in enterprise solution pricing and implementation timeline for Q2 deployment.',
                        'Phone screening completed successfully. Lead confirmed budget availability and decision-making authority. Discovery call scheduled for next week to discuss detailed requirements.',
                        'Discovery call scheduled for next week. Lead provided preliminary requirements and timeline. Moving forward with detailed needs assessment and comprehensive solution presentation.',
                        'Lead qualification criteria met based on budget, authority, need, and timeline. Moving forward with detailed needs assessment and comprehensive solution presentation for stakeholders.',
                        'Initial assessment completed. Client has complex requirements that will need custom solution development and implementation approach for their specific business needs.',
                        'Client expressed interest in our premium package but needs approval from their board of directors before proceeding with any financial commitments.',
                        'Meeting scheduled for next week to discuss their specific requirements and how our services can address their current business challenges.',
                        'Client called to discuss their budget constraints. We need to prepare flexible proposal that meets their needs within allocated budget.',
                        'Received detailed RFP from client. We need to prepare comprehensive response addressing all their technical requirements and business objectives.',
                        'Client seems very interested but wants to see case studies and references from similar projects we have completed successfully.'
                    ],
                    'Sent' => [
                        'Comprehensive proposal sent including technical specifications, pricing, and implementation timeline. Lead confirmed receipt and committed to review within one week for decision.',
                        'Quote delivered with multiple pricing options and service levels. Lead confirmed receipt and review timeline. Follow-up meeting scheduled for next week to discuss.',
                        'Solution overview presentation completed successfully. Lead expressed strong interest in our platform capabilities and requested detailed pricing information for budget approval process.',
                        'Proposal follow-up scheduled for next week. Lead team is reviewing internally with stakeholders. Decision expected within two weeks of review completion and evaluation.',
                        'Technical documentation sent to IT team for infrastructure compatibility review. They confirmed our solution meets all their security and integration requirements.',
                        'Implementation plan delivered showing project phases and milestones. Lead project manager confirmed timeline aligns with their business objectives and resource availability.',
                        'Contract terms and service agreement sent for legal review. Lead procurement team is evaluating terms and conditions for compliance with policies.',
                        'Pricing breakdown provided with detailed cost analysis and ROI projections. Lead finance team is reviewing investment parameters and budget allocation requirements.',
                        'Service level agreement document sent outlining support commitments and response times. Lead operations team is reviewing to ensure alignment with expectations.',
                        'Next steps document provided with clear action items and decision timeline. Lead confirmed they will provide feedback within five business days.'
                    ],
                    'Open' => [
                        'Proposal review meeting held with key stakeholders. Lead asked detailed questions about integration capabilities and ongoing support services available for implementation.',
                        'Technical discussion completed with IT team. All compatibility concerns addressed successfully. Lead confirmed technical requirements can be met with our proposed solution approach.',
                        'Pricing negotiation in progress with procurement team. Lead requested volume discounts and extended payment terms. Management approval required for final decision making.',
                        'Reference customer call arranged for next week. Lead wants to hear about implementation experience from similar client in their industry sector for validation.',
                        'Stakeholder presentation delivered to executive team. All decision makers are aligned on moving forward with evaluation process and committed to timeline.',
                        'Budget approval process initiated with finance team. Lead confirmed investment parameters are within allocated budget for this fiscal year and project priority.',
                        'Timeline confirmation meeting scheduled to align implementation phases with business calendar and resource availability for successful deployment and adoption.',
                        'Security review completed with compliance team. All data protection and regulatory requirements have been validated and approved for implementation.',
                        'Demo session conducted for end users. Positive feedback received on user interface and functionality. Training requirements have been identified and documented.',
                        'Final questions addressed regarding ongoing support and maintenance. Lead satisfied with service level commitments and escalation procedures for issue resolution.'
                    ],
                    'Revised' => [
                        'Proposal revised based on client feedback. Adjusted pricing structure and implementation approach to better align with their budget and timeline constraints for deployment.',
                        'Updated solution design to address specific integration requirements and compliance needs. Technical team validated all modifications can be implemented successfully within timeframe.',
                        'Modified contract terms to accommodate client procurement policies and approval processes. Legal teams from both sides reviewing updated terms and conditions for agreement.',
                        'Revised timeline presented to align with client budget cycle and resource availability. Implementation phases adjusted to minimize business disruption during deployment and transition.',
                        'Pricing adjustments made to accommodate budget constraints while maintaining solution integrity. Alternative service packages presented to meet financial requirements and objectives.',
                        'Scope modifications implemented based on stakeholder priorities. Core functionality preserved while optional features moved to future phases for budget optimization.',
                        'Implementation approach revised to support phased deployment. This allows for gradual rollout and reduced risk while meeting immediate business needs.',
                        'Contract terms updated to reflect new pricing and scope. Legal review completed and all compliance requirements addressed in revised agreement.',
                        'Technical specifications adjusted to work with existing infrastructure. Integration complexity reduced while maintaining full functionality and performance requirements.',
                        'Final revision incorporates all stakeholder feedback. Proposal now perfectly aligns with business requirements, budget constraints, and implementation timeline preferences.'
                    ],
                    'Declined' => [
                        'Lead declined our proposal due to budget constraints. They appreciated our solution but cannot proceed with investment at this time for implementation.',
                        'Proposal rejected in favor of competitor solution. Lead cited lower pricing as primary decision factor. Maintaining relationship for future opportunities and potential partnerships.',
                        'Lead decided to postpone project implementation until next fiscal year. Budget allocation not approved for current year. Following up in six months for reconsideration.',
                        'Internal priorities changed and project was cancelled. Lead confirmed our solution was technically sound but timing no longer works for their organization and goals.',
                        'Executive team decided to focus on other initiatives this year. Our solution remains of interest for future consideration when priorities shift.',
                        'Budget reallocation to other projects prevented approval. Lead expressed continued interest and requested to be contacted when budget becomes available.',
                        'Timing mismatch with business calendar prevented implementation. Lead wants to revisit opportunity during next planning cycle when resources are available.',
                        'Organizational restructuring delayed all new technology investments. Lead confirmed interest remains strong for future engagement when situation stabilizes.',
                        'Compliance requirements changed making current solution unsuitable. Lead appreciated our efforts and will consider us for future projects.',
                        'Market conditions forced budget freeze on all non-essential projects. Lead committed to reconnecting when economic situation improves for organization.'
                    ],
                    'Accepted' => [
                        'Proposal accepted! Contract negotiation initiated with legal teams from both organizations. Implementation planning meeting scheduled to discuss project timeline and resource allocation requirements.',
                        'Implementation planning meeting scheduled for next week. Project team assignments and timeline confirmed. Client success manager assigned and kickoff meeting planned for project initiation.',
                        'Onboarding process initiated successfully. Client success manager assigned and kickoff meeting planned. Technical team ready to begin implementation and data migration process for deployment.',
                        'Contract signed and project officially launched. Implementation team ready to begin deployment. First milestone deliverables scheduled for completion within thirty days of project start.',
                        'Project kickoff meeting completed successfully. All team members introduced and communication protocols established. Project charter signed and approved by stakeholders.',
                        'Implementation roadmap finalized with clear milestones and deliverables. Resource allocation confirmed and project timeline approved by both organizations for execution.',
                        'Technical team assigned and infrastructure assessment completed. All system requirements validated and integration planning initiated for seamless deployment.',
                        'Training schedule established for end users and administrators. Learning management system configured and training materials prepared for effective knowledge transfer.',
                        'Success metrics defined and measurement framework established. Regular review meetings scheduled to track progress and ensure project delivers expected business value.',
                        'Partnership officially launched with celebration meeting. Long-term relationship established with dedicated account management and ongoing support commitment.'
                    ]
                ]
            ];

            foreach ($leads as $lead) {
                if (!$lead) {
                    continue;
                }
                $pipelineName = $lead->pipeline?->name ?? 'Sales';
                $stageName = $lead->stage?->name ?? 'Draft';

                // Create 1-2 emails per lead
                $emailCount = rand(1, 2);
                $emailTemplates = $leadEmailTemplates[$pipelineName][$stageName] ?? $leadEmailTemplates['Sales']['Draft'];

                for ($i = 0; $i < $emailCount; $i++) {
                    $templateIndex = $i % count($emailTemplates['subjects']);

                    if (!$lead->id || !$lead->email) {
                        continue;
                    }

                    LeadEmail::create([
                        'lead_id' => $lead->id,
                        'to' => $lead->email,
                        'subject' => $emailTemplates['subjects'][$templateIndex],
                        'description' => $emailTemplates['descriptions'][$templateIndex],
                        'created_at' => $lead->created_at->addDays(rand(1, 7))->addHours(rand(1, 23)),
                    ]);
                }

                // Create 1-2 discussions per lead
                $discussionCount = rand(1, 2);
                $discussionTemplates = $leadDiscussionTemplates[$pipelineName][$stageName] ?? $leadDiscussionTemplates['Sales']['Draft'];

                for ($i = 0; $i < $discussionCount; $i++) {
                    if (empty($users)) {
                        break;
                    }
                    $randomUser = $users[array_rand($users)];
                    $templateIndex = $i % count($discussionTemplates);

                    if (!$lead->id || !$randomUser) {
                        continue;
                    }

                    LeadDiscussion::create([
                        'lead_id' => $lead->id,
                        'comment' => $discussionTemplates[$templateIndex],
                        'creator_id' => $randomUser,
                        'created_by' => $userId,
                        'created_at' => $lead->created_at->addDays(rand(2, 10))->addHours(rand(1, 23)),
                    ]);
                }
            }
        }
    }

    private function seedLeadFiles($leads): void
    {
        $leadFilesData = [
            // Marketing Pipeline - 17 records (picked from all 30 Marketing leads)
            ['subject' => 'Marketing Automation', 'files' => ['Marketing_Automation_Lead_File.pdf']],
            ['subject' => 'Custom Development', 'files' => ['Custom_Development_Lead_File.jpg']],
            ['subject' => 'Service Integration', 'files' => ['Service_Integration_Lead_File.docx']],
            ['subject' => 'Cloud Migration', 'files' => ['Cloud_Migration_Lead_File.docx']],
            ['subject' => 'AI Implementation', 'files' => ['AI_Implementation_Lead_File.png', 'AI_Implementation_Lead_File.pdf']],
            ['subject' => 'Payment Gateway', 'files' => ['Payment_Gateway_Lead_File.pdf', 'Payment_Gateway_Lead_File.jpg']],
            ['subject' => 'Platform Migration', 'files' => ['Platform_Migration_Lead_File.png', 'Platform_Migration_Lead_File.docx']],
            ['subject' => 'Security Assessment', 'files' => ['Security_Assessment_Lead_File.docx']],
            ['subject' => 'Solution Consultation', 'files' => ['Solution_Consultation_Lead_File.png', 'Solution_Consultation_Lead_File.pdf']],
            ['subject' => 'Mobile App Development', 'files' => ['Mobile_App_Development_Lead_File.jpg', 'Mobile_App_Development_Lead_File.pdf']],
            ['subject' => 'Data Analysis Tools', 'files' => ['Data_Analysis_Tools_Lead_File.png']],
            ['subject' => 'Product Demo Request', 'files' => ['Product_Demo_Request_Lead_File.png']],
            ['subject' => 'Pricing Information', 'files' => ['Pricing_Information_Lead_File.jpg']],
            ['subject' => 'E-commerce Platform', 'files' => ['E_commerce_Platform_Lead_File.png']],
            ['subject' => 'Partnership Inquiry', 'files' => ['Partnership_Inquiry_Lead_File.pdf', 'Partnership_Inquiry_Lead_File.docx']],
            ['subject' => 'Healthcare Solutions', 'files' => ['Healthcare_Solutions_Lead_File.jpg']],
            ['subject' => 'Production Optimization', 'files' => ['Production_Optimization_Lead_File.pdf']],

            // Lead Qualification Pipeline - 17 records (picked from all 30 LQ leads)
            ['subject' => 'ROI Assessment', 'files' => ['ROI_Assessment_Lead_File.pdf', 'ROI_Assessment_Lead_File.jpg']],
            ['subject' => 'Vendor Assessment', 'files' => ['Vendor_Assessment_Lead_File.jpg', 'Vendor_Assessment_Lead_File.pdf']],
            ['subject' => 'Cost Analysis', 'files' => ['Cost_Analysis_Lead_File.png']],
            ['subject' => 'Enterprise Assessment', 'files' => ['Enterprise_Assessment_Lead_File.png']],
            ['subject' => 'Integration Assessment', 'files' => ['Integration_Assessment_Lead_File.jpg']],
            ['subject' => 'Workflow Optimization', 'files' => ['Workflow_Optimization_Lead_File.docx']],
            ['subject' => 'Automation Feasibility', 'files' => ['Automation_Feasibility_Lead_File.png', 'Automation_Feasibility_Lead_File.pdf']],
            ['subject' => 'Performance Analysis', 'files' => ['Performance_Analysis_Lead_File.pdf']],
            ['subject' => 'Risk Management', 'files' => ['Risk_Management_Lead_File.png', 'Risk_Management_Lead_File.pdf']],
            ['subject' => 'Compliance Review', 'files' => ['Compliance_Review_Lead_File.jpg']],
            ['subject' => 'Security Evaluation', 'files' => ['Security_Evaluation_Lead_File.docx']],
            ['subject' => 'Quality Control System', 'files' => ['Quality_Control_System_Lead_File.png', 'Quality_Control_System_Lead_File.docx']],
            ['subject' => 'Procurement Review', 'files' => ['Procurement_Review_Lead_File.docx']],
            ['subject' => 'Budget Evaluation', 'files' => ['Budget_Evaluation_Lead_File.docx']],
            ['subject' => 'Data Management Review', 'files' => ['Data_Management_Review_Lead_File.png']],
            ['subject' => 'Timeline Planning', 'files' => ['Timeline_Planning_Lead_File.pdf']],
            ['subject' => 'Training Requirements', 'files' => ['Training_Requirements_Lead_File.pdf', 'Training Requirements_Lead_File.jpg']],

            // Sales Pipeline - 17 records (picked from all 30 Sales leads)
            ['subject' => 'Enterprise Software Purchase', 'files' => ['Enterprise_Software_Purchase_Lead_File.png']],
            ['subject' => 'Multi-Year Contract', 'files' => ['Multi_Year_Contract_Lead_File.pdf', 'Multi_Year_Contract_Lead_File.docx']],
            ['subject' => 'Analytics Platform License', 'files' => ['Analytics_Platform_License_Lead_File.pdf']],
            ['subject' => 'Healthcare Software License', 'files' => ['Healthcare_Software_License_Lead_File.docx']],
            ['subject' => 'Implementation Services', 'files' => ['Implementation_Services_Lead_File.png', 'Implementation_Services_Lead_File.pdf']],
            ['subject' => 'Development Framework Sale', 'files' => ['Development_Framework_Sale_Lead_File.png', 'Development_Framework_Sale_Lead_File.jpg']],
            ['subject' => 'Support Contract Extension', 'files' => ['Support_Contract_Extension_Lead_File.pdf']],
            ['subject' => 'Professional Services Quote', 'files' => ['Professional_Services_Quote_Lead_File.png', 'Professional_Services_Quote_Lead_File.docx']],
            ['subject' => 'Training Package Deal', 'files' => ['Training_Package_Deal_Lead_File.docx']],
            ['subject' => 'Annual License Renewal', 'files' => ['Annual_License_Renewal_Lead_File.png', 'Annual_License_Renewal_Lead_File.pdf']],
            ['subject' => 'Migration Service Purchase', 'files' => ['Migration_Service_Purchase_Lead_File.jpg']],
            ['subject' => 'Volume Discount Request', 'files' => ['Volume_Discount_Request_Lead_File.jpg']],
            ['subject' => 'Premium Package Upgrade', 'files' => ['Premium_Package_Upgrade_Lead_File.png']],
            ['subject' => 'Corporate License Agreement', 'files' => ['Corporate_License_Agreement_Lead_File.png']],
            ['subject' => 'E-commerce Solution Purchase', 'files' => ['E_commerce_Solution_Purchase_Lead_File.png']],
            ['subject' => 'Payment Processing Deal', 'files' => ['Payment_Processing_Deal_Lead_File.pdf', 'Payment_Processing_Deal_Lead_File.jpg']],
            ['subject' => 'Custom Solution Quote', 'files' => ['Custom_Solution_Quote_Lead_File.docx']],
        ];

        // Create files for each pipeline
        $pipelineFiles = [
            'Sales' => array_slice($leadFilesData, 0, 17),
            'Marketing' => array_slice($leadFilesData, 17, 17),
            'Lead Qualification' => array_slice($leadFilesData, 34, 17)
        ];

        foreach ($leads as $lead) {
            if (!$lead) {
                continue;
            }
            $pipelineName = $lead->pipeline?->name ?? 'Sales';
            $files = $pipelineFiles[$pipelineName] ?? $pipelineFiles['Sales'];

            // Select random file record for this lead
            $fileData = $files[array_rand($files)];

            foreach ($fileData['files'] as $fileName) {
                if (!$lead->id || !$fileName) {
                    continue;
                }

                \Workdo\Lead\Models\LeadFile::create([
                    'lead_id' => $lead->id,
                    'file_name' => $fileName,
                    'file_path' => $fileName,
                ]);
            }
        }
    }
}
