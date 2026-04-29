<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\UserLead;
use Workdo\Lead\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserLeadSeeder extends Seeder
{
    public function run($userId): void
    {
        if (UserLead::where('user_id', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $leads = Lead::where('created_by', $userId)->get();
            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();

            if ($leads->isEmpty() || empty($users)) {
                return;
            }

            foreach ($leads as $lead) {
                // Then assign 1-2 additional users (avoiding the primary user)
                $availableUsers = array_diff($users, [$lead->user_id]);

                if (!empty($availableUsers)) {
                    $randomUsers = collect($availableUsers)->shuffle()->take(rand(1, min(2, count($availableUsers))))->all();

                    foreach ($randomUsers as $additionalUserId) {
                        UserLead::firstOrCreate([
                            'user_id' => $additionalUserId,
                            'lead_id' => $lead->id,
                        ]);
                    }
                }
            }
        }
    }
}
