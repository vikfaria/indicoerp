<?php

namespace Workdo\AIAssistant\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\AIAssistant\Models\AIPrompt;

class AIPromptSeeder extends Seeder
{
    public function run()
    {
        $prompts = [
            // general
            [
                'module'          => 'general',
                'submodule'       => null,
                'field_type'      => 'title',
                'prompt_template' => 'Generate a professional and engaging title for the given context: {context}',
                'description'     => 'Generic title generation for any module'
            ],
            [
                'module'          => 'general',
                'submodule'       => null,
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed and comprehensive description for: {context}. Include key features and benefits.',
                'description'     => 'Generic description generation for any module'
            ],
            [
                'module'          => 'general',
                'submodule'       => 'warehouses',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional warehouse name for a facility located in {city}. Make it descriptive and business-appropriate.',
                'description'     => 'Warehouse name generation'
            ],

            // CMMS
            [
                'module'          => 'cmms',
                'submodule'       => 'workorder',
                'field_type'      => 'workorder_name',
                'prompt_template' => 'Generate a professional work order name for maintenance tasks. Make it clear, specific and action-oriented.',
                'description'     => 'Work order name generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'workorder',
                'field_type'      => 'instructions',
                'prompt_template' => 'Write detailed maintenance instructions. Include step-by-step procedures, safety requirements, and expected outcomes.',
                'description'     => 'Work order instructions generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'workorder_logtime',
                'field_type'      => 'description',
                'prompt_template' => 'Create a detailed log time description for maintenance work. Include tasks completed and progress made.',
                'description'     => 'Work order log time description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'workorder_invoice',
                'field_type'      => 'description',
                'prompt_template' => 'Generate a detailed invoice description for maintenance work. Include services provided and materials used.',
                'description'     => 'Work order invoice description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional component name for industrial equipment. Make it descriptive and industry-standard.',
                'description'     => 'Component name generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component',
                'field_type'      => 'sku',
                'prompt_template' => 'Generate a unique SKU code for industrial components. Use alphanumeric format with letters and numbers.',
                'description'     => 'Component SKU generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component',
                'field_type'      => 'category',
                'prompt_template' => 'Generate an appropriate category name for industrial components. Make it industry-standard and descriptive.',
                'description'     => 'Component category generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component',
                'field_type'      => 'component_tag',
                'prompt_template' => 'Generate relevant tags for industrial components. Include maintenance type and equipment classification.',
                'description'     => 'Component tag generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive description for industrial components. Include specifications, maintenance requirements, and operational details.',
                'description'     => 'Component description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'component_logtime',
                'field_type'      => 'description',
                'prompt_template' => 'Create a detailed log time description for {hours} hours and {minutes} minutes of maintenance work on component. Include tasks performed and component status.',
                'description'     => 'Component log time description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'preventive_maintenance',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive preventive maintenance description. Include maintenance procedures, safety protocols, and expected outcomes.',
                'description'     => 'Preventive maintenance description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'preventive_maintenance_logtime',
                'field_type'      => 'description',
                'prompt_template' => 'Create a detailed log time description for {hours} hours and {minutes} minutes of preventive maintenance work. Include maintenance tasks performed and system status.',
                'description'     => 'Preventive maintenance log time description generation'
            ],
            [
                'module'          => 'cmms',
                'submodule'       => 'preventive_maintenance_invoice',
                'field_type'      => 'description',
                'prompt_template' => 'Generate a detailed invoice description for preventive maintenance work. Include maintenance services provided and materials used.',
                'description'     => 'Preventive maintenance invoice description generation'
            ],

            // Contract
            [
                'module'          => 'contract',
                'submodule'       => 'contract',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a professional contract subject line. Make it clear, specific and business-appropriate.',
                'description'     => 'Contract subject generation'
            ],

            // Lead
            [
                'module'          => 'lead',
                'submodule'       => 'lead',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional lead name for a potential customer or client. Make it realistic and business-appropriate.',
                'description'     => 'Lead name generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'lead',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a compelling lead subject line that captures interest and describes the business opportunity.',
                'description'     => 'Lead subject generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'lead',
                'field_type'      => 'notes',
                'prompt_template' => 'Write comprehensive lead notes including contact details, conversation summary, follow-up actions, and potential opportunities.',
                'description'     => 'Lead notes generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'lead_email',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a compelling email subject line for lead communication. Make it engaging and relevant to business opportunities.',
                'description'     => 'Lead email subject generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'lead_email',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional email content for lead communication. Include introduction, value proposition, and clear call-to-action.',
                'description'     => 'Lead email description generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'deal',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and compelling deal name that reflects the business opportunity and value proposition.',
                'description'     => 'Deal name generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'deal',
                'field_type'      => 'notes',
                'prompt_template' => 'Write comprehensive deal notes including opportunity details, client requirements, competitive analysis, timeline, and next steps.',
                'description'     => 'Deal notes generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'deal_email',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a compelling email subject line for deal communication. Make it engaging and relevant to the business opportunity.',
                'description'     => 'Deal email subject generation'
            ],
            [
                'module'          => 'lead',
                'submodule'       => 'deal_email',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional email content for deal communication. Include value proposition, deal benefits, and clear call-to-action.',
                'description'     => 'Deal email description generation'
            ],

            // ProductService
            [
                'module'          => 'productservice',
                'submodule'       => 'item',
                'field_type'      => 'description',
                'prompt_template' => 'Write a compelling short description for a product/service item. Make it concise, informative, and highlight key features and benefits.',
                'description'     => 'Item short description generation'
            ],

            // Recruitment
            [
                'module'          => 'recruitment',
                'submodule'       => 'job_posting',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a compelling and professional job title that accurately reflects the role and attracts qualified candidates.',
                'description'     => 'Job posting title generation'
            ],
            [
                'module'          => 'recruitment',
                'submodule'       => 'job_posting',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive job description that includes role overview, key responsibilities, and what makes this position attractive to candidates.',
                'description'     => 'Job posting description generation'
            ],
            [
                'module'          => 'recruitment',
                'submodule'       => 'job_posting',
                'field_type'      => 'requirements',
                'prompt_template' => 'Create detailed job requirements including education, experience, skills, and qualifications needed for the position.',
                'description'     => 'Job posting requirements generation'
            ],
            [
                'module'          => 'recruitment',
                'submodule'       => 'job_posting',
                'field_type'      => 'benefits',
                'prompt_template' => 'Write attractive employee benefits and perks that highlight what the company offers to employees in this role.',
                'description'     => 'Job posting benefits generation'
            ],
            [
                'module'          => 'recruitment',
                'submodule'       => 'job_posting',
                'field_type'      => 'terms_condition',
                'prompt_template' => 'Generate professional terms and conditions for job applications including employment terms, policies, and legal requirements.',
                'description'     => 'Job posting terms and conditions generation'
            ],
            [
                'module'          => 'recruitment',
                'submodule'       => 'offer_letter_template',
                'field_type'      => 'template',
                'prompt_template' => 'Generate a professional offer letter template including company introduction, position details, compensation, benefits, and terms. Use placeholders: {applicant_name}, {company_name}, {job_title}, {salary}, {start_date}, {workplace_location}, {days_of_week}, {salary_type}, {salary_duration}, {offer_expiration_date}.',
                'description'     => 'Offer letter template generation'
            ],

            // Sales
            [
                'module'          => 'sales',
                'submodule'       => 'account',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional account description including company overview, business activities, key services, and relevant business information.',
                'description'     => 'Account description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'call',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive sales call name that clearly indicates the purpose and context of the call.',
                'description'     => 'Sales call name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'call',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive sales call description including call objectives, key discussion points, expected outcomes, and follow-up actions.',
                'description'     => 'Sales call description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'case',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a clear and descriptive sales case name that summarizes the issue or request being addressed.',
                'description'     => 'Sales case name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'case',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed sales case description including the issue details, customer impact, resolution steps, and expected timeline.',
                'description'     => 'Sales case description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'contact',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional contact description including role, responsibilities, key contact information, and relationship details.',
                'description'     => 'Sales contact description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'document',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive document name that clearly indicates the document type and purpose.',
                'description'     => 'Sales document name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'document',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive document description including purpose, content overview, usage guidelines, and relevant details.',
                'description'     => 'Sales document description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'meeting',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive meeting name that clearly indicates the meeting purpose and context.',
                'description'     => 'Sales meeting name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'meeting',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive meeting description including objectives, agenda items, expected outcomes, and preparation requirements.',
                'description'     => 'Sales meeting description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'opportunity',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a compelling and professional opportunity name that reflects the business potential and value proposition.',
                'description'     => 'Sales opportunity name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'opportunity',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed opportunity description including business requirements, potential value, timeline, key stakeholders, and success criteria.',
                'description'     => 'Sales opportunity description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'quote',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive quote name that clearly identifies the proposal and its purpose.',
                'description'     => 'Sales quote name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'quote',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive quote description including proposal overview, deliverables, terms, and value proposition.',
                'description'     => 'Sales quote description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'quote_item',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed item description including product/service details, specifications, features, and relevant information for the quote line item.',
                'description'     => 'Sales quote item description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'invoice',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive invoice name that clearly identifies the billing document and its purpose.',
                'description'     => 'Sales invoice name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'invoice',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive invoice description including billing details, services provided, payment terms, and relevant information.',
                'description'     => 'Sales invoice description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'invoice_item',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed item description including product/service details, specifications, features, and relevant information for the invoice line item.',
                'description'     => 'Sales invoice item description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'order',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive sales order name that clearly identifies the order and its purpose.',
                'description'     => 'Sales order name generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'order',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive sales order description including order details, delivery requirements, terms, and relevant information.',
                'description'     => 'Sales order description generation'
            ],
            [
                'module'          => 'sales',
                'submodule'       => 'order_item',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed item description including product/service details, specifications, features, and relevant information for the sales order line item.',
                'description'     => 'Sales order item description generation'
            ],

            // Taskly
            [
                'module'          => 'taskly',
                'submodule'       => 'project',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a professional and descriptive project name that clearly identifies the project scope and objectives.',
                'description'     => 'Project name generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'project',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive project description including objectives, scope, deliverables, timeline, and key requirements.',
                'description'     => 'Project description generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'milestone',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and descriptive milestone title that identifies the key deliverable or achievement.',
                'description'     => 'Milestone title generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'milestone',
                'field_type'      => 'summary',
                'prompt_template' => 'Write a concise milestone summary including objectives, deliverables, success criteria, and key activities.',
                'description'     => 'Milestone summary generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'task',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and actionable task title that describes what needs to be accomplished.',
                'description'     => 'Task title generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'task',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed task description including objectives, requirements, acceptance criteria, and any relevant context or constraints.',
                'description'     => 'Task description generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'bug',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and descriptive bug title that summarizes the issue, including the affected component or feature.',
                'description'     => 'Bug title generation'
            ],
            [
                'module'          => 'taskly',
                'submodule'       => 'bug',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive bug description including steps to reproduce, expected behavior, actual behavior, environment details, and impact assessment.',
                'description'     => 'Bug description generation'
            ],

            // Training
            [
                'module'          => 'training',
                'submodule'       => 'training',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive training description including learning objectives, target audience, key topics covered, training methods, and expected outcomes.',
                'description'     => 'Training description generation'
            ],

            // Account
            [
                'module'          => 'account',
                'submodule'       => 'bank_transfer',
                'field_type'      => 'description',
                'prompt_template' => 'Write a clear and professional bank transfer description including the purpose of transfer, transaction details, and any relevant reference information.',
                'description'     => 'Bank transfer description generation'
            ],
            [
                'module'          => 'account',
                'submodule'       => 'revenue',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed revenue description including the source of income, transaction details, and any relevant business context or reference information.',
                'description'     => 'Revenue description generation'
            ],
            [
                'module'          => 'account',
                'submodule'       => 'expense',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed expense description including the purpose of expenditure, business justification, and any relevant transaction or reference details.',
                'description'     => 'Expense description generation'
            ],

            // HRM
            [
                'module'          => 'hrm',
                'submodule'       => 'leave_application',
                'field_type'      => 'reason',
                'prompt_template' => 'Write a professional and clear leave application reason explaining the purpose of leave, duration justification, and any relevant personal or professional circumstances.',
                'description'     => 'Leave application reason generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'award',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional and meaningful award description highlighting the employee\'s achievements, contributions, and the specific reasons for recognition.',
                'description'     => 'Award description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'resignation',
                'field_type'      => 'description',
                'prompt_template' => 'Write a professional resignation description including detailed reasons for leaving, transition plans, and any relevant circumstances or feedback.',
                'description'     => 'Resignation description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'event',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and engaging event title that captures the purpose and nature of the corporate or organizational event.',
                'description'     => 'Event title generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'event',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive event description including objectives, agenda highlights, target audience, expected outcomes, and any relevant logistics or requirements.',
                'description'     => 'Event description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'promotion',
                'field_type'      => 'reason',
                'prompt_template' => 'Write a professional promotion reason highlighting the employee\'s achievements, performance improvements, leadership qualities, and justification for career advancement.',
                'description'     => 'Promotion reason generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'complaint',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a clear and professional complaint subject line that summarizes the issue while maintaining workplace professionalism.',
                'description'     => 'Complaint subject generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'complaint',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed and professional complaint description including specific incidents, dates, witnesses, impact on work environment, and desired resolution.',
                'description'     => 'Complaint description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'warning',
                'field_type'      => 'subject',
                'prompt_template' => 'Generate a clear and professional warning subject line that summarizes the performance or conduct issue while maintaining workplace professionalism.',
                'description'     => 'Warning subject generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'warning',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed and professional warning description including specific incidents, policy violations, expected improvements, consequences, and support resources available.',
                'description'     => 'Warning description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'termination',
                'field_type'      => 'reason',
                'prompt_template' => 'Write a professional and clear termination reason including specific grounds for termination, policy violations, performance issues, or business circumstances.',
                'description'     => 'Termination reason generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'termination',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed termination description including timeline of events, documentation references, final performance review, transition arrangements, and legal compliance notes.',
                'description'     => 'Termination description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'announcement',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and engaging announcement title that captures attention and communicates the key message effectively to employees.',
                'description'     => 'Announcement title generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'announcement',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive announcement description including key details, action items, deadlines, contact information, and any relevant policies or procedures.',
                'description'     => 'Announcement description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'document',
                'field_type'      => 'title',
                'prompt_template' => 'Generate a clear and descriptive document title that accurately reflects the content and purpose of the HR document.',
                'description'     => 'HRM document title generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'document',
                'field_type'      => 'description',
                'prompt_template' => 'Write a detailed document description including purpose, target audience, key contents, usage guidelines, and any compliance or policy information.',
                'description'     => 'HRM document description generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'holiday',
                'field_type'      => 'name',
                'prompt_template' => 'Generate a clear and appropriate holiday name that accurately reflects the occasion, cultural significance, or celebration being observed.',
                'description'     => 'Holiday name generation'
            ],
            [
                'module'          => 'hrm',
                'submodule'       => 'holiday',
                'field_type'      => 'description',
                'prompt_template' => 'Write a comprehensive holiday description including cultural significance, traditions, observance details, employee benefits, and any relevant company policies.',
                'description'     => 'Holiday description generation'
            ]
        ];

        foreach ($prompts as $prompt) {
            AIPrompt::firstOrCreate(
                [
                    'module'     => $prompt['module'],
                    'submodule'  => $prompt['submodule'] ?? null,
                    'field_type' => $prompt['field_type']
                ],
                $prompt
            );
        }
    }
}
