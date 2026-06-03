<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\CompanyFiscalProfile;
use App\Models\AccountingPeriod;
use App\Models\FiscalCalendarEvent;
use App\Models\FiscalExportHistory;
use App\Services\SaftExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FiscalProfileController extends Controller
{
    use ScePermissionChecks;

    public function index(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $profile = CompanyFiscalProfile::firstOrNew(['company_id' => creatorId()]);
        if (empty($profile->legal_name)) {
            $profile->legal_name = Auth::user()?->name;
        }
        if (empty($profile->taxpayer_type)) {
            $profile->taxpayer_type = 'ordinary';
        }
        if (empty($profile->state_of_certification)) {
            $profile->state_of_certification = 'not_certified';
        }
        if (empty($profile->software_certificate_number)) {
            $profile->software_certificate_number = '0';
        }

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
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'nuit' => 'required|string|size:9',
            'legal_name' => 'nullable|string|max:255',
            'fiscal_regime' => 'nullable|string|in:normal,simplified,exempt',
            'tax_regime' => 'nullable|string|in:normal,simplified,exempt', // backward compatibility
            'accounting_framework' => 'required|string|in:pgc_nirf,pgc_pe,ispc',
            'entity_classification' => 'nullable|string|in:large,medium,small,micro,ispc',
            'nirf_classification' => 'nullable|string|in:large,medium,small,micro,ispc', // backward compatibility
            'taxpayer_type' => 'nullable|string|max:80',
            'state_of_certification' => 'nullable|string|max:50',
            'software_certificate_number' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:50',
            'economic_activity_code' => 'nullable|string|max:20',
            'activity_code' => 'nullable|string|max:20', // backward compatibility
        ]);

        $currentProfile = CompanyFiscalProfile::where('company_id', creatorId())->first();

        $payload = [
            'nuit' => $validated['nuit'],
            'legal_name' => $validated['legal_name']
                ?? $currentProfile?->legal_name
                ?? Auth::user()?->name,
            'fiscal_regime' => $validated['fiscal_regime']
                ?? $validated['tax_regime']
                ?? 'normal',
            'accounting_framework' => $validated['accounting_framework'],
            'entity_classification' => $validated['entity_classification']
                ?? $validated['nirf_classification']
                ?? 'small',
            'taxpayer_type' => $validated['taxpayer_type']
                ?? $currentProfile?->taxpayer_type
                ?? 'ordinary',
            'state_of_certification' => $validated['state_of_certification']
                ?? $currentProfile?->state_of_certification
                ?? 'not_certified',
            'software_certificate_number' => $validated['software_certificate_number']
                ?? $currentProfile?->software_certificate_number
                ?? '0',
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

        FiscalCalendarEvent::generateForYear(creatorId(), (int) date('Y'));

        return back()->with('success', __('Perfil fiscal actualizado com sucesso.'));
    }

    public function generatePeriods(Request $request)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = $request->input('year', date('Y'));
        AccountingPeriod::generateForYear(creatorId(), (int) $year);
        return back()->with('success', __('Períodos gerados para o exercício :year.', ['year' => $year]));
    }

    // Fiscal Calendar
    public function calendar(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

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
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = $request->input('year', date('Y'));
        FiscalCalendarEvent::generateForYear(creatorId(), (int) $year);
        return back()->with('success', __('Calendário fiscal gerado para :year.', ['year' => $year]));
    }

    public function completeCalendarEvent(FiscalCalendarEvent $event)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($event->company_id !== creatorId()) {
            abort(403, __('Permission denied'));
        }

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
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $service = app(SaftExportService::class);
            $xsdRequired = (bool) config('sce.saft.require_xsd_validation', false);
            $xsdPath = (string) config('sce.saft.xsd_path', '');
            $xsdPathReady = $xsdPath !== '' && is_file($xsdPath) && is_readable($xsdPath);
            $filename = sprintf(
                'mozambique-saft-%s-to-%s.xml',
                $validated['start_date'],
                $validated['end_date']
            );
            $xml = $service->generate(
                creatorId(),
                $validated['start_date'],
                $validated['end_date']
            );
            $service->validateGeneratedXml($xml);

            $profile = CompanyFiscalProfile::where('company_id', creatorId())->first();
            $relativePath = sprintf(
                'fiscal-exports/%d/saft_xml/%s-%s-%s',
                creatorId(),
                now()->format('YmdHis'),
                substr(hash('sha256', $xml), 0, 12),
                $filename
            );
            Storage::disk('local')->put($relativePath, $xml);

            FiscalExportHistory::query()->create([
                'company_id' => creatorId(),
                'export_type' => 'saft_xml',
                'period_start' => $validated['start_date'],
                'period_end' => $validated['end_date'],
                'generated_by' => Auth::id(),
                'file_name' => $filename,
                'file_hash' => hash('sha256', $xml),
                'file_path' => $relativePath,
                'status' => 'generated',
                'metadata' => [
                    'content_type' => 'application/xml',
                    'validation' => [
                        'well_formed' => true,
                        'xsd_required' => $xsdRequired,
                        'xsd_path_configured' => $xsdPath !== '',
                        'xsd_path_ready' => $xsdPathReady,
                        'xsd_validated' => $xsdRequired && $xsdPathReady,
                        'source' => 'sce.fiscal.saft-export',
                    ],
                    'fiscal_year' => substr($validated['start_date'], 0, 4),
                    'xml_size_bytes' => strlen($xml),
                    'generated_at' => now()->toIso8601String(),
                    'profile_state_of_certification' => $profile?->state_of_certification,
                    'software_certificate_number' => $profile?->software_certificate_number,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
