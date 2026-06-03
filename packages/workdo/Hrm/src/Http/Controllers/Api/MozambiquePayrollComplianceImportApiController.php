<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MozambiqueHrWorkforceExportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MozambiquePayrollComplianceImportApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly MozambiqueHrWorkforceExportService $workforceExportService
    ) {}

    public function importWorkforce(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('edit-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $filePath = $request->file('csv_file')?->getRealPath();
        if (!$filePath) {
            return $this->errorResponse('Invalid CSV upload.', null, 422);
        }

        $summary = $this->workforceExportService->importCsv(
            creatorId(),
            $filePath,
            Auth::id()
        );

        $processed = (int) ($summary['processed'] ?? 0);
        if ($processed === 0 && !empty($summary['errors'])) {
            return $this->errorResponse((string) ($summary['errors'][0]['message'] ?? 'Workforce import failed.'), $summary, 422);
        }

        return $this->successResponse($summary, 'Workforce import completed.');
    }

    public function importAttendance(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('edit-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $filePath = $request->file('csv_file')?->getRealPath();
        if (!$filePath) {
            return $this->errorResponse('Invalid CSV upload.', null, 422);
        }

        $summary = $this->workforceExportService->importAttendanceCsv(
            creatorId(),
            $filePath,
            Auth::id()
        );

        $processed = (int) ($summary['processed'] ?? 0);
        if ($processed === 0 && !empty($summary['errors'])) {
            return $this->errorResponse((string) ($summary['errors'][0]['message'] ?? 'Attendance import failed.'), $summary, 422);
        }

        return $this->successResponse($summary, 'Attendance import completed.');
    }

    public function importAnnualLeavePlans(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('edit-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $filePath = $request->file('csv_file')?->getRealPath();
        if (!$filePath) {
            return $this->errorResponse('Invalid CSV upload.', null, 422);
        }

        $summary = $this->workforceExportService->importAnnualLeavePlansCsv(
            creatorId(),
            $filePath,
            Auth::id()
        );

        $processed = (int) ($summary['processed'] ?? 0);
        if ($processed === 0 && !empty($summary['errors'])) {
            return $this->errorResponse((string) ($summary['errors'][0]['message'] ?? 'Annual leave plans import failed.'), $summary, 422);
        }

        return $this->successResponse($summary, 'Annual leave plans import completed.');
    }
}

