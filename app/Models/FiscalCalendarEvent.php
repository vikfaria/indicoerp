<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalCalendarEvent extends Model
{
    protected $fillable = [
        'company_id', 'code', 'title', 'description', 'obligation_type',
        'due_date', 'reference_period', 'status', 'completed_date',
        'completed_by', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')->where('due_date', '<', now()->toDateString());
    }

    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '>=', now()->toDateString())
            ->where('due_date', '<=', now()->addDays($days)->toDateString());
    }

    /**
     * Generate standard Mozambican fiscal calendar for a year.
     */
    public static function generateForYear(int $companyId, int $year): void
    {
        $events = [];

        // IVA monthly (até dia 20 do mês seguinte)
        for ($m = 1; $m <= 12; $m++) {
            $nextMonth = $m === 12 ? 1 : $m + 1;
            $nextYear = $m === 12 ? $year + 1 : $year;
            $events[] = [
                'code' => "IVA-{$year}-{$m}",
                'title' => "Declaração Periódica IVA — " . sprintf('%02d/%d', $m, $year),
                'type' => 'vat',
                'due' => sprintf('%04d-%02d-20', $nextYear, $nextMonth),
                'ref' => sprintf('%04d-%02d', $year, $m),
            ];
        }

        // Retenções na fonte (até dia 20 do mês seguinte)
        for ($m = 1; $m <= 12; $m++) {
            $nextMonth = $m === 12 ? 1 : $m + 1;
            $nextYear = $m === 12 ? $year + 1 : $year;
            $events[] = [
                'code' => "RET-{$year}-{$m}",
                'title' => "Declaração Retenções Fonte — " . sprintf('%02d/%d', $m, $year),
                'type' => 'withholding',
                'due' => sprintf('%04d-%02d-20', $nextYear, $nextMonth),
                'ref' => sprintf('%04d-%02d', $year, $m),
            ];
        }

        // IRPS (até dia 20 do mês seguinte)
        for ($m = 1; $m <= 12; $m++) {
            $nextMonth = $m === 12 ? 1 : $m + 1;
            $nextYear = $m === 12 ? $year + 1 : $year;
            $events[] = [
                'code' => "IRPS-{$year}-{$m}",
                'title' => "Declaração IRPS — " . sprintf('%02d/%d', $m, $year),
                'type' => 'irps',
                'due' => sprintf('%04d-%02d-20', $nextYear, $nextMonth),
                'ref' => sprintf('%04d-%02d', $year, $m),
            ];
        }

        // INSS (até dia 10 do mês seguinte)
        for ($m = 1; $m <= 12; $m++) {
            $nextMonth = $m === 12 ? 1 : $m + 1;
            $nextYear = $m === 12 ? $year + 1 : $year;
            $events[] = [
                'code' => "INSS-{$year}-{$m}",
                'title' => "Contribuição INSS — " . sprintf('%02d/%d', $m, $year),
                'type' => 'inss',
                'due' => sprintf('%04d-%02d-10', $nextYear, $nextMonth),
                'ref' => sprintf('%04d-%02d', $year, $m),
            ];
        }

        // PPC IRPC (Maio, Julho, Setembro)
        foreach ([5, 7, 9] as $m) {
            $events[] = [
                'code' => "PPC-{$year}-{$m}",
                'title' => "Pagamento por Conta IRPC — " . sprintf('%02d/%d', $m, $year),
                'type' => 'irpc',
                'due' => sprintf('%04d-%02d-30', $year, $m),
                'ref' => sprintf('%04d-%02d', $year, $m),
            ];
        }

        // Declaração Modelo 20 IRPC (até 31 de Maio do ano seguinte)
        $events[] = [
            'code' => "M20-{$year}",
            'title' => "Declaração Modelo 20 IRPC — Exercício {$year}",
            'type' => 'irpc',
            'due' => sprintf('%04d-05-31', $year + 1),
            'ref' => (string) $year,
        ];

        // SAF-T anual (até 30 de Junho do ano seguinte)
        $events[] = [
            'code' => "SAFT-{$year}",
            'title' => "Entrega SAF-T — Exercício {$year}",
            'type' => 'saft',
            'due' => sprintf('%04d-06-30', $year + 1),
            'ref' => (string) $year,
        ];

        // Contas anuais aprovação (até 31 de Março do ano seguinte)
        $events[] = [
            'code' => "CONTAS-{$year}",
            'title' => "Aprovação Contas Anuais — Exercício {$year}",
            'type' => 'annual_accounts',
            'due' => sprintf('%04d-03-31', $year + 1),
            'ref' => (string) $year,
        ];

        foreach ($events as $event) {
            static::firstOrCreate(
                ['company_id' => $companyId, 'code' => $event['code']],
                [
                    'title' => $event['title'],
                    'obligation_type' => $event['type'],
                    'due_date' => $event['due'],
                    'reference_period' => $event['ref'],
                    'status' => 'pending',
                    'created_by' => $companyId,
                ]
            );
        }
    }
}
