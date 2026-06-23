<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->fixJournalEntriesUniqueness();
        $this->createJournalNumberSequencesTable();
    }

    public function down(): void
    {
        $this->dropJournalNumberSequencesTable();
        $this->restoreGlobalJournalNumberUniqueness();
    }

    private function fixJournalEntriesUniqueness(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (Schema::hasIndex('journal_entries', 'journal_entries_journal_number_unique')) {
                $table->dropUnique(['journal_number']);
            }
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            if ($this->hasColumns('journal_entries', ['created_by', 'journal_number'])) {
                $table->unique(['created_by', 'journal_number'], 'journal_entries_created_by_journal_number_unique');
            }
        });
    }

    private function restoreGlobalJournalNumberUniqueness(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (Schema::hasIndex('journal_entries', 'journal_entries_created_by_journal_number_unique')) {
                $table->dropUnique('journal_entries_created_by_journal_number_unique');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('journal_entries', 'journal_number')) {
                $table->unique('journal_number');
            }
        });
    }

    private function createJournalNumberSequencesTable(): void
    {
        if (Schema::hasTable('journal_number_sequences')) {
            return;
        }

        Schema::create('journal_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('fiscal_year', 4);
            $table->string('scope_key', 100);
            $table->string('prefix', 20);
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(
                ['created_by', 'fiscal_year', 'scope_key', 'prefix'],
                'journal_number_sequences_scope_unique'
            );
            $table->index(['created_by', 'fiscal_year', 'prefix'], 'journal_number_sequences_year_prefix_idx');
        });
    }

    private function dropJournalNumberSequencesTable(): void
    {
        Schema::dropIfExists('journal_number_sequences');
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
};
