<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\EmployeeDocumentType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoEmployeeDocumentTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (EmployeeDocumentType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $demoDocuments = [
                [
                    'document_name' => 'National Identity Card',
                    'description' => 'Government-issued national identity card for official identification and verification of citizenship status.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                ],
                [
                    'document_name' => 'Passport',
                    'description' => 'Official government-issued passport document for international travel and primary identification purposes.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                ],
                [
                    'document_name' => 'Birth Certificate',
                    'description' => 'Official birth certificate issued by government authorities to verify date and place of birth.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                ],
                [
                    'document_name' => 'Social Security Card',
                    'description' => 'Social security card or equivalent document for tax identification and government benefit purposes.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                ],
                [
                    'document_name' => 'Educational Degree Certificate',
                    'description' => 'Official degree certificate from accredited educational institution verifying highest qualification achieved.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                ],
                [
                    'document_name' => 'Professional Resume',
                    'description' => 'Comprehensive resume detailing work experience, education, skills, and professional achievements.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                ],
                [
                    'document_name' => 'Bank Account Statement',
                    'description' => 'Recent bank account statement or bank verification letter for salary deposit and financial verification.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                ],
                [
                    'document_name' => 'Professional Photograph',
                    'description' => 'Recent passport-sized professional photograph for employee identification card and official records.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                ],
                [
                    'document_name' => 'Medical Fitness Certificate',
                    'description' => 'Medical certificate from licensed physician confirming physical and mental fitness for employment.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                ],
                [
                    'document_name' => 'Employment Authorization Document',
                    'description' => 'Legal work authorization document or visa permitting employment in the country of operation.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                ],
                [
                    'document_name' => 'Tax Identification Number',
                    'description' => 'Official tax identification number or taxpayer registration certificate for payroll processing.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                ],
                [
                    'document_name' => 'Emergency Contact Information',
                    'description' => 'Detailed emergency contact information including names, relationships, and contact numbers of family members.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                ],
                [
                    'document_name' => 'Previous Employment Letter',
                    'description' => 'Employment verification letter from previous employer detailing job role, duration, and performance.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                ],
                [
                    'document_name' => 'Professional License Certificate',
                    'description' => 'Professional license or certification required for specific job roles and industry compliance.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                ],
                [
                    'document_name' => 'Character Reference Letters',
                    'description' => 'Character reference letters from previous employers, colleagues, or professional contacts.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                ],
                [
                    'document_name' => 'Academic Transcripts',
                    'description' => 'Official academic transcripts from educational institutions showing grades and course completion.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                ],
                [
                    'document_name' => 'Driving License',
                    'description' => 'Valid driving license for positions requiring vehicle operation or transportation responsibilities.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                ],
                [
                    'document_name' => 'Insurance Documentation',
                    'description' => 'Health insurance documentation or proof of coverage for employee benefit enrollment.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                ],
                [
                    'document_name' => 'Background Check Authorization',
                    'description' => 'Signed authorization form permitting comprehensive background check and criminal record verification.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                    'updated_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                ],
                [
                    'document_name' => 'Non-Disclosure Agreement',
                    'description' => 'Signed non-disclosure agreement protecting company confidential information and trade secrets.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                ],
                [
                    'document_name' => 'Employment Contract',
                    'description' => 'Signed employment contract outlining terms, conditions, salary, benefits, and job responsibilities.',
                    'is_required' => true,
                    'created_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                    'updated_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                ],
                [
                    'document_name' => 'Drug Test Results',
                    'description' => 'Pre-employment drug screening test results from certified medical laboratory or testing facility.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                    'updated_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                ],
                [
                    'document_name' => 'Salary Expectation Form',
                    'description' => 'Completed salary expectation and compensation requirement form for payroll setup and negotiation.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                ],
                [
                    'document_name' => 'Training Certificates',
                    'description' => 'Professional training certificates and skill development course completion documents.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                    'updated_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                ],
                [
                    'document_name' => 'Portfolio Documentation',
                    'description' => 'Professional portfolio showcasing previous work samples, projects, and creative achievements.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                ],
                [
                    'document_name' => 'Language Proficiency Certificate',
                    'description' => 'Language proficiency test results or certificates for multilingual positions and international roles.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                ],
                [
                    'document_name' => 'Security Clearance Documentation',
                    'description' => 'Security clearance certificates and background verification for sensitive positions and government contracts.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                ],
                [
                    'document_name' => 'Pension Fund Documentation',
                    'description' => 'Pension fund enrollment forms and retirement benefit documentation for long-term employee benefits.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                ],
                [
                    'document_name' => 'Union Membership Certificate',
                    'description' => 'Labor union membership certificate and documentation for unionized positions and collective bargaining.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                ],
                [
                    'document_name' => 'Performance Evaluation Records',
                    'description' => 'Previous performance evaluation records and appraisal documents from former employers.',
                    'is_required' => false,
                    'created_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                ],
            ];

            foreach ($demoDocuments as $doc) {
                EmployeeDocumentType::updateOrCreate(
                    [
                        'document_name' => $doc['document_name'],
                        'created_by' => $userId
                    ],
                    [
                        'description' => $doc['description'],
                        'is_required' => $doc['is_required'],
                        'creator_id' => $userId,
                        'created_at' => $doc['created_at'],
                        'updated_at' => $doc['updated_at'],
                    ]
                );
            }
        }
    }
}
