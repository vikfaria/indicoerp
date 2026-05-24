<?php

namespace Database\Seeders;

use App\Models\WithholdingTaxRule;
use Illuminate\Database\Seeder;

class MzWithholdingRulesSeeder extends Seeder
{
    public function run(): void
    {
        WithholdingTaxRule::seedDefaults();
        $this->command?->info('Withholding tax rules: ' . WithholdingTaxRule::count() . ' carregadas.');
    }
}
