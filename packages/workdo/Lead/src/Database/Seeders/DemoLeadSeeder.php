<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\Source;
use Workdo\Lead\Models\Label;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoLeadSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Lead::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $pipelines = Pipeline::where('created_by', $userId)->get();
            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();

            if ($pipelines->isEmpty() || empty($users)) {
                return;
            }

            $leadData = [
                // Marketing Pipeline Leads (30 records)
                ['name' => 'Hazel Cox', 'email' => 'hazel.cox@gmail.com', 'subject' => 'Product Demo Request', 'date' => Carbon::now()->subMonths(6)->setDay(24), 'created_at' => Carbon::now()->subDays(179)],
                ['name' => 'Leo Ward', 'email' => 'leo.ward@yahoo.com', 'subject' => 'Partnership Inquiry', 'date' => Carbon::now()->subMonths(5)->setDay(16), 'created_at' => Carbon::now()->subDays(173)],
                ['name' => 'Violet Richardson', 'email' => 'violet.richardson@hotmail.com', 'subject' => 'Pricing Information', 'date' => Carbon::now()->subMonths(4)->setDay(11), 'created_at' => Carbon::now()->subDays(167)],
                ['name' => 'Ezra Butler', 'email' => 'ezra.butler@outlook.com', 'subject' => 'Solution Consultation', 'date' => Carbon::now()->subMonths(3)->setDay(18), 'created_at' => Carbon::now()->subDays(161)],
                ['name' => 'Aurora Simmons', 'email' => 'aurora.simmons@gmail.com', 'subject' => 'Service Integration', 'date' => Carbon::now()->subMonths(2)->setDay(13), 'created_at' => Carbon::now()->subDays(155)],
                ['name' => 'Kai Foster', 'email' => 'kai.foster@yahoo.com', 'subject' => 'Custom Development', 'date' => Carbon::now()->subMonths(2)->setDay(26), 'created_at' => Carbon::now()->subDays(149)],
                ['name' => 'Savannah Henderson', 'email' => 'savannah.henderson@hotmail.com', 'subject' => 'Platform Migration', 'date' => Carbon::now()->subMonth()->setDay(9), 'created_at' => Carbon::now()->subDays(143)],
                ['name' => 'Maverick Bryant', 'email' => 'maverick.bryant@gmail.com', 'subject' => 'Marketing Automation', 'date' => Carbon::now()->subMonth()->setDay(23), 'created_at' => Carbon::now()->subDays(137)],
                ['name' => 'Skylar Alexander', 'email' => 'skylar.alexander@outlook.com', 'subject' => 'API Integration', 'date' => Carbon::now()->subDays(9), 'created_at' => Carbon::now()->subDays(131)],
                ['name' => 'Jaxon Russell', 'email' => 'jaxon.russell@yahoo.com', 'subject' => 'AI Implementation', 'date' => Carbon::now()->subDays(5), 'created_at' => Carbon::now()->subDays(125)],
                ['name' => 'Paisley Griffin', 'email' => 'paisley.griffin@gmail.com', 'subject' => 'Cloud Migration', 'date' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDays(119)],
                ['name' => 'Roman Diaz', 'email' => 'roman.diaz@hotmail.com', 'subject' => 'Data Analysis Tools', 'date' => Carbon::now()->addDay(), 'created_at' => Carbon::now()->subDays(113)],
                ['name' => 'Kinsley Hayes', 'email' => 'kinsley.hayes@outlook.com', 'subject' => 'Mobile App Development', 'date' => Carbon::now()->addDays(5), 'created_at' => Carbon::now()->subDays(107)],
                ['name' => 'Declan Myers', 'email' => 'declan.myers@gmail.com', 'subject' => 'Security Assessment', 'date' => Carbon::now()->addDays(7), 'created_at' => Carbon::now()->subDays(101)],
                ['name' => 'Nova Ford', 'email' => 'nova.ford@yahoo.com', 'subject' => 'E-commerce Platform', 'date' => Carbon::now()->addDays(9), 'created_at' => Carbon::now()->subDays(95)],
                ['name' => 'Axel Hamilton', 'email' => 'axel.hamilton@hotmail.com', 'subject' => 'Payment Gateway', 'date' => Carbon::now()->addDays(12), 'created_at' => Carbon::now()->subDays(89)],
                ['name' => 'Emery Graham', 'email' => 'emery.graham@gmail.com', 'subject' => 'Healthcare Solutions', 'date' => Carbon::now()->addDays(14), 'created_at' => Carbon::now()->subDays(83)],
                ['name' => 'Knox Sullivan', 'email' => 'knox.sullivan@outlook.com', 'subject' => 'Learning Management', 'date' => Carbon::now()->addDays(16), 'created_at' => Carbon::now()->subDays(77)],
                ['name' => 'Wren Wallace', 'email' => 'wren.wallace@yahoo.com', 'subject' => 'Supply Chain Management', 'date' => Carbon::now()->addDays(18), 'created_at' => Carbon::now()->subDays(71)],
                ['name' => 'Atlas Woods', 'email' => 'atlas.woods@gmail.com', 'subject' => 'Production Optimization', 'date' => Carbon::now()->addDays(20), 'created_at' => Carbon::now()->subDays(65)],
                ['name' => 'Sage Cole', 'email' => 'sage.cole@hotmail.com', 'subject' => 'Digital Transformation', 'date' => Carbon::now()->addDays(24), 'created_at' => Carbon::now()->subDays(59)],
                ['name' => 'Phoenix West', 'email' => 'phoenix.west@outlook.com', 'subject' => 'Technology Assessment', 'date' => Carbon::now()->addDays(26), 'created_at' => Carbon::now()->subDays(53)],
                ['name' => 'River Jordan', 'email' => 'river.jordan@gmail.com', 'subject' => 'Creative Platform Demo', 'date' => Carbon::now()->addDays(28), 'created_at' => Carbon::now()->subDays(47)],
                ['name' => 'Sage Barnes', 'email' => 'sage.barnes@yahoo.com', 'subject' => 'Marketing Tool Evaluation', 'date' => Carbon::now()->addDays(32), 'created_at' => Carbon::now()->subDays(41)],
                ['name' => 'Rowan Fisher', 'email' => 'rowan.fisher@hotmail.com', 'subject' => 'Online Store Setup', 'date' => Carbon::now()->addMonth()->setDay(5), 'created_at' => Carbon::now()->subDays(26)],
                ['name' => 'Ember Price', 'email' => 'ember.price@gmail.com', 'subject' => 'Cloud Infrastructure', 'date' => Carbon::now()->addMonth()->setDay(10), 'created_at' => Carbon::now()->subDays(19)],
                ['name' => 'Orion Bennett', 'email' => 'orion.bennett@outlook.com', 'subject' => 'Analytics Implementation', 'date' => Carbon::now()->addMonth()->setDay(17), 'created_at' => Carbon::now()->subDays(14)],
                ['name' => 'Willa Powell', 'email' => 'willa.powell@yahoo.com', 'subject' => 'App Development Inquiry', 'date' => Carbon::now()->addMonth()->setDay(24), 'created_at' => Carbon::now()->subDays(9)],
                ['name' => 'Zander Long', 'email' => 'zander.long@gmail.com', 'subject' => 'AI Solution Exploration', 'date' => Carbon::now()->addMonths(2)->setDay(2), 'created_at' => Carbon::now()->subDays(5)],
                ['name' => 'Indie Hughes', 'email' => 'indie.hughes@hotmail.com', 'subject' => 'Blockchain Integration', 'date' => Carbon::now()->addMonths(2)->setDay(9), 'created_at' => Carbon::now()->subDays(2)],

                // Lead Qualification Pipeline Leads (30 records)
                ['name' => 'Remi Flores', 'email' => 'remi.flores@gmail.com', 'subject' => 'Enterprise Assessment', 'date' => Carbon::now()->subMonths(6)->setDay(26), 'created_at' => Carbon::now()->subDays(178)],
                ['name' => 'Crew Washington', 'email' => 'crew.washington@yahoo.com', 'subject' => 'System Evaluation', 'date' => Carbon::now()->subMonths(5)->setDay(18), 'created_at' => Carbon::now()->subDays(172)],
                ['name' => 'Sloane Butler', 'email' => 'sloane.butler@hotmail.com', 'subject' => 'BI Platform Review', 'date' => Carbon::now()->subMonths(4)->setDay(13), 'created_at' => Carbon::now()->subDays(166)],
                ['name' => 'Ari Simmons', 'email' => 'ari.simmons@outlook.com', 'subject' => 'Automation Feasibility', 'date' => Carbon::now()->subMonths(3)->setDay(20), 'created_at' => Carbon::now()->subDays(160)],
                ['name' => 'Lennox Patterson', 'email' => 'lennox.patterson@gmail.com', 'subject' => 'Workflow Optimization', 'date' => Carbon::now()->subMonths(2)->setDay(15), 'created_at' => Carbon::now()->subDays(154)],
                ['name' => 'Marlowe Jenkins', 'email' => 'marlowe.jenkins@yahoo.com', 'subject' => 'Integration Assessment', 'date' => Carbon::now()->subMonths(2)->setDay(28), 'created_at' => Carbon::now()->subDays(148)],
                ['name' => 'Sutton Perry', 'email' => 'sutton.perry@hotmail.com', 'subject' => 'Quality Control System', 'date' => Carbon::now()->subMonth()->setDay(11), 'created_at' => Carbon::now()->subDays(142)],
                ['name' => 'Bellamy Powell', 'email' => 'bellamy.powell@gmail.com', 'subject' => 'Performance Analysis', 'date' => Carbon::now()->subMonth()->setDay(25), 'created_at' => Carbon::now()->subDays(136)],
                ['name' => 'Finley Long', 'email' => 'finley.long@outlook.com', 'subject' => 'Compliance Review', 'date' => Carbon::now()->subDays(11), 'created_at' => Carbon::now()->subDays(130)],
                ['name' => 'Oakley Hughes', 'email' => 'oakley.hughes@yahoo.com', 'subject' => 'Risk Management', 'date' => Carbon::now()->subDays(5), 'created_at' => Carbon::now()->subDays(124)],
                ['name' => 'Lennon Flores', 'email' => 'lennon.flores@gmail.com', 'subject' => 'Security Evaluation', 'date' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDays(118)],
                ['name' => 'Harley Washington', 'email' => 'harley.washington@hotmail.com', 'subject' => 'Data Management Review', 'date' => Carbon::now()->addDay(), 'created_at' => Carbon::now()->subDays(112)],
                ['name' => 'Peyton Butler', 'email' => 'peyton.butler@outlook.com', 'subject' => 'Vendor Assessment', 'date' => Carbon::now()->addDays(7), 'created_at' => Carbon::now()->subDays(106)],
                ['name' => 'Reese Simmons', 'email' => 'reese.simmons@gmail.com', 'subject' => 'Procurement Review', 'date' => Carbon::now()->addDays(9), 'created_at' => Carbon::now()->subDays(100)],
                ['name' => 'Sage Patterson', 'email' => 'sage.patterson@yahoo.com', 'subject' => 'Cost Analysis', 'date' => Carbon::now()->addDays(11), 'created_at' => Carbon::now()->subDays(94)],
                ['name' => 'River Jenkins', 'email' => 'river.jenkins@hotmail.com', 'subject' => 'ROI Assessment', 'date' => Carbon::now()->addDays(14), 'created_at' => Carbon::now()->subDays(88)],
                ['name' => 'Rowan Perry', 'email' => 'rowan.perry@gmail.com', 'subject' => 'Budget Evaluation', 'date' => Carbon::now()->addDays(16), 'created_at' => Carbon::now()->subDays(82)],
                ['name' => 'Emery Powell', 'email' => 'emery.powell@outlook.com', 'subject' => 'Timeline Planning', 'date' => Carbon::now()->addDays(18), 'created_at' => Carbon::now()->subDays(76)],
                ['name' => 'Quinn Long', 'email' => 'quinn.long@yahoo.com', 'subject' => 'Resource Planning', 'date' => Carbon::now()->addDays(20), 'created_at' => Carbon::now()->subDays(70)],
                ['name' => 'Avery Hughes', 'email' => 'avery.hughes@gmail.com', 'subject' => 'Capacity Assessment', 'date' => Carbon::now()->addDays(22), 'created_at' => Carbon::now()->subDays(64)],
                ['name' => 'Cameron Flores', 'email' => 'cameron.flores@hotmail.com', 'subject' => 'Scalability Analysis', 'date' => Carbon::now()->addDays(23), 'created_at' => Carbon::now()->subDays(58)],
                ['name' => 'Dakota Washington', 'email' => 'dakota.washington@outlook.com', 'subject' => 'Performance Evaluation', 'date' => Carbon::now()->addDays(25), 'created_at' => Carbon::now()->subDays(52)],
                ['name' => 'Hayden Butler', 'email' => 'hayden.butler@gmail.com', 'subject' => 'User Testing', 'date' => Carbon::now()->addDays(27), 'created_at' => Carbon::now()->subDays(46)],
                ['name' => 'Finley Simmons', 'email' => 'finley.simmons@yahoo.com', 'subject' => 'Training Requirements', 'date' => Carbon::now()->addDays(31), 'created_at' => Carbon::now()->subDays(40)],
                ['name' => 'Blaine Patterson', 'email' => 'blaine.patterson@hotmail.com', 'subject' => 'Change Impact Analysis', 'date' => Carbon::now()->addMonth()->setDay(6), 'created_at' => Carbon::now()->subDays(25)],
                ['name' => 'Sage Jenkins', 'email' => 'sage.jenkins@gmail.com', 'subject' => 'Stakeholder Review', 'date' => Carbon::now()->addMonth()->setDay(13), 'created_at' => Carbon::now()->subDays(18)],
                ['name' => 'Blake Perry', 'email' => 'blake.perry@outlook.com', 'subject' => 'Requirements Analysis', 'date' => Carbon::now()->addMonth()->setDay(20), 'created_at' => Carbon::now()->subDays(13)],
                ['name' => 'Jordan Powell', 'email' => 'jordan.powell@yahoo.com', 'subject' => 'Feasibility Assessment', 'date' => Carbon::now()->addMonth()->setDay(27), 'created_at' => Carbon::now()->subDays(8)],
                ['name' => 'Casey Long', 'email' => 'casey.long@gmail.com', 'subject' => 'Technical Evaluation', 'date' => Carbon::now()->addMonths(2)->setDay(4), 'created_at' => Carbon::now()->subDays(4)],
                ['name' => 'Taylor Hughes', 'email' => 'taylor.hughes@hotmail.com', 'subject' => 'Architecture Review', 'date' => Carbon::now()->addMonths(2)->setDay(11), 'created_at' => Carbon::now(1)],

                // Sales Pipeline Leads (30 records)
                ['name' => 'Oliver Bennett', 'email' => 'oliver.bennett@gmail.com', 'subject' => 'Enterprise Software Purchase', 'date' => Carbon::now()->subMonths(6)->setDay(22), 'created_at' => Carbon::now()->subDays(180)],
                ['name' => 'Charlotte Foster', 'email' => 'charlotte.foster@yahoo.com', 'subject' => 'Annual License Renewal', 'date' => Carbon::now()->subMonths(5)->setDay(14), 'created_at' => Carbon::now()->subDays(174)],
                ['name' => 'Ethan Murphy', 'email' => 'ethan.murphy@hotmail.com', 'subject' => 'Custom Solution Quote', 'date' => Carbon::now()->subMonths(4)->setDay(9), 'created_at' => Carbon::now()->subDays(168)],
                ['name' => 'Sophia Richardson', 'email' => 'sophia.richardson@gmail.com', 'subject' => 'Multi-Year Contract', 'date' => Carbon::now()->subMonths(3)->setDay(16), 'created_at' => Carbon::now()->subDays(162)],
                ['name' => 'Lucas Cooper', 'email' => 'lucas.cooper@outlook.com', 'subject' => 'Premium Package Upgrade', 'date' => Carbon::now()->subMonths(2)->setDay(11), 'created_at' => Carbon::now()->subDays(156)],
                ['name' => 'Amelia Brooks', 'email' => 'amelia.brooks@yahoo.com', 'subject' => 'Volume Discount Request', 'date' => Carbon::now()->subMonths(2)->setDay(24), 'created_at' => Carbon::now()->subDays(150)],
                ['name' => 'Mason Reed', 'email' => 'mason.reed@gmail.com', 'subject' => 'Implementation Services', 'date' => Carbon::now()->subMonth()->setDay(7), 'created_at' => Carbon::now()->subDays(144)],
                ['name' => 'Harper Bailey', 'email' => 'harper.bailey@hotmail.com', 'subject' => 'Training Package Deal', 'date' => Carbon::now()->subMonth()->setDay(21), 'created_at' => Carbon::now()->subDays(138)],
                ['name' => 'Logan Kelly', 'email' => 'logan.kelly@gmail.com', 'subject' => 'Support Contract Extension', 'date' => Carbon::now()->subDays(7), 'created_at' => Carbon::now()->subDays(132)],
                ['name' => 'Ella Howard', 'email' => 'ella.howard@yahoo.com', 'subject' => 'Professional Services Quote', 'date' => Carbon::now()->subDays(3), 'created_at' => Carbon::now()->subDays(126)],
                ['name' => 'Jackson Ward', 'email' => 'jackson.ward@outlook.com', 'subject' => 'Migration Service Purchase', 'date' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDays(120)],
                ['name' => 'Avery Torres', 'email' => 'avery.torres@gmail.com', 'subject' => 'Analytics Platform License', 'date' => Carbon::now()->addDay(), 'created_at' => Carbon::now()->subDays(114)],
                ['name' => 'Scarlett Peterson', 'email' => 'scarlett.peterson@hotmail.com', 'subject' => 'Development Framework Sale', 'date' => Carbon::now()->addDays(3), 'created_at' => Carbon::now()->subDays(108)],
                ['name' => 'Aiden Gray', 'email' => 'aiden.gray@yahoo.com', 'subject' => 'Security Audit Service', 'date' => Carbon::now()->addDays(5), 'created_at' => Carbon::now()->subDays(102)],
                ['name' => 'Luna Ramirez', 'email' => 'luna.ramirez@gmail.com', 'subject' => 'E-commerce Solution Purchase', 'date' => Carbon::now()->addDays(7), 'created_at' => Carbon::now()->subDays(96)],
                ['name' => 'Grayson James', 'email' => 'grayson.james@outlook.com', 'subject' => 'Payment Processing Deal', 'date' => Carbon::now()->addDays(10), 'created_at' => Carbon::now()->subDays(90)],
                ['name' => 'Layla Watson', 'email' => 'layla.watson@hotmail.com', 'subject' => 'Healthcare Software License', 'date' => Carbon::now()->addDays(12), 'created_at' => Carbon::now()->subDays(84)],
                ['name' => 'Carter Phillips', 'email' => 'carter.phillips@gmail.com', 'subject' => 'Educational Platform Sale', 'date' => Carbon::now()->addDays(14), 'created_at' => Carbon::now()->subDays(78)],
                ['name' => 'Zoe Evans', 'email' => 'zoe.evans@yahoo.com', 'subject' => 'Supply Chain Software', 'date' => Carbon::now()->addDays(16), 'created_at' => Carbon::now()->subDays(72)],
                ['name' => 'Liam Turner', 'email' => 'liam.turner@gmail.com', 'subject' => 'Manufacturing System Purchase', 'date' => Carbon::now()->addDays(18), 'created_at' => Carbon::now()->subDays(66)],
                ['name' => 'Aria Collins', 'email' => 'aria.collins@hotmail.com', 'subject' => 'Corporate License Agreement', 'date' => Carbon::now()->addDays(20), 'created_at' => Carbon::now()->subDays(60)],
                ['name' => 'Noah Edwards', 'email' => 'noah.edwards@outlook.com', 'subject' => 'Retail Management System', 'date' => Carbon::now()->addDays(22), 'created_at' => Carbon::now()->subDays(54)],
                ['name' => 'Chloe Stewart', 'email' => 'chloe.stewart@gmail.com', 'subject' => 'International Trade Platform', 'date' => Carbon::now()->addDays(24), 'created_at' => Carbon::now()->subDays(48)],
                ['name' => 'Owen Sanchez', 'email' => 'owen.sanchez@yahoo.com', 'subject' => 'Content Management Purchase', 'date' => Carbon::now()->addDays(26), 'created_at' => Carbon::now()->subDays(42)],
                ['name' => 'Mia Morris', 'email' => 'mia.morris@hotmail.com', 'subject' => 'Financial Software License', 'date' => Carbon::now()->addDays(28), 'created_at' => Carbon::now()->subDays(27)],
                ['name' => 'Caleb Rogers', 'email' => 'caleb.rogers@gmail.com', 'subject' => 'Startup Package Deal', 'date' => Carbon::now()->addDays(30), 'created_at' => Carbon::now()->subDays(20)],
                ['name' => 'Lily Cook', 'email' => 'lily.cook@outlook.com', 'subject' => 'Project Management Tool', 'date' => Carbon::now()->addMonth()->setDay(3), 'created_at' => Carbon::now()->subDays(15)],
                ['name' => 'Hunter Morgan', 'email' => 'hunter.morgan@yahoo.com', 'subject' => 'Consulting Software Suite', 'date' => Carbon::now()->addMonth()->setDay(8), 'created_at' => Carbon::now()->subDays(10)],
                ['name' => 'Nora Bell', 'email' => 'nora.bell@gmail.com', 'subject' => 'Logistics Management System', 'date' => Carbon::now()->addMonth()->setDay(15), 'created_at' => Carbon::now()->subDays(6)],
                ['name' => 'Wyatt Rivera', 'email' => 'wyatt.rivera@hotmail.com', 'subject' => 'Hotel Management Software', 'date' => Carbon::now()->addMonth()->setDay(22), 'created_at' => Carbon::now()->subDays(3)],
            ];

            $notes = [
                "Initial contact established through multiple touchpoints with strong interest demonstrated. Follow-up meeting scheduled to discuss specific requirements and next steps.",
                "Productive discussion completed with key decision makers present. Budget parameters confirmed and approval process timeline established for moving forward with evaluation.",
                "Detailed information package sent including pricing structure and implementation timeline. Client team currently reviewing materials and will provide feedback within one week.",
                "Comprehensive presentation delivered covering all requested topics and use cases. Positive reception received with several technical questions answered during the session.",
                "Requirements gathering session completed with thorough documentation of current processes. Gap analysis shows significant opportunities for improvement and efficiency gains.",
                "Technical evaluation meeting scheduled with IT team to assess compatibility requirements. Infrastructure review and integration planning discussion will be primary focus.",
                "Pricing discussion held with finance team covering various licensing options available. Volume discounts and multi-year terms presented for consideration and evaluation.",
                "Extended evaluation period approved to allow comprehensive testing of key features. Additional user access provided to facilitate broader organizational assessment and feedback.",
                "Reference customer introduction arranged to share implementation experience and lessons learned. Similar industry background will provide relevant insights for decision making.",
                "Implementation approach discussed including timeline, resource requirements, and project phases. Preferred deployment strategy identified with minimal business disruption as priority.",
                "Security and compliance review completed with all requirements thoroughly addressed. Documentation provided covering data protection, access controls, and audit trail capabilities.",
                "Budget approval process initiated with finance committee reviewing investment proposal. Cost-benefit analysis demonstrates strong return on investment within reasonable timeframe.",
                "Executive briefing delivered to senior leadership team covering strategic benefits. Management support confirmed for proceeding with detailed evaluation and selection process.",
                "Technical architecture review completed with systems integration requirements documented. API capabilities and data flow requirements assessed for seamless connectivity.",
                "Vendor comparison analysis provided highlighting key differentiators and competitive advantages. Evaluation criteria matrix completed showing strong alignment with organizational needs.",
                "Proof of concept development approved with specific success criteria established. Limited scope pilot will demonstrate core functionality using real organizational data.",
                "Training needs assessment completed identifying user groups and skill level requirements. Comprehensive education program designed to ensure successful adoption and utilization.",
                "Data migration planning session held with database administrators and technical team. Strategy developed to ensure data integrity and minimize system downtime.",
                "Service level agreement terms discussed covering support coverage and response time requirements. Escalation procedures and account management structure clearly defined.",
                "Contract terms review in progress with legal teams from both organizations. Final negotiations focused on implementation timeline, payment terms, and service commitments.",
                "Go-live support plan established with dedicated resources assigned for transition period. Post-implementation review schedule confirmed to ensure successful adoption.",
                "User acceptance testing phase completed with all critical functionality validated successfully. Final approval documentation prepared for management sign-off and project closure.",
                "System deployment completed on schedule with all stakeholders trained and operational. Initial performance metrics show positive results exceeding baseline expectations.",
                "Post-implementation review conducted with lessons learned documented for future reference. User satisfaction surveys show high adoption rates and positive feedback.",
                "Ongoing relationship management structure established with regular check-ins scheduled quarterly. Account review process will identify optimization opportunities and expansion possibilities.",
                "Strategic partnership discussion initiated exploring opportunities for expanded collaboration. Joint initiatives identified that could provide mutual benefits and market advantages.",
                "Integration project completed successfully with all systems communicating effectively and data flowing properly. Performance monitoring shows stable operations meeting requirements.",
                "Pilot program results exceeded expectations with strong user engagement and measurable benefits. Full deployment recommendation approved by steering committee for organization-wide rollout.",
                "Executive sponsorship confirmed with C-level champion assigned to oversee project success. Leadership commitment ensures adequate resources and organizational support throughout implementation.",
                "Business case analysis completed showing compelling return on investment and operational benefits. Financial justification approved by executive committee for budget allocation."
            ];

            $usedUserIds = [];
            $leadIndex = 0;

            foreach ($pipelines as $pipeline) {
                // Skip Sales pipeline
                if ($pipeline->name === 'Sales') continue;
                
                $stages = LeadStage::where('pipeline_id', $pipeline->id)->get();

                if ($stages->isEmpty()) continue;

                // Create 10 leads per pipeline with proper date distribution
                for ($i = 0; $i < 10; $i++) {
                    if ($leadIndex >= count($leadData)) break;

                    $lead = $leadData[$leadIndex];

                    // Assign unique user_id (1 lead per user)
                    $availableUsers = array_diff($users, $usedUserIds);
                    if (empty($availableUsers)) {
                        $availableUsers = $users; // Reset if all users used
                        $usedUserIds = [];
                    }
                    $assignedUserId = $availableUsers[array_rand($availableUsers)];
                    $usedUserIds[] = $assignedUserId;

                    // Realistic stage distribution based on sales funnel
                    if ($pipeline->name === 'Sales') {
                        // Sales funnel: more leads in early stages
                        if ($i < 4) $stageIndex = 0; // Draft (40%)
                        elseif ($i < 7) $stageIndex = 1; // Sent (30%)
                        elseif ($i < 8) $stageIndex = 2; // Open (10%)
                        elseif ($i < 9) $stageIndex = 3; // Revised (10%)
                        else $stageIndex = 4; // Accepted (10%)
                    } elseif ($pipeline->name === 'Marketing') {
                        // Marketing funnel: gradual decrease
                        if ($i < 4) $stageIndex = 0; // Prospect (40%)
                        elseif ($i < 6) $stageIndex = 1; // Contacted (20%)
                        elseif ($i < 8) $stageIndex = 2; // Engaged (20%)
                        elseif ($i < 9) $stageIndex = 3; // Qualified (10%)
                        else $stageIndex = 4; // Converted (10%)
                    } else {
                        // Lead Qualification: assessment-based distribution
                        if ($i < 3) $stageIndex = 0; // Unqualified (30%)
                        elseif ($i < 5) $stageIndex = 1; // In Review (20%)
                        elseif ($i < 7) $stageIndex = 2; // Qualified (20%)
                        elseif ($i < 9) $stageIndex = 3; // Approved (20%)
                        else $stageIndex = 4; // Rejected (10%)
                    }
                    $stage = $stages[$stageIndex];

                    // Select source IDs and random product IDs
                    $sourceIds = Source::where('created_by', $userId)->pluck('id')->toArray();

                    $productIds = [];
                    if (Module_is_active('ProductService')) {
                        $productIds = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();
                    }

                    $selectedSourceId = !empty($sourceIds) ? $sourceIds[array_rand($sourceIds)] : null;
                    $selectedProductId = !empty($productIds) ? $productIds[array_rand($productIds)] : null;

                    // Select pipeline-appropriate label IDs (1-3 labels)
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

                    // Generate a random phone number
                    $countryCodes = ['+1', '+44', '+91', '+61', '+81', '+49', '+33', '+39', '+55', '+971', '+86', '+7', '+27', '+82', '+34'];
                    $hour = rand(0, 23);
                    $minute = rand(0, 59);
                    $second = rand(0, 59);

                    Lead::create([
                        'name' => $lead['name'],
                        'email' => $lead['email'],
                        'subject' => $lead['subject'],
                        'user_id' => $assignedUserId,
                        'pipeline_id' => $pipeline->id,
                        'stage_id' => $stage->id,
                        'sources' => $selectedSourceId ?? null,
                        'products' => $selectedProductId ?? null,
                        'notes' => $notes[$leadIndex % count($notes)] ?? null,
                        'labels' => implode(',', $selectedLabelIds) ?? null,
                        'order' => 0,
                        'phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999),
                        'is_active' => true,
                        'date' => $lead['date']->format('Y-m-d'),
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $lead['created_at']->setTime($hour, $minute, $second)->format('Y-m-d H:i:s'),
                    ]);

                    $leadIndex++;
                }
            }
        }
    }
}
