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
    public static function expectedDefinitionsForYear(int $year): array
    {
        $events = [];

        $addMonthlyEvents = static function (array &$events, int $year, string $prefix, string $titlePrefix, string $obligationType, int $dueDay): void {
            for ($month = 1; $month <= 12; $month++) {
                $nextMonth = $month === 12 ? 1 : $month + 1;
                $nextYear = $month === 12 ? $year + 1 : $year;

                $events[] = [
                    'code' => sprintf('%s-%d-%d', $prefix, $year, $month),
                    'title' => sprintf('%s — %02d/%d', $titlePrefix, $month, $year),
                    'obligation_type' => $obligationType,
                    'due_date' => sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $dueDay),
                    'reference_period' => sprintf('%04d-%02d', $year, $month),
                ];
            }
        };

        // IVA monthly (até dia 20 do mês seguinte)
        $addMonthlyEvents($events, $year, 'IVA', 'Declaração Periódica IVA', 'vat', 20);

        // Retenções na fonte (até dia 20 do mês seguinte)
        $addMonthlyEvents($events, $year, 'RET', 'Declaração Retenções Fonte', 'withholding', 20);

        // IRPS (até dia 20 do mês seguinte)
        $addMonthlyEvents($events, $year, 'IRPS', 'Declaração IRPS', 'irps', 20);

        // INSS (até dia 10 do mês seguinte)
        $addMonthlyEvents($events, $year, 'INSS', 'Contribuição INSS', 'inss', 10);

        // PPC IRPC (Maio, Julho, Setembro)
        foreach ([5, 7, 9] as $month) {
            $events[] = [
                'code' => sprintf('PPC-%d-%d', $year, $month),
                'title' => sprintf('Pagamento por Conta IRPC — %02d/%d', $month, $year),
                'obligation_type' => 'irpc',
                'due_date' => sprintf('%04d-%02d-30', $year, $month),
                'reference_period' => sprintf('%04d-%02d', $year, $month),
            ];
        }

        // Declaração Modelo 20 IRPC (até 31 de Maio do ano seguinte)
        $events[] = [
            'code' => sprintf('M20-%d', $year),
            'title' => sprintf('Declaração Modelo 20 IRPC — Exercício %d', $year),
            'obligation_type' => 'irpc',
            'due_date' => sprintf('%04d-05-31', $year + 1),
            'reference_period' => (string) $year,
        ];

        // SAF-T anual (até 30 de Junho do ano seguinte)
        $events[] = [
            'code' => sprintf('SAFT-%d', $year),
            'title' => sprintf('Entrega SAF-T — Exercício %d', $year),
            'obligation_type' => 'saft',
            'due_date' => sprintf('%04d-06-30', $year + 1),
            'reference_period' => (string) $year,
        ];

        // Contas anuais aprovação (até 31 de Março do ano seguinte)
        $events[] = [
            'code' => sprintf('CONTAS-%d', $year),
            'title' => sprintf('Aprovação Contas Anuais — Exercício %d', $year),
            'obligation_type' => 'annual_accounts',
            'due_date' => sprintf('%04d-03-31', $year + 1),
            'reference_period' => (string) $year,
        ];

        return $events;
    }

    public static function generateForYear(int $companyId, int $year): void
    {
        foreach (static::expectedDefinitionsForYear($year) as $event) {
            $calendarEvent = static::firstOrNew([
                'company_id' => $companyId,
                'code' => $event['code'],
            ]);

            $calendarEvent->title = $event['title'];
            $calendarEvent->obligation_type = $event['obligation_type'];
            $calendarEvent->due_date = $event['due_date'];
            $calendarEvent->reference_period = $event['reference_period'];

            if (!$calendarEvent->exists) {
                $calendarEvent->status = 'pending';
                $calendarEvent->created_by = $companyId;
            }

            $calendarEvent->save();
        }
    }
}
