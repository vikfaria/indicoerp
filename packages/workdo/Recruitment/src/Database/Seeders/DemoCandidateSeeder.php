<?php

namespace Workdo\Recruitment\Database\Seeders;

use App\Models\User;
use Workdo\Recruitment\Models\Candidate;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\CandidateSources;

class DemoCandidateSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Candidate::where('created_by', $userId)->exists()) {
            return;
        }

        $jobs = JobPosting::where('created_by', $userId)->where('is_published', 1)->where('status', 'active')->pluck('id')->toArray();
        $users = User::emp()->where('created_by', $userId)->pluck('id')->toArray();
        $candidateSources = CandidateSources::where('created_by', $userId)->where('is_active', 1)->pluck('id')->toArray();

        if (empty($jobs) || empty($users) || empty($candidateSources)) {
            return;
        }

        $countryCodes = ['+1', '+44', '+91', '+61', '+81', '+49', '+33', '+39', '+55', '+97', '+86', '+7', '+27', '+82', '+34'];

        $candidates = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@example.com', 'gender' => 'male', 'current_company' => 'Microsoft', 'current_position' => 'Senior Software Engineer', 'experience_years' => 8, 'current_salary' => 95000, 'expected_salary' => 110000, 'skills' => 'C#, .NET, Azure, SQL Server, JavaScript', 'education' => 'Bachelor of Computer Science'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah.johnson@example.com', 'gender' => 'female', 'current_company' => 'Google', 'current_position' => 'UX Designer', 'experience_years' => 6, 'current_salary' => 85000, 'expected_salary' => 95000, 'skills' => 'Figma, Adobe XD, User Research, Prototyping, HTML/CSS', 'education' => 'Bachelor of Design'],
            ['first_name' => 'Michael', 'last_name' => 'Brown', 'email' => 'michael.brown@example.com', 'gender' => 'male', 'current_company' => 'Amazon', 'current_position' => 'Data Scientist', 'experience_years' => 5, 'current_salary' => 105000, 'expected_salary' => 120000, 'skills' => 'Python, R, Machine Learning, SQL, Tableau', 'education' => 'Master of Data Science'],
            ['first_name' => 'Emily', 'last_name' => 'Davis', 'email' => 'emily.davis@example.com', 'gender' => 'female', 'current_company' => 'Facebook', 'current_position' => 'Product Manager', 'experience_years' => 7, 'current_salary' => 115000, 'expected_salary' => 130000, 'skills' => 'Product Strategy, Agile, Analytics, User Research, SQL', 'education' => 'Master of Business Administration'],
            ['first_name' => 'David', 'last_name' => 'Wilson', 'email' => 'david.wilson@example.com', 'gender' => 'male', 'current_company' => 'Apple', 'current_position' => 'iOS Developer', 'experience_years' => 4, 'current_salary' => 90000, 'expected_salary' => 105000, 'skills' => 'Swift, Objective-C, Xcode, iOS SDK, Git', 'education' => 'Bachelor of Computer Engineering'],
            ['first_name' => 'Jessica', 'last_name' => 'Miller', 'email' => 'jessica.miller@example.com', 'gender' => 'female', 'current_company' => 'Netflix', 'current_position' => 'DevOps Engineer', 'experience_years' => 6, 'current_salary' => 100000, 'expected_salary' => 115000, 'skills' => 'AWS, Docker, Kubernetes, Jenkins, Python', 'education' => 'Bachelor of Information Technology'],
            ['first_name' => 'Robert', 'last_name' => 'Garcia', 'email' => 'robert.garcia@example.com', 'gender' => 'male', 'current_company' => 'Tesla', 'current_position' => 'Full Stack Developer', 'experience_years' => 5, 'current_salary' => 88000, 'expected_salary' => 100000, 'skills' => 'React, Node.js, MongoDB, Express, JavaScript', 'education' => 'Bachelor of Software Engineering'],
            ['first_name' => 'Lisa', 'last_name' => 'Anderson', 'email' => 'lisa.anderson@example.com', 'gender' => 'female', 'current_company' => 'Salesforce', 'current_position' => 'Marketing Manager', 'experience_years' => 8, 'current_salary' => 75000, 'expected_salary' => 85000, 'skills' => 'Digital Marketing, SEO, Google Analytics, Content Strategy', 'education' => 'Bachelor of Marketing'],
            ['first_name' => 'James', 'last_name' => 'Taylor', 'email' => 'james.taylor@example.com', 'gender' => 'male', 'current_company' => 'IBM', 'current_position' => 'Business Analyst', 'experience_years' => 4, 'current_salary' => 70000, 'expected_salary' => 80000, 'skills' => 'Business Analysis, SQL, Excel, Process Improvement', 'education' => 'Bachelor of Business Administration'],
            ['first_name' => 'Amanda', 'last_name' => 'Thomas', 'email' => 'amanda.thomas@example.com', 'gender' => 'female', 'current_company' => 'Oracle', 'current_position' => 'Database Administrator', 'experience_years' => 7, 'current_salary' => 85000, 'expected_salary' => 95000, 'skills' => 'Oracle, MySQL, PostgreSQL, Database Design, Performance Tuning', 'education' => 'Bachelor of Computer Science'],
            ['first_name' => 'Christopher', 'last_name' => 'Jackson', 'email' => 'christopher.jackson@example.com', 'gender' => 'male', 'current_company' => 'Adobe', 'current_position' => 'Frontend Developer', 'experience_years' => 3, 'current_salary' => 72000, 'expected_salary' => 82000, 'skills' => 'React, Vue.js, HTML5, CSS3, JavaScript, TypeScript', 'education' => 'Bachelor of Web Development'],
            ['first_name' => 'Michelle', 'last_name' => 'White', 'email' => 'michelle.white@example.com', 'gender' => 'female', 'current_company' => 'Uber', 'current_position' => 'QA Engineer', 'experience_years' => 5, 'current_salary' => 68000, 'expected_salary' => 78000, 'skills' => 'Selenium, TestNG, Java, API Testing, Automation', 'education' => 'Bachelor of Computer Science'],
            ['first_name' => 'Daniel', 'last_name' => 'Harris', 'email' => 'daniel.harris@example.com', 'gender' => 'male', 'current_company' => 'Spotify', 'current_position' => 'Backend Developer', 'experience_years' => 6, 'current_salary' => 92000, 'expected_salary' => 105000, 'skills' => 'Java, Spring Boot, Microservices, REST APIs, Docker', 'education' => 'Master of Computer Science'],
            ['first_name' => 'Nicole', 'last_name' => 'Martin', 'email' => 'nicole.martin@example.com', 'gender' => 'female', 'current_company' => 'LinkedIn', 'current_position' => 'HR Business Partner', 'experience_years' => 9, 'current_salary' => 82000, 'expected_salary' => 92000, 'skills' => 'HR Strategy, Talent Management, Employee Relations, HRIS', 'education' => 'Master of Human Resources'],
            ['first_name' => 'Kevin', 'last_name' => 'Thompson', 'email' => 'kevin.thompson@example.com', 'gender' => 'male', 'current_company' => 'Airbnb', 'current_position' => 'Security Analyst', 'experience_years' => 4, 'current_salary' => 78000, 'expected_salary' => 88000, 'skills' => 'Cybersecurity, Penetration Testing, SIEM, Risk Assessment', 'education' => 'Bachelor of Cybersecurity'],
            ['first_name' => 'Rachel', 'last_name' => 'Lee', 'email' => 'rachel.lee@example.com', 'gender' => 'female', 'current_company' => 'Slack', 'current_position' => 'Project Manager', 'experience_years' => 7, 'current_salary' => 85000, 'expected_salary' => 95000, 'skills' => 'Agile, Scrum, JIRA, Project Planning, Team Leadership', 'education' => 'Bachelor of Project Management'],
            ['first_name' => 'Brian', 'last_name' => 'Clark', 'email' => 'brian.clark@example.com', 'gender' => 'male', 'current_company' => 'Zoom', 'current_position' => 'Mobile Developer', 'experience_years' => 5, 'current_salary' => 87000, 'expected_salary' => 98000, 'skills' => 'Android, Kotlin, Java, Flutter, React Native', 'education' => 'Bachelor of Mobile Development'],
            ['first_name' => 'Stephanie', 'last_name' => 'Rodriguez', 'email' => 'stephanie.rodriguez@example.com', 'gender' => 'female', 'current_company' => 'Dropbox', 'current_position' => 'Financial Analyst', 'experience_years' => 4, 'current_salary' => 65000, 'expected_salary' => 75000, 'skills' => 'Financial Modeling, Excel, SQL, Budgeting, Forecasting', 'education' => 'Bachelor of Finance'],
            ['first_name' => 'Andrew', 'last_name' => 'Lewis', 'email' => 'andrew.lewis@example.com', 'gender' => 'male', 'current_company' => 'Square', 'current_position' => 'Solutions Architect', 'experience_years' => 10, 'current_salary' => 125000, 'expected_salary' => 140000, 'skills' => 'System Architecture, Cloud Computing, AWS, Microservices', 'education' => 'Master of Software Architecture'],
            ['first_name' => 'Megan', 'last_name' => 'Walker', 'email' => 'megan.walker@example.com', 'gender' => 'female', 'current_company' => 'Pinterest', 'current_position' => 'Content Strategist', 'experience_years' => 6, 'current_salary' => 72000, 'expected_salary' => 82000, 'skills' => 'Content Marketing, SEO, Social Media, Analytics', 'education' => 'Bachelor of Communications'],
            ['first_name' => 'Thomas', 'last_name' => 'Martinez', 'email' => 'thomas.martinez@example.com', 'gender' => 'male', 'current_company' => 'Shopify', 'current_position' => 'E-commerce Manager', 'experience_years' => 5, 'current_salary' => 78000, 'expected_salary' => 88000, 'skills' => 'E-commerce, Shopify, Digital Marketing, Analytics, SEO', 'education' => 'Bachelor of Business'],
            ['first_name' => 'Patricia', 'last_name' => 'Young', 'email' => 'patricia.young@example.com', 'gender' => 'female', 'current_company' => 'Stripe', 'current_position' => 'Payment Systems Engineer', 'experience_years' => 7, 'current_salary' => 98000, 'expected_salary' => 110000, 'skills' => 'Payment APIs, Java, Spring, Microservices, Security', 'education' => 'Master of Computer Science'],
            ['first_name' => 'William', 'last_name' => 'Hall', 'email' => 'william.hall@example.com', 'gender' => 'male', 'current_company' => 'Twilio', 'current_position' => 'Cloud Engineer', 'experience_years' => 6, 'current_salary' => 95000, 'expected_salary' => 108000, 'skills' => 'AWS, Azure, Terraform, Python, CI/CD', 'education' => 'Bachelor of Cloud Computing'],
            ['first_name' => 'Jennifer', 'last_name' => 'Allen', 'email' => 'jennifer.allen@example.com', 'gender' => 'female', 'current_company' => 'HubSpot', 'current_position' => 'Sales Operations Manager', 'experience_years' => 8, 'current_salary' => 82000, 'expected_salary' => 92000, 'skills' => 'Salesforce, Sales Analytics, CRM, Process Optimization', 'education' => 'Bachelor of Business Administration'],
            ['first_name' => 'Matthew', 'last_name' => 'King', 'email' => 'matthew.king@example.com', 'gender' => 'male', 'current_company' => 'Atlassian', 'current_position' => 'Technical Writer', 'experience_years' => 4, 'current_salary' => 68000, 'expected_salary' => 78000, 'skills' => 'Technical Documentation, API Documentation, Markdown, Git', 'education' => 'Bachelor of English'],
            ['first_name' => 'Laura', 'last_name' => 'Wright', 'email' => 'laura.wright@example.com', 'gender' => 'female', 'current_company' => 'Zendesk', 'current_position' => 'Customer Success Manager', 'experience_years' => 5, 'current_salary' => 72000, 'expected_salary' => 82000, 'skills' => 'Customer Success, CRM, Communication, Problem Solving', 'education' => 'Bachelor of Communications'],
            ['first_name' => 'Joshua', 'last_name' => 'Lopez', 'email' => 'joshua.lopez@example.com', 'gender' => 'male', 'current_company' => 'Cisco', 'current_position' => 'Network Engineer', 'experience_years' => 9, 'current_salary' => 88000, 'expected_salary' => 98000, 'skills' => 'Networking, Cisco, TCP/IP, Firewall, VPN', 'education' => 'Bachelor of Network Engineering'],
            ['first_name' => 'Angela', 'last_name' => 'Hill', 'email' => 'angela.hill@example.com', 'gender' => 'female', 'current_company' => 'Workday', 'current_position' => 'HR Systems Analyst', 'experience_years' => 6, 'current_salary' => 75000, 'expected_salary' => 85000, 'skills' => 'HRIS, Workday, Data Analysis, Process Improvement', 'education' => 'Bachelor of Human Resources'],
            ['first_name' => 'Ryan', 'last_name' => 'Scott', 'email' => 'ryan.scott@example.com', 'gender' => 'male', 'current_company' => 'VMware', 'current_position' => 'Virtualization Engineer', 'experience_years' => 7, 'current_salary' => 92000, 'expected_salary' => 105000, 'skills' => 'VMware, Virtualization, Linux, Windows Server, Automation', 'education' => 'Bachelor of Information Technology'],
            ['first_name' => 'Samantha', 'last_name' => 'Green', 'email' => 'samantha.green@example.com', 'gender' => 'female', 'current_company' => 'ServiceNow', 'current_position' => 'IT Service Manager', 'experience_years' => 8, 'current_salary' => 85000, 'expected_salary' => 95000, 'skills' => 'ITIL, ServiceNow, IT Operations, Incident Management', 'education' => 'Master of IT Management'],
            ['first_name' => 'Brandon', 'last_name' => 'Mitchell', 'email' => 'brandon.mitchell@example.com', 'gender' => 'male', 'current_company' => 'Snap Inc', 'current_position' => 'AR Developer', 'experience_years' => 3, 'current_salary' => 82000, 'expected_salary' => 92000, 'skills' => 'Unity, C#, ARKit, ARCore, 3D Modeling', 'education' => 'Bachelor of Game Development'],
            ['first_name' => 'Olivia', 'last_name' => 'Carter', 'email' => 'olivia.carter@example.com', 'gender' => 'female', 'current_company' => 'Lyft', 'current_position' => 'Operations Manager', 'experience_years' => 6, 'current_salary' => 78000, 'expected_salary' => 88000, 'skills' => 'Operations Management, Logistics, Data Analysis, Excel', 'education' => 'Bachelor of Operations Management'],
            ['first_name' => 'Nathan', 'last_name' => 'Adams', 'email' => 'nathan.adams@example.com', 'gender' => 'male', 'current_company' => 'Reddit', 'current_position' => 'Community Manager', 'experience_years' => 4, 'current_salary' => 62000, 'expected_salary' => 72000, 'skills' => 'Community Management, Social Media, Content Moderation', 'education' => 'Bachelor of Communications'],
            ['first_name' => 'Victoria', 'last_name' => 'Baker', 'email' => 'victoria.baker@example.com', 'gender' => 'female', 'current_company' => 'Etsy', 'current_position' => 'Marketplace Analyst', 'experience_years' => 5, 'current_salary' => 70000, 'expected_salary' => 80000, 'skills' => 'Market Research, SQL, Tableau, E-commerce Analytics', 'education' => 'Bachelor of Economics'],
            ['first_name' => 'Gregory', 'last_name' => 'Nelson', 'email' => 'gregory.nelson@example.com', 'gender' => 'male', 'current_company' => 'PayPal', 'current_position' => 'Fraud Analyst', 'experience_years' => 6, 'current_salary' => 75000, 'expected_salary' => 85000, 'skills' => 'Fraud Detection, Risk Analysis, SQL, Machine Learning', 'education' => 'Bachelor of Finance'],
            ['first_name' => 'Sophia', 'last_name' => 'Campbell', 'email' => 'sophia.campbell@example.com', 'gender' => 'female', 'current_company' => 'Squarespace', 'current_position' => 'Web Designer', 'experience_years' => 4, 'current_salary' => 68000, 'expected_salary' => 78000, 'skills' => 'Web Design, HTML, CSS, JavaScript, Figma, Photoshop', 'education' => 'Bachelor of Web Design'],
            ['first_name' => 'Tyler', 'last_name' => 'Parker', 'email' => 'tyler.parker@example.com', 'gender' => 'male', 'current_company' => 'Coinbase', 'current_position' => 'Blockchain Developer', 'experience_years' => 5, 'current_salary' => 110000, 'expected_salary' => 125000, 'skills' => 'Solidity, Ethereum, Web3.js, Smart Contracts, Node.js', 'education' => 'Master of Computer Science'],
            ['first_name' => 'Hannah', 'last_name' => 'Evans', 'email' => 'hannah.evans@example.com', 'gender' => 'female', 'current_company' => 'DoorDash', 'current_position' => 'Logistics Coordinator', 'experience_years' => 3, 'current_salary' => 58000, 'expected_salary' => 68000, 'skills' => 'Logistics, Supply Chain, Route Optimization, Excel', 'education' => 'Bachelor of Supply Chain Management'],
            ['first_name' => 'Aaron', 'last_name' => 'Collins', 'email' => 'aaron.collins@example.com', 'gender' => 'male', 'current_company' => 'Grubhub', 'current_position' => 'Restaurant Partner Manager', 'experience_years' => 4, 'current_salary' => 65000, 'expected_salary' => 75000, 'skills' => 'Account Management, Sales, CRM, Negotiation', 'education' => 'Bachelor of Business'],
            ['first_name' => 'Natalie', 'last_name' => 'Stewart', 'email' => 'natalie.stewart@example.com', 'gender' => 'female', 'current_company' => 'Instacart', 'current_position' => 'Product Analyst', 'experience_years' => 5, 'current_salary' => 80000, 'expected_salary' => 90000, 'skills' => 'Product Analytics, SQL, Python, A/B Testing, Tableau', 'education' => 'Bachelor of Data Analytics']
        ];

        $statuses = ['0', '1', '2', '3', '4', '5'];
        $statusIndex = 0;

        foreach ($candidates as $candidate) {
            Candidate::create(array_merge($candidate, [
                'phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999),
                'dob' => now()->subYears(rand(25, 45))->subDays(rand(1, 365))->format('Y-m-d'),
                'country' => 'United States',
                'state' => ['California', 'New York', 'Texas', 'Florida', 'Washington'][array_rand(['California', 'New York', 'Texas', 'Florida', 'Washington'])],
                'city' => ['San Francisco', 'New York', 'Austin', 'Miami', 'Seattle'][array_rand(['San Francisco', 'New York', 'Austin', 'Miami', 'Seattle'])],
                'notice_period' => ['Immediate', '2 weeks', '1 month', '2 months'][array_rand(['Immediate', '2 weeks', '1 month', '2 months'])],
                'portfolio_url' => 'https://portfolio.' . strtolower($candidate['first_name']) . strtolower($candidate['last_name']) . '.com',
                'linkedin_url' => 'https://linkedin.com/in/' . strtolower($candidate['first_name']) . '-' . strtolower($candidate['last_name']),
                'profile_path' => null,
                'resume_path' => null,
                'cover_letter_path' => null,
                'status' => $statuses[$statusIndex++ % count($statuses)],
                'application_date' => now()->subDays(rand(1, 90))->format('Y-m-d'),
                'custom_question' => null,
                'tracking_id' => Candidate::generateTrackingId($userId),
                'job_id' => fake()->randomElement($jobs),
                'user_id' => fake()->randomElement($users),
                'source_id' => fake()->randomElement($candidateSources),
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
