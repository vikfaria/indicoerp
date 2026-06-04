<?php

namespace App\Services;

use Carbon\Carbon;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeProbationProfile;

class MozambiqueProbationCessationService
{
    public function close(
        Employee $employee,
        Carbon|string $cessationDate,
        string $reason,
        ?string $notes = null,
        ?string $triggerLabel = null,
        ?string $probationTypeLabel = null
    ): bool {
        $profile = EmployeeProbationProfile::query()
            ->where('employee_id', $employee->id)
            ->first();

        if (!$profile) {
            return false;
        }

        $cessationAt = $cessationDate instanceof Carbon
            ? $cessationDate->copy()->startOfDay()
            : Carbon::parse($cessationDate)->startOfDay();

        if (!$this->shouldCloseProbation($profile, $cessationAt, $probationTypeLabel)) {
            return false;
        }

        $profile->decision_status = 'ceased';
        $profile->decision_date = $cessationAt->toDateString();
        $profile->cessation_reason = trim($reason) !== '' ? trim($reason) : ($profile->cessation_reason ?? '');

        if (in_array((string) $profile->evaluation_status, ['pending', 'ongoing'], true)) {
            $profile->evaluation_status = 'failed';
        }

        if (empty($profile->recommendation)) {
            $profile->recommendation = 'cease';
        }

        $profile->notes = $this->appendNotes(
            $profile->notes,
            $notes ?: sprintf(
                'Probation cessation registered via %s on %s.',
                $triggerLabel ?: 'offboarding',
                $cessationAt->toDateString()
            )
        );

        $profile->save();

        return true;
    }

    private function shouldCloseProbation(
        EmployeeProbationProfile $profile,
        Carbon $cessationAt,
        ?string $probationTypeLabel
    ): bool {
        $label = strtolower(trim((string) ($probationTypeLabel ?? '')));

        if ($label !== '' && str_contains($label, 'probation')) {
            return true;
        }

        if (!$profile->expected_end_at) {
            return false;
        }

        return $cessationAt->lessThanOrEqualTo(Carbon::parse($profile->expected_end_at)->endOfDay());
    }

    private function appendNotes(?string $existingNotes, string $append): string
    {
        $existingNotes = trim((string) $existingNotes);
        $append = trim($append);

        if ($existingNotes === '') {
            return $append;
        }

        if ($append === '') {
            return $existingNotes;
        }

        if (str_contains($existingNotes, $append)) {
            return $existingNotes;
        }

        return $existingNotes . "\n\n" . $append;
    }
}
