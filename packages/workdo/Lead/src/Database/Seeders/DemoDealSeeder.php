<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Source;
use Workdo\Lead\Models\Label;
use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\UserDeal;
use Workdo\Lead\Models\DealDiscussion;
use Workdo\Lead\Models\DealFile;
use Workdo\Lead\Models\DealCall;
use Workdo\Lead\Models\DealEmail;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDealSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Deal::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $pipelines = Pipeline::where('created_by', $userId)->get();
            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();

            if ($pipelines->isEmpty() || empty($users)) {
                return;
            }

            $dealData = [
                // Marketing Pipeline Deals (30 records)
                ['name' => 'Brand Identity Development Package', 'price' => 35000, 'created_at' => Carbon::now()->subDays(179)],
                ['name' => 'Digital Marketing Campaign Launch', 'price' => 45000, 'created_at' => Carbon::now()->subDays(173)],
                ['name' => 'Website Redesign and Development', 'price' => 65000, 'created_at' => Carbon::now()->subDays(167)],
                ['name' => 'Social Media Management Contract', 'price' => 25000, 'created_at' => Carbon::now()->subDays(161)],
                ['name' => 'Content Marketing Strategy', 'price' => 35000, 'created_at' => Carbon::now()->subDays(155)],
                ['name' => 'SEO Optimization Services', 'price' => 28000, 'created_at' => Carbon::now()->subDays(149)],
                ['name' => 'Email Marketing Automation', 'price' => 22000, 'created_at' => Carbon::now()->subDays(143)],
                ['name' => 'Video Production Services', 'price' => 55000, 'created_at' => Carbon::now()->subDays(137)],
                ['name' => 'Marketing Analytics Platform', 'price' => 42000, 'created_at' => Carbon::now()->subDays(131)],
                ['name' => 'Lead Generation Campaign', 'price' => 38000, 'created_at' => Carbon::now()->subDays(125)],
                ['name' => 'Trade Show Marketing Package', 'price' => 48000, 'created_at' => Carbon::now()->subDays(119)],
                ['name' => 'Public Relations Campaign', 'price' => 32000, 'created_at' => Carbon::now()->subDays(113)],
                ['name' => 'Influencer Marketing Program', 'price' => 28000, 'created_at' => Carbon::now()->subDays(107)],
                ['name' => 'Market Research Study', 'price' => 35000, 'created_at' => Carbon::now()->subDays(101)],
                ['name' => 'Customer Experience Audit', 'price' => 25000, 'created_at' => Carbon::now()->subDays(95)],
                ['name' => 'Brand Positioning Strategy', 'price' => 45000, 'created_at' => Carbon::now()->subDays(89)],
                ['name' => 'Marketing Technology Stack', 'price' => 65000, 'created_at' => Carbon::now()->subDays(83)],
                ['name' => 'Customer Segmentation Analysis', 'price' => 32000, 'created_at' => Carbon::now()->subDays(80)],
                ['name' => 'Conversion Rate Optimization', 'price' => 38000, 'created_at' => Carbon::now()->subDays(74)],
                ['name' => 'Marketing Automation Setup', 'price' => 42000, 'created_at' => Carbon::now()->subDays(68)],
                ['name' => 'Customer Journey Mapping', 'price' => 28000, 'created_at' => Carbon::now()->subDays(62)],
                ['name' => 'Competitive Analysis Report', 'price' => 22000, 'created_at' => Carbon::now()->subDays(56)],
                ['name' => 'Marketing Performance Dashboard', 'price' => 35000, 'created_at' => Carbon::now()->subDays(50)],
                ['name' => 'Event Marketing Campaign', 'price' => 45000, 'created_at' => Carbon::now()->subDays(44)],
                ['name' => 'Partnership Marketing Program', 'price' => 38000, 'created_at' => Carbon::now()->subDays(29)],
                ['name' => 'Loyalty Program Development', 'price' => 55000, 'created_at' => Carbon::now()->subDays(22)],
                ['name' => 'Customer Retention Strategy', 'price' => 42000, 'created_at' => Carbon::now()->subDays(17)],
                ['name' => 'Marketing ROI Analysis', 'price' => 25000, 'created_at' => Carbon::now()->subDays(12)],
                ['name' => 'Omnichannel Marketing Setup', 'price' => 65000, 'created_at' => Carbon::now()->subDays(8)],
                ['name' => 'Marketing Compliance Audit', 'price' => 32000, 'created_at' => Carbon::now()->subDays(7)],

                // Lead Qualification Pipeline Deals (30 records)
                ['name' => 'Enterprise Assessment Consulting', 'price' => 85000, 'created_at' => Carbon::now()->subDays(178)],
                ['name' => 'System Integration Evaluation', 'price' => 95000, 'created_at' => Carbon::now()->subDays(172)],
                ['name' => 'Business Intelligence Review', 'price' => 75000, 'created_at' => Carbon::now()->subDays(166)],
                ['name' => 'Process Automation Feasibility', 'price' => 65000, 'created_at' => Carbon::now()->subDays(160)],
                ['name' => 'Technology Roadmap Planning', 'price' => 105000, 'created_at' => Carbon::now()->subDays(154)],
                ['name' => 'Digital Transformation Assessment', 'price' => 125000, 'created_at' => Carbon::now()->subDays(148)],
                ['name' => 'Quality Management System', 'price' => 85000, 'created_at' => Carbon::now()->subDays(142)],
                ['name' => 'Performance Optimization Study', 'price' => 75000, 'created_at' => Carbon::now()->subDays(136)],
                ['name' => 'Compliance Framework Review', 'price' => 95000, 'created_at' => Carbon::now()->subDays(130)],
                ['name' => 'Risk Management Assessment', 'price' => 115000, 'created_at' => Carbon::now()->subDays(124)],
                ['name' => 'Security Evaluation Services', 'price' => 135000, 'created_at' => Carbon::now()->subDays(118)],
                ['name' => 'Data Management Strategy', 'price' => 85000, 'created_at' => Carbon::now()->subDays(112)],
                ['name' => 'Vendor Selection Consulting', 'price' => 55000, 'created_at' => Carbon::now()->subDays(106)],
                ['name' => 'Procurement Process Review', 'price' => 65000, 'created_at' => Carbon::now()->subDays(100)],
                ['name' => 'Cost-Benefit Analysis Study', 'price' => 45000, 'created_at' => Carbon::now()->subDays(94)],
                ['name' => 'ROI Assessment Consulting', 'price' => 75000, 'created_at' => Carbon::now()->subDays(88)],
                ['name' => 'Budget Planning Services', 'price' => 55000, 'created_at' => Carbon::now()->subDays(82)],
                ['name' => 'Implementation Timeline Planning', 'price' => 65000, 'created_at' => Carbon::now()->subDays(75)],
                ['name' => 'Resource Allocation Study', 'price' => 85000, 'created_at' => Carbon::now()->subDays(69)],
                ['name' => 'Capacity Assessment Services', 'price' => 95000, 'created_at' => Carbon::now()->subDays(63)],
                ['name' => 'Scalability Analysis Report', 'price' => 105000, 'created_at' => Carbon::now()->subDays(59)],
                ['name' => 'Performance Benchmarking', 'price' => 75000, 'created_at' => Carbon::now()->subDays(51)],
                ['name' => 'User Acceptance Testing', 'price' => 65000, 'created_at' => Carbon::now()->subDays(45)],
                ['name' => 'Training Needs Assessment', 'price' => 45000, 'created_at' => Carbon::now()->subDays(39)],
                ['name' => 'Change Impact Analysis', 'price' => 85000, 'created_at' => Carbon::now()->subDays(28)],
                ['name' => 'Stakeholder Alignment Study', 'price' => 55000, 'created_at' => Carbon::now()->subDays(21)],
                ['name' => 'Requirements Analysis Services', 'price' => 75000, 'created_at' => Carbon::now()->subDays(16)],
                ['name' => 'Technical Feasibility Study', 'price' => 95000, 'created_at' => Carbon::now()->subDays(11)],
                ['name' => 'Solution Architecture Review', 'price' => 115000, 'created_at' => Carbon::now()->subDays(7)],
                ['name' => 'Strategic Planning Consulting', 'price' => 135000, 'created_at' => Carbon::now()->subDays(6)],

                // Sales Pipeline Deals (30 records)
                ['name' => 'Enterprise CRM Implementation Project', 'price' => 125000, 'created_at' => Carbon::now()->subDays(180)],
                ['name' => 'Annual Software License Renewal', 'price' => 85000, 'created_at' => Carbon::now()->subDays(174)],
                ['name' => 'Custom ERP Solution Development', 'price' => 195000, 'created_at' => Carbon::now()->subDays(168)],
                ['name' => 'Multi-Year Support Contract', 'price' => 75000, 'created_at' => Carbon::now()->subDays(162)],
                ['name' => 'Cloud Migration Services Package', 'price' => 145000, 'created_at' => Carbon::now()->subDays(156)],
                ['name' => 'Business Intelligence Platform', 'price' => 110000, 'created_at' => Carbon::now()->subDays(150)],
                ['name' => 'Digital Transformation Initiative', 'price' => 225000, 'created_at' => Carbon::now()->subDays(144)],
                ['name' => 'Cybersecurity Assessment Package', 'price' => 65000, 'created_at' => Carbon::now()->subDays(138)],
                ['name' => 'Data Analytics Implementation', 'price' => 95000, 'created_at' => Carbon::now()->subDays(132)],
                ['name' => 'Mobile Application Development', 'price' => 155000, 'created_at' => Carbon::now()->subDays(126)],
                ['name' => 'API Integration Services', 'price' => 45000, 'created_at' => Carbon::now()->subDays(120)],
                ['name' => 'E-commerce Platform Upgrade', 'price' => 135000, 'created_at' => Carbon::now()->subDays(114)],
                ['name' => 'Workflow Automation System', 'price' => 85000, 'created_at' => Carbon::now()->subDays(108)],
                ['name' => 'Healthcare Management Solution', 'price' => 175000, 'created_at' => Carbon::now()->subDays(102)],
                ['name' => 'Financial Reporting Dashboard', 'price' => 55000, 'created_at' => Carbon::now()->subDays(96)],
                ['name' => 'Supply Chain Optimization', 'price' => 165000, 'created_at' => Carbon::now()->subDays(90)],
                ['name' => 'Customer Portal Development', 'price' => 75000, 'created_at' => Carbon::now()->subDays(84)],
                ['name' => 'Inventory Management System', 'price' => 105000, 'created_at' => Carbon::now()->subDays(81)],
                ['name' => 'Project Management Platform', 'price' => 95000, 'created_at' => Carbon::now()->subDays(83)],
                ['name' => 'Document Management Solution', 'price' => 65000, 'created_at' => Carbon::now()->subDays(69)],
                ['name' => 'Quality Assurance Framework', 'price' => 85000, 'created_at' => Carbon::now()->subDays(63)],
                ['name' => 'Performance Monitoring Tools', 'price' => 45000, 'created_at' => Carbon::now()->subDays(57)],
                ['name' => 'Compliance Management System', 'price' => 125000, 'created_at' => Carbon::now()->subDays(51)],
                ['name' => 'Training Management Platform', 'price' => 75000, 'created_at' => Carbon::now()->subDays(45)],
                ['name' => 'Risk Assessment Solution', 'price' => 95000, 'created_at' => Carbon::now()->subDays(30)],
                ['name' => 'Vendor Management Portal', 'price' => 55000, 'created_at' => Carbon::now()->subDays(23)],
                ['name' => 'Asset Tracking System', 'price' => 115000, 'created_at' => Carbon::now()->subDays(18)],
                ['name' => 'Communication Platform Upgrade', 'price' => 85000, 'created_at' => Carbon::now()->subDays(13)],
                ['name' => 'Business Process Automation', 'price' => 145000, 'created_at' => Carbon::now()->subDays(9)],
                ['name' => 'Strategic Consulting Services', 'price' => 195000, 'created_at' => Carbon::now()->subDays(6)],
            ];

            $notes = [
                "Initial proposal submitted with comprehensive technical specifications and implementation timeline. Client expressed strong interest in our enterprise-grade solution and requested detailed cost breakdown for budget approval process.",
                "Follow-up meeting scheduled with decision makers to discuss contract terms and service level agreements. Technical team confirmed compatibility with existing infrastructure and provided integration roadmap.",
                "Detailed requirements gathering completed with stakeholder interviews and system analysis. Gap analysis identified key improvement opportunities and potential ROI of 300% within first year of implementation.",
                "Proof of concept demonstration delivered successfully with positive client feedback. Technical evaluation phase approved and extended pilot program initiated to validate core functionality with real data.",
                "Contract negotiation in progress with legal teams reviewing terms and conditions. Pricing structure finalized with volume discounts and multi-year commitment incentives included in final proposal.",
                "Implementation planning phase initiated with project timeline and resource allocation confirmed. Client team assigned and training schedule established for smooth transition and user adoption.",
                "Executive presentation delivered to senior leadership team highlighting strategic benefits and competitive advantages. Management approval secured for proceeding with full-scale deployment.",
                "Technical architecture review completed with systems integration requirements documented. API specifications and data migration strategy approved by client IT department and security team.",
                "Vendor evaluation process concluded with our solution selected as preferred choice. Reference customer calls completed successfully and due diligence phase finalized with positive outcome.",
                "Budget approval obtained from finance committee with investment justification accepted. Purchase order processing initiated and contract execution scheduled for next business week.",
                "Go-live preparation in progress with user acceptance testing completed successfully. Training materials finalized and support team prepared for post-implementation assistance and ongoing maintenance.",
                "Post-implementation review conducted with excellent user satisfaction scores and performance metrics exceeding baseline expectations. Expansion opportunities identified for additional modules.",
                "Strategic partnership discussion initiated exploring long-term collaboration opportunities. Joint initiatives proposed that could provide mutual benefits and enhanced market positioning for both organizations.",
                "Pilot program results exceeded expectations with measurable business impact demonstrated. Full deployment recommendation approved by steering committee for organization-wide rollout within next quarter.",
                "Business case analysis completed showing compelling return on investment and operational efficiency gains. Financial justification approved by executive committee for immediate budget allocation.",
                "Requirements validation completed with all critical functionality confirmed and tested. User stories documented and acceptance criteria established for successful project delivery and client satisfaction.",
                "Risk assessment and mitigation strategies developed for potential implementation challenges. Contingency plans established and escalation procedures defined for smooth project execution.",
                "Change management strategy implemented with comprehensive communication plan and stakeholder engagement. User adoption metrics tracked and training effectiveness measured throughout deployment.",
                "Quality assurance framework established with testing protocols and performance benchmarks. Continuous improvement process defined for ongoing optimization and feature enhancement.",
                "Service level agreements finalized with response time commitments and availability guarantees. Support structure established with dedicated account management and technical assistance.",
                "Integration testing completed successfully with all third-party systems communicating effectively. Data synchronization validated and security protocols implemented according to compliance requirements.",
                "Performance optimization completed with system response times improved by 40% and user productivity increased significantly. Monitoring tools deployed for ongoing performance tracking.",
                "Customer success metrics established with key performance indicators and regular review schedule. Quarterly business reviews planned to ensure continued value delivery and relationship management.",
                "Scalability testing completed with system capacity validated for future growth requirements. Infrastructure recommendations provided for optimal performance under increased load conditions.",
                "Security audit completed with all compliance requirements met and certification obtained. Data protection measures implemented according to industry standards and regulatory guidelines.",
                "Training program completed with high user satisfaction scores and competency assessments passed. Knowledge transfer documentation provided for internal team capability building.",
                "Maintenance and support procedures established with proactive monitoring and preventive maintenance schedule. Help desk integration completed for seamless user support experience.",
                "Business continuity planning completed with disaster recovery procedures tested and validated. Backup systems configured and failover processes documented for operational resilience.",
                "Performance analytics dashboard deployed with real-time monitoring and reporting capabilities. Executive dashboards configured for strategic decision making and operational oversight.",
                "Project closure activities completed with lessons learned documented and best practices captured. Success celebration planned and customer testimonial obtained for future reference opportunities."
            ];

            $clients = User::where('created_by', $userId)->where('type', 'client')->pluck('id')->toArray();
            // $usedUserIds = [];
            $usedClientIds = [];
            $dealIndex = 0;
            $convertedLeadIndex = 0;
            $countryCodes = ['+1', '+44', '+91', '+61', '+81', '+49', '+33', '+39', '+55', '+86', '+7', '+27', '+82', '+34'];

            foreach ($pipelines as $pipeline) {
                // Skip Sales pipeline
                if ($pipeline->name === 'Sales') continue;
                
                // Get 6 leads for conversion per pipeline (reduced from 17)
                $convertibleLeads = Lead::where('created_by', $userId)
                    ->where('pipeline_id', $pipeline->id)
                    ->where('is_converted', 0)
                    ->orderBy('created_at', 'asc')
                    ->limit(6)
                    ->get();

                $stages = DealStage::where('pipeline_id', $pipeline->id)->get();
                if ($stages->isEmpty()) continue;

                $convertedLeadIndex = 0;

                // Create 10 deals per pipeline (reduced from 30)
                for ($i = 0; $i < 10; $i++) {
                    if ($dealIndex >= count($dealData)) break;

                    $deal = $dealData[$dealIndex];

                    // Realistic stage distribution based on deal funnel
                    $stageCount = $stages->count();
                    if ($pipeline->name === 'Marketing') {
                        // Marketing funnel: Campaign Launch (40%), Lead Generation (30%), Nurturing (20%), Qualification (10%)
                        if ($i < 4) $stageIndex = 0; // Campaign Launch
                        elseif ($i < 7) $stageIndex = min(1, $stageCount - 1); // Lead Generation
                        elseif ($i < 9) $stageIndex = min(2, $stageCount - 1); // Nurturing
                        else $stageIndex = min(3, $stageCount - 1); // Qualification
                    } else {
                        // Lead Qualification: Initial Contact (30%), Needs Assessment (30%), Solution Fit (20%), Proposal Sent (20%)
                        if ($i < 3) $stageIndex = 0; // Initial Contact
                        elseif ($i < 6) $stageIndex = min(1, $stageCount - 1); // Needs Assessment
                        elseif ($i < 8) $stageIndex = min(2, $stageCount - 1); // Solution Fit
                        else $stageIndex = min(3, $stageCount - 1); // Proposal Sent
                    }
                    $stage = $stages[$stageIndex];

                    // Status distribution: Active (60%), Won (30%), Loss (10%)
                    if ($i < 6) $status = 'Active';
                    elseif ($i < 9) $status = 'Won';
                    else $status = 'Loss';

                    // Check if this is a converted lead (first 6 deals per pipeline)
                    $convertedLead = null;
                    if ($i < 6 && $convertedLeadIndex < count($convertibleLeads)) {
                        $convertedLead = $convertibleLeads[$convertedLeadIndex];
                        // Ensure deal created_at is after lead created_at
                        if ($convertedLead && $deal['created_at']->lte($convertedLead->created_at)) {
                            $deal['created_at'] = $convertedLead->created_at->addDays(3);
                        }
                        $convertedLeadIndex++;
                    }

                    // Get sources, products, labels from converted lead or random selection
                    if ($convertedLead) {
                        // Use lead data for converted deals (following convertToDeal logic)
                        $sources = $convertedLead->sources;
                        $products = $convertedLead->products;
                        $noteText  = $convertedLead->notes;
                        $labels = $convertedLead->labels;
                        $dealName = $convertedLead->subject . ' - ' . 'From Lead';
                        $pipelineId = $pipeline->id; // Always use current pipeline, not converted lead's pipeline
                    } else {
                        // Random selection for non-converted deals
                        $sourceIds = Source::where('created_by', $userId)->pluck('id')->toArray();
                        $productIds = [];
                        if (Module_is_active('ProductService')) {
                        $productIds = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();
                        }

                        $sources = !empty($sourceIds) ? $sourceIds[array_rand($sourceIds)] : null;
                        $products = !empty($productIds) ? $productIds[array_rand($productIds)] : null;
                        $noteText  = $notes[$dealIndex % count($notes)] ?? null;

                        // Select pipeline-appropriate labels
                        $labelIds = Label::where('pipeline_id', $pipeline->id)->pluck('id')->toArray();
                        $selectedLabelIds = [];
                        if (!empty($labelIds)) {
                            $labelCount = rand(1, min(3, count($labelIds)));
                            $randomLabelIds = array_rand($labelIds, $labelCount);
                            if (!is_array($randomLabelIds)) $randomLabelIds = [$randomLabelIds];
                            foreach ($randomLabelIds as $labelIndex) {
                                $selectedLabelIds[] = $labelIds[$labelIndex];
                            }
                        }
                        $labels = implode(',', $selectedLabelIds);
                        $dealName = $deal['name'];
                        $pipelineId = $pipeline->id;
                    }

                    $hour = rand(0, 23);
                    $minute = rand(0, 59);
                    $second = rand(0, 59);

                    $dealRecord = Deal::create([
                        'name' => $dealName,
                        'price' => $deal['price'],
                        'pipeline_id' => $pipelineId,
                        'stage_id' => $stage->id,
                        'sources' => !empty($sources) ? (array)$sources : null,
                        'products' => !empty($products) ? (array)$products : null,
                        'notes' => $noteText,
                        'labels' => $labels,
                        'phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999),
                        'status' => $status,
                        'order' => 0,
                        'is_active' => $status === 'Active',
                        'creator_id' => $userId,
                        'created_by' => $convertedLead ? $convertedLead->created_by : $userId,
                        'created_at' => $deal['created_at']->setTime($hour, $minute, $second)->format('Y-m-d H:i:s'),
                    ]);

                    // Mark lead as converted and transfer related data
                    if ($convertedLead) {
                        $convertedLead->update(['is_converted' => $dealRecord->id]);

                        // Load lead relationships for transfer
                        $convertedLead->load(['tasks', 'userLeads', 'discussions', 'files', 'calls', 'emails']);

                        // Assign unique client_id
                        if (!empty($clients)) {
                            $availableClients = array_diff($clients, $usedClientIds);
                            if (empty($availableClients)) {
                                $availableClients = $clients;
                                $usedClientIds = [];
                            }
                            $assignedClientId = $availableClients[array_rand($availableClients)];
                            $usedClientIds[] = $assignedClientId;

                            ClientDeal::create([
                                'deal_id' => $dealRecord->id,
                                'client_id' => $assignedClientId,
                            ]);
                        }

                        // Transfer tasks
                        if ($convertedLead->tasks) {
                            foreach ($convertedLead->tasks as $task) {
                                DealTask::create([
                                    'deal_id' => $dealRecord->id,
                                    'name' => $task->name,
                                    'date' => $task->date,
                                    'time' => $task->time,
                                    'priority' => $task->priority,
                                    'status' => $task->status,
                                    'creator_id' => $task->creator_id,
                                    'created_by' => $task->created_by,
                                ]);
                            }
                        }

                        // Transfer users
                        if ($convertedLead->userLeads) {
                            foreach ($convertedLead->userLeads as $userLead) {
                                UserDeal::firstOrCreate([
                                    'user_id' => $userLead->user_id,
                                    'deal_id' => $dealRecord->id,
                                ]);
                            }
                        }

                        // Transfer discussions
                        if ($convertedLead->discussions) {
                            foreach ($convertedLead->discussions as $discussion) {
                                DealDiscussion::create([
                                    'deal_id' => $dealRecord->id,
                                    'comment' => $discussion->comment,
                                    'creator_id' => $discussion->creator_id,
                                    'created_by' => $discussion->created_by,
                                ]);
                            }
                        }

                        // Transfer files
                        if ($convertedLead->files) {
                            foreach ($convertedLead->files as $file) {
                                DealFile::create([
                                    'deal_id' => $dealRecord->id,
                                    'file_name' => $file->file_name,
                                    'file_path' => $file->file_path,
                                ]);
                            }
                        }

                        // Transfer calls
                        if ($convertedLead->calls) {
                            foreach ($convertedLead->calls as $call) {
                                DealCall::create([
                                    'deal_id' => $dealRecord->id,
                                    'subject' => $call->subject,
                                    'call_type' => $call->call_type,
                                    'duration' => $call->duration,
                                    'user_id' => $call->user_id,
                                    'description' => $call->description,
                                    'call_result' => $call->call_result,
                                ]);
                            }
                        }

                        // Transfer emails
                        if ($convertedLead->emails) {
                            foreach ($convertedLead->emails as $email) {
                                DealEmail::create([
                                    'deal_id' => $dealRecord->id,
                                    'to' => $email->to,
                                    'subject' => $email->subject,
                                    'description' => $email->description,
                                ]);
                            }
                        }
                    }

                    $dealIndex++;
                }
            }
        }
    }
}
