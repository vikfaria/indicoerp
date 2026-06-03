<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MozambiquePayrollAccountingExportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MozambiquePayrollAccountingApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly MozambiquePayrollAccountingExportService $payrollAccountingExportService
    ) {}

    public function costAllocation(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('view-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $dataset = $this->payrollAccountingExportService->buildCostAllocationDataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        return $this->successResponse($dataset, 'Cost allocation dataset generated.');
    }

    public function journalLines(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('view-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $dataset = $this->payrollAccountingExportService->buildJournalLinesDataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        return $this->successResponse($dataset, 'Payroll accounting journal lines dataset generated.');
    }

    public function monthlySummary(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('view-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $dataset = $this->payrollAccountingExportService->buildMonthlyPayrollSummaryDataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        return $this->successResponse($dataset, 'Payroll monthly summary dataset generated.');
    }
}

