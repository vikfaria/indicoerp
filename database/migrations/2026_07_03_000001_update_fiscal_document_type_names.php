<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiscal_document_types')) {
            return;
        }

        DB::table('fiscal_document_types')
            ->where('code', 'FR')
            ->update(['name' => 'Recibo']);

        DB::table('fiscal_document_types')
            ->where('code', 'RC')
            ->update(['name' => 'Recibo de Pagamento']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fiscal_document_types')) {
            return;
        }

        DB::table('fiscal_document_types')
            ->where('code', 'FR')
            ->update(['name' => 'Factura-Recibo']);

        DB::table('fiscal_document_types')
            ->where('code', 'RC')
            ->update(['name' => 'Recibo']);
    }
};
