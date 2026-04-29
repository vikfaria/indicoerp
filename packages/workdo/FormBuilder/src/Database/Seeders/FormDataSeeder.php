<?php

namespace Workdo\FormBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Models\FormField;
use Workdo\FormBuilder\Models\FormResponse;
use Carbon\Carbon;

class FormDataSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Form::where('created_by', $userId)->exists()) {
            return;
        }

        $countryCodes = ['+1', '+44', '+91', '+61', '+81', '+49', '+33', '+39', '+55', '+97', '+86', '+7', '+27', '+82', '+34'];

        $forms = [
            [
                'name' => 'Customer Feedback Survey',
                'layout' => 'single',
                'fields' => [
                    ['label' => 'Full Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Enter your name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'your@email.com'],
                    ['label' => 'Rating', 'type' => 'select', 'required' => true, 'options' => ['Excellent', 'Good', 'Average', 'Poor']],
                    ['label' => 'Comments', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Share your feedback'],
                ],
                'responses' => [
                    ['Full Name' => 'John Smith', 'Email' => 'john.smith@email.com', 'Rating' => 'Excellent', 'Comments' => 'Great service!'],
                    ['Full Name' => 'Sarah Johnson', 'Email' => 'sarah.j@email.com', 'Rating' => 'Good', 'Comments' => 'Quick response time.'],
                    ['Full Name' => 'Michael Brown', 'Email' => 'mbrown@email.com', 'Rating' => 'Excellent', 'Comments' => 'Exceeded expectations.'],
                    ['Full Name' => 'Emily Davis', 'Email' => 'emily.davis@email.com', 'Rating' => 'Average', 'Comments' => 'Could be improved.'],
                    ['Full Name' => 'David Wilson', 'Email' => 'dwilson@email.com', 'Rating' => 'Good', 'Comments' => 'Professional service.'],
                    ['Full Name' => 'Lisa Anderson', 'Email' => 'lisa.a@email.com', 'Rating' => 'Excellent', 'Comments' => 'Will recommend!'],
                    ['Full Name' => 'James Taylor', 'Email' => 'jtaylor@email.com', 'Rating' => 'Good', 'Comments' => 'Happy overall.'],
                    ['Full Name' => 'Jennifer Martinez', 'Email' => 'jmartinez@email.com', 'Rating' => 'Excellent', 'Comments' => 'Outstanding support.'],
                    ['Full Name' => 'Robert Garcia', 'Email' => 'rgarcia@email.com', 'Rating' => 'Average', 'Comments' => 'Met expectations.'],
                    ['Full Name' => 'Mary Rodriguez', 'Email' => 'mrodriguez@email.com', 'Rating' => 'Good', 'Comments' => 'Smooth process.'],
                    ['Full Name' => 'William Lee', 'Email' => 'wlee@email.com', 'Rating' => 'Excellent', 'Comments' => 'Quality service.'],
                    ['Full Name' => 'Patricia White', 'Email' => 'pwhite@email.com', 'Rating' => 'Good', 'Comments' => 'Helpful team.'],
                    ['Full Name' => 'Thomas Harris', 'Email' => 'tharris@email.com', 'Rating' => 'Excellent', 'Comments' => 'Best experience.'],
                    ['Full Name' => 'Linda Clark', 'Email' => 'lclark@email.com', 'Rating' => 'Good', 'Comments' => 'Reliable service.'],
                    ['Full Name' => 'Charles Lewis', 'Email' => 'clewis@email.com', 'Rating' => 'Average', 'Comments' => 'Decent service.'],
                ]
            ],
            [
                'name' => 'Job Application Form',
                'layout' => 'two-column',
                'fields' => [
                    ['label' => 'Full Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'email@example.com'],
                    ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '+1234567890'],
                    ['label' => 'Position', 'type' => 'select', 'required' => true, 'options' => ['Software Engineer', 'Product Manager', 'Designer', 'Marketing Manager']],
                    ['label' => 'Experience (years)', 'type' => 'number', 'required' => true, 'placeholder' => '0'],
                    ['label' => 'Cover Letter', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Tell us about yourself'],
                ],
                'responses' => [
                    ['Full Name' => 'Alexander Mitchell', 'Email' => 'alexander.mitchell@techpro.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Software Engineer', 'Experience (years)' => '5', 'Cover Letter' => 'Full-stack developer with Laravel expertise.'],
                    ['Full Name' => 'Victoria Thompson', 'Email' => 'victoria.thompson@productleader.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Product Manager', 'Experience (years)' => '7', 'Cover Letter' => 'Product strategy and team leadership.'],
                    ['Full Name' => 'Christopher Davis', 'Email' => 'christopher.davis@designstudio.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Designer', 'Experience (years)' => '4', 'Cover Letter' => 'UI/UX designer passionate about user experience.'],
                    ['Full Name' => 'Samantha Rodriguez', 'Email' => 'samantha.rodriguez@marketingpro.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Marketing Manager', 'Experience (years)' => '6', 'Cover Letter' => 'Digital marketing expert.'],
                    ['Full Name' => 'Jonathan Wilson', 'Email' => 'jonathan.wilson@cloudtech.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Software Engineer', 'Experience (years)' => '3', 'Cover Letter' => 'Backend specialist with cloud experience.'],
                    ['Full Name' => 'Amanda Foster', 'Email' => 'amanda.f@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Designer', 'Experience (years)' => '5', 'Cover Letter' => 'Brand identity specialist.'],
                    ['Full Name' => 'Brian Mitchell', 'Email' => 'brian.m@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Software Engineer', 'Experience (years)' => '8', 'Cover Letter' => 'Senior developer with microservices expertise.'],
                    ['Full Name' => 'Sophia Bell', 'Email' => 'sophia.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Designer', 'Experience (years)' => '6', 'Cover Letter' => 'Motion graphics expert.'],
                    ['Full Name' => 'Eric Murphy', 'Email' => 'eric.m@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Software Engineer', 'Experience (years)' => '4', 'Cover Letter' => 'Mobile app developer.'],
                    ['Full Name' => 'Olivia Reed', 'Email' => 'olivia.r@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Product Manager', 'Experience (years)' => '9', 'Cover Letter' => 'Enterprise product management.'],
                    ['Full Name' => 'Nathan Price', 'Email' => 'nathan.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Marketing Manager', 'Experience (years)' => '5', 'Cover Letter' => 'Content marketing expert.'],
                    ['Full Name' => 'Emma Watson', 'Email' => 'emma.w@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Designer', 'Experience (years)' => '3', 'Cover Letter' => 'Modern design trends.'],
                    ['Full Name' => 'Tyler Brooks', 'Email' => 'tyler.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Position' => 'Software Engineer', 'Experience (years)' => '6', 'Cover Letter' => 'DevOps specialist.'],
                ]
            ],
            [
                'name' => 'Conference Registration 2024',
                'layout' => 'card',
                'fields' => [
                    ['label' => 'Attendee Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'contact@email.com'],
                    ['label' => 'Company', 'type' => 'text', 'required' => false, 'placeholder' => 'Company name'],
                    ['label' => 'Ticket Type', 'type' => 'radio', 'required' => true, 'options' => ['Early Bird', 'Regular', 'VIP']],
                    ['label' => 'Dietary Requirements', 'type' => 'checkbox', 'required' => false, 'options' => ['Vegetarian', 'Vegan', 'Gluten-free', 'None']],
                ],
                'responses' => [
                    ['Attendee Name' => 'Michael Chen', 'Email' => 'mchen@techcorp.com', 'Company' => 'TechCorp Inc', 'Ticket Type' => 'VIP', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Sarah Williams', 'Email' => 'swilliams@startup.io', 'Company' => 'Startup.io', 'Ticket Type' => 'Early Bird', 'Dietary Requirements' => 'Vegetarian'],
                    ['Attendee Name' => 'David Kumar', 'Email' => 'dkumar@solutions.com', 'Company' => 'Solutions Ltd', 'Ticket Type' => 'Regular', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Emma Rodriguez', 'Email' => 'erodriguez@digital.com', 'Company' => 'Digital Agency', 'Ticket Type' => 'VIP', 'Dietary Requirements' => 'Vegan'],
                    ['Attendee Name' => 'James Peterson', 'Email' => 'jpeterson@enterprise.com', 'Company' => 'Enterprise Systems', 'Ticket Type' => 'Regular', 'Dietary Requirements' => 'Gluten-free'],
                    ['Attendee Name' => 'Lisa Zhang', 'Email' => 'lzhang@innovations.com', 'Company' => 'Innovations Co', 'Ticket Type' => 'Early Bird', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Robert Taylor', 'Email' => 'rtaylor@consulting.com', 'Company' => 'Taylor Consulting', 'Ticket Type' => 'VIP', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Jennifer Lee', 'Email' => 'jlee@software.com', 'Company' => 'Software Solutions', 'Ticket Type' => 'Regular', 'Dietary Requirements' => 'Vegetarian'],
                    ['Attendee Name' => 'Thomas Anderson', 'Email' => 'tanderson@tech.com', 'Company' => 'Tech Ventures', 'Ticket Type' => 'Early Bird', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Maria Garcia', 'Email' => 'mgarcia@creative.com', 'Company' => 'Creative Studios', 'Ticket Type' => 'Regular', 'Dietary Requirements' => 'Vegan'],
                    ['Attendee Name' => 'Christopher White', 'Email' => 'cwhite@systems.com', 'Company' => 'Systems Group', 'Ticket Type' => 'VIP', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Amanda Brown', 'Email' => 'abrown@media.com', 'Company' => 'Media Corp', 'Ticket Type' => 'Early Bird', 'Dietary Requirements' => 'Gluten-free'],
                    ['Attendee Name' => 'Daniel Martinez', 'Email' => 'dmartinez@cloud.com', 'Company' => 'Cloud Services', 'Ticket Type' => 'Regular', 'Dietary Requirements' => 'None'],
                    ['Attendee Name' => 'Michelle Johnson', 'Email' => 'mjohnson@data.com', 'Company' => 'Data Analytics', 'Ticket Type' => 'VIP', 'Dietary Requirements' => 'Vegetarian'],
                ]
            ],
            [
                'name' => 'Newsletter Subscription',
                'layout' => 'single',
                'fields' => [
                    ['label' => 'Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your name'],
                    ['label' => 'Email Address', 'type' => 'email', 'required' => true, 'placeholder' => 'email@example.com'],
                    ['label' => 'Interests', 'type' => 'checkbox', 'required' => false, 'options' => ['Technology', 'Business', 'Marketing', 'Design']],
                    ['label' => 'Frequency', 'type' => 'radio', 'required' => true, 'options' => ['Daily', 'Weekly', 'Monthly']],
                ],
                'responses' => [
                    ['Name' => 'Alice Cooper', 'Email Address' => 'alice.c@email.com', 'Interests' => 'Technology', 'Frequency' => 'Weekly'],
                    ['Name' => 'Bob Dylan', 'Email Address' => 'bob.d@email.com', 'Interests' => 'Business', 'Frequency' => 'Daily'],
                    ['Name' => 'Carol King', 'Email Address' => 'carol.k@email.com', 'Interests' => 'Marketing', 'Frequency' => 'Monthly'],
                    ['Name' => 'Derek Jones', 'Email Address' => 'derek.j@email.com', 'Interests' => 'Technology', 'Frequency' => 'Weekly'],
                    ['Name' => 'Elena Martinez', 'Email Address' => 'elena.m@email.com', 'Interests' => 'Design', 'Frequency' => 'Weekly'],
                    ['Name' => 'Frank Wright', 'Email Address' => 'frank.w@email.com', 'Interests' => 'Business', 'Frequency' => 'Monthly'],
                    ['Name' => 'Grace Lee', 'Email Address' => 'grace.l@email.com', 'Interests' => 'Technology', 'Frequency' => 'Daily'],
                    ['Name' => 'Henry Ford', 'Email Address' => 'henry.f@email.com', 'Interests' => 'Marketing', 'Frequency' => 'Weekly'],
                    ['Name' => 'Iris West', 'Email Address' => 'iris.w@email.com', 'Interests' => 'Design', 'Frequency' => 'Monthly'],
                    ['Name' => 'Jack Ryan', 'Email Address' => 'jack.r@email.com', 'Interests' => 'Technology', 'Frequency' => 'Weekly'],
                    ['Name' => 'Kate Bishop', 'Email Address' => 'kate.b@email.com', 'Interests' => 'Business', 'Frequency' => 'Daily'],
                    ['Name' => 'Leo Valdez', 'Email Address' => 'leo.v@email.com', 'Interests' => 'Marketing', 'Frequency' => 'Weekly'],
                ]
            ],
            [
                'name' => 'Contact Us Form',
                'layout' => 'two-column',
                'fields' => [
                    ['label' => 'Full Name', 'type' => 'text', 'required' => true, 'placeholder' => 'John Doe'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'john@example.com'],
                    ['label' => 'Phone Number', 'type' => 'tel', 'required' => false, 'placeholder' => '+1234567890'],
                    ['label' => 'Subject', 'type' => 'select', 'required' => true, 'options' => ['General Inquiry', 'Support', 'Sales', 'Partnership']],
                    ['label' => 'Message', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Your message here'],
                ],
                'responses' => [
                    ['Full Name' => 'Sam Wilson', 'Email' => 'sam.w@company.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'General Inquiry', 'Message' => 'I would like to know more about your services.'],
                    ['Full Name' => 'Tina Turner', 'Email' => 'tina.t@business.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Support', 'Message' => 'Need help with account setup.'],
                    ['Full Name' => 'Uma Thurman', 'Email' => 'uma.t@enterprise.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Sales', 'Message' => 'Interested in enterprise pricing.'],
                    ['Full Name' => 'Victor Stone', 'Email' => 'victor.s@tech.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Partnership', 'Message' => 'Exploring partnership opportunities.'],
                    ['Full Name' => 'Wendy Marvel', 'Email' => 'wendy.m@startup.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'General Inquiry', 'Message' => 'Can you provide a demo?'],
                    ['Full Name' => 'Xavier Woods', 'Email' => 'xavier.w@solutions.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Support', 'Message' => 'Issues with payment processing.'],
                    ['Full Name' => 'Yara Shahidi', 'Email' => 'yara.s@agency.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Sales', 'Message' => 'Looking for bulk licensing.'],
                    ['Full Name' => 'Zack Morris', 'Email' => 'zack.m@consulting.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Partnership', 'Message' => 'Interested in reseller program.'],
                    ['Full Name' => 'Anna Bell', 'Email' => 'anna.b@digital.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'General Inquiry', 'Message' => 'What are your integration capabilities?'],
                    ['Full Name' => 'Bruce Wayne', 'Email' => 'bruce.w@corp.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Support', 'Message' => 'Need API documentation help.'],
                    ['Full Name' => 'Cathy Freeman', 'Email' => 'cathy.f@media.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Sales', 'Message' => 'Request for custom pricing.'],
                    ['Full Name' => 'Doug Ross', 'Email' => 'doug.r@systems.com', 'Phone Number' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Subject' => 'Partnership', 'Message' => 'Strategic alliance discussion.'],
                ]
            ],
            [
                'name' => 'Online Course Enrollment',
                'layout' => 'card',
                'fields' => [
                    ['label' => 'Student Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'student@email.com'],
                    ['label' => 'Course Selection', 'type' => 'select', 'required' => true, 'options' => ['Web Development', 'Data Science', 'Digital Marketing', 'Graphic Design']],
                    ['label' => 'Experience Level', 'type' => 'radio', 'required' => true, 'options' => ['Beginner', 'Intermediate', 'Advanced']],
                    ['label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['label' => 'Goals', 'type' => 'textarea', 'required' => false, 'placeholder' => 'What do you hope to achieve?'],
                ],
                'responses' => [
                    ['Student Name' => 'Adam Smith', 'Email' => 'adam.s@student.com', 'Course Selection' => 'Web Development', 'Experience Level' => 'Beginner', 'Start Date' => '2024-02-01', 'Goals' => 'Career change to development.'],
                    ['Student Name' => 'Bella Swan', 'Email' => 'bella.s@student.com', 'Course Selection' => 'Data Science', 'Experience Level' => 'Intermediate', 'Start Date' => '2024-02-15', 'Goals' => 'Enhance data analysis skills.'],
                    ['Student Name' => 'Carlos Mendez', 'Email' => 'carlos.m@student.com', 'Course Selection' => 'Digital Marketing', 'Experience Level' => 'Beginner', 'Start Date' => '2024-03-01', 'Goals' => 'Start freelance business.'],
                    ['Student Name' => 'Diana Prince', 'Email' => 'diana.p@student.com', 'Course Selection' => 'Graphic Design', 'Experience Level' => 'Advanced', 'Start Date' => '2024-02-10', 'Goals' => 'Master advanced techniques.'],
                    ['Student Name' => 'Ethan Hunt', 'Email' => 'ethan.h@student.com', 'Course Selection' => 'Web Development', 'Experience Level' => 'Intermediate', 'Start Date' => '2024-02-20', 'Goals' => 'Build modern applications.'],
                    ['Student Name' => 'Felicity Jones', 'Email' => 'felicity.j@student.com', 'Course Selection' => 'Data Science', 'Experience Level' => 'Beginner', 'Start Date' => '2024-03-05', 'Goals' => 'Transition to analytics.'],
                    ['Student Name' => 'Gabriel Garcia', 'Email' => 'gabriel.g@student.com', 'Course Selection' => 'Digital Marketing', 'Experience Level' => 'Intermediate', 'Start Date' => '2024-02-25', 'Goals' => 'Improve SEO skills.'],
                    ['Student Name' => 'Hannah Montana', 'Email' => 'hannah.m@student.com', 'Course Selection' => 'Graphic Design', 'Experience Level' => 'Beginner', 'Start Date' => '2024-03-10', 'Goals' => 'Create brand designs.'],
                    ['Student Name' => 'Isaac Newton', 'Email' => 'isaac.n@student.com', 'Course Selection' => 'Web Development', 'Experience Level' => 'Advanced', 'Start Date' => '2024-02-05', 'Goals' => 'Learn latest frameworks.'],
                    ['Student Name' => 'Jessica Jones', 'Email' => 'jessica.j@student.com', 'Course Selection' => 'Data Science', 'Experience Level' => 'Intermediate', 'Start Date' => '2024-02-18', 'Goals' => 'Apply ML to business.'],
                    ['Student Name' => 'Kyle Reese', 'Email' => 'kyle.r@student.com', 'Course Selection' => 'Digital Marketing', 'Experience Level' => 'Advanced', 'Start Date' => '2024-03-15', 'Goals' => 'Growth hacking strategies.'],
                ]
            ],
            [
                'name' => 'Medical Appointment Booking',
                'layout' => 'two-column',
                'fields' => [
                    ['label' => 'Patient Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'patient@email.com'],
                    ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '+1234567890'],
                    ['label' => 'Department', 'type' => 'select', 'required' => true, 'options' => ['General Medicine', 'Dentistry', 'Cardiology', 'Orthopedics']],
                    ['label' => 'Preferred Date', 'type' => 'date', 'required' => true],
                    ['label' => 'Preferred Time', 'type' => 'time', 'required' => true],
                    ['label' => 'Reason for Visit', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Brief description'],
                ],
                'responses' => [
                    ['Patient Name' => 'Richard Roe', 'Email' => 'richard.r@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'General Medicine', 'Preferred Date' => '2024-02-15', 'Preferred Time' => '09:00', 'Reason for Visit' => 'Annual checkup.'],
                    ['Patient Name' => 'Samantha Carter', 'Email' => 'samantha.c@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Dentistry', 'Preferred Date' => '2024-02-16', 'Preferred Time' => '10:30', 'Reason for Visit' => 'Dental cleaning.'],
                    ['Patient Name' => 'Timothy Drake', 'Email' => 'timothy.d@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Cardiology', 'Preferred Date' => '2024-02-17', 'Preferred Time' => '14:00', 'Reason for Visit' => 'Heart condition follow-up.'],
                    ['Patient Name' => 'Ursula Vernon', 'Email' => 'ursula.v@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Orthopedics', 'Preferred Date' => '2024-02-18', 'Preferred Time' => '11:00', 'Reason for Visit' => 'Knee pain consultation.'],
                    ['Patient Name' => 'Vincent Vega', 'Email' => 'vincent.v@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'General Medicine', 'Preferred Date' => '2024-02-19', 'Preferred Time' => '15:30', 'Reason for Visit' => 'Flu symptoms.'],
                    ['Patient Name' => 'Wanda Maximoff', 'Email' => 'wanda.m@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Dentistry', 'Preferred Date' => '2024-02-20', 'Preferred Time' => '09:30', 'Reason for Visit' => 'Tooth pain.'],
                    ['Patient Name' => 'Xavier Charles', 'Email' => 'xavier.c@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Cardiology', 'Preferred Date' => '2024-02-21', 'Preferred Time' => '13:00', 'Reason for Visit' => 'Blood pressure monitoring.'],
                    ['Patient Name' => 'Yvonne Strahovski', 'Email' => 'yvonne.s@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Orthopedics', 'Preferred Date' => '2024-02-22', 'Preferred Time' => '10:00', 'Reason for Visit' => 'Back pain assessment.'],
                    ['Patient Name' => 'Zachary Levi', 'Email' => 'zachary.l@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'General Medicine', 'Preferred Date' => '2024-02-23', 'Preferred Time' => '16:00', 'Reason for Visit' => 'Prescription refill.'],
                    ['Patient Name' => 'Abigail Adams', 'Email' => 'abigail.a@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Dentistry', 'Preferred Date' => '2024-02-24', 'Preferred Time' => '11:30', 'Reason for Visit' => 'Teeth whitening.'],
                    ['Patient Name' => 'Benjamin Button', 'Email' => 'benjamin.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Cardiology', 'Preferred Date' => '2024-02-25', 'Preferred Time' => '14:30', 'Reason for Visit' => 'Chest pain evaluation.'],
                    ['Patient Name' => 'Catherine Zeta', 'Email' => 'catherine.z@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Orthopedics', 'Preferred Date' => '2024-02-26', 'Preferred Time' => '09:00', 'Reason for Visit' => 'Sports injury.'],
                    ['Patient Name' => 'Dominic Toretto', 'Email' => 'dominic.t@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'General Medicine', 'Preferred Date' => '2024-02-27', 'Preferred Time' => '15:00', 'Reason for Visit' => 'Allergy testing.'],
                    ['Patient Name' => 'Eleanor Roosevelt', 'Email' => 'eleanor.r@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Dentistry', 'Preferred Date' => '2024-02-28', 'Preferred Time' => '10:00', 'Reason for Visit' => 'Root canal treatment.'],
                    ['Patient Name' => 'Franklin Pierce', 'Email' => 'franklin.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Department' => 'Cardiology', 'Preferred Date' => '2024-02-29', 'Preferred Time' => '13:30', 'Reason for Visit' => 'ECG and stress test.'],
                ]
            ],
            [
                'name' => 'Volunteer Registration Form',
                'layout' => 'single',
                'fields' => [
                    ['label' => 'Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'volunteer@email.com'],
                    ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '+1234567890'],
                    ['label' => 'Areas of Interest', 'type' => 'checkbox', 'required' => true, 'options' => ['Education', 'Environment', 'Healthcare', 'Community Service']],
                    ['label' => 'Availability', 'type' => 'select', 'required' => true, 'options' => ['Weekdays', 'Weekends', 'Both', 'Flexible']],
                    ['label' => 'Why do you want to volunteer?', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Share your motivation'],
                ],
                'responses' => [
                    ['Name' => 'Harper Lee', 'Email' => 'harper.l@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Education', 'Availability' => 'Weekends', 'Why do you want to volunteer?' => 'Passionate about teaching children.'],
                    ['Name' => 'Ian Fleming', 'Email' => 'ian.f@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Environment', 'Availability' => 'Flexible', 'Why do you want to volunteer?' => 'Environmental conservation.'],
                    ['Name' => 'Jane Austen', 'Email' => 'jane.a@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Healthcare', 'Availability' => 'Weekdays', 'Why do you want to volunteer?' => 'Healthcare professional giving back.'],
                    ['Name' => 'Kurt Vonnegut', 'Email' => 'kurt.v@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Community Service', 'Availability' => 'Both', 'Why do you want to volunteer?' => 'Strengthen community bonds.'],
                    ['Name' => 'Louise Penny', 'Email' => 'louise.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Education', 'Availability' => 'Weekends', 'Why do you want to volunteer?' => 'Love working with students.'],
                    ['Name' => 'Oscar Wilde', 'Email' => 'oscar.w@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Community Service', 'Availability' => 'Weekends', 'Why do you want to volunteer?' => 'Organizing community events.'],
                    ['Name' => 'Pearl Buck', 'Email' => 'pearl.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Education', 'Availability' => 'Both', 'Why do you want to volunteer?' => 'Dedicated to literacy programs.'],
                    ['Name' => 'Quinn Martin', 'Email' => 'quinn.m@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Environment', 'Availability' => 'Weekends', 'Why do you want to volunteer?' => 'Local cleanup initiatives.'],
                    ['Name' => 'Ruth Bader', 'Email' => 'ruth.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Areas of Interest' => 'Healthcare', 'Availability' => 'Weekdays', 'Why do you want to volunteer?' => 'Mental health awareness.'],
                ]
            ],
            [
                'name' => 'Restaurant Reservation',
                'layout' => 'two-column',
                'fields' => [
                    ['label' => 'Guest Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'guest@email.com'],
                    ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '+1234567890'],
                    ['label' => 'Number of Guests', 'type' => 'number', 'required' => true, 'placeholder' => '2'],
                    ['label' => 'Reservation Date', 'type' => 'date', 'required' => true],
                    ['label' => 'Reservation Time', 'type' => 'time', 'required' => true],
                    ['label' => 'Special Requests', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Any dietary restrictions or preferences'],
                ],
                'responses' => [
                    ['Guest Name' => 'Anthony Bourdain', 'Email' => 'anthony.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '4', 'Reservation Date' => '2024-02-14', 'Reservation Time' => '19:00', 'Special Requests' => 'Window table, anniversary celebration.'],
                    ['Guest Name' => 'Betty Crocker', 'Email' => 'betty.c@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '2', 'Reservation Date' => '2024-02-15', 'Reservation Time' => '18:30', 'Special Requests' => 'Vegetarian options needed.'],
                    ['Guest Name' => 'Charlie Chaplin', 'Email' => 'charlie.c@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '6', 'Reservation Date' => '2024-02-16', 'Reservation Time' => '20:00', 'Special Requests' => 'Private dining for business meeting.'],
                    ['Guest Name' => 'Dolly Parton', 'Email' => 'dolly.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '3', 'Reservation Date' => '2024-02-17', 'Reservation Time' => '19:30', 'Special Requests' => 'Gluten-free menu options.'],
                    ['Guest Name' => 'Elvis Presley', 'Email' => 'elvis.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '2', 'Reservation Date' => '2024-02-18', 'Reservation Time' => '18:00', 'Special Requests' => 'Quiet corner table.'],
                    ['Guest Name' => 'Frank Sinatra', 'Email' => 'frank.s@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '5', 'Reservation Date' => '2024-02-19', 'Reservation Time' => '20:30', 'Special Requests' => 'Birthday celebration, dessert with candle.'],
                    ['Guest Name' => 'Grace Kelly', 'Email' => 'grace.k@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '2', 'Reservation Date' => '2024-02-20', 'Reservation Time' => '19:00', 'Special Requests' => 'Wine pairing recommendations.'],
                    ['Guest Name' => 'Humphrey Bogart', 'Email' => 'humphrey.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '4', 'Reservation Date' => '2024-02-21', 'Reservation Time' => '18:30', 'Special Requests' => 'Seafood allergies in party.'],
                    ['Guest Name' => 'Katharine Hepburn', 'Email' => 'katharine.h@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '6', 'Reservation Date' => '2024-02-24', 'Reservation Time' => '18:00', 'Special Requests' => 'Family gathering, high chairs needed.'],
                    ['Guest Name' => 'Lauren Bacall', 'Email' => 'lauren.b@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Number of Guests' => '2', 'Reservation Date' => '2024-02-25', 'Reservation Time' => '20:30', 'Special Requests' => 'Champagne on arrival.'],
                ]
            ],
            [
                'name' => 'Gym Membership Application',
                'layout' => 'card',
                'fields' => [
                    ['label' => 'Full Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'member@email.com'],
                    ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '+1234567890'],
                    ['label' => 'Membership Type', 'type' => 'radio', 'required' => true, 'options' => ['Basic', 'Premium', 'VIP']],
                    ['label' => 'Fitness Goals', 'type' => 'checkbox', 'required' => false, 'options' => ['Weight Loss', 'Muscle Gain', 'Cardio', 'Flexibility']],
                    ['label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['label' => 'Medical Conditions', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Any health concerns we should know'],
                ],
                'responses' => [
                    ['Full Name' => 'Arnold Strong', 'Email' => 'arnold.s@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Premium', 'Fitness Goals' => 'Muscle Gain', 'Start Date' => '2024-02-01', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Brenda Fit', 'Email' => 'brenda.f@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Basic', 'Fitness Goals' => 'Weight Loss', 'Start Date' => '2024-02-05', 'Medical Conditions' => 'Mild asthma'],
                    ['Full Name' => 'Carl Runner', 'Email' => 'carl.r@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'VIP', 'Fitness Goals' => 'Cardio', 'Start Date' => '2024-02-10', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Dana Flex', 'Email' => 'dana.f@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Premium', 'Fitness Goals' => 'Flexibility', 'Start Date' => '2024-02-15', 'Medical Conditions' => 'Previous knee injury'],
                    ['Full Name' => 'Eric Power', 'Email' => 'eric.p@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Basic', 'Fitness Goals' => 'Muscle Gain', 'Start Date' => '2024-02-20', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Fiona Health', 'Email' => 'fiona.h@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Premium', 'Fitness Goals' => 'Weight Loss', 'Start Date' => '2024-02-25', 'Medical Conditions' => 'Diabetes type 2'],
                    ['Full Name' => 'Greg Athlete', 'Email' => 'greg.a@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'VIP', 'Fitness Goals' => 'Cardio', 'Start Date' => '2024-03-01', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Holly Yoga', 'Email' => 'holly.y@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Basic', 'Fitness Goals' => 'Flexibility', 'Start Date' => '2024-03-05', 'Medical Conditions' => 'Back pain'],
                    ['Full Name' => 'Ivan Lift', 'Email' => 'ivan.l@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Premium', 'Fitness Goals' => 'Muscle Gain', 'Start Date' => '2024-03-10', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Kevin Gym', 'Email' => 'kevin.g@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Basic', 'Fitness Goals' => 'Cardio', 'Start Date' => '2024-03-20', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Laura Stretch', 'Email' => 'laura.s@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Premium', 'Fitness Goals' => 'Flexibility', 'Start Date' => '2024-03-25', 'Medical Conditions' => 'Arthritis'],
                    ['Full Name' => 'Mike Muscle', 'Email' => 'mike.m@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'VIP', 'Fitness Goals' => 'Muscle Gain', 'Start Date' => '2024-04-01', 'Medical Conditions' => 'None'],
                    ['Full Name' => 'Nina Cardio', 'Email' => 'nina.c@email.com', 'Phone' => $countryCodes[array_rand($countryCodes)] . mt_rand(1000000000, 9999999999), 'Membership Type' => 'Basic', 'Fitness Goals' => 'Weight Loss', 'Start Date' => '2024-04-05', 'Medical Conditions' => 'None'],
                ]
            ],
            [
                'name' => 'Technical Support Ticket',
                'layout' => 'single',
                'fields' => [
                    ['label' => 'Your Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Full name'],
                    ['label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'support@email.com'],
                    ['label' => 'Priority', 'type' => 'radio', 'required' => true, 'options' => ['Low', 'Medium', 'High', 'Critical']],
                    ['label' => 'Category', 'type' => 'select', 'required' => true, 'options' => ['Technical Issue', 'Billing', 'Feature Request', 'Bug Report']],
                    ['label' => 'Subject', 'type' => 'text', 'required' => true, 'placeholder' => 'Brief description'],
                    ['label' => 'Description', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Detailed description of the issue'],
                ],
                'responses' => [
                    ['Your Name' => 'Adam West', 'Email' => 'adam.w@user.com', 'Priority' => 'High', 'Category' => 'Technical Issue', 'Subject' => 'Login not working', 'Description' => 'Unable to login with correct credentials.'],
                    ['Your Name' => 'Betty White', 'Email' => 'betty.w@user.com', 'Priority' => 'Medium', 'Category' => 'Billing', 'Subject' => 'Incorrect charge', 'Description' => 'Charged twice for same subscription.'],
                    ['Your Name' => 'Carl Sagan', 'Email' => 'carl.s@user.com', 'Priority' => 'Low', 'Category' => 'Feature Request', 'Subject' => 'Dark mode option', 'Description' => 'Would love dark mode for interface.'],
                    ['Your Name' => 'Diane Keaton', 'Email' => 'diane.k@user.com', 'Priority' => 'Critical', 'Category' => 'Bug Report', 'Subject' => 'Data loss issue', 'Description' => 'Lost all saved data after update.'],
                    ['Your Name' => 'Gene Wilder', 'Email' => 'gene.w@user.com', 'Priority' => 'Low', 'Category' => 'Feature Request', 'Subject' => 'Export to PDF', 'Description' => 'Need PDF export for reports.'],
                    ['Your Name' => 'Helen Mirren', 'Email' => 'helen.m@user.com', 'Priority' => 'High', 'Category' => 'Bug Report', 'Subject' => 'Search not working', 'Description' => 'Search returns no results.'],
                    ['Your Name' => 'Ian McKellen', 'Email' => 'ian.m@user.com', 'Priority' => 'Critical', 'Category' => 'Technical Issue', 'Subject' => 'System crash', 'Description' => 'App crashes on analytics dashboard.'],
                    ['Your Name' => 'Julia Roberts', 'Email' => 'julia.r@user.com', 'Priority' => 'Medium', 'Category' => 'Billing', 'Subject' => 'Invoice not received', 'Description' => 'Missing invoice for last month.'],
                    ['Your Name' => 'Kevin Spacey', 'Email' => 'kevin.s@user.com', 'Priority' => 'Low', 'Category' => 'Feature Request', 'Subject' => 'Mobile app', 'Description' => 'Need mobile app version.'],
                    ['Your Name' => 'Lucy Liu', 'Email' => 'lucy.l@user.com', 'Priority' => 'High', 'Category' => 'Bug Report', 'Subject' => 'Notification not working', 'Description' => 'Not receiving email notifications.'],
                    ['Your Name' => 'Morgan Freeman', 'Email' => 'morgan.f@user.com', 'Priority' => 'Medium', 'Category' => 'Technical Issue', 'Subject' => 'Slow performance', 'Description' => 'System very slow this week.'],
                ]
            ],
        ];

        foreach ($forms as $formIndex => $formData) {
            $formCreatedAt = Carbon::now()->subDays(30 - ($formIndex * 3))
                ->addHours(rand(8, 18))
                ->addMinutes(rand(0, 59))
                ->addSeconds(rand(0, 59));

            $form = Form::updateOrCreate(
                ['name' => $formData['name'], 'created_by' => $userId],
                [
                    'code' => Form::generateCode(),
                    'is_active' => true,
                    'default_layout' => $formData['layout'],
                    'creator_id' => $userId,
                    'created_at' => $formCreatedAt,
                    'updated_at' => $formCreatedAt,
                ]
            );

            $fieldMap = [];
            foreach ($formData['fields'] as $index => $field) {
                $formField = FormField::updateOrCreate(
                    ['form_id' => $form->id, 'label' => $field['label']],
                    [
                        'type' => $field['type'],
                        'required' => $field['required'],
                        'placeholder' => $field['placeholder'] ?? null,
                        'options' => $field['options'] ?? null,
                        'order' => $index + 1,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $formCreatedAt,
                        'updated_at' => $formCreatedAt,
                    ]
                );
                $fieldMap[$field['label']] = $formField->id;
            }

            foreach ($formData['responses'] as $responseIndex => $response) {
                $responseData = [];
                foreach ($response as $label => $value) {
                    if (isset($fieldMap[$label])) {
                        $responseData[$fieldMap[$label]] = $value;
                    }
                }

                $responseCreatedAt = $formCreatedAt->copy()
                    ->addHours(rand(1, 24 * 7))
                    ->addMinutes(rand(0, 59))
                    ->addSeconds(rand(0, 59));

                FormResponse::firstOrCreate(
                    ['form_id' => $form->id, 'response_data' => $responseData],
                    [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $responseCreatedAt,
                        'updated_at' => $responseCreatedAt,
                    ]
                );
            }
        }
    }
}
