<?php

namespace Workdo\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Training\Models\Trainer;
use Carbon\Carbon;

class TrainerDemoSeeder extends Seeder
{
    public function run($userId)
    {
        if (Trainer::where('created_by', $userId)->exists()) {
            return;
        }
        $countryCodes = ['+1', '+44', '+91', '+61', '+81', '+49', '+33', '+39', '+55', '+97', '+86', '+7', '+27', '+82', '+34'];
        
        $trainerData = [
            [
                'name' => 'Dr. Sarah Mitchell',
                'email' => 'sarah.mitchell@techcorp.com',
                'experience' => '12 years',
                'expertise' => 'Technical Skills Development, Software Architecture, Cloud Computing',
                'qualification' => 'PhD in Computer Science, AWS Certified Solutions Architect, Microsoft Azure Expert',
                'branch_id' => 1,
            ],
            [
                'name' => 'Michael Thompson',
                'email' => 'michael.thompson@leadpro.com',
                'experience' => '15 years',
                'expertise' => 'Leadership & Management Excellence, Team Building, Strategic Planning',
                'qualification' => 'MBA in Leadership, Certified Executive Coach, PMP Certification',
                'branch_id' => 2,
            ],
            [
                'name' => 'Jennifer Rodriguez',
                'email' => 'jennifer.rodriguez@digitalmax.com',
                'experience' => '8 years',
                'expertise' => 'Digital Marketing & Analytics, SEO, Social Media Strategy',
                'qualification' => 'Master in Digital Marketing, Google Analytics Certified, Facebook Blueprint Certified',
                'branch_id' => 3,
            ],
            [
                'name' => 'David Chen',
                'email' => 'david.chen@serviceexcellence.com',
                'experience' => '10 years',
                'expertise' => 'Customer Service Excellence, Communication Skills, Conflict Resolution',
                'qualification' => 'Bachelor in Business Administration, Certified Customer Experience Professional',
                'branch_id' => 4,
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@projectpro.com',
                'experience' => '14 years',
                'expertise' => 'Project Management Professional, Agile Methodology, Risk Management',
                'qualification' => 'PMP Certified, Scrum Master Certified, PRINCE2 Practitioner',
                'branch_id' => 5,
            ],
            [
                'name' => 'Robert Williams',
                'email' => 'robert.williams@salesforce.com',
                'experience' => '11 years',
                'expertise' => 'Sales & Business Development, Negotiation Skills, CRM Management',
                'qualification' => 'MBA in Sales Management, Salesforce Certified Administrator',
                'branch_id' => 1,
            ],
            [
                'name' => 'Amanda Davis',
                'email' => 'amanda.davis@qualityassurance.com',
                'experience' => '9 years',
                'expertise' => 'Quality Assurance & Testing, Process Improvement, ISO Standards',
                'qualification' => 'Master in Quality Management, Six Sigma Black Belt, ISO 9001 Lead Auditor',
                'branch_id' => 2,
            ],
            [
                'name' => 'James Wilson',
                'email' => 'james.wilson@financepro.com',
                'experience' => '13 years',
                'expertise' => 'Financial Management & Analysis, Budget Planning, Investment Strategy',
                'qualification' => 'CPA Certified, MBA in Finance, Chartered Financial Analyst',
                'branch_id' => 3,
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria.garcia@hrexcellence.com',
                'experience' => '7 years',
                'expertise' => 'Human Resources Development, Talent Management, Employee Engagement',
                'qualification' => 'Master in Human Resources, SHRM Certified Professional, PHR Certified',
                'branch_id' => 4,
            ],
            [
                'name' => 'Kevin Brown',
                'email' => 'kevin.brown@commskills.com',
                'experience' => '6 years',
                'expertise' => 'Communication & Presentation Skills, Public Speaking, Training Delivery',
                'qualification' => 'Bachelor in Communications, Certified Professional Speaker, Train-the-Trainer Certified',
                'branch_id' => 5,
            ],
            [
                'name' => 'Dr. Rachel Kim',
                'email' => 'rachel.kim@datascience.com',
                'experience' => '10 years',
                'expertise' => 'Data Science & Analytics, Machine Learning, Business Intelligence',
                'qualification' => 'PhD in Data Science, Certified Analytics Professional, Tableau Expert',
                'branch_id' => 1,
            ],
            [
                'name' => 'Thomas Johnson',
                'email' => 'thomas.johnson@cybersecurity.com',
                'experience' => '12 years',
                'expertise' => 'Cybersecurity & Information Security, Risk Assessment, Compliance',
                'qualification' => 'Master in Cybersecurity, CISSP Certified, CISM Certified',
                'branch_id' => 2,
            ],
            [
                'name' => 'Nicole Martinez',
                'email' => 'nicole.martinez@operations.com',
                'experience' => '9 years',
                'expertise' => 'Supply Chain & Operations Management, Process Optimization, Lean Six Sigma',
                'qualification' => 'MBA in Operations, Lean Six Sigma Master Black Belt, APICS Certified',
                'branch_id' => 3,
            ],
            [
                'name' => 'Christopher Lee',
                'email' => 'christopher.lee@innovation.com',
                'experience' => '8 years',
                'expertise' => 'Innovation & Creative Thinking, Design Thinking, Product Development',
                'qualification' => 'Master in Innovation Management, Design Thinking Certified, IDEO Certified',
                'branch_id' => 4,
            ],
            [
                'name' => 'Stephanie Taylor',
                'email' => 'stephanie.taylor@compliance.com',
                'experience' => '11 years',
                'expertise' => 'Compliance & Regulatory Training, Legal Standards, Risk Management',
                'qualification' => 'JD Law Degree, Certified Compliance Professional, Risk Management Certified',
                'branch_id' => 5,
            ],
            [
                'name' => 'Daniel White',
                'email' => 'daniel.white@safety.com',
                'experience' => '14 years',
                'expertise' => 'Health & Safety Management, Emergency Response, Workplace Safety',
                'qualification' => 'Master in Occupational Safety, OSHA Certified Trainer, First Aid Instructor',
                'branch_id' => 1,
            ],
            [
                'name' => 'Ashley Harris',
                'email' => 'ashley.harris@strategy.com',
                'experience' => '13 years',
                'expertise' => 'Business Strategy & Planning, Market Analysis, Strategic Implementation',
                'qualification' => 'MBA in Strategic Management, Certified Management Consultant, Strategy Certified',
                'branch_id' => 2,
            ],
            [
                'name' => 'Matthew Clark',
                'email' => 'matthew.clark@cloudtech.com',
                'experience' => '7 years',
                'expertise' => 'Cloud Computing & DevOps, AWS, Azure, Containerization',
                'qualification' => 'AWS Solutions Architect, Azure DevOps Expert, Kubernetes Certified',
                'branch_id' => 3,
            ],
            [
                'name' => 'Jessica Lewis',
                'email' => 'jessica.lewis@productmanagement.com',
                'experience' => '9 years',
                'expertise' => 'Product Management & Development, User Experience, Agile Product Development',
                'qualification' => 'Master in Product Management, Certified Product Manager, UX Design Certified',
                'branch_id' => 4,
            ],
            [
                'name' => 'Ryan Walker',
                'email' => 'ryan.walker@emotionaliq.com',
                'experience' => '6 years',
                'expertise' => 'Emotional Intelligence & Soft Skills, Team Dynamics, Interpersonal Skills',
                'qualification' => 'Master in Psychology, EQ-i 2.0 Certified, Emotional Intelligence Coach',
                'branch_id' => 5,
            ],
            [
                'name' => 'Lauren Hall',
                'email' => 'lauren.hall@agilecoach.com',
                'experience' => '10 years',
                'expertise' => 'Agile & Scrum Methodology, Team Coaching, Agile Transformation',
                'qualification' => 'Certified Scrum Master, Agile Coach Certified, SAFe Program Consultant',
                'branch_id' => 1,
            ],
            [
                'name' => 'Brian Allen',
                'email' => 'brian.allen@businessintelligence.com',
                'experience' => '11 years',
                'expertise' => 'Business Intelligence & Reporting, Data Visualization, Analytics Strategy',
                'qualification' => 'Master in Business Analytics, Tableau Expert, Power BI Certified',
                'branch_id' => 2,
            ],
            [
                'name' => 'Samantha Young',
                'email' => 'samantha.young@mobiledev.com',
                'experience' => '8 years',
                'expertise' => 'Mobile App Development, iOS, Android, Cross-platform Development',
                'qualification' => 'Bachelor in Mobile Development, iOS Developer Certified, Android Developer Expert',
                'branch_id' => 3,
            ],
            [
                'name' => 'Gregory King',
                'email' => 'gregory.king@ecommerce.com',
                'experience' => '12 years',
                'expertise' => 'E-commerce & Online Business, Digital Commerce, Online Marketing',
                'qualification' => 'MBA in E-commerce, Shopify Expert, Google Ads Certified',
                'branch_id' => 4,
            ],
            [
                'name' => 'Megan Scott',
                'email' => 'megan.scott@productivity.com',
                'experience' => '5 years',
                'expertise' => 'Time Management & Productivity, Workflow Optimization, Personal Effectiveness',
                'qualification' => 'Certified Productivity Coach, Getting Things Done Certified, Time Management Expert',
                'branch_id' => 5,
            ],
            [
                'name' => 'Jonathan Adams',
                'email' => 'jonathan.adams@negotiation.com',
                'experience' => '14 years',
                'expertise' => 'Negotiation & Conflict Resolution, Mediation, Business Negotiation',
                'qualification' => 'Master in Conflict Resolution, Certified Mediator, Harvard Negotiation Certified',
                'branch_id' => 1,
            ],
            [
                'name' => 'Kimberly Baker',
                'email' => 'kimberly.baker@sustainability.com',
                'experience' => '9 years',
                'expertise' => 'Environmental Sustainability, Green Technology, Corporate Social Responsibility',
                'qualification' => 'Master in Environmental Science, LEED Certified, Sustainability Professional',
                'branch_id' => 2,
            ],
            [
                'name' => 'Steven Green',
                'email' => 'steven.green@internationalbusiness.com',
                'experience' => '13 years',
                'expertise' => 'International Business & Trade, Global Operations, Cross-cultural Communication',
                'qualification' => 'MBA in International Business, Export-Import Certified, Cultural Intelligence Certified',
                'branch_id' => 3,
            ],
            [
                'name' => 'Michelle Nelson',
                'email' => 'michelle.nelson@riskmanagement.com',
                'experience' => '11 years',
                'expertise' => 'Risk Management & Assessment, Business Continuity, Crisis Management',
                'qualification' => 'Master in Risk Management, Certified Risk Manager, Business Continuity Certified',
                'branch_id' => 4,
            ],
            [
                'name' => 'Dr. Andrew Carter',
                'email' => 'andrew.carter@artificialintelligence.com',
                'experience' => '10 years',
                'expertise' => 'Artificial Intelligence & Machine Learning, Deep Learning, AI Implementation',
                'qualification' => 'PhD in Artificial Intelligence, Machine Learning Engineer Certified, TensorFlow Expert',
                'branch_id' => 5,
            ],
        ];
        
        foreach ($trainerData as $index => $data) {
            $countryCode = $countryCodes[array_rand($countryCodes)];
            $branchId = $data['branch_id'];
            
            if (Module_is_active('Hrm')) {
                // Get branches created by this user
                $userBranches = \Workdo\Hrm\Models\Branch::where('created_by', $userId)->pluck('id')->toArray();
                
                if (!empty($userBranches)) {
                    // Use actual branch ID from user's branches
                    $actualBranchId = $userBranches[($branchId - 1) % count($userBranches)];
                    
                    // Get departments for this branch
                    $branchDepartments = \Workdo\Hrm\Models\Department::where('branch_id', $actualBranchId)
                        ->where('created_by', $userId)
                        ->pluck('id')->toArray();
                    
                    $departmentId = !empty($branchDepartments) ? $branchDepartments[array_rand($branchDepartments)] : null;
                    $branchId = $actualBranchId;
                } else {
                    // No branches found, skip this trainer
                    continue;
                }
            } else {
                $departmentId = 1;
            }
            
            if ($branchId && $departmentId) {
                Trainer::create([
                    'name' => $data['name'],
                    'contact' => $countryCode . mt_rand(1000000000, 9999999999),
                    'email' => $data['email'],
                    'experience' => $data['experience'],
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'expertise' => $data['expertise'],
                    'qualification' => $data['qualification'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => Carbon::now()->subDays(180 - ($index * 6))->subHours(rand(1, 23))->subMinutes(rand(1, 59)),
                    'updated_at' => Carbon::now()->subDays(180 - ($index * 6))->subHours(rand(1, 23))->subMinutes(rand(1, 59)),
                ]);
            }
        }
    }
}