<?php

namespace Workdo\Recruitment\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\CustomQuestion;

class DemoCustomQuestionSeeder extends Seeder
{
    public function run($userId = null): void
    {
        if (CustomQuestion::where('created_by', $userId)->exists()) {
            return;
        }

        $questions = [
            ['question' => 'Are you authorized to work in this country?', 'type' => 'radio', 'options' => json_encode(['Yes', 'No']), 'is_required' => true, 'is_active' => true, 'sort_order' => 1],
            ['question' => 'What is your expected salary range?', 'type' => 'select', 'options' => json_encode(['$30,000 - $40,000', '$40,000 - $60,000', '$60,000 - $80,000', '$80,000 - $100,000', '$100,000+']), 'is_required' => false, 'is_active' => true, 'sort_order' => 2],
            ['question' => 'Do you have experience with the following technologies?', 'type' => 'checkbox', 'options' => json_encode(['PHP', 'Laravel', 'React', 'Vue.js', 'Node.js', 'Python', 'Java', 'MySQL', 'PostgreSQL']), 'is_required' => false, 'is_active' => true, 'sort_order' => 3],
            ['question' => 'Why are you interested in this position?', 'type' => 'textarea', 'options' => null, 'is_required' => true, 'is_active' => true, 'sort_order' => 4],
            ['question' => 'How many years of relevant experience do you have?', 'type' => 'select', 'options' => json_encode(['0-1 years', '1-3 years', '3-5 years', '5-10 years', '10+ years']), 'is_required' => true, 'is_active' => true, 'sort_order' => 5],
            ['question' => 'Are you willing to relocate?', 'type' => 'radio', 'options' => json_encode(['Yes', 'No', 'Maybe']), 'is_required' => false, 'is_active' => true, 'sort_order' => 6],
            ['question' => 'What is your date of birth?', 'type' => 'date', 'options' => null, 'is_required' => false, 'is_active' => false, 'sort_order' => 7],
            ['question' => 'What is your preferred work arrangement?', 'type' => 'radio', 'options' => json_encode(['Remote', 'On-site', 'Hybrid']), 'is_required' => false, 'is_active' => true, 'sort_order' => 8],
            ['question' => 'LinkedIn Profile URL', 'type' => 'text', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 9],
            ['question' => 'Tell us about your biggest professional achievement', 'type' => 'textarea', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 10],
            ['question' => 'How many years of total work experience do you have?', 'type' => 'number', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 11],
            ['question' => 'What programming languages are you proficient in?', 'type' => 'checkbox', 'options' => json_encode(['JavaScript', 'Python', 'Java', 'C#', 'PHP', 'Ruby', 'Go', 'Swift', 'Kotlin']), 'is_required' => false, 'is_active' => true, 'sort_order' => 12],
            ['question' => 'What is your highest level of education?', 'type' => 'select', 'options' => json_encode(['High School', 'Associate Degree', 'Bachelor Degree', 'Master Degree', 'PhD', 'Other']), 'is_required' => true, 'is_active' => true, 'sort_order' => 13],
            ['question' => 'Do you have any professional certifications?', 'type' => 'textarea', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 14],
            ['question' => 'What is your notice period?', 'type' => 'select', 'options' => json_encode(['Immediate', '1 week', '2 weeks', '1 month', '2 months', '3 months']), 'is_required' => true, 'is_active' => true, 'sort_order' => 15],
            ['question' => 'Do you have experience working remotely?', 'type' => 'radio', 'options' => json_encode(['Yes', 'No']), 'is_required' => false, 'is_active' => true, 'sort_order' => 16],
            ['question' => 'What motivates you in your work?', 'type' => 'textarea', 'options' => null, 'is_required' => false, 'is_active' => false, 'sort_order' => 17],
            ['question' => 'Rate your English proficiency', 'type' => 'select', 'options' => json_encode(['Beginner', 'Intermediate', 'Advanced', 'Native']), 'is_required' => false, 'is_active' => true, 'sort_order' => 18],
            ['question' => 'Do you have a valid driver license?', 'type' => 'radio', 'options' => json_encode(['Yes', 'No']), 'is_required' => false, 'is_active' => false, 'sort_order' => 19],
            ['question' => 'Portfolio or GitHub URL', 'type' => 'text', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 20],
            ['question' => 'What is your current job title?', 'type' => 'text', 'options' => null, 'is_required' => false, 'is_active' => true, 'sort_order' => 21]
        ];

        foreach ($questions as $question) {
            $question['creator_id'] = $userId;
            $question['created_by'] = $userId;
            CustomQuestion::create($question);
        }
    }
}
