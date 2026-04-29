<?php

namespace Workdo\Contract\Database\Seeders;

use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractAttachment;
use Workdo\Contract\Models\ContractComment;
use Workdo\Contract\Models\ContractNote;
use Workdo\Contract\Models\ContractRenewal;
use Workdo\Contract\Models\ContractSignature;
use Illuminate\Database\Seeder;
use Workdo\Contract\Models\ContractType;
use App\Models\User;

class DemoContractSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Contract::where('created_by', $userId)->where('source_type', 'contract')->exists()) {
            return;
        }

        \setSetting('contract_prefix', 'CON', $userId);

        $contractData = [
            [
                'subject' => 'Enterprise CRM System Development',
                'description' => 'Development of a comprehensive customer relationship management system with advanced analytics, automation workflows, and integration capabilities for enterprise-level operations.',
                'value' => 285000.00
            ],
            [
                'subject' => 'Cloud Infrastructure Migration Services',
                'description' => 'Complete migration of on-premise infrastructure to AWS cloud platform including data migration, security implementation, and performance optimization.',
                'value' => 175000.00
            ],
            [
                'subject' => 'Digital Marketing Automation Platform',
                'description' => 'Implementation of marketing automation platform with email campaigns, lead scoring, customer segmentation, and ROI tracking capabilities.',
                'value' => 95000.00
            ],
            [
                'subject' => 'Cybersecurity Assessment & Implementation',
                'description' => 'Comprehensive security audit, vulnerability assessment, and implementation of advanced security measures including firewall, intrusion detection, and compliance frameworks.',
                'value' => 125000.00
            ],
            [
                'subject' => 'E-commerce Platform Modernization',
                'description' => 'Modernization of legacy e-commerce platform with mobile-first design, payment gateway integration, inventory management, and analytics dashboard.',
                'value' => 220000.00
            ],
            [
                'subject' => 'Business Intelligence & Analytics Solution',
                'description' => 'Implementation of BI solution with real-time dashboards, predictive analytics, data warehousing, and automated reporting for strategic decision making.',
                'value' => 165000.00
            ],
            [
                'subject' => 'Mobile Application Development Suite',
                'description' => 'Development of native iOS and Android applications with backend API, user authentication, push notifications, and offline synchronization capabilities.',
                'value' => 145000.00
            ],
            [
                'subject' => 'DevOps & CI/CD Pipeline Implementation',
                'description' => 'Setup of DevOps practices including continuous integration, continuous deployment, automated testing, monitoring, and infrastructure as code.',
                'value' => 85000.00
            ],
            [
                'subject' => 'AI-Powered Customer Support System',
                'description' => 'Implementation of AI chatbot and customer support system with natural language processing, ticket routing, and knowledge base integration.',
                'value' => 115000.00
            ],
            [
                'subject' => 'Data Lake & ETL Pipeline Development',
                'description' => 'Construction of enterprise data lake with ETL pipelines, data governance, quality monitoring, and integration with multiple data sources.',
                'value' => 195000.00
            ],
            [
                'subject' => 'Blockchain Supply Chain Solution',
                'description' => 'Development of blockchain-based supply chain tracking system with smart contracts, transparency features, and real-time monitoring capabilities.',
                'value' => 320000.00
            ],
            [
                'subject' => 'IoT Fleet Management Platform',
                'description' => 'Implementation of IoT-based fleet management system with GPS tracking, predictive maintenance, fuel optimization, and driver behavior analytics.',
                'value' => 180000.00
            ],
            [
                'subject' => 'Healthcare Management System',
                'description' => 'Comprehensive healthcare management platform with patient records, appointment scheduling, billing integration, and HIPAA compliance features.',
                'value' => 275000.00
            ],
            [
                'subject' => 'Financial Trading Platform',
                'description' => 'High-frequency trading platform with real-time market data, algorithmic trading capabilities, risk management, and regulatory compliance.',
                'value' => 450000.00
            ],
            [
                'subject' => 'Learning Management System',
                'description' => 'Enterprise LMS with course creation tools, progress tracking, certification management, and integration with HR systems.',
                'value' => 135000.00
            ],
            [
                'subject' => 'Inventory Management Optimization',
                'description' => 'Advanced inventory management system with demand forecasting, automated reordering, warehouse optimization, and multi-location support.',
                'value' => 98000.00
            ],
            [
                'subject' => 'Real Estate CRM & Portal',
                'description' => 'Real estate management platform with property listings, client management, virtual tours, document management, and commission tracking.',
                'value' => 155000.00
            ],
            [
                'subject' => 'Manufacturing ERP Integration',
                'description' => 'Integration of manufacturing processes with ERP system including production planning, quality control, inventory management, and reporting.',
                'value' => 240000.00
            ],
            [
                'subject' => 'Social Media Analytics Dashboard',
                'description' => 'Comprehensive social media analytics platform with sentiment analysis, competitor tracking, influencer identification, and ROI measurement.',
                'value' => 75000.00
            ],
            [
                'subject' => 'Compliance Management System',
                'description' => 'Regulatory compliance management platform with audit trails, policy management, risk assessment, and automated compliance reporting.',
                'value' => 190000.00
            ]
        ];

        $users = User::where('created_by', $userId)
            ->whereIn('type', ['staff', 'client'])
            ->get();
        $contractTypes = ContractType::where('created_by', $userId)->get();

        foreach ($contractData as $index => $data) {
            // Spread created_at across last 6 months, ordered oldest to newest
            $createdAt = \Carbon\Carbon::now()->subDays(180 - ($index * 6));
            $startDate = $createdAt->copy()->addDays(rand(1, 30));
            $endDate = $startDate->copy()->addMonths(rand(6, 24));

            $contract = Contract::create([
                'subject' => $data['subject'],
                'user_id' => $users->random()->id ?? 1,
                'value' => $data['value'],
                'type_id' => $contractTypes->random()->id ?? 1,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'description' => $data['description'],
                'status' => ['pending', 'accepted', 'declined', 'closed'][array_rand(['pending', 'accepted', 'declined', 'closed'])],
                'creator_id' => $userId,
                'created_by' => $userId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->createAttachments($contract, $users);
            $this->createComments($contract, $users);
            $this->createNotes($contract, $users);
            $this->createRenewals($contract, $userId);
            $this->createSignatures($contract);
        }
    }

    private function createAttachments($contract, $users)
    {
        // Create dummy files if they don't exist
        $attachmentDir = storage_path('app/public/contracts/attachments');
        if (!file_exists($attachmentDir)) {
            mkdir($attachmentDir, 0755, true);
        }

        $attachmentTypes = [
            ['name' => 'budget_breakdown.pdf', 'path' => 'budget_breakdown.pdf'],
            ['name' => 'contract_agreement_signed.pdf', 'path' => 'contract_agreement_signed.pdf'],
            ['name' => 'deployment_guide.pdf', 'path' => 'deployment_guide.pdf'],
            ['name' => 'project_timeline.xlsx', 'path' => 'project_timeline.xlsx'],
            ['name' => 'requirements_document.pdf', 'path' => 'requirements_document.pdf'],
            ['name' => 'security_compliance_report.pdf', 'path' => 'security_compliance_report.pdf'],
            ['name' => 'technical_specifications.docx', 'path' => 'technical_specifications.docx'],
            ['name' => 'testing_plan.docx', 'path' => 'testing_plan.docx'],
            ['name' => 'user_manual.pdf', 'path' => 'user_manual.pdf']
        ];



        $attachmentCount = rand(2, 3);
        for ($i = 0; $i < $attachmentCount; $i++) {
            $attachment = $attachmentTypes[array_rand($attachmentTypes)];
            ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_name' => $attachment['name'],
                'file_path' => $attachment['path'],
                'uploaded_by' => $users->random()->id,
                'creator_id' => $contract->creator_id,
                'created_by' => $contract->created_by,
                'created_at' => $contract->created_at->copy()->addDays(rand(1, 30)),
            ]);
        }
    }

    private function createComments($contract, $users)
    {
        $comments = [
            'Initial project kickoff meeting scheduled for next week.',
            'Requirements gathering phase completed successfully.',
            'Technical architecture review completed with stakeholders.',
            'Development phase is progressing according to timeline.',
            'First milestone deliverables have been submitted for review.',
            'Client feedback incorporated into the current sprint.',
            'Security audit findings addressed and resolved.',
            'Performance testing results exceed expected benchmarks.',
            'User acceptance testing phase initiated with key users.',
            'Documentation updates completed for latest features.',
            'Integration testing with third-party systems successful.',
            'Code review process completed with no critical issues.',
            'Deployment to staging environment completed successfully.',
            'Training materials prepared for end-user sessions.',
            'Final quality assurance testing in progress.',
            'Go-live preparation checklist reviewed and approved.',
            'Post-deployment monitoring systems are operational.',
            'Client satisfaction survey results are very positive.',
            'Knowledge transfer sessions completed with client team.',
            'Project closure documentation finalized and archived.'
        ];

        $commentCount = rand(1, 3);
        for ($i = 0; $i < $commentCount; $i++) {
            ContractComment::create([
                'contract_id' => $contract->id,
                'comment' => $comments[array_rand($comments)],
                'user_id' => $users->random()->id,
                'is_edited' => rand(0, 1) && rand(1, 5) == 1,
                'creator_id' => $contract->creator_id,
                'created_by' => $contract->created_by,
                'created_at' => $contract->created_at->copy()->addDays(rand(1, 60)),
            ]);
        }
    }

    private function createNotes($contract, $users)
    {
        $notes = [
            'Client prefers weekly status updates via email and monthly progress meetings.',
            'Technical team recommends using microservices architecture for better scalability.',
            'Budget allocation approved for additional security features implementation.',
            'Stakeholder feedback suggests prioritizing mobile responsiveness.',
            'Integration with existing CRM system requires additional API development.',
            'Performance requirements updated to handle 10,000 concurrent users.',
            'Compliance requirements include GDPR and SOC 2 Type II certification.',
            'Client team availability limited during holiday season - adjust timeline accordingly.',
            'Third-party vendor dependencies identified and mitigation strategies developed.',
            'Change request submitted for additional reporting dashboard features.'
        ];

        $noteCount = rand(1, 3);
        for ($i = 0; $i < $noteCount; $i++) {
            ContractNote::create([
                'contract_id' => $contract->id,
                'note' => $notes[array_rand($notes)],
                'user_id' => $users->random()->id,
                'is_edited' => rand(0, 1) && rand(1, 7) == 1,
                'creator_id' => $contract->creator_id,
                'created_by' => $contract->created_by,
                'created_at' => $contract->created_at->copy()->addDays(rand(1, 45)),
            ]);
        }
    }

    private function createRenewals($contract, $userId)
    {
        $renewalCount = rand(1, 3);
        $baseValue = $contract->value;

        for ($i = 0; $i < $renewalCount; $i++) {
            $renewalStartDate = \Carbon\Carbon::parse($contract->start_date)->addDays(rand(30, 365));
            $renewalEndDate = $renewalStartDate->copy()->addMonths(rand(6, 12));
            $valueIncrease = (rand(5, 25) / 100); // 5-25% increase
            $renewalValue = $baseValue * (1 + $valueIncrease);

            $renewalNotes = [
                'Annual renewal with enhanced features and extended support coverage.',
                'Contract renewal includes additional modules and increased user capacity.',
                'Renewal terms updated to reflect current market rates and service levels.',
                'Extended contract period with improved SLA and response times.',
                'Renewal includes migration to latest platform version and new features.',
                'Multi-year contract extension with volume discount pricing applied.',
                'Renewal incorporates new compliance requirements and security standards.',
                'Contract extension includes priority support and dedicated account management.',
                'Renewal with expanded geographic coverage and additional service locations.',
                'Updated contract terms include cloud migration and modernization services.'
            ];

            ContractRenewal::create([
                'contract_id' => $contract->id,
                'start_date' => $renewalStartDate->format('Y-m-d'),
                'end_date' => $renewalEndDate->format('Y-m-d'),
                'value' => $renewalValue,
                'notes' => $renewalNotes[array_rand($renewalNotes)],
                'status' => ['draft', 'pending', 'approved', 'active', 'expired', 'cancelled'][array_rand(['draft', 'pending', 'approved', 'active', 'expired', 'cancelled'])],
                'created_by' => $userId,
                'created_at' => $contract->created_at->copy()->addDays(rand(15, 90)),
            ]);

            $baseValue = $renewalValue; // Use new value for next renewal
        }
    }

    private function createSignatures($contract)
    {
        // 80% chance to have signatures
        if (rand(1, 10) > 8) {
            return;
        }

        $now = $contract->created_at->copy()->addDays(rand(5, 60));
        $users = User::whereIn('id', [$contract->created_by, $contract->user_id])->get();
        $createdByUser = $users->firstWhere('id', $contract->created_by);
        $contractUser = $users->firstWhere('id', $contract->user_id);

        $signatures = [
            [
                'user' => $createdByUser,
                'creator_id' => $contract->created_by
            ],
            [
                'user' => $contractUser,
                'creator_id' => $contractUser->created_by
            ]
        ];

        foreach ($signatures as $signature) {
            ContractSignature::create([
                'contract_id' => $contract->id,
                'user_id' => $signature['user']->id,
                'signature_type' => ['draw', 'type', 'upload'][array_rand(['draw', 'type', 'upload'])],
                'signature_data' => $this->generateSignatureData($signature['user']->name),
                'signed_at' => $now,
                'creator_id' => $signature['creator_id'],
                'created_by' => $contract->created_by,
            ]);
        }
    }

    private function generateSignatureData($userName)
    {
        // Create SVG signature based on user name
        $width = 200;
        $height = 60;
        $fontSize = 24;

        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<text x="10" y="40" font-family="cursive, serif" font-size="' . $fontSize . '" fill="#1a365d" transform="rotate(-2)">';
        $svg .= htmlspecialchars($userName);
        $svg .= '</text>';
        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
