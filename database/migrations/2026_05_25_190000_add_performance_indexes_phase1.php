<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSettingsIndexes();
        $this->addUserActiveModulesIndexes();
        $this->addRecruitmentSettingsIndexes();
        $this->addChartOfAccountsIndexes();
        $this->addJournalEntriesIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('settings', 'settings_created_by_key_idx');
        $this->dropIndexIfExists('settings', 'settings_created_by_public_key_idx');

        $this->dropIndexIfExists('user_active_modules', 'uam_user_module_idx');

        $this->dropIndexIfExists('recruitment_settings', 'recruit_set_created_key_idx');

        $this->dropIndexIfExists('chart_of_accounts', 'coa_created_code_idx');
        $this->dropIndexIfExists('chart_of_accounts', 'coa_created_active_idx');

        $this->dropIndexIfExists('journal_entries', 'je_created_year_num_idx');
        $this->dropIndexIfExists('journal_entries', 'je_created_status_date_idx');
        $this->dropIndexIfExists('journal_entries', 'je_created_journal_date_idx');
    }

    private function addSettingsIndexes(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table): void {
            if (!$this->indexExists('settings', 'settings_created_by_key_idx')
                && $this->hasColumns('settings', ['created_by', 'key'])) {
                $table->index(['created_by', 'key'], 'settings_created_by_key_idx');
            }

            if (!$this->indexExists('settings', 'settings_created_by_public_key_idx')
                && $this->hasColumns('settings', ['created_by', 'is_public', 'key'])) {
                $table->index(['created_by', 'is_public', 'key'], 'settings_created_by_public_key_idx');
            }
        });
    }

    private function addUserActiveModulesIndexes(): void
    {
        if (!Schema::hasTable('user_active_modules')) {
            return;
        }

        Schema::table('user_active_modules', function (Blueprint $table): void {
            if (!$this->indexExists('user_active_modules', 'uam_user_module_idx')
                && $this->hasColumns('user_active_modules', ['user_id', 'module'])) {
                $table->index(['user_id', 'module'], 'uam_user_module_idx');
            }
        });
    }

    private function addRecruitmentSettingsIndexes(): void
    {
        if (!Schema::hasTable('recruitment_settings')) {
            return;
        }

        Schema::table('recruitment_settings', function (Blueprint $table): void {
            if (!$this->indexExists('recruitment_settings', 'recruit_set_created_key_idx')
                && $this->hasColumns('recruitment_settings', ['created_by', 'key'])) {
                $table->index(['created_by', 'key'], 'recruit_set_created_key_idx');
            }
        });
    }

    private function addChartOfAccountsIndexes(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table): void {
            if (!$this->indexExists('chart_of_accounts', 'coa_created_code_idx')
                && $this->hasColumns('chart_of_accounts', ['created_by', 'account_code'])) {
                $table->index(['created_by', 'account_code'], 'coa_created_code_idx');
            }

            if (!$this->indexExists('chart_of_accounts', 'coa_created_active_idx')
                && $this->hasColumns('chart_of_accounts', ['created_by', 'is_active'])) {
                $table->index(['created_by', 'is_active'], 'coa_created_active_idx');
            }
        });
    }

    private function addJournalEntriesIndexes(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (!$this->indexExists('journal_entries', 'je_created_year_num_idx')
                && $this->hasColumns('journal_entries', ['created_by', 'fiscal_year', 'period_number'])) {
                $table->index(['created_by', 'fiscal_year', 'period_number'], 'je_created_year_num_idx');
            }

            if (!$this->indexExists('journal_entries', 'je_created_status_date_idx')
                && $this->hasColumns('journal_entries', ['created_by', 'status', 'journal_date'])) {
                $table->index(['created_by', 'status', 'journal_date'], 'je_created_status_date_idx');
            }

            if (!$this->indexExists('journal_entries', 'je_created_journal_date_idx')
                && $this->hasColumns('journal_entries', ['created_by', 'accounting_journal_id', 'journal_date'])) {
                $table->index(['created_by', 'accounting_journal_id', 'journal_date'], 'je_created_journal_date_idx');
            }
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }
};
