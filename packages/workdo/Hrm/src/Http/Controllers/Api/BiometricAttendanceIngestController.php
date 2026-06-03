<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MozambiqueLabourComplianceService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class BiometricAttendanceIngestController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly MozambiqueLabourComplianceService $labourComplianceService)
    {
    }

    public function ingest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:users,id',
            'employee_identifier' => 'required|string|max:190',
            'identifier_type' => 'nullable|in:employee_id,tax_payer_id,user_email,user_id',
            'action' => 'required|in:clockin,clockout',
            'occurred_at' => 'required|date',
            'device_id' => 'nullable|string|max:120',
            'device_name' => 'nullable|string|max:160',
            'source_reference' => 'nullable|string|max:160',
            'notes' => 'nullable|string|max:500',
            'device_token' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $companyId = (int) $request->input('company_id');
        $suppliedToken = (string) (
            $request->header('X-HRM-DEVICE-TOKEN')
            ?: $request->input('device_token')
            ?: ''
        );

        if (!$this->isDeviceTokenValid($companyId, $suppliedToken)) {
            return $this->errorResponse('Invalid or missing biometric device token.', null, 403);
        }

        $identifierType = (string) ($request->input('identifier_type') ?: 'employee_id');
        $employeeIdentifier = trim((string) $request->input('employee_identifier'));
        $employee = $this->resolveEmployee($companyId, $identifierType, $employeeIdentifier);

        if (!$employee) {
            return $this->errorResponse('Employee not found for provided biometric identifier.', null, 404);
        }

        $occurredAt = Carbon::parse((string) $request->input('occurred_at'));
        $deviceId = $this->nullableTrimmed($request->input('device_id'));
        $deviceName = $this->nullableTrimmed($request->input('device_name'));
        $sourceReference = $this->nullableTrimmed($request->input('source_reference'))
            ?: sprintf('biometric:%s:%s', $deviceId ?: 'unknown', $occurredAt->toIso8601String());
        $notes = $this->nullableTrimmed($request->input('notes'));

        if ((string) $request->input('action') === 'clockin') {
            return $this->handleClockIn($employee, $companyId, $occurredAt, $deviceId, $deviceName, $sourceReference, $notes);
        }

        return $this->handleClockOut($employee, $companyId, $occurredAt, $deviceId, $deviceName, $sourceReference, $notes);
    }

    private function handleClockIn(
        Employee $employee,
        int $companyId,
        Carbon $occurredAt,
        ?string $deviceId,
        ?string $deviceName,
        string $sourceReference,
        ?string $notes
    ) {
        $employeeUserId = (int) $employee->user_id;
        $attendanceDate = $occurredAt->toDateString();

        $weeklyRestValidation = $this->labourComplianceService->validateWeeklyRestForAttendanceDate(
            $companyId,
            $employeeUserId,
            $attendanceDate
        );
        if (!($weeklyRestValidation['valid'] ?? false)) {
            return $this->errorResponse((string) ($weeklyRestValidation['message'] ?? 'Weekly rest rule violated.'), null, 422);
        }

        $existing = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->whereDate('date', $attendanceDate)
            ->orderByDesc('id')
            ->first();

        if ($existing && $existing->clock_in && !$existing->clock_out) {
            return $this->successResponse([
                'attendance_id' => (int) $existing->id,
                'employee_user_id' => $employeeUserId,
                'date' => $attendanceDate,
                'clock_in' => Carbon::parse((string) $existing->clock_in)->toDateTimeString(),
                'status' => 'already_open',
            ], 'Clock-in already registered for this date.');
        }

        if ($existing && $existing->clock_in && $existing->clock_out) {
            return $this->errorResponse('Attendance is already closed for this date.', null, 422);
        }

        $shiftId = $this->resolveShiftId($employee->shift);

        if ($existing) {
            $existing->update([
                'shift_id' => $existing->shift_id ?: $shiftId,
                'clock_in' => $occurredAt,
                'clock_out' => null,
                'break_hour' => 0,
                'total_hour' => 0,
                'overtime_hours' => 0,
                'overtime_amount' => 0,
                'status' => 'present',
                'source_channel' => 'biometric',
                'source_device_id' => $deviceId,
                'source_device_label' => $deviceName,
                'source_reference' => $sourceReference,
                'notes' => $notes,
            ]);

            return $this->successResponse([
                'attendance_id' => (int) $existing->id,
                'employee_user_id' => $employeeUserId,
                'date' => $attendanceDate,
                'clock_in' => $occurredAt->toDateTimeString(),
                'status' => 'clocked_in',
            ], 'Clock-in registered successfully.');
        }

        $attendance = Attendance::query()->create([
            'employee_id' => $employeeUserId,
            'shift_id' => $shiftId,
            'date' => $attendanceDate,
            'clock_in' => $occurredAt,
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => $deviceId,
            'source_device_label' => $deviceName,
            'source_reference' => $sourceReference,
            'notes' => $notes,
            'creator_id' => null,
            'created_by' => $companyId,
        ]);

        return $this->successResponse([
            'attendance_id' => (int) $attendance->id,
            'employee_user_id' => $employeeUserId,
            'date' => $attendanceDate,
            'clock_in' => $occurredAt->toDateTimeString(),
            'status' => 'clocked_in',
        ], 'Clock-in registered successfully.');
    }

    private function handleClockOut(
        Employee $employee,
        int $companyId,
        Carbon $occurredAt,
        ?string $deviceId,
        ?string $deviceName,
        string $sourceReference,
        ?string $notes
    ) {
        $employeeUserId = (int) $employee->user_id;

        $openAttendance = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('clock_in', '<=', $occurredAt->toDateString())
            ->orderByDesc('clock_in')
            ->first();

        if (!$openAttendance) {
            return $this->errorResponse('No open attendance found for biometric clock-out.', null, 422);
        }

        $clockInAt = Carbon::parse((string) $openAttendance->clock_in);
        if ($occurredAt->lt($clockInAt)) {
            return $this->errorResponse('Clock-out timestamp cannot be before clock-in timestamp.', null, 422);
        }

        $shift = $this->resolveShiftForAttendance($openAttendance, $employee, $companyId);
        $calculated = $this->calculateAttendanceData($clockInAt, $occurredAt, $shift, $employee);

        $openAttendance->update([
            'clock_out' => $occurredAt,
            'break_hour' => $calculated['total_hour']['total_break_hours'],
            'total_hour' => $calculated['total_hour']['total_working_hours'],
            'overtime_hours' => $calculated['overtime_hours'],
            'overtime_amount' => $calculated['overtime_amount'],
            'status' => $calculated['status'],
            'source_channel' => 'biometric',
            'source_device_id' => $deviceId,
            'source_device_label' => $deviceName,
            'source_reference' => $sourceReference,
            'notes' => $notes ?: $openAttendance->notes,
        ]);

        return $this->successResponse([
            'attendance_id' => (int) $openAttendance->id,
            'employee_user_id' => $employeeUserId,
            'date' => optional($openAttendance->date)->format('Y-m-d'),
            'clock_in' => $clockInAt->toDateTimeString(),
            'clock_out' => $occurredAt->toDateTimeString(),
            'total_hours' => $calculated['total_hour']['total_working_hours'],
            'overtime_hours' => $calculated['overtime_hours'],
            'status' => 'clocked_out',
        ], 'Clock-out registered successfully.');
    }

    private function resolveEmployee(int $companyId, string $identifierType, string $identifier): ?Employee
    {
        $query = Employee::query()
            ->where('created_by', $companyId)
            ->with(['shift', 'user:id,email']);

        switch ($identifierType) {
            case 'tax_payer_id':
                $query->where('tax_payer_id', $identifier);
                break;
            case 'user_email':
                $normalizedEmail = strtolower($identifier);
                $query->whereHas('user', function ($userQuery) use ($normalizedEmail): void {
                    $userQuery->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                });
                break;
            case 'user_id':
                $query->where('user_id', (int) $identifier);
                break;
            case 'employee_id':
            default:
                $query->where('employee_id', $identifier);
                break;
        }

        return $query->first();
    }

    private function isDeviceTokenValid(int $companyId, string $suppliedToken): bool
    {
        if ($suppliedToken === '') {
            return false;
        }

        $settings = Setting::query()
            ->where('created_by', $companyId)
            ->whereIn('key', ['mz_attendance_device_ingest_enabled', 'mz_attendance_device_ingest_token'])
            ->pluck('value', 'key');

        $isEnabled = true;
        if (array_key_exists('mz_attendance_device_ingest_enabled', $settings->all())) {
            $isEnabled = $this->toBooleanSetting($settings['mz_attendance_device_ingest_enabled']);
        }

        if (!$isEnabled) {
            return false;
        }

        $expectedToken = (string) ($settings['mz_attendance_device_ingest_token'] ?? '');

        if ($expectedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $suppliedToken);
    }

    private function resolveShiftForAttendance(Attendance $attendance, Employee $employee, int $companyId): ?Shift
    {
        $shiftId = $attendance->shift_id ?: $this->resolveShiftId($employee->shift);
        if (!$shiftId) {
            return null;
        }

        return Shift::query()
            ->where('id', $shiftId)
            ->where('created_by', $companyId)
            ->first();
    }

    private function resolveShiftId($shift): ?int
    {
        if ($shift instanceof Shift) {
            return (int) $shift->id;
        }

        if (is_numeric($shift)) {
            return (int) $shift;
        }

        return null;
    }

    private function calculateAttendanceData(Carbon $clockIn, Carbon $clockOut, ?Shift $shift, Employee $employee): array
    {
        $totalHourData = $this->calculateTotalHours($clockIn, $clockOut, $shift);
        $totalHour = $totalHourData['total_working_hours'];

        $standardHours = ($shift && $this->getWorkingHour($shift) > 0)
            ? $this->getWorkingHour($shift)
            : 8.0;

        $overtimeHours = max(0, round($totalHour - $standardHours, 2));
        $overtimeAmount = 0.0;
        if ($overtimeHours > 0 && (float) $employee->rate_per_hour > 0) {
            $overtimeAmount = round($overtimeHours * (float) $employee->rate_per_hour, 2);
        }

        $status = 'absent';
        if ($totalHour > 0) {
            $halfDayThreshold = $standardHours / 2;
            if ($totalHour >= $standardHours) {
                $status = 'present';
            } elseif ($totalHour >= $halfDayThreshold) {
                $status = 'half day';
            }
        }

        return [
            'total_hour' => $totalHourData,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'status' => $status,
        ];
    }

    private function calculateTotalHours(Carbon $clockIn, Carbon $clockOut, ?Shift $shift): array
    {
        $clockInTime = $clockIn->copy();
        $clockOutTime = $clockOut->copy();

        if ($clockOutTime->lt($clockInTime)) {
            $clockOutTime->addDay();
        }

        $totalMinutes = abs($clockOutTime->diffInMinutes($clockInTime));
        $breakMinutes = 0;

        if ($shift && $shift->break_start_time && $shift->break_end_time) {
            $breakStart = Carbon::parse((string) $shift->break_start_time);
            $breakEnd = Carbon::parse((string) $shift->break_end_time);

            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            if ($clockInTime->lte($breakStart) && $clockOutTime->gte($breakEnd)) {
                $breakMinutes = $this->breakDuration($shift);
            } elseif ($clockInTime->lte($breakStart) && $clockOutTime->gt($breakStart) && $clockOutTime->lte($breakEnd)) {
                $breakMinutes = abs($clockOutTime->diffInMinutes($breakStart));
            } elseif ($clockInTime->gt($breakStart) && $clockInTime->lt($breakEnd) && $clockOutTime->gte($breakEnd)) {
                $breakMinutes = abs($breakEnd->diffInMinutes($clockInTime));
            }
        }

        $workingMinutes = max(0, $totalMinutes - $breakMinutes);

        return [
            'total_working_hours' => round($workingMinutes / 60, 2),
            'total_break_hours' => round($breakMinutes / 60, 2),
        ];
    }

    private function breakDuration(Shift $shift): int
    {
        $breakStart = Carbon::parse((string) $shift->break_start_time);
        $breakEnd = Carbon::parse((string) $shift->break_end_time);
        if ($breakEnd->lt($breakStart)) {
            $breakEnd->addDay();
        }

        return abs($breakEnd->diffInMinutes($breakStart));
    }

    private function getWorkingHour(Shift $shift): float
    {
        $start = Carbon::parse((string) $shift->start_time);
        $end = Carbon::parse((string) $shift->end_time);

        if ($shift->is_night_shift && $end->lt($start)) {
            $end->addDay();
        }

        $totalMinutes = abs($end->diffInMinutes($start)) - $this->breakDuration($shift);

        return round(max(0, $totalMinutes) / 60, 2);
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }

    private function toBooleanSetting(mixed $value): bool
    {
        $normalized = strtolower(trim((string) ($value ?? '')));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
