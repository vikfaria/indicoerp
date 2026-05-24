<?php

namespace App\Http\Controllers;

use App\Models\CompanyFiscalProfile;
use App\Models\AccountingPeriod;
use App\Models\FiscalCalendarEvent;
use App\Services\SaftExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FiscalProfileController extends Controller
{
    public function index(Request $request)
    {
        $profile = CompanyFiscalProfile::firstOrNew(['company_id' => creatorId()]);

        $periods = AccountingPeriod::where('company_id', creatorId())
            ->orderByDesc('fiscal_year')
            ->orderBy('period_number')
            ->get();

        return Inertia::render('Fiscal/Profile/Index', [
            'profile' => $profile,
            'periods' => $periods,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nuit' => 'required|string|size:9',
            'fiscal_regime' => 'nullable|string|in:normal,simplified,exempt',
            'tax_regime' => 'nullable|string|in:normal,simplified,exempt', // backward compatibility
            'accounting_framework' => 'required|string|in:pgc_nirf,pgc_pe,ispc',
            'entity_classification' => 'nullable|string|in:large,medium,small,micro,ispc',
            'nirf_classification' => 'nullable|string|in:large,medium,small,micro,ispc', // backward compatibility
            'province' => 'nullable|string|max:50',
            'economic_activity_code' => 'nullable|string|max:20',
            'activity_code' => 'nullable|string|max:20', // backward compatibility
        ]);

        $payload = [
            'nuit' => $validated['nuit'],
            'fiscal_regime' => $validated['fiscal_regime']
                ?? $validated['tax_regime']
                ?? 'normal',
            'accounting_framework' => $validated['accounting_framework'],
            'entity_classification' => $validated['entity_classification']
                ?? $validated['nirf_classification']
                ?? 'small',
            'province' => $validated['province'] ?? null,
            'economic_activity_code' => $validated['economic_activity_code']
                ?? $validated['activity_code']
                ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ];

        CompanyFiscalProfile::updateOrCreate(
            ['company_id' => creatorId()],
            $payload
        );

        return back()->with('success', __('Perfil fiscal actualizado com sucesso.'));
    }

    public function generatePeriods(Request $request)
    {
        $year = $request->input('year', date('Y'));
        AccountingPeriod::generateForYear(creatorId(), (int) $year);
        return back()->with('success', __('Períodos gerados para o exercício :year.', ['year' => $year]));
    }

    // Fiscal Calendar
    public function calendar(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $events = FiscalCalendarEvent::where('company_id', creatorId())
            ->whereYear('due_date', $year)
            ->orderBy('due_date')
            ->get();

        return Inertia::render('Fiscal/Calendar/Index', [
            'events' => $events,
            'year' => (int) $year,
        ]);
    }

    public function generateCalendar(Request $request)
    {
        $year = $request->input('year', date('Y'));
        FiscalCalendarEvent::generateForYear(creatorId(), (int) $year);
        return back()->with('success', __('Calendário fiscal gerado para :year.', ['year' => $year]));
    }

    public function completeCalendarEvent(FiscalCalendarEvent $event)
    {
        $event->update([
            'status' => 'completed',
            'completed_date' => now()->toDateString(),
            'completed_by' => Auth::id(),
        ]);
        return back()->with('success', __('Obrigação fiscal concluída.'));
    }

    // SAF-T Export
    public function exportSaft(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $service = app(SaftExportService::class);
            $path = $service->exportToFile(creatorId(), $validated['start_date'], $validated['end_date']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/xml',
        ])->deleteFileAfterSend(true);
    }
}
