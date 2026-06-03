<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MozambiquePayrollSubmissionReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MozambiquePayrollSubmissionApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly MozambiquePayrollSubmissionReportService $payrollSubmissionReportService
    ) {}

    public function modelo19(Request $request)
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

        $dataset = $this->payrollSubmissionReportService->buildModelo19Dataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        return $this->successResponse($dataset, 'Modelo 19 dataset generated.');
    }

    public function inss(Request $request)
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

        $dataset = $this->payrollSubmissionReportService->buildInssDataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        return $this->successResponse($dataset, 'INSS dataset generated.');
    }

    public function bankPaymentFile(Request $request)
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

        $dataset = $this->payrollSubmissionReportService->buildBankPaymentDataset(
            creatorId(),
            $validator->validated()['reference_period'] ?? null
        );

        if (!Auth::user()->can('view-sensitive-employee-data')) {
            $dataset['rows'] = collect((array) ($dataset['rows'] ?? []))
                ->map(function (array $row): array {
                    $row['employee_nuit'] = $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2);
                    $row['account_holder_name'] = $this->maskPersonName((string) ($row['account_holder_name'] ?? ''));
                    $row['bank_identifier_code'] = $this->maskIdentifier((string) ($row['bank_identifier_code'] ?? ''), 2, 2);
                    $row['account_number'] = $this->maskIdentifier((string) ($row['account_number'] ?? ''), 0, 4);

                    return $row;
                })
                ->values()
                ->all();
        }

        return $this->successResponse($dataset, 'Payroll bank payment dataset generated.');
    }

    public function annualFiscalHistory(Request $request)
    {
        if (!Auth::user() || !Auth::user()->can('view-payrolls')) {
            return $this->errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'fiscal_year' => 'nullable|digits:4|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $dataset = $this->payrollSubmissionReportService->buildAnnualFiscalHistoryDataset(
            creatorId(),
            isset($validator->validated()['fiscal_year'])
                ? (string) $validator->validated()['fiscal_year']
                : null
        );

        return $this->successResponse($dataset, 'Annual fiscal history dataset generated.');
    }

    private function maskIdentifier(string $value, int $visiblePrefix = 2, int $visibleSuffix = 2): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $length = strlen($clean);
        if ($length <= ($visiblePrefix + $visibleSuffix)) {
            return str_repeat('*', $length);
        }

        $maskedLength = max(4, $length - ($visiblePrefix + $visibleSuffix));

        return substr($clean, 0, $visiblePrefix)
            . str_repeat('*', $maskedLength)
            . substr($clean, -$visibleSuffix);
    }

    private function maskPersonName(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $firstChar = substr($clean, 0, 1);

        return sprintf('%s%s', $firstChar, str_repeat('*', max(3, strlen($clean) - 1)));
    }
}

