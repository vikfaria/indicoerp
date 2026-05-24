<?php

namespace Database\Seeders;

use App\Models\FiscalDocumentType;
use App\Models\MzVatCode;
use Illuminate\Database\Seeder;

class MzFiscalDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        MzVatCode::seedDefaults();
        $this->command?->info('MZ VAT codes: ' . MzVatCode::count() . ' carregados.');

        FiscalDocumentType::seedDefaults();
        $this->command?->info('Fiscal document types: ' . FiscalDocumentType::count() . ' carregados.');
    }
}
