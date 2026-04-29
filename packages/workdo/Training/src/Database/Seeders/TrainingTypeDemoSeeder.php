<?php

namespace Workdo\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Training\Models\TrainingType;
use Carbon\Carbon;
use Workdo\Hrm\Models\Department;

class TrainingTypeDemoSeeder extends Seeder
{
    public function run($userId)
    {        
        if (TrainingType::where('created_by', $userId)->exists()) {
            return;
        }       
        
        $trainingTypeData = [
            [
                'name' => 'Technical Skills Development',
                'description' => 'Comprehensive technical training programs designed to enhance programming, software development, and IT infrastructure skills. Covers modern technologies, frameworks, and best practices for technical excellence.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Leadership & Management Excellence',
                'description' => 'Strategic leadership development program focusing on team management, decision-making, and organizational leadership. Builds essential skills for effective people management and strategic thinking.',
                'branch_id' => 2,
            ],
            [
                'name' => 'Digital Marketing & Analytics',
                'description' => 'Modern digital marketing strategies including social media marketing, SEO, content marketing, and data analytics. Designed to boost online presence and marketing effectiveness.',
                'branch_id' => 3,
            ],
            [
                'name' => 'Customer Service Excellence',
                'description' => 'Customer-centric service training focusing on communication skills, problem resolution, and customer satisfaction. Enhances customer relationship management and service quality standards.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Project Management Professional',
                'description' => 'Comprehensive project management training covering methodologies, tools, and techniques. Includes Agile, Scrum, and traditional project management approaches for successful project delivery.',
                'branch_id' => 5,
            ],
            [
                'name' => 'Sales & Business Development',
                'description' => 'Advanced sales techniques, lead generation, and business development strategies. Focuses on relationship building, negotiation skills, and revenue growth optimization.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Quality Assurance & Testing',
                'description' => 'Quality assurance methodologies, testing frameworks, and quality control processes. Ensures product excellence and maintains high standards across all deliverables.',
                'branch_id' => 2,
            ],
            [
                'name' => 'Financial Management & Analysis',
                'description' => 'Financial planning, budgeting, and analysis training for better financial decision-making. Covers financial reporting, cost management, and investment analysis techniques.',
                'branch_id' => 3,
            ],
            [
                'name' => 'Human Resources Development',
                'description' => 'HR best practices including recruitment, employee engagement, performance management, and organizational development. Builds effective people management capabilities.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Communication & Presentation Skills',
                'description' => 'Professional communication training covering verbal, written, and presentation skills. Enhances interpersonal communication and public speaking abilities for workplace success.',
                'branch_id' => 5,
            ],
            [
                'name' => 'Data Science & Analytics',
                'description' => 'Data analysis, visualization, and machine learning fundamentals. Covers statistical analysis, data mining, and business intelligence tools for data-driven decision making.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Cybersecurity & Information Security',
                'description' => 'Comprehensive cybersecurity training covering threat assessment, security protocols, and data protection. Essential for maintaining organizational security and compliance.',
                'branch_id' => 2,
            ],
            [
                'name' => 'Supply Chain & Operations Management',
                'description' => 'Operations optimization, supply chain management, and process improvement methodologies. Focuses on efficiency, cost reduction, and operational excellence.',
                'branch_id' => 3,
            ],
            [
                'name' => 'Innovation & Creative Thinking',
                'description' => 'Creative problem-solving techniques, innovation methodologies, and design thinking approaches. Encourages innovative solutions and creative workplace culture.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Compliance & Regulatory Training',
                'description' => 'Industry compliance requirements, regulatory standards, and legal obligations. Ensures organizational adherence to laws, regulations, and industry standards.',
                'branch_id' => 5,
            ],
            [
                'name' => 'Health & Safety Management',
                'description' => 'Workplace safety protocols, health management systems, and emergency response procedures. Critical for maintaining safe working environments and employee wellbeing.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Business Strategy & Planning',
                'description' => 'Strategic planning methodologies, market analysis, and business development strategies. Develops strategic thinking and long-term planning capabilities.',
                'branch_id' => 2,
            ],
            [
                'name' => 'Cloud Computing & DevOps',
                'description' => 'Cloud infrastructure, DevOps practices, and modern deployment strategies. Covers AWS, Azure, containerization, and continuous integration/deployment methodologies.',
                'branch_id' => 3,
            ],
            [
                'name' => 'Product Management & Development',
                'description' => 'Product lifecycle management, user experience design, and product strategy development. Essential for successful product planning and market positioning.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Emotional Intelligence & Soft Skills',
                'description' => 'Emotional intelligence development, interpersonal skills, and workplace relationship management. Builds essential soft skills for professional success and team collaboration.',
                'branch_id' => 5,
            ],
            [
                'name' => 'Agile & Scrum Methodology',
                'description' => 'Agile project management, Scrum framework implementation, and iterative development processes. Essential for modern project delivery and team collaboration.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Business Intelligence & Reporting',
                'description' => 'Business intelligence tools, data visualization, and reporting systems. Enables data-driven insights and informed business decision-making processes.',
                'branch_id' => 2,
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Mobile application development for iOS and Android platforms. Covers native and cross-platform development frameworks, UI/UX design, and mobile optimization techniques.',
                'branch_id' => 3,
            ],
            [
                'name' => 'E-commerce & Online Business',
                'description' => 'E-commerce platform management, online marketing strategies, and digital business operations. Focuses on online sales optimization and digital commerce success.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Time Management & Productivity',
                'description' => 'Personal productivity techniques, time management strategies, and workflow optimization. Enhances individual and team efficiency for better work-life balance.',
                'branch_id' => 5,
            ],
            [
                'name' => 'Negotiation & Conflict Resolution',
                'description' => 'Professional negotiation techniques, conflict resolution strategies, and mediation skills. Essential for business dealings and workplace harmony management.',
                'branch_id' => 1,
            ],
            [
                'name' => 'Environmental Sustainability',
                'description' => 'Sustainable business practices, environmental compliance, and green technology implementation. Promotes eco-friendly operations and corporate social responsibility.',
                'branch_id' => 2,
            ],
            [
                'name' => 'International Business & Trade',
                'description' => 'Global business operations, international trade regulations, and cross-cultural communication. Essential for companies operating in international markets.',
                'branch_id' => 3,
            ],
            [
                'name' => 'Risk Management & Assessment',
                'description' => 'Risk identification, assessment methodologies, and mitigation strategies. Critical for organizational resilience and business continuity planning.',
                'branch_id' => 4,
            ],
            [
                'name' => 'Artificial Intelligence & Machine Learning',
                'description' => 'AI fundamentals, machine learning algorithms, and practical implementation strategies. Prepares teams for AI integration and intelligent automation solutions.',
                'branch_id' => 5,
            ],
        ];
        
        foreach ($trainingTypeData as $index => $data) {
            $branchId = $data['branch_id'];
            $branchDepartments = Department::where('branch_id', $branchId)->pluck('id')->toArray();
            $departmentId = !empty($branchDepartments) ? $branchDepartments[array_rand($branchDepartments)] : 1;
            if($branchId && $departmentId){
            TrainingType::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'creator_id' => 2,
                'created_by' => 2,
                'created_at' => Carbon::now()->subDays(180 - ($index * 6))->subHours(rand(1, 23))->subMinutes(rand(1, 59)),
                'updated_at' => Carbon::now()->subDays(180 - ($index * 6))->subHours(rand(1, 23))->subMinutes(rand(1, 59)),
            ]);
        }
        }
    }
}