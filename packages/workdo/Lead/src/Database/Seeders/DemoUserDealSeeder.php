<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\UserDeal;
use Workdo\Lead\Models\Deal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Workdo\Lead\Models\Lead;

class DemoUserDealSeeder extends Seeder
{
    public function run($userId): void
    {
        if (UserDeal::where('user_id', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            // Only get deals that are NOT converted from leads (they already have users)
            $convertedDealIds = Lead::where('created_by', $userId)
                ->where('is_converted', '>', 0)
                ->pluck('is_converted')
                ->toArray();

            $deals = Deal::where('created_by', $userId)
                ->whereNotIn('id', $convertedDealIds)
                ->get();

            $users = User::where('created_by', $userId)->where('type', '!=', 'client')->pluck('id')->toArray();

            if ($deals->isEmpty() || empty($users)) {
                return;
            }

            foreach ($deals as $deal) {
                // Get existing assigned users for this deal
                $existingUsers = $deal->users()->pluck('user_id')->toArray();

                // Assign 1-2 additional users (avoiding already assigned users)
                $availableUsers = array_diff($users, $existingUsers);

                if (!empty($availableUsers)) {
                    $randomUsers = collect($availableUsers)->shuffle()->take(rand(1, min(2, count($availableUsers))))->all();

                    foreach ($randomUsers as $additionalUserId) {
                        UserDeal::firstOrCreate([
                            'user_id' => $additionalUserId,
                            'deal_id' => $deal->id,
                        ]);
                    }
                }
            }
        }
    }
}
