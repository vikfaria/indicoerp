<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warnings')) {
            return;
        }

        Schema::table('warnings', function (Blueprint $table): void {
            if (!Schema::hasColumn('warnings', 'note_of_culpa_issued_at')) {
                $table->date('note_of_culpa_issued_at')->nullable()->after('warning_date');
            }

            if (!Schema::hasColumn('warnings', 'note_of_culpa_delivered_at')) {
                $table->date('note_of_culpa_delivered_at')->nullable()->after('note_of_culpa_issued_at');
            }

            if (!Schema::hasColumn('warnings', 'worker_refused_note_of_culpa')) {
                $table->boolean('worker_refused_note_of_culpa')->default(false)->after('note_of_culpa_delivered_at');
            }

            if (!Schema::hasColumn('warnings', 'refusal_witness_one_name')) {
                $table->string('refusal_witness_one_name', 120)->nullable()->after('worker_refused_note_of_culpa');
            }

            if (!Schema::hasColumn('warnings', 'refusal_witness_two_name')) {
                $table->string('refusal_witness_two_name', 120)->nullable()->after('refusal_witness_one_name');
            }

            if (!Schema::hasColumn('warnings', 'response_deadline_at')) {
                $table->date('response_deadline_at')->nullable()->after('refusal_witness_two_name');
                $table->index('response_deadline_at');
            }

            if (!Schema::hasColumn('warnings', 'decision_deadline_at')) {
                $table->date('decision_deadline_at')->nullable()->after('response_deadline_at');
                $table->index('decision_deadline_at');
            }

            if (!Schema::hasColumn('warnings', 'disciplinary_sanction')) {
                $table->string('disciplinary_sanction', 40)->nullable()->after('decision_deadline_at');
            }

            if (!Schema::hasColumn('warnings', 'disciplinary_decision_at')) {
                $table->date('disciplinary_decision_at')->nullable()->after('disciplinary_sanction');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('warnings')) {
            return;
        }

        Schema::table('warnings', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('warnings', 'note_of_culpa_issued_at') ? 'note_of_culpa_issued_at' : null,
                Schema::hasColumn('warnings', 'note_of_culpa_delivered_at') ? 'note_of_culpa_delivered_at' : null,
                Schema::hasColumn('warnings', 'worker_refused_note_of_culpa') ? 'worker_refused_note_of_culpa' : null,
                Schema::hasColumn('warnings', 'refusal_witness_one_name') ? 'refusal_witness_one_name' : null,
                Schema::hasColumn('warnings', 'refusal_witness_two_name') ? 'refusal_witness_two_name' : null,
                Schema::hasColumn('warnings', 'response_deadline_at') ? 'response_deadline_at' : null,
                Schema::hasColumn('warnings', 'decision_deadline_at') ? 'decision_deadline_at' : null,
                Schema::hasColumn('warnings', 'disciplinary_sanction') ? 'disciplinary_sanction' : null,
                Schema::hasColumn('warnings', 'disciplinary_decision_at') ? 'disciplinary_decision_at' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
