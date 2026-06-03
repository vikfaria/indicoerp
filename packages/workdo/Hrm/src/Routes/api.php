<?php

use Illuminate\Support\Facades\Route;
use Workdo\Hrm\Http\Controllers\Api\AttendanceApiController;
use Workdo\Hrm\Http\Controllers\Api\BiometricAttendanceIngestController;
use Workdo\Hrm\Http\Controllers\Api\DashboardApiController;
use Workdo\Hrm\Http\Controllers\Api\HolidayApiController;
use Workdo\Hrm\Http\Controllers\Api\LeaveApiController;
use Workdo\Hrm\Http\Controllers\Api\LeaveTypeApiController;
use Workdo\Hrm\Http\Controllers\Api\MozambiquePayrollAccountingApiController;
use Workdo\Hrm\Http\Controllers\Api\MozambiquePayrollComplianceImportApiController;
use Workdo\Hrm\Http\Controllers\Api\MozambiquePayrollSubmissionApiController;

Route::prefix('api')->middleware(['api.json'])->group(function () {
    Route::post('hrm/attendance/device-ingest', [BiometricAttendanceIngestController::class, 'ingest']);

    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'hrm'], function () {
        Route::get('home', [DashboardApiController::class, 'index']);
        Route::post('events', [DashboardApiController::class, 'getEvents']);
        Route::get('holidays-list', [HolidayApiController::class, 'index']);
        
        Route::post('attendence-history', [AttendanceApiController::class, 'history']);
        Route::post('clock-in-out', [AttendanceApiController::class, 'clockInOut']);
        
        Route::get('get-leaves', [LeaveApiController::class, 'index']);
        Route::post('leave-request', [LeaveApiController::class, 'store']);
        
        Route::get('get-leaves-types', [LeaveTypeApiController::class, 'index']);

        Route::get('payroll-accounting/cost-allocation', [MozambiquePayrollAccountingApiController::class, 'costAllocation']);
        Route::get('payroll-accounting/journal-lines', [MozambiquePayrollAccountingApiController::class, 'journalLines']);
        Route::get('payroll-accounting/monthly-summary', [MozambiquePayrollAccountingApiController::class, 'monthlySummary']);
        Route::get('payroll-submission/modelo19', [MozambiquePayrollSubmissionApiController::class, 'modelo19']);
        Route::get('payroll-submission/inss', [MozambiquePayrollSubmissionApiController::class, 'inss']);
        Route::get('payroll-submission/bank-payment-file', [MozambiquePayrollSubmissionApiController::class, 'bankPaymentFile']);
        Route::get('payroll-submission/annual-fiscal-history', [MozambiquePayrollSubmissionApiController::class, 'annualFiscalHistory']);

        Route::post('payroll-compliance/import/workforce', [MozambiquePayrollComplianceImportApiController::class, 'importWorkforce']);
        Route::post('payroll-compliance/import/attendance', [MozambiquePayrollComplianceImportApiController::class, 'importAttendance']);
        Route::post('payroll-compliance/import/annual-leave-plans', [MozambiquePayrollComplianceImportApiController::class, 'importAnnualLeavePlans']);
    });
});
