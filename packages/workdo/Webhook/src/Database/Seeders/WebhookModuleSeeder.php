<?php

namespace Workdo\Webhook\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Webhook\Models\WebhookModule;

class WebhookModuleSeeder extends Seeder
{
    public function run(): void
    {
        $events = config('webhook-events.events', []);

        foreach ($events as $eventClass => $configs) {
            // Handle both single config and array of configs
            $configArray = isset($configs[0]) ? $configs : [$configs];
            
            foreach ($configArray as $config) {
                WebhookModule::firstOrCreate([
                    'module' => $config['module'],
                    'submodule' => $config['action'],
                    'type' => $config['type'] ?? 'company'
                ]);
            }
        }
    }
}