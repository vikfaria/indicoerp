<?php

namespace App\Console\Commands;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCalendarEvent;
use App\Models\FiscalDocumentType;
use App\Models\MzVatCode;
use App\Models\WithholdingTaxRule;
use App\Services\PgcImportService;
use Database\Seeders\PgcNirfSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SceSetupCommand extends Command
{
    protected $signature = 'sce:setup
        {--company= : Company ID to set up (defaults to company 2 / first non-admin)}
        {--framework=pgc_nirf : Accounting framework (pgc_nirf or pgc_pe)}
        {--year= : Fiscal year for periods (defaults to current year)}
        {--skip-catalog : Skip seeding the PGC catalog}
        {--skip-import : Skip importing PGC into company chart of accounts}
        {--force : Overwrite existing data}';

    protected $description = 'Set up SCE Moçambique compliance for a company (PGC, VAT, journals, periods)';

    public function handle(PgcImportService $pgcImportService): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║  SCE Moçambique — Setup Inicial              ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        $companyId = (int) ($this->option('company') ?: $this->resolveDefaultCompany());
        $framework = $this->option('framework');
        $year = (int) ($this->option('year') ?: date('Y'));

        if ($companyId === 0) {
            $this->error('Nenhuma empresa encontrada. Crie uma empresa primeiro.');
            return self::FAILURE;
        }

        $this->info("Empresa: #{$companyId}  |  Framework: {$framework}  |  Exercício: {$year}");
        $this->info('');

        // Step 1: Seed PGC-NIRF catalog
        if (!$this->option('skip-catalog')) {
            $this->task('1. Carregar catálogo PGC-NIRF', function () {
                $seeder = new PgcNirfSeeder();
                $seeder->setCommand($this);
                $seeder->run();
                return true;
            });
        }

        // Step 2: Seed VAT codes
        $this->task('2. Carregar códigos IVA', function () {
            MzVatCode::seedDefaults();
            $this->line("   → " . MzVatCode::count() . " códigos IVA");
            return true;
        });

        // Step 3: Seed Fiscal Document Types
        $this->task('3. Carregar tipos documentos fiscais', function () {
            FiscalDocumentType::seedDefaults();
            $this->line("   → " . FiscalDocumentType::count() . " tipos");
            return true;
        });

        // Step 4: Seed Withholding Tax Rules
        $this->task('4. Carregar regras retenção na fonte', function () {
            WithholdingTaxRule::seedDefaults();
            $this->line("   → " . WithholdingTaxRule::count() . " regras");
            return true;
        });

        // Step 5: Import PGC into company chart of accounts
        if (!$this->option('skip-import')) {
            $this->task("5. Importar PGC para empresa #{$companyId}", function () use ($pgcImportService, $companyId, $framework) {
                $result = $pgcImportService->importForCompany($companyId, $framework);

                if ($result['error']) {
                    $this->error("   Erro: " . $result['error']);
                    return false;
                }

                $this->line("   → {$result['imported']} importadas, {$result['skipped']} já existiam");
                return true;
            });
        }

        // Step 6: Seed default accounting journals
        $this->task("6. Criar diários contabilísticos padrão", function () use ($companyId) {
            AccountingJournal::seedDefaults($companyId);
            $count = AccountingJournal::where('company_id', $companyId)->count();
            $this->line("   → {$count} diários");
            return true;
        });

        // Step 7: Generate accounting periods
        $this->task("7. Gerar períodos contabilísticos {$year}", function () use ($companyId, $year) {
            if (!Schema::hasTable('accounting_periods')) {
                $this->warn('   Tabela accounting_periods não existe. Execute as migrações primeiro.');
                return false;
            }

            $existing = AccountingPeriod::where('company_id', $companyId)
                ->where('fiscal_year', $year)
                ->count();

            if ($existing > 0 && !$this->option('force')) {
                $this->line("   → {$existing} períodos já existem para {$year}");
                return true;
            }

            for ($m = 1; $m <= 12; $m++) {
                $startDate = sprintf('%d-%02d-01', $year, $m);
                $endDate = date('Y-m-t', strtotime($startDate));

                AccountingPeriod::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'fiscal_year' => $year,
                        'period_number' => $m,
                    ],
                    [
                        'period_name' => ucfirst(strftime('%B', mktime(0, 0, 0, $m, 1, $year))),
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => 'open',
                        'created_by' => $companyId,
                    ]
                );
            }

            // Period 13 — closing adjustments
            AccountingPeriod::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'fiscal_year' => $year,
                    'period_number' => 13,
                ],
                [
                    'period_name' => 'Regularizações',
                    'start_date' => "{$year}-12-31",
                    'end_date' => "{$year}-12-31",
                    'status' => 'open',
                    'created_by' => $companyId,
                ]
            );

            $this->line("   → 13 períodos criados para {$year}");
            return true;
        });

        // Step 8: Ensure fiscal profile exists
        $this->task("8. Verificar perfil fiscal", function () use ($companyId, $framework) {
            if (!Schema::hasTable('company_fiscal_profiles')) {
                $this->warn('   Tabela company_fiscal_profiles não existe.');
                return false;
            }

            $company = \App\Models\User::find($companyId);

            $profile = CompanyFiscalProfile::firstOrCreate(
                ['company_id' => $companyId],
                [
                    'legal_name' => $company?->name ?? null,
                    'accounting_framework' => $framework,
                    'fiscal_regime' => 'normal',
                    'entity_classification' => 'small',
                    'fiscal_year_start_month' => 1,
                    'taxpayer_type' => 'ordinary',
                    'state_of_certification' => 'not_certified',
                    'software_certificate_number' => '0',
                    'is_active' => true,
                    'created_by' => $companyId,
                ]
            );

            $this->line("   → Perfil fiscal: {$profile->accounting_framework}, regime: {$profile->fiscal_regime}");
            return true;
        });

        // Step 9: Generate fiscal calendar
        $this->task("9. Gerar calendário fiscal {$year}", function () use ($companyId, $year) {
            if (!Schema::hasTable('fiscal_calendar_events')) {
                $this->warn('   Tabela fiscal_calendar_events não existe. Execute as migrações primeiro.');
                return false;
            }

            FiscalCalendarEvent::generateForYear($companyId, $year);
            $count = FiscalCalendarEvent::where('company_id', $companyId)
                ->whereYear('due_date', $year)
                ->count();

            $this->line("   → {$count} eventos fiscais gerados");
            return true;
        });

        // Step 10: Validate PGC structure
        $this->task("10. Validar estrutura PGC", function () use ($pgcImportService, $companyId) {
            $issues = $pgcImportService->validateStructure($companyId);

            if (empty($issues)) {
                $this->line("   → Estrutura PGC válida ✓");
            } else {
                $this->warn("   → " . count($issues) . " problemas encontrados:");
                foreach ($issues as $issue) {
                    $this->line("     ⚠ {$issue}");
                }
            }
            return empty($issues);
        });

        $this->info('');
        $this->info('✅ Setup SCE concluído!');
        $this->info('');
        $this->info('Próximos passos:');
        $this->info('  1. Aceda a /sce/fiscal para configurar o NUIT e dados da empresa');
        $this->info('  2. Aceda a /sce/journals para verificar os diários criados');
        $this->info('  3. Aceda a /sce/fiscal/calendar para gerar o calendário fiscal');
        $this->info('');

        return self::SUCCESS;
    }

    private function resolveDefaultCompany(): int
    {
        // Try to find the first company user (type = 'company' or id > 1)
        $company = \App\Models\User::where('type', 'company')
            ->orderBy('id')
            ->first();

        return $company?->id ?? 2;
    }

    /**
     * Display a task with status indicator.
     */
    private function task(string $description, \Closure $callback): void
    {
        $this->info($description);

        try {
            $result = $callback();
            if ($result === false) {
                $this->warn("   [SKIPPED]");
            }
        } catch (\Throwable $e) {
            $this->error("   [ERRO] " . $e->getMessage());
        }

        $this->info('');
    }
}
