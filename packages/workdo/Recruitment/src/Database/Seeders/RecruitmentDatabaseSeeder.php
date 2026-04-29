<?php

namespace Workdo\Recruitment\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Workdo\Recruitment\Models\Job;

class RecruitmentDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);
        $this->call(EmailTemplatesSeeder::class);
        $this->call(NotificationsTableSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;

            Job::defaultdata($userId);


            (new DemoJobTypeSeeder())->run($userId);
            (new DemoCandidateSourcesSeeder())->run($userId);
            (new DemoInterviewTypeSeeder())->run($userId);
            (new DemoOnboardingChecklistSeeder())->run($userId);
            (new DemoJobLocationSeeder())->run($userId);
            (new DemoCustomQuestionSeeder())->run($userId);
            (new DemoJobPostingSeeder())->run($userId);
            (new DemoCandidateSeeder())->run($userId);
            (new DemoInterviewRoundSeeder())->run($userId);
            (new DemoInterviewSeeder())->run($userId);
            (new DemoInterviewFeedbackSeeder())->run($userId);
            (new DemoCandidateAssessmentSeeder())->run($userId);
            (new DemoOfferSeeder())->run($userId);
            (new DemoChecklistItemSeeder())->run($userId);
            (new DemoCandidateOnboardingSeeder())->run($userId);
            (new DemoRecruitmentSettingSeeder())->run($userId);
        }
    }
}
