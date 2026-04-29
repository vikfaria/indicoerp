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
use Workdo\Lead\Models\DealFile;

class DemoDealEmailDiscussionSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $deals = Deal::where('created_by', $userId)->with(['pipeline', 'stage'])->get();
            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();
            $clients = User::where('created_by', $userId)->where('type', 'client')->get();

            if ($deals->isEmpty() || empty($users)) {
                return;
            }

            $this->seedDealFiles($deals);

            // Deal email templates based on pipeline and stage
            $dealEmailTemplates = [
                'Marketing' => [
                    'Campaign Launch' => [
                        'subjects' => ['Campaign Launch Confirmation', 'Creative Assets Approval', 'Launch Timeline Update', 'Channel Activation', 'Performance Tracking', 'Budget Allocation', 'Target Audience', 'Content Strategy', 'Media Planning', 'Launch Metrics'],
                        'descriptions' => [
                            'Your marketing campaign has been successfully launched across all selected channels. Initial metrics look promising.',
                            'Please review and approve the final creative assets before we proceed with the full campaign rollout.',
                            'Campaign launch timeline has been updated. All deliverables are on track for the agreed schedule.',
                            'Channel activation completed across digital platforms. All tracking pixels and analytics are properly configured.',
                            'Performance tracking dashboard is live. Real-time metrics are available for campaign monitoring and optimization.',
                            'Budget allocation confirmed across all channels. Spend distribution aligns with agreed strategy and objectives.',
                            'Target audience segments have been activated. Demographic and behavioral targeting parameters are optimized.',
                            'Content strategy implementation is complete. All messaging variants are live and performing as expected.',
                            'Media planning execution successful. Ad placements are active across premium inventory and targeted locations.',
                            'Launch metrics show strong initial performance. Early indicators suggest campaign will exceed projected results.'
                        ]
                    ],
                    'Lead Generation' => [
                        'subjects' => ['Lead Generation Results', 'Performance Metrics Update', 'Optimization Recommendations', 'Conversion Analysis', 'Quality Assessment', 'Cost Efficiency', 'Channel Performance', 'Audience Insights', 'A/B Test Results', 'Scaling Strategy'],
                        'descriptions' => [
                            'Your lead generation campaign has produced 150 qualified leads in the first week. Conversion rate is above target.',
                            'Weekly performance metrics show strong engagement rates. Click-through rate is 25% higher than industry average.',
                            'Based on current performance data, we recommend optimizing ad spend allocation to maximize lead quality.',
                            'Conversion analysis reveals highest performing creative and messaging combinations for continued optimization.',
                            'Quality assessment shows 85% of leads meet qualification criteria. Lead scoring model is working effectively.',
                            'Cost efficiency metrics demonstrate 30% lower cost per lead compared to industry benchmarks and expectations.',
                            'Channel performance analysis shows social media and search driving highest quality lead generation.',
                            'Audience insights reveal new demographic segments showing strong engagement and conversion potential.',
                            'A/B test results indicate landing page variant B increases conversion rate by 40% over original.',
                            'Scaling strategy prepared to increase budget allocation to highest performing channels and audiences.'
                        ]
                    ],
                    'Nurturing' => [
                        'subjects' => ['Lead Nurturing Progress', 'Content Performance Report', 'Engagement Analytics', 'Email Sequences', 'Behavioral Tracking', 'Personalization Results', 'Conversion Funnel', 'Automation Performance', 'Segmentation Analysis', 'Next Stage Progression'],
                        'descriptions' => [
                            'Lead nurturing sequences are performing well. 35% of leads have progressed to the next stage in funnel.',
                            'Content performance report shows high engagement with our educational email series and webinar invitations.',
                            'Engagement analytics indicate strong interest in product demos. Recommend increasing demo invitation frequency.',
                            'Email sequences showing excellent open and click rates. Personalized content is driving higher engagement.',
                            'Behavioral tracking reveals key interest indicators. Lead scoring model updated based on engagement patterns.',
                            'Personalization results show 50% higher engagement when content matches lead industry and role.',
                            'Conversion funnel analysis identifies optimization opportunities at each stage of the nurturing process.',
                            'Automation performance exceeds expectations. Triggered campaigns are delivering relevant content at optimal timing.',
                            'Segmentation analysis reveals distinct buyer personas with different content preferences and engagement patterns.',
                            'Next stage progression metrics show 40% of nurtured leads are ready for sales qualification.'
                        ]
                    ],
                    'Qualification' => [
                        'subjects' => ['Lead Qualification Update', 'Sales Handoff Preparation', 'Qualified Lead Report', 'Scoring Model Results', 'Readiness Assessment', 'Priority Ranking', 'Sales Intelligence', 'Handoff Documentation', 'Quality Metrics', 'Conversion Tracking'],
                        'descriptions' => [
                            'Lead qualification process has identified 45 high-value prospects ready for sales engagement.',
                            'Preparing qualified leads for sales handoff. All leads have been scored and prioritized based on fit criteria.',
                            'Monthly qualified lead report shows 20% increase in lead quality compared to previous campaign.',
                            'Scoring model results indicate strong correlation between engagement patterns and sales conversion rates.',
                            'Readiness assessment completed for all qualified leads. Sales team briefed on individual lead profiles.',
                            'Priority ranking established based on lead score, engagement level, and business fit criteria.',
                            'Sales intelligence package prepared including lead history, interests, and optimal contact approach.',
                            'Handoff documentation complete with detailed lead profiles and recommended next steps for sales team.',
                            'Quality metrics show 90% of qualified leads meet ideal customer profile criteria and requirements.',
                            'Conversion tracking system updated to monitor lead progression through sales pipeline stages.'
                        ]
                    ],
                    'Handoff' => [
                        'subjects' => ['Sales Handoff Complete', 'Lead Transfer Confirmation', 'Campaign Closure Report', 'Performance Summary', 'ROI Analysis', 'Success Metrics', 'Lessons Learned', 'Future Recommendations', 'Team Feedback', 'Next Campaign Planning'],
                        'descriptions' => [
                            'All qualified leads have been successfully transferred to the sales team with complete lead intelligence.',
                            'Lead transfer process completed. Sales team has been briefed on lead history and engagement patterns.',
                            'Campaign closure report shows successful achievement of all KPIs. ROI exceeded expectations by 30%.',
                            'Performance summary demonstrates campaign success across all key metrics and business objectives.',
                            'ROI analysis shows 250% return on marketing investment with strong pipeline contribution to revenue.',
                            'Success metrics exceeded targets in lead volume, quality, and cost efficiency across all channels.',
                            'Lessons learned documented for future campaign optimization and strategic planning improvements.',
                            'Future recommendations include scaling successful tactics and exploring new channel opportunities.',
                            'Team feedback collected from sales and marketing for continuous improvement of handoff process.',
                            'Next campaign planning initiated based on insights and performance data from this successful initiative.'
                        ]
                    ]
                ],
                'Lead Qualification' => [
                    'Initial Contact' => [
                        'subjects' => ['Initial Contact Confirmation', 'Discovery Call Scheduled', 'Information Gathering', 'Stakeholder Identification', 'Requirements Overview', 'Timeline Discussion', 'Budget Parameters', 'Decision Process', 'Next Steps Planning', 'Qualification Criteria'],
                        'descriptions' => [
                            'Thank you for your interest in our enterprise solution. We have scheduled an initial discovery call.',
                            'Initial contact has been established. Our team is excited to learn more about your business requirements.',
                            'We are gathering preliminary information to ensure our upcoming discussion is productive and focused.',
                            'Stakeholder identification process to understand decision-making structure and key influencers in your organization.',
                            'Requirements overview session scheduled to understand your business objectives and technical needs.',
                            'Timeline discussion to align our evaluation process with your decision-making schedule and implementation needs.',
                            'Budget parameters conversation to ensure our solutions align with your investment capacity and expectations.',
                            'Decision process clarification to understand approval workflow and key criteria for vendor selection.',
                            'Next steps planning to establish clear timeline and milestones for the qualification and evaluation process.',
                            'Qualification criteria review to ensure mutual fit and alignment before proceeding with detailed assessment.'
                        ]
                    ],
                    'Needs Assessment' => [
                        'subjects' => ['Needs Assessment Summary', 'Requirements Documentation', 'Solution Fit Analysis', 'Gap Analysis', 'Priority Ranking', 'Success Criteria', 'Stakeholder Interviews', 'Current State Review', 'Future State Vision', 'Implementation Readiness'],
                        'descriptions' => [
                            'Needs assessment completed successfully. We have documented your key requirements and pain points.',
                            'Requirements documentation has been prepared based on our detailed discussions with your team.',
                            'Solution fit analysis shows strong alignment between your needs and our platform capabilities.',
                            'Gap analysis identifies areas where our solution addresses current limitations and improvement opportunities.',
                            'Priority ranking established for requirements based on business impact and implementation complexity.',
                            'Success criteria defined to measure project outcomes and ensure alignment with business objectives.',
                            'Stakeholder interviews completed to gather comprehensive requirements from all affected departments.',
                            'Current state review documents existing processes, systems, and challenges that need to be addressed.',
                            'Future state vision articulated showing how our solution will transform your business operations.',
                            'Implementation readiness assessment confirms your organization is prepared for successful deployment.'
                        ]
                    ],
                    'Solution Fit' => [
                        'subjects' => ['Solution Demonstration', 'Technical Architecture Review', 'Implementation Planning', 'Integration Assessment', 'Security Validation', 'Performance Testing', 'User Experience Review', 'Scalability Analysis', 'Compliance Check', 'ROI Projection'],
                        'descriptions' => [
                            'Solution demonstration confirmed excellent fit for your use case. Technical team validated all requirements.',
                            'Technical architecture review completed. Our solution integrates seamlessly with your existing systems.',
                            'Implementation planning session scheduled to discuss timeline, resources, and project phases.',
                            'Integration assessment confirms compatibility with your current technology stack and data systems.',
                            'Security validation completed successfully. All compliance and data protection requirements are met.',
                            'Performance testing results demonstrate our solution can handle your projected workload and growth.',
                            'User experience review shows intuitive interface that will drive high adoption rates across your organization.',
                            'Scalability analysis confirms our platform can grow with your business needs and future requirements.',
                            'Compliance check validates adherence to all industry regulations and internal governance policies.',
                            'ROI projection shows significant business value and cost savings within 18 months of implementation.'
                        ]
                    ],
                    'Proposal Sent' => [
                        'subjects' => ['Proposal Delivery Confirmation', 'Proposal Review Meeting', 'Questions and Clarifications', 'Executive Summary', 'Technical Specifications', 'Commercial Terms', 'Implementation Timeline', 'Service Levels', 'Contract Terms', 'Next Steps Guide'],
                        'descriptions' => [
                            'Your detailed proposal has been delivered. Please review and let us know if you have questions.',
                            'Proposal review meeting scheduled for next week to discuss terms, pricing, and implementation approach.',
                            'We are available to answer any questions or provide clarifications on the proposal details.',
                            'Executive summary highlights key benefits, investment requirements, and expected business outcomes.',
                            'Technical specifications document outlines system architecture, integration approach, and performance metrics.',
                            'Commercial terms include pricing structure, payment schedule, and service level commitments.',
                            'Implementation timeline shows project phases, milestones, and resource requirements for successful deployment.',
                            'Service levels define support commitments, response times, and ongoing maintenance responsibilities.',
                            'Contract terms outline legal framework, intellectual property rights, and termination provisions.',
                            'Next steps guide provides clear action items and decision timeline for moving forward with implementation.'
                        ]
                    ],
                    'Decision' => [
                        'subjects' => ['Decision Timeline Update', 'Final Negotiations', 'Contract Preparation', 'Executive Approval', 'Legal Review', 'Procurement Process', 'Budget Approval', 'Stakeholder Alignment', 'Risk Assessment', 'Implementation Planning'],
                        'descriptions' => [
                            'Decision timeline has been confirmed. We understand you need two weeks for internal review and approval.',
                            'Final negotiations are progressing well. Legal teams from both sides are reviewing contract terms.',
                            'Contract preparation is underway. We expect to have final documents ready for signature next week.',
                            'Executive approval process initiated with presentation to leadership team and board of directors.',
                            'Legal review in progress to ensure contract terms comply with corporate policies and regulatory requirements.',
                            'Procurement process moving forward with vendor qualification and contract approval workflow.',
                            'Budget approval secured from finance team. Investment parameters confirmed and purchase order prepared.',
                            'Stakeholder alignment achieved across all departments. Implementation team assignments confirmed.',
                            'Risk assessment completed with mitigation strategies identified for all potential implementation challenges.',
                            'Implementation planning ready to begin immediately upon contract execution and project authorization.'
                        ]
                    ]
                ],
                'Sales' => [
                    'Initial Contact' => [
                        'subjects' => ['Welcome to Our Sales Process', 'Initial Contact Confirmation', 'Discovery Session Scheduled', 'Needs Assessment Planning', 'Stakeholder Introduction', 'Requirements Gathering', 'Solution Overview', 'Timeline Discussion', 'Budget Parameters', 'Next Steps Planning'],
                        'descriptions' => [
                            'Welcome to our sales process! We are excited to learn about your business needs and explore opportunities.',
                            'Initial contact has been established. Our sales representative will reach out within 24 hours to schedule discovery.',
                            'Discovery session has been scheduled for next week. We will discuss your requirements and demonstrate our solution.',
                            'Needs assessment planning session to understand your business objectives and current challenges in detail.',
                            'Stakeholder introduction meeting to identify key decision makers and understand your evaluation process.',
                            'Requirements gathering session scheduled to document your technical and business needs comprehensively.',
                            'Solution overview presentation prepared to demonstrate how our platform addresses your specific challenges.',
                            'Timeline discussion needed to align our sales process with your decision-making schedule and implementation needs.',
                            'Budget parameters conversation to ensure our solutions align with your investment capacity and expectations.',
                            'Next steps planning to establish clear milestones and timeline for the evaluation and selection process.'
                        ]
                    ],
                    'Qualification' => [
                        'subjects' => ['Qualification Assessment Complete', 'Budget and Timeline Confirmation', 'Stakeholder Identification', 'Decision Authority Validation', 'Technical Requirements Review', 'Business Case Development', 'ROI Analysis', 'Implementation Planning', 'Risk Assessment', 'Success Criteria Definition'],
                        'descriptions' => [
                            'Qualification assessment has been completed successfully. You meet all criteria for our enterprise solution.',
                            'Budget parameters and implementation timeline have been confirmed. Moving forward with detailed solution design.',
                            'Key stakeholders have been identified and decision-making process clarified. Preparing comprehensive proposal for your review.',
                            'Decision authority validation confirms you have the necessary approval power for this investment decision.',
                            'Technical requirements review completed with your IT team. All compatibility and integration needs documented.',
                            'Business case development session to quantify expected benefits and return on investment for your organization.',
                            'ROI analysis prepared showing projected cost savings and revenue improvements from our solution implementation.',
                            'Implementation planning discussion to outline deployment approach, timeline, and resource requirements.',
                            'Risk assessment completed to identify potential challenges and mitigation strategies for successful implementation.',
                            'Success criteria definition session to establish measurable outcomes and performance indicators for the project.'
                        ]
                    ],
                    'Meeting' => [
                        'subjects' => ['Meeting Summary and Next Steps', 'Technical Requirements Review', 'Solution Demonstration Follow-up', 'Stakeholder Presentation', 'Executive Briefing', 'Implementation Discussion', 'Integration Planning', 'Security Review', 'Performance Validation', 'Contract Terms Review'],
                        'descriptions' => [
                            'Thank you for the productive meeting. Please find attached the summary and proposed next steps.',
                            'Technical requirements have been reviewed and validated. Our solution addresses all your specified needs.',
                            'Solution demonstration was well received. Technical team confirmed feasibility of all requested features and integrations.',
                            'Stakeholder presentation completed successfully. All key decision makers are aligned on moving forward with evaluation.',
                            'Executive briefing provided comprehensive overview of business benefits and strategic value of our solution.',
                            'Implementation discussion outlined deployment phases, timeline, and resource requirements for successful project execution.',
                            'Integration planning session confirmed compatibility with existing systems and data migration approach.',
                            'Security review completed with your compliance team. All regulatory and data protection requirements validated.',
                            'Performance validation demonstrates our solution can handle your projected workload and growth requirements.',
                            'Contract terms review session to discuss legal framework, service levels, and ongoing support commitments.'
                        ]
                    ],
                    'Proposal' => [
                        'subjects' => ['Formal Proposal Delivery', 'Investment and ROI Analysis', 'Implementation Roadmap', 'Technical Specifications', 'Service Level Agreement', 'Contract Terms', 'Pricing Structure', 'Payment Schedule', 'Support Framework', 'Success Metrics'],
                        'descriptions' => [
                            'Your formal proposal has been delivered with detailed specifications, pricing, and terms for your review.',
                            'Investment analysis shows projected ROI of 250% within 18 months based on your operational metrics.',
                            'Implementation roadmap outlines all project phases, milestones, and resource requirements for successful deployment.',
                            'Technical specifications document provides comprehensive details on system architecture and integration approach.',
                            'Service level agreement defines our commitments for support, response times, and performance guarantees.',
                            'Contract terms outline the legal framework, intellectual property rights, and termination provisions.',
                            'Pricing structure includes all components, services, and optional features with transparent cost breakdown.',
                            'Payment schedule offers flexible terms aligned with project milestones and your budget cycle.',
                            'Support framework details our ongoing maintenance, training, and customer success programs.',
                            'Success metrics definition establishes measurable outcomes and key performance indicators for the project.'
                        ]
                    ],
                    'Close' => [
                        'subjects' => ['Contract Finalization', 'Implementation Kickoff', 'Welcome to Partnership', 'Project Team Introduction', 'Success Manager Assignment', 'Training Schedule', 'Go-Live Planning', 'Support Setup', 'Performance Monitoring', 'Relationship Launch'],
                        'descriptions' => [
                            'Contract finalization is in progress. Legal teams are reviewing final terms and conditions.',
                            'Implementation kickoff meeting scheduled for next week. Project team introductions and timeline review planned.',
                            'Welcome to our partnership! We are committed to ensuring your success with our solution.',
                            'Project team introduction meeting to establish communication protocols and assign responsibilities.',
                            'Success manager assignment completed. Your dedicated account manager will guide you through implementation.',
                            'Training schedule prepared for your team to ensure effective adoption and utilization of our platform.',
                            'Go-live planning session to finalize deployment strategy and establish rollback procedures if needed.',
                            'Support setup includes 24/7 technical assistance and dedicated success management during transition.',
                            'Performance monitoring framework established to track project progress and business value delivery.',
                            'Relationship launch celebration planned to mark the beginning of our long-term strategic partnership.'
                        ]
                    ]
                ]
            ];

            // Deal discussion templates (30-35 words each)
            $dealDiscussionTemplates = [
                'Marketing' => [
                    'Campaign Launch' => [
                        'Campaign strategy finalized with client approval. Creative assets are ready and media buy is confirmed. Launch checklist completed and tracking systems configured for monitoring.',
                        'Launch checklist completed successfully. All tracking pixels installed and analytics dashboards configured. Campaign went live this morning across all channels with positive initial response.',
                        'Campaign went live this morning across all channels. Initial response rates are tracking above projections. Post-launch review scheduled for next week to analyze performance.',
                        'Post-launch review scheduled for next week. Client is pleased with early performance indicators. Campaign optimization recommendations prepared based on initial data and insights.',
                        'Channel activation completed across all digital platforms. Social media, search, and display campaigns are running smoothly with proper budget allocation and targeting.',
                        'Performance tracking dashboard shows strong initial metrics. Click-through rates are 35% above industry average with excellent engagement across all audience segments.',
                        'Budget allocation optimized based on early performance data. Shifting spend to highest performing channels and audiences for maximum ROI and lead generation.',
                        'Target audience response exceeding expectations significantly. Demographic and behavioral targeting parameters are delivering qualified prospects at scale for sales team.',
                        'Content strategy implementation successful across all touchpoints. Messaging resonates well with target audience and drives strong engagement and conversion rates.',
                        'Launch metrics indicate campaign will exceed all projected KPIs. Early success suggests scaling opportunity for expanded reach and lead generation volume.'
                    ],
                    'Lead Generation' => [
                        'Lead generation metrics exceed expectations significantly. Cost per lead is 20% below target with higher quality scores. A/B testing results show improvement in conversion rates.',
                        'A/B testing results show significant improvement in conversion rates with new landing page design. Lead scoring model is working effectively for qualification and prioritization.',
                        'Lead scoring model is working effectively. Sales team reports higher close rates on qualified leads. Monthly lead generation target achieved in just three weeks of campaign.',
                        'Monthly lead generation target achieved in just three weeks. Scaling budget for remaining campaign period. Lead quality metrics consistently above industry benchmarks for performance.',
                        'Conversion analysis reveals optimal messaging and creative combinations. Implementing winning variants across all channels to maximize lead generation efficiency and quality.',
                        'Quality assessment shows 90% of leads meet ideal customer profile. Lead scoring algorithm accurately identifies high-value prospects for sales team prioritization.',
                        'Cost efficiency metrics demonstrate exceptional performance. Cost per qualified lead is 40% below industry average while maintaining high conversion rates.',
                        'Channel performance analysis identifies social media as top performer. Reallocating budget to maximize reach and engagement with target audience segments.',
                        'Audience insights reveal new high-value segments. Expanding targeting to include similar demographics and behavioral patterns for increased lead volume.',
                        'Scaling strategy implemented to capitalize on strong performance. Increased budget allocation to highest converting channels and audience segments for growth.'
                    ],
                    'Nurturing' => [
                        'Lead nurturing sequences performing exceptionally well. Email open rates are 45% above industry average with strong click-through and engagement metrics.',
                        'Content performance analysis shows educational materials driving highest engagement. Webinar invitations and case studies generate most qualified lead progression.',
                        'Engagement analytics indicate strong interest in product demonstrations. Scheduling demo requests increased by 60% since nurturing campaign launch.',
                        'Behavioral tracking reveals key buying signals. Lead scoring model updated to reflect engagement patterns that correlate with sales conversion.',
                        'Personalization results show dramatic improvement in engagement. Industry-specific content generates 70% higher click-through rates than generic messaging.',
                        'Conversion funnel optimization identifies bottlenecks at consideration stage. Implementing targeted content to address common objections and concerns.',
                        'Automation performance exceeds expectations with triggered campaigns. Behavioral-based email sequences drive 50% higher progression to sales-qualified status.',
                        'Segmentation analysis reveals distinct buyer personas with different content preferences. Tailoring nurturing tracks to match specific industry and role requirements.',
                        'Next stage progression metrics show 45% of nurtured leads advance to sales qualification. Nurturing effectiveness significantly improves conversion rates.',
                        'Lead progression velocity increased by 30% with optimized nurturing. Time from initial contact to sales-qualified lead reduced through strategic content delivery.'
                    ],
                    'Qualification' => [
                        'Lead qualification process identifies 60 high-value prospects ready for immediate sales engagement. Scoring model accuracy validated through sales team feedback.',
                        'Sales handoff preparation completed with detailed lead intelligence. Each qualified lead includes engagement history, interests, and recommended approach for sales team.',
                        'Qualified lead report shows 25% increase in lead quality compared to previous quarter. Sales team reports higher close rates on marketing-qualified leads.',
                        'Scoring model results demonstrate strong correlation between engagement patterns and sales success. Model refinements improve qualification accuracy and sales efficiency.',
                        'Readiness assessment completed for all qualified leads. Sales team briefed on individual lead profiles, pain points, and optimal contact strategies.',
                        'Priority ranking established based on lead score, engagement level, and business fit. High-priority leads receive immediate sales attention for faster conversion.',
                        'Sales intelligence package includes comprehensive lead profiles. Engagement history, content preferences, and buying signals documented for sales team success.',
                        'Handoff documentation complete with detailed lead profiles and next steps. Sales team equipped with all information needed for successful lead conversion.',
                        'Quality metrics show 95% of qualified leads meet ideal customer profile criteria. Qualification process effectively filters and prioritizes best opportunities.',
                        'Conversion tracking system updated to monitor lead progression through sales pipeline. Marketing attribution maintained throughout entire customer journey.'
                    ],
                    'Handoff' => [
                        'Sales handoff completed successfully with full lead intelligence transfer. Sales team briefed on engagement history and optimal approach for each qualified lead.',
                        'Lead transfer process completed with comprehensive documentation. Sales team has complete visibility into lead journey and engagement patterns for effective follow-up.',
                        'Campaign closure report demonstrates exceptional ROI of 320%. All key performance indicators exceeded targets with strong pipeline contribution to revenue.',
                        'Performance summary shows campaign success across all metrics. Lead volume, quality, and cost efficiency all exceeded targets and industry benchmarks.',
                        'ROI analysis demonstrates 280% return on marketing investment. Strong pipeline contribution validates campaign strategy and execution effectiveness for future initiatives.',
                        'Success metrics exceeded all targets including lead volume, quality scores, and conversion rates. Campaign sets new benchmark for future marketing initiatives.',
                        'Lessons learned documented for future campaign optimization. Key insights include audience preferences, content performance, and channel effectiveness for replication.',
                        'Future recommendations include scaling successful tactics and exploring new channels. Opportunity exists to expand reach while maintaining quality and efficiency.',
                        'Team feedback collected from sales and marketing teams. Handoff process improvements identified to enhance collaboration and lead conversion rates.',
                        'Next campaign planning initiated based on performance insights. Successful strategies will be replicated and scaled for continued growth and success.'
                    ]
                ],
                'Lead Qualification' => [
                    'Initial Contact' => [
                        'Initial contact established with key stakeholder. Initial interest confirmed and discovery call scheduled. Prospect responded positively to outreach and confirmed evaluation timeline.',
                        'Prospect responded positively to outreach. They are actively evaluating solutions for Q2 implementation. Initial qualification criteria met including budget authority and confirmed timeline.',
                        'Initial qualification criteria met successfully. Prospect has budget authority and confirmed timeline for decision. Discovery call revealed strong fit for our enterprise solution and capabilities.',
                        'Discovery call revealed strong fit for our enterprise solution. Moving to detailed needs assessment phase. Stakeholder introductions scheduled to understand decision-making process and requirements.',
                        'Stakeholder identification completed with key decision makers mapped. Executive sponsor identified and engaged in evaluation process with clear authority for final selection.',
                        'Requirements overview session provided comprehensive understanding of business objectives. Technical needs assessment scheduled to validate solution fit and integration requirements.',
                        'Timeline discussion confirmed alignment with business calendar and budget cycle. Implementation window identified that minimizes business disruption and maximizes success.',
                        'Budget parameters discussion revealed adequate investment capacity. Financial authority confirmed with procurement team engaged in evaluation and approval process.',
                        'Decision process clarification shows structured evaluation with clear criteria. Vendor selection committee identified with timeline for final decision and contract execution.',
                        'Qualification criteria review confirms mutual fit and alignment. Moving forward with detailed assessment and solution demonstration for stakeholder evaluation.'
                    ],
                    'Needs Assessment' => [
                        'Comprehensive needs assessment completed successfully. Identified five key pain points our solution directly addresses. Stakeholder interviews revealed unanimous support for digital transformation initiative.',
                        'Stakeholder interviews revealed unanimous support for digital transformation initiative. Current state analysis shows significant opportunities for efficiency improvement and cost reduction through automation.',
                        'Current state analysis shows significant opportunities for efficiency improvement. Needs assessment summary approved by client team. Technical requirements clearly documented and validated by stakeholders.',
                        'Needs assessment summary approved by client. Technical requirements clearly documented and validated. Moving forward with solution fit analysis and technical architecture review for validation.',
                        'Gap analysis identifies critical areas where current systems fall short. Our solution addresses 100% of identified gaps with additional capabilities for future growth.',
                        'Priority ranking established for requirements based on business impact. High-priority needs align perfectly with our core platform capabilities and proven results.',
                        'Success criteria defined with measurable outcomes and timeline. Clear ROI expectations established with finance team approval for investment level required.',
                        'Stakeholder interviews completed across all affected departments. Comprehensive requirements gathered with unanimous agreement on solution approach and expected benefits.',
                        'Current state documentation reveals inefficiencies our solution eliminates. Process improvement opportunities identified that will deliver immediate value and cost savings.',
                        'Future state vision articulated showing transformation potential. Leadership team excited about operational improvements and competitive advantages our solution enables.'
                    ],
                    'Solution Fit' => [
                        'Solution demonstration confirmed perfect fit for all use cases. Technical team validated integration approach and confirmed compatibility with existing infrastructure.',
                        'Technical architecture review completed successfully. Our platform integrates seamlessly with current systems while providing scalability for future growth and expansion.',
                        'Implementation planning session outlined deployment approach and timeline. Resource requirements confirmed and project team assignments identified for successful execution.',
                        'Integration assessment validates compatibility with existing technology stack. Data migration approach confirmed with minimal business disruption during transition period.',
                        'Security validation completed with compliance team approval. All regulatory requirements met with enhanced security features exceeding current standards and policies.',
                        'Performance testing demonstrates platform can handle projected workload. Scalability confirmed for anticipated growth with room for expansion beyond current requirements.',
                        'User experience review shows intuitive interface design. High adoption rates expected based on user feedback and similarity to familiar business applications.',
                        'Scalability analysis confirms platform grows with business needs. Architecture supports future requirements and additional modules without system replacement.',
                        'Compliance check validates adherence to industry regulations. Internal governance policies satisfied with enhanced controls and audit capabilities provided.',
                        'ROI projection shows 300% return within 24 months. Business case approved by finance team with strong justification for investment and expected outcomes.'
                    ],
                    'Proposal Sent' => [
                        'Detailed proposal delivered with comprehensive solution overview. Executive summary highlights key benefits and investment requirements for leadership team review.',
                        'Proposal review meeting scheduled with all stakeholders. Technical specifications and commercial terms will be discussed in detail for final evaluation.',
                        'Questions and clarifications session planned to address any concerns. Technical team available to provide additional information and validation as needed.',
                        'Executive summary presentation scheduled for leadership team. Business case and ROI analysis will be reviewed for final investment approval and authorization.',
                        'Technical specifications review completed with IT team. All integration requirements validated and implementation approach approved by technical stakeholders.',
                        'Commercial terms discussion scheduled with procurement team. Pricing structure and payment terms align with budget parameters and corporate policies.',
                        'Implementation timeline review shows alignment with business calendar. Project phases and milestones confirmed with resource allocation and availability.',
                        'Service levels presentation demonstrates commitment to success. Support structure and response times exceed current vendor standards and expectations.',
                        'Contract terms review initiated with legal team. Standard terms acceptable with minor modifications for compliance with corporate procurement policies.',
                        'Next steps guide provides clear path to contract execution. Decision timeline confirmed with all stakeholders committed to evaluation schedule.'
                    ],
                    'Decision' => [
                        'Decision timeline confirmed with two-week internal review period. All stakeholders committed to evaluation schedule and final selection by month end.',
                        'Final negotiations progressing smoothly with legal teams. Contract terms being refined to address specific requirements while maintaining standard framework.',
                        'Contract preparation underway with final documents expected next week. Legal review completed with only minor modifications required for compliance.',
                        'Executive approval process initiated with leadership presentation. Board review scheduled for final investment authorization and project approval.',
                        'Legal review completed successfully with contract terms approved. Procurement process moving forward with vendor qualification and purchase order preparation.',
                        'Procurement process advancing with all requirements satisfied. Vendor qualification completed and contract approval workflow initiated for final authorization.',
                        'Budget approval secured from finance team with investment authorized. Purchase order preparation initiated and contract execution scheduled for next week.',
                        'Stakeholder alignment achieved across all departments. Implementation team assignments confirmed and project planning ready to begin immediately.',
                        'Risk assessment completed with mitigation strategies approved. All potential challenges identified with contingency plans established for successful implementation.',
                        'Implementation planning ready to commence upon contract signature. Project team assembled and kickoff meeting scheduled for immediate project initiation.'
                    ]
                ],
                'Sales' => [
                    'Initial Contact' => [
                        'Initial contact established with decision maker. Strong interest expressed in our enterprise solution. Discovery call scheduled to understand specific requirements and timeline.',
                        'Discovery call completed successfully. Client confirmed active evaluation of solutions for Q2 implementation. Budget authority and timeline validated for moving forward with process.',
                        'Needs assessment revealed perfect fit for our platform. Client has budget authority and clear timeline. Stakeholder introductions completed with key decision makers for evaluation.',
                        'Stakeholder introductions completed successfully. All key decision makers are engaged and supportive of evaluation process. Moving forward with detailed qualification assessment and presentation.',
                        'Needs assessment planning session confirmed comprehensive understanding of business objectives. Client provided detailed information about current challenges and desired outcomes.',
                        'Stakeholder identification process mapped all key influencers and decision makers. Executive sponsor identified with clear authority for final vendor selection.',
                        'Requirements gathering session documented technical and business needs. Client confirmed our solution aligns with their strategic objectives and growth plans.',
                        'Solution overview presentation demonstrated platform capabilities. Client impressed with features and confirmed strong alignment with their business requirements.',
                        'Timeline discussion established evaluation schedule and implementation preferences. Client confirmed availability for next steps and committed to decision timeline.',
                        'Budget parameters conversation confirmed adequate investment capacity. Client has allocated sufficient funds and confirmed procurement process for vendor selection.'
                    ],
                    'Qualification' => [
                        'Qualification criteria thoroughly validated. Client meets all requirements for enterprise engagement. Budget confirmation received and decision-making process clarified for next steps and timeline.',
                        'Budget confirmation received from finance team. Client has allocated sufficient funds for comprehensive implementation. Decision-making process clarified with clear timeline established for selection.',
                        'Decision-making process clarified with executive team. Client has authority to make final selection within 30 days. Technical requirements assessment scheduled for validation and confirmation.',
                        'Technical requirements assessment completed successfully. Our solution addresses 100% of specified needs. Moving forward with detailed solution presentation and demonstration for stakeholders.',
                        'Decision authority validation confirms client has necessary approval power. Executive sponsor engaged and committed to evaluation process with clear decision timeline.',
                        'Technical requirements review completed with IT team. All compatibility and integration needs documented and validated by technical stakeholders.',
                        'Business case development session quantified expected benefits. ROI projections show significant value creation and cost savings for the organization.',
                        'ROI analysis demonstrates compelling business case with 280% return. Finance team approved investment level and confirmed budget allocation for implementation.',
                        'Implementation planning discussion outlined deployment approach. Client confirmed resource availability and project team assignments for successful execution.',
                        'Risk assessment identified potential challenges and mitigation strategies. Client comfortable with implementation approach and confident in successful deployment.'
                    ],
                    'Meeting' => [
                        'Executive presentation delivered to C-level team. Unanimous support for moving forward with evaluation process. Technical deep-dive session scheduled with IT team for validation.',
                        'Technical deep-dive session completed with IT team. All integration requirements validated and security concerns addressed. ROI analysis prepared for finance team review and approval.',
                        'ROI analysis presented showing 300% return within 24 months. Finance team approved investment level. Reference customer meeting arranged for validation and experience sharing.',
                        'Reference customer meeting arranged successfully. Client will speak with similar organization about implementation experience. Proposal preparation initiated based on requirements and discussions.',
                        'Stakeholder presentation engaged all key decision makers. Comprehensive solution overview demonstrated platform capabilities and business value for the organization.',
                        'Executive briefing provided strategic overview of business benefits. Leadership team aligned on moving forward with detailed evaluation and vendor selection process.',
                        'Implementation discussion outlined project phases and timeline. Client confirmed resource allocation and project team availability for successful deployment.',
                        'Integration planning session validated technical compatibility. IT team approved architecture and confirmed seamless integration with existing systems.',
                        'Security review completed with compliance team approval. All regulatory requirements met and data protection concerns addressed comprehensively.',
                        'Performance validation demonstrated platform scalability. Client confident our solution can handle projected growth and future business requirements.'
                    ],
                    'Proposal' => [
                        'Comprehensive proposal delivered including all technical specifications and commercial terms. Proposal presentation meeting scheduled with full executive team and key stakeholders for review.',
                        'Proposal presentation meeting scheduled with executive team. All key stakeholders will be present for detailed review. Legal review initiated by procurement team for compliance.',
                        'Legal review initiated by client procurement team. Contract terms and conditions under review. Competitive evaluation completed with positive feedback received from stakeholders.',
                        'Competitive evaluation completed successfully. Client confirmed we are the preferred vendor for this initiative. Final negotiations scheduled to address remaining terms and conditions.',
                        'Technical specifications review validated all system requirements. Client IT team approved architecture and confirmed implementation approach meets their standards.',
                        'Service level agreement discussion established support commitments. Client satisfied with response times and performance guarantees outlined in proposal.',
                        'Contract terms review addressed legal framework and compliance. Client legal team approved standard terms with minor modifications for corporate policies.',
                        'Pricing structure discussion confirmed value proposition. Client finance team approved investment level and confirmed alignment with budget parameters.',
                        'Payment schedule negotiation established flexible terms. Client procurement team approved payment structure aligned with project milestones and budget cycle.',
                        'Success metrics definition established measurable outcomes. Client committed to performance tracking and quarterly business reviews for value validation.'
                    ],
                    'Close' => [
                        'Final negotiations completed successfully. Contract terms agreed upon by both legal teams. Executive approval obtained and purchase order processing initiated for implementation.',
                        'Executive approval obtained from client leadership team. Purchase order processing initiated and implementation planning begun. Project team assignments confirmed for deployment and support.',
                        'Contract signed and project officially launched. Implementation team assigned and kickoff scheduled for next week. Client success manager introduced for ongoing support and relationship management.',
                        'Deal closed successfully! Client is excited to begin implementation and achieve projected benefits. Implementation kickoff meeting scheduled to discuss timeline and milestones for success.',
                        'Project team introduction completed with role assignments. Communication protocols established and project charter approved by both organizations for execution.',
                        'Success manager assignment finalized with dedicated account management. Client relationship established for ongoing support and strategic partnership development.',
                        'Training schedule established for comprehensive user adoption. Client team prepared for platform onboarding and certification program for effective utilization.',
                        'Go-live planning session finalized deployment strategy. Testing phases and rollback procedures established for risk mitigation during implementation.',
                        'Support setup completed with 24/7 technical assistance. Dedicated success management assigned for transition period and ongoing relationship management.',
                        'Partnership launch celebration planned to mark relationship beginning. Long-term strategic partnership established with mutual commitment to success and growth.'
                    ]
                ]
            ];

            foreach ($deals as $deal) {
                if (!$deal) {
                    continue;
                }
                $pipelineName = $deal->pipeline?->name ?? 'Sales';
                $stageName = $deal->stage?->name ?? 'Initial Contact';

                // Create 1-2 emails per deal
                $emailCount = rand(1, 2);
                $emailTemplates = $dealEmailTemplates[$pipelineName][$stageName] ?? $dealEmailTemplates['Sales']['Initial Contact'];

                for ($i = 0; $i < $emailCount; $i++) {
                    $templateIndex = $i % count($emailTemplates['subjects']);

                    // Use client email if available, otherwise use a user email
                    $recipientEmail = $clients->isNotEmpty() ? $clients->random()->email : 'client@example.com';

                    if (!$deal->id || !$recipientEmail) {
                        continue;
                    }

                    DealEmail::create([
                        'deal_id' => $deal->id,
                        'to' => $recipientEmail,
                        'subject' => $emailTemplates['subjects'][$templateIndex],
                        'description' => $emailTemplates['descriptions'][$templateIndex],
                        'created_at' => $deal->created_at->addDays(rand(1, 14))->addHours(rand(1, 23)),
                    ]);
                }

                // Create 1-2 discussions per deal
                $discussionCount = rand(1, 2);
                $discussionTemplates = $dealDiscussionTemplates[$pipelineName][$stageName] ?? $dealDiscussionTemplates['Sales']['Initial Contact'];

                for ($i = 0; $i < $discussionCount; $i++) {
                    if (empty($users)) {
                        break;
                    }
                    $randomUser = $users[array_rand($users)];
                    $templateIndex = $i % count($discussionTemplates);

                    if (!$deal->id || !$randomUser) {
                        continue;
                    }

                    DealDiscussion::create([
                        'deal_id' => $deal->id,
                        'comment' => $discussionTemplates[$templateIndex],
                        'creator_id' => $randomUser,
                        'created_by' => $userId,
                        'created_at' => $deal->created_at->addDays(rand(3, 21))->addHours(rand(1, 23)),
                    ]);
                }
            }
        }
    }



    private function seedDealFiles($deals): void
    {
        // 17 records per pipeline: 7 with 2 files, 10 with 1 file (mixed arrangement)
        $dealFilesData = [
            // Marketing Pipeline - 17 random subjects from all 30 records
            ['subject' => 'Content Marketing Strategy', 'files' => ['Content_Marketing_Strategy_Deal_File.docx']],
            ['subject' => 'SEO Optimization Services', 'files' => ['SEO_Optimization_Services_Deal_File.jpg']],
            ['subject' => 'Customer Journey Mapping', 'files' => ['Customer_Journey_Mapping_Deal_File.pdf']],
            ['subject' => 'Marketing Automation Setup', 'files' => ['Marketing_Automation_Setup_Deal_File.docx']],
            ['subject' => 'Video Production Services', 'files' => ['Video_Production_Services_Deal_File.pdf']],
            ['subject' => 'Brand Positioning Strategy', 'files' => ['Brand_Positioning_Strategy_Deal_File.jpg', 'Brand_Positioning_Strategy_Deal_File.pdf']],
            ['subject' => 'Marketing Analytics Platform', 'files' => ['Marketing_Analytics_Platform_Deal_File.jpg']],
            ['subject' => 'Trade Show Marketing Package', 'files' => ['Trade_Show_Marketing_Package_Deal_File.docx']],
            ['subject' => 'Marketing ROI Analysis', 'files' => ['Marketing_ROI_Analysis_Deal_File.pdf']],
            ['subject' => 'Customer Experience Audit', 'files' => ['Customer_Experience_Audit_Deal_File.png']],
            ['subject' => 'Email Marketing Automation', 'files' => ['Email_Marketing_Automation_Deal_File.png', 'Email_Marketing_Automation_Deal_File.docx']],
            ['subject' => 'Brand Identity Development Package', 'files' => ['Brand_Identity_Development_Package_Deal_File.png']],
            ['subject' => 'Lead Generation Campaign', 'files' => ['Lead_Generation_Campaign_Deal_File.png', 'Lead_Generation_Campaign_Deal_File.pdf']],
            ['subject' => 'Social Media Management Contract', 'files' => ['Social_Media_Management_Contract_Deal_File.png', 'Social_Media_Management_Contract_Deal_File.pdf']],
            ['subject' => 'Omnichannel Marketing Setup', 'files' => ['Omnichannel_Marketing_Setup_Deal_File.docx']],
            ['subject' => 'Marketing Technology Stack', 'files' => ['Marketing_Technology_Stack_Deal_File.docx']],
            ['subject' => 'Public Relations Campaign', 'files' => ['Public_Relations_Campaign_Deal_File.png']],

            // Lead Qualification Pipeline - 17 random subjects from all 30 records
            ['subject' => 'Digital Transformation Assessment', 'files' => ['Digital_Transformation_Assessment_Deal_File.jpg']],
            ['subject' => 'Requirements Analysis Services', 'files' => ['Requirements_Analysis_Services_Deal_File.pdf']],
            ['subject' => 'System Integration Evaluation', 'files' => ['System_Integration_Evaluation_Deal_File.png', 'System_Integration_Evaluation_Deal_File.pdf']],
            ['subject' => 'Change Impact Analysis', 'files' => ['Change_Impact_Analysis_Deal_File.png']],
            ['subject' => 'ROI Assessment Consulting', 'files' => ['ROI_Assessment_Consulting_Deal_File.pdf', 'ROI_Assessment_Consulting_Deal_File.jpg']],
            ['subject' => 'Cost-Benefit Analysis Study', 'files' => ['Cost_Benefit_Analysis_Study_Deal_File.png']],
            ['subject' => 'Vendor Selection Consulting', 'files' => ['Vendor_Selection_Consulting_Deal_File.jpg', 'Vendor_Selection_Consulting_Deal_File.pdf']],
            ['subject' => 'Enterprise Assessment Consulting', 'files' => ['Enterprise_Assessment_Consulting_Deal_File.png']],
            ['subject' => 'Performance Optimization Study', 'files' => ['Performance_Optimization_Study_Deal_File.pdf']],
            ['subject' => 'Compliance Framework Review', 'files' => ['Compliance_Framework_Review_Deal_File.jpg']],
            ['subject' => 'Security Evaluation Services', 'files' => ['Security_Evaluation_Services_Deal_File.docx']],
            ['subject' => 'Business Intelligence Review', 'files' => ['Business_Intelligence_Review_Deal_File.jpg']],
            ['subject' => 'Technology Roadmap Planning', 'files' => ['Technology_Roadmap_Planning_Deal_File.docx']],
            ['subject' => 'Procurement Process Review', 'files' => ['Procurement_Process_Review_Deal_File.docx']],
            ['subject' => 'Budget Planning Services', 'files' => ['Budget_Planning_Services_Deal_File.docx']],
            ['subject' => 'Training Needs Assessment', 'files' => ['Training_Needs_Assessment_Deal_File.pdf']],
            ['subject' => 'Capacity Assessment Services', 'files' => ['Capacity_Assessment_Services_Deal_File.jpg', 'Capacity_Assessment_Services_Deal_File.pdf']],

            // Sales Pipeline - 17 random subjects from all 30 records
            ['subject' => 'Annual Software License Renewal', 'files' => ['Annual_Software_License_Renewal_Deal_File.png', 'Annual_Software_License_Renewal_Deal_File.pdf']],
            ['subject' => 'Cloud Migration Services Package', 'files' => ['Cloud_Migration_Services_Package_Deal_File.png']],
            ['subject' => 'Mobile Application Development', 'files' => ['Mobile_Application_Development_Deal_File.png', 'Mobile_Application_Development_Deal_File.docx']],
            ['subject' => 'Custom ERP Solution Development', 'files' => ['Custom_ERP_Solution_Development_Deal_File.docx']],
            ['subject' => 'Data Analytics Implementation', 'files' => ['Data_Analytics_Implementation_Deal_File.pdf']],
            ['subject' => 'Business Process Automation', 'files' => ['Business_Process_Automation_Deal_File.pdf']],
            ['subject' => 'Vendor Management Portal', 'files' => ['Vendor_Management_Portal_Deal_File.pdf']],
            ['subject' => 'Strategic Consulting Services', 'files' => ['Strategic_Consulting_Services_Deal_File.docx']],
            ['subject' => 'Digital Transformation Initiative', 'files' => ['Digital_Transformation_Initiative_Deal_File.png', 'Digital_Transformation_Initiative_Deal_File.pdf']],
            ['subject' => 'E-commerce Platform Upgrade', 'files' => ['E_commerce_Platform_Upgrade_Deal_File.pdf']],
            ['subject' => 'Workflow Automation System', 'files' => ['Workflow_Automation_System_Deal_File.png', 'Workflow_Automation_System_Deal_File.jpg']],
            ['subject' => 'Project Management Platform', 'files' => ['Project_Management_Platform_Deal_File.docx']],
            ['subject' => 'Compliance Management System', 'files' => ['Compliance_Management_System_Deal_File.pdf']],
            ['subject' => 'Financial Reporting Dashboard', 'files' => ['Financial_Reporting_Dashboard_Deal_File.png']],
            ['subject' => 'Communication Platform Upgrade', 'files' => ['Communication_Platform_Upgrade_Deal_File.docx']],
            ['subject' => 'Asset Tracking System', 'files' => ['Asset_Tracking_System_Deal_File.pdf']],
            ['subject' => 'Customer Portal Development', 'files' => ['Customer_Portal_Development_Deal_File.docx']],
        ];

        // Create files for each pipeline
        $pipelineFiles = [
            'Sales' => array_slice($dealFilesData, 0, 17),
            'Marketing' => array_slice($dealFilesData, 17, 17),
            'Lead Qualification' => array_slice($dealFilesData, 34, 17)
        ];

        foreach ($deals as $deal) {
            if (!$deal) {
                continue;
            }
            $pipelineName = $deal->pipeline?->name ?? 'Sales';
            $files = $pipelineFiles[$pipelineName] ?? $pipelineFiles['Sales'];

            // Select random file record for this deal
            $fileData = $files[array_rand($files)];

            foreach ($fileData['files'] as $fileName) {
                if (!$deal->id || !$fileName) {
                    continue;
                }

                DealFile::create([
                    'deal_id' => $deal->id,
                    'file_name' => $fileName,
                    'file_path' => $fileName,
                ]);
            }
        }
    }
}
