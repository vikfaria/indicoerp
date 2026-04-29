<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoClientDealSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ClientDeal::whereHas('deal', function($query) use ($userId) {
            $query->where('created_by', $userId);
        })->exists()) {
            return;
        }
        if (!empty($userId)) {
            // Only get deals that are NOT converted from leads (they already have clients)
            $convertedDealIds = Lead::where('created_by', $userId)
                ->where('is_converted', '>', 0)
                ->pluck('is_converted')
                ->toArray();

            $deals = Deal::where('created_by', $userId)
                ->whereNotIn('id', $convertedDealIds)
                ->get();

            $clients = User::where('created_by', $userId)->where('type', 'client')->pluck('id')->toArray();

            if ($deals->isEmpty() || empty($clients)) {
                return;
            }

            foreach ($deals as $deal) {
                // Get existing assigned clients for this deal
                $existingClients = $deal->clientDeals()->pluck('client_id')->toArray();
                
                // Assign 1-2 additional clients (avoiding already assigned clients)
                $availableClients = array_diff($clients, $existingClients);

                if (!empty($availableClients)) {
                    $randomClients = collect($availableClients)->shuffle()->take(rand(1, min(2, count($availableClients))))->all();

                    foreach ($randomClients as $additionalClientId) {
                        ClientDeal::firstOrCreate([
                            'client_id' => $additionalClientId,
                            'deal_id' => $deal->id,
                        ]);
                    }
                }
            }
        }
    }
}