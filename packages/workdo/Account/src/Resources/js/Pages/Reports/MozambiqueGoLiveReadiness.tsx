import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { RefreshCw, ShieldCheck } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate } from '@/utils/helpers';

interface ReadinessCheck {
    code: string;
    label: string;
    status: 'pass' | 'warn' | 'fail';
    critical: boolean;
    details: string;
    meta?: Record<string, unknown>;
}

interface ReadinessSummary {
    pass: number;
    warn: number;
    fail: number;
}

interface ReadinessPayload {
    generated_at: string;
    overall_status: 'ready' | 'attention' | 'blocked';
    summary: ReadinessSummary;
    checks: ReadinessCheck[];
    formal_go_live_criteria: {
        critical_checks_passed: boolean;
        legal_review_completed: boolean;
        commercial_readiness_completed: boolean;
        pilot_completed: boolean;
        pilot_registry_populated: boolean;
        pilot_real_companies_validated: boolean;
        payroll_sector_validation_completed: boolean;
        payroll_real_cases_validated: boolean;
        accounting_local_validation_completed: boolean;
        accounting_real_cases_validated: boolean;
        e2e_scenarios_completed: boolean;
        formal_approval_granted: boolean;
        recommended_for_launch: boolean;
    };
    attestations: {
        legal_review_status: string;
        legal_reviewed_at: string;
        legal_notes: string;
        commercial_readiness_status: string;
        commercial_reviewed_at: string;
        commercial_notes: string;
        pilot_status: string;
        pilot_completed_at: string;
        pilot_company_count: number;
        pilot_notes: string;
        payroll_sector_validation_status: string;
        payroll_sector_validation_completed_at: string;
        payroll_sector_validation_notes: string;
        accounting_local_validation_status: string;
        accounting_local_validation_completed_at: string;
        accounting_local_validation_notes: string;
        e2e_sales_flow_status: string;
        e2e_purchase_flow_status: string;
        e2e_pos_flow_status: string;
        e2e_payroll_flow_status: string;
        e2e_completed_at: string;
        e2e_notes: string;
        go_live_approved: string;
        go_live_approved_at: string;
        go_live_approval_notes: string;
    };
}

interface PilotCompany {
    id: number;
    company_name: string;
    company_nuit: string | null;
    industry_sector: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    status: 'planned' | 'active' | 'completed' | 'on_hold' | 'cancelled';
    pilot_start_date: string | null;
    pilot_end_date: string | null;
    validation_result: 'pending' | 'passed' | 'failed';
    validation_signed_at: string | null;
    validation_evidence_ref: string | null;
    validation_notes: string | null;
    validation_scope: string | null;
    notes: string | null;
}

interface ValidationCase {
    id: number;
    domain: 'payroll' | 'accounting';
    company_name: string;
    company_nuit: string | null;
    industry_sector: string | null;
    scenario_code: string | null;
    scenario_description: string | null;
    result: 'pending' | 'passed' | 'failed';
    executed_at: string | null;
    evidence_ref: string | null;
    notes: string | null;
}

const statusClassMap: Record<ReadinessCheck['status'], string> = {
    pass: 'bg-green-100 text-green-800',
    warn: 'bg-yellow-100 text-yellow-800',
    fail: 'bg-red-100 text-red-800',
};

export default function MozambiqueGoLiveReadiness() {
    const { t } = useTranslation();
    const [data, setData] = useState<ReadinessPayload | null>(null);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [pilotCompanies, setPilotCompanies] = useState<PilotCompany[]>([]);
    const [validationCases, setValidationCases] = useState<ValidationCase[]>([]);
    const [pilotLoading, setPilotLoading] = useState(false);
    const [validationLoading, setValidationLoading] = useState(false);
    const [pilotSaving, setPilotSaving] = useState(false);
    const [validationSaving, setValidationSaving] = useState(false);
    const [pilotForm, setPilotForm] = useState({
        company_name: '',
        company_nuit: '',
        industry_sector: '',
        contact_name: '',
        contact_email: '',
        contact_phone: '',
        status: 'planned',
        pilot_start_date: '',
        pilot_end_date: '',
        validation_result: 'pending',
        validation_signed_at: '',
        validation_evidence_ref: '',
        validation_notes: '',
        validation_scope: '',
        notes: '',
    });
    const [form, setForm] = useState<ReadinessPayload['attestations']>({
        legal_review_status: 'pending',
        legal_reviewed_at: '',
        legal_notes: '',
        commercial_readiness_status: 'pending',
        commercial_reviewed_at: '',
        commercial_notes: '',
        pilot_status: 'not_started',
        pilot_completed_at: '',
        pilot_company_count: 0,
        pilot_notes: '',
        payroll_sector_validation_status: 'not_started',
        payroll_sector_validation_completed_at: '',
        payroll_sector_validation_notes: '',
        accounting_local_validation_status: 'not_started',
        accounting_local_validation_completed_at: '',
        accounting_local_validation_notes: '',
        e2e_sales_flow_status: 'not_started',
        e2e_purchase_flow_status: 'not_started',
        e2e_pos_flow_status: 'not_started',
        e2e_payroll_flow_status: 'not_started',
        e2e_completed_at: '',
        e2e_notes: '',
        go_live_approved: 'off',
        go_live_approved_at: '',
        go_live_approval_notes: '',
    });
    const [validationForm, setValidationForm] = useState({
        domain: 'payroll',
        company_name: '',
        company_nuit: '',
        industry_sector: '',
        scenario_code: '',
        scenario_description: '',
        result: 'pending',
        executed_at: '',
        evidence_ref: '',
        notes: '',
    });

    const fetchData = async () => {
        setLoading(true);
        try {
            const [readinessResponse, pilotsResponse, validationCasesResponse] = await Promise.all([
                axios.get(route('account.reports.mozambique-go-live-readiness')),
                axios.get(route('account.reports.mozambique-go-live-readiness.pilot-companies.index')),
                axios.get(route('account.reports.mozambique-go-live-readiness.validation-cases.index')),
            ]);

            setData(readinessResponse.data);
            setPilotCompanies(pilotsResponse.data?.data ?? []);
            setValidationCases(validationCasesResponse.data?.data ?? []);

            if (readinessResponse.data?.attestations) {
                setForm({
                    ...readinessResponse.data.attestations,
                });
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchPilotCompanies = async () => {
        setPilotLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-go-live-readiness.pilot-companies.index'));
            setPilotCompanies(response.data?.data ?? []);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setPilotLoading(false);
        }
    };

    const fetchValidationCases = async () => {
        setValidationLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-go-live-readiness.validation-cases.index'));
            setValidationCases(response.data?.data ?? []);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setValidationLoading(false);
        }
    };

    const saveAttestation = async () => {
        setSaving(true);
        try {
            const response = await axios.post(route('account.reports.mozambique-go-live-readiness.attestation'), form);
            if (response.data?.data) {
                setData(response.data.data);
                if (response.data.data?.attestations) {
                    setForm({
                        ...response.data.data.attestations,
                    });
                }
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setSaving(false);
        }
    };

    const registerPilotCompany = async () => {
        if (!pilotForm.company_name.trim()) {
            return;
        }

        setPilotSaving(true);
        try {
            await axios.post(route('account.reports.mozambique-go-live-readiness.pilot-companies.store'), pilotForm);
            setPilotForm({
                company_name: '',
                company_nuit: '',
                industry_sector: '',
                contact_name: '',
                contact_email: '',
                contact_phone: '',
                status: 'planned',
                pilot_start_date: '',
                pilot_end_date: '',
                validation_result: 'pending',
                validation_signed_at: '',
                validation_evidence_ref: '',
                validation_notes: '',
                validation_scope: '',
                notes: '',
            });
            await Promise.all([fetchPilotCompanies(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setPilotSaving(false);
        }
    };

    const markPilotAsCompleted = async (pilot: PilotCompany) => {
        try {
            await axios.put(route('account.reports.mozambique-go-live-readiness.pilot-companies.update', pilot.id), {
                ...pilot,
                status: 'completed',
                pilot_end_date: pilot.pilot_end_date || new Date().toISOString().slice(0, 10),
            });
            await Promise.all([fetchPilotCompanies(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const removePilotCompany = async (pilotId: number) => {
        try {
            await axios.delete(route('account.reports.mozambique-go-live-readiness.pilot-companies.destroy', pilotId));
            await Promise.all([fetchPilotCompanies(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const registerValidationCase = async () => {
        if (!validationForm.company_name.trim()) {
            return;
        }

        setValidationSaving(true);
        try {
            await axios.post(route('account.reports.mozambique-go-live-readiness.validation-cases.store'), validationForm);
            setValidationForm({
                domain: 'payroll',
                company_name: '',
                company_nuit: '',
                industry_sector: '',
                scenario_code: '',
                scenario_description: '',
                result: 'pending',
                executed_at: '',
                evidence_ref: '',
                notes: '',
            });
            await Promise.all([fetchValidationCases(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setValidationSaving(false);
        }
    };

    const markValidationCaseAsPassed = async (validationCase: ValidationCase) => {
        try {
            await axios.put(route('account.reports.mozambique-go-live-readiness.validation-cases.update', validationCase.id), {
                ...validationCase,
                result: 'passed',
                executed_at: validationCase.executed_at || new Date().toISOString().slice(0, 10),
            });
            await Promise.all([fetchValidationCases(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const removeValidationCase = async (validationCaseId: number) => {
        try {
            await axios.delete(route('account.reports.mozambique-go-live-readiness.validation-cases.destroy', validationCaseId));
            await Promise.all([fetchValidationCases(), fetchData()]);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    useEffect(() => {
        // eslint-disable-next-line @typescript-eslint/no-floating-promises
        fetchData();
    }, []);

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h3 className="font-semibold text-lg">{t('Mozambique Go-Live Readiness')}</h3>
                        {data?.generated_at && (
                            <p className="text-sm text-gray-600">
                                {t('Last check')}: {formatDate(data.generated_at)}
                            </p>
                        )}
                    </div>
                    <Button onClick={fetchData} disabled={loading} size="sm" className="gap-2">
                        <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        {loading ? t('Checking...') : t('Run Check')}
                    </Button>
                </div>
            </CardContent>

            <CardContent className="p-6">
                {!data ? (
                    <NoRecordsFound
                        icon={ShieldCheck}
                        title={t('Go-Live Readiness')}
                        description={t('Run the readiness check to see compliance and operational blockers')}
                        className="h-auto py-8"
                    />
                ) : (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="rounded-lg border p-4 bg-white">
                                <p className="text-sm text-gray-500">{t('Overall Status')}</p>
                                <p className="mt-1 font-semibold capitalize">{data.overall_status}</p>
                            </div>
                            <div className="rounded-lg border p-4 bg-white">
                                <p className="text-sm text-gray-500">{t('Passed Checks')}</p>
                                <p className="mt-1 font-semibold text-green-700">{data.summary.pass}</p>
                            </div>
                            <div className="rounded-lg border p-4 bg-white">
                                <p className="text-sm text-gray-500">{t('Warnings')}</p>
                                <p className="mt-1 font-semibold text-yellow-700">{data.summary.warn}</p>
                            </div>
                            <div className="rounded-lg border p-4 bg-white">
                                <p className="text-sm text-gray-500">{t('Failed Checks')}</p>
                                <p className="mt-1 font-semibold text-red-700">{data.summary.fail}</p>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4 bg-white space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <h4 className="font-semibold">{t('Formal Go-Live Criteria')}</h4>
                                <span className={`inline-flex px-2 py-1 rounded-full text-xs font-semibold ${data.formal_go_live_criteria.recommended_for_launch ? statusClassMap.pass : statusClassMap.fail}`}>
                                    {data.formal_go_live_criteria.recommended_for_launch ? t('Recommended') : t('Not Ready')}
                                </span>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Critical checks')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.critical_checks_passed ? t('Passed') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Pilot status')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.pilot_completed ? t('Completed') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Pilot registry')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.pilot_registry_populated ? t('Populated') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Pilot real evidence')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.pilot_real_companies_validated ? t('Validated') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('E2E scenarios')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.e2e_scenarios_completed ? t('Completed') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Payroll sector validation')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.payroll_sector_validation_completed ? t('Completed') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Payroll real cases')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.payroll_real_cases_validated ? t('Validated') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Accounting local validation')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.accounting_local_validation_completed ? t('Completed') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Accounting real cases')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.accounting_real_cases_validated ? t('Validated') : t('Pending')}</p>
                                </div>
                                <div className="border rounded p-3">
                                    <p className="text-gray-500">{t('Formal approval')}</p>
                                    <p className="font-medium">{data.formal_go_live_criteria.formal_approval_granted ? t('Granted') : t('Missing')}</p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4 bg-white space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <h4 className="font-semibold">{t('Pilot Validation Cases (Payroll/Accounting)')}</h4>
                                <Button size="sm" variant="outline" onClick={fetchValidationCases} disabled={validationLoading}>
                                    {validationLoading ? t('Loading...') : t('Refresh')}
                                </Button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <select
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={validationForm.domain}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, domain: e.target.value }))}
                                >
                                    <option value="payroll">{t('Payroll')}</option>
                                    <option value="accounting">{t('Accounting')}</option>
                                </select>
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Company name')}
                                    value={validationForm.company_name}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, company_name: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Company NUIT')}
                                    value={validationForm.company_nuit}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, company_nuit: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Industry sector')}
                                    value={validationForm.industry_sector}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, industry_sector: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Scenario code')}
                                    value={validationForm.scenario_code}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, scenario_code: e.target.value }))}
                                />
                                <select
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={validationForm.result}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, result: e.target.value }))}
                                >
                                    <option value="pending">{t('Pending')}</option>
                                    <option value="passed">{t('Passed')}</option>
                                    <option value="failed">{t('Failed')}</option>
                                </select>
                                <input
                                    type="date"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={validationForm.executed_at}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, executed_at: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Evidence reference')}
                                    value={validationForm.evidence_ref}
                                    onChange={(e) => setValidationForm((prev) => ({ ...prev, evidence_ref: e.target.value }))}
                                />
                                <Button size="sm" onClick={registerValidationCase} disabled={validationSaving}>
                                    {validationSaving ? t('Saving...') : t('Add Validation Case')}
                                </Button>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-gray-50">
                                            <th className="px-3 py-2 text-left">{t('Domain')}</th>
                                            <th className="px-3 py-2 text-left">{t('Company')}</th>
                                            <th className="px-3 py-2 text-left">{t('Sector')}</th>
                                            <th className="px-3 py-2 text-left">{t('Result')}</th>
                                            <th className="px-3 py-2 text-left">{t('Evidence')}</th>
                                            <th className="px-3 py-2 text-left">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {validationCases.map((validationCase) => (
                                            <tr key={validationCase.id} className="border-b">
                                                <td className="px-3 py-2">{validationCase.domain}</td>
                                                <td className="px-3 py-2 font-medium">{validationCase.company_name}</td>
                                                <td className="px-3 py-2">{validationCase.industry_sector || '-'}</td>
                                                <td className="px-3 py-2">{validationCase.result}</td>
                                                <td className="px-3 py-2">
                                                    {validationCase.executed_at || '-'}
                                                    {validationCase.evidence_ref ? ` • ${validationCase.evidence_ref}` : ''}
                                                </td>
                                                <td className="px-3 py-2 space-x-2">
                                                    {validationCase.result !== 'passed' && (
                                                        <Button size="sm" variant="outline" onClick={() => markValidationCaseAsPassed(validationCase)}>
                                                            {t('Mark Passed')}
                                                        </Button>
                                                    )}
                                                    <Button size="sm" variant="destructive" onClick={() => removeValidationCase(validationCase.id)}>
                                                        {t('Remove')}
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4 bg-white space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <h4 className="font-semibold">{t('Pilot Company Registry')}</h4>
                                <Button size="sm" variant="outline" onClick={fetchPilotCompanies} disabled={pilotLoading}>
                                    {pilotLoading ? t('Loading...') : t('Refresh')}
                                </Button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Company name')}
                                    value={pilotForm.company_name}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, company_name: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Industry sector')}
                                    value={pilotForm.industry_sector}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, industry_sector: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Company NUIT')}
                                    value={pilotForm.company_nuit}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, company_nuit: e.target.value }))}
                                />
                                <select
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={pilotForm.status}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, status: e.target.value }))}
                                >
                                    <option value="planned">{t('Planned')}</option>
                                    <option value="active">{t('Active')}</option>
                                    <option value="completed">{t('Completed')}</option>
                                    <option value="on_hold">{t('On Hold')}</option>
                                    <option value="cancelled">{t('Cancelled')}</option>
                                </select>
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Contact name')}
                                    value={pilotForm.contact_name}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, contact_name: e.target.value }))}
                                />
                                <input
                                    type="email"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Contact email')}
                                    value={pilotForm.contact_email}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, contact_email: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Contact phone')}
                                    value={pilotForm.contact_phone}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, contact_phone: e.target.value }))}
                                />
                                <input
                                    type="date"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={pilotForm.pilot_start_date}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, pilot_start_date: e.target.value }))}
                                />
                                <input
                                    type="date"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={pilotForm.pilot_end_date}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, pilot_end_date: e.target.value }))}
                                />
                                <select
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={pilotForm.validation_result}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, validation_result: e.target.value }))}
                                >
                                    <option value="pending">{t('Validation Pending')}</option>
                                    <option value="passed">{t('Validation Passed')}</option>
                                    <option value="failed">{t('Validation Failed')}</option>
                                </select>
                                <input
                                    type="date"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    value={pilotForm.validation_signed_at}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, validation_signed_at: e.target.value }))}
                                />
                                <input
                                    type="text"
                                    className="w-full border rounded px-3 py-2 text-sm"
                                    placeholder={t('Evidence reference')}
                                    value={pilotForm.validation_evidence_ref}
                                    onChange={(e) => setPilotForm((prev) => ({ ...prev, validation_evidence_ref: e.target.value }))}
                                />
                                <Button size="sm" onClick={registerPilotCompany} disabled={pilotSaving}>
                                    {pilotSaving ? t('Saving...') : t('Add Pilot Company')}
                                </Button>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-gray-50">
                                            <th className="px-3 py-2 text-left">{t('Company')}</th>
                                            <th className="px-3 py-2 text-left">{t('Sector')}</th>
                                            <th className="px-3 py-2 text-left">{t('Status')}</th>
                                            <th className="px-3 py-2 text-left">{t('Validation')}</th>
                                            <th className="px-3 py-2 text-left">{t('Contact')}</th>
                                            <th className="px-3 py-2 text-left">{t('Dates')}</th>
                                            <th className="px-3 py-2 text-left">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pilotCompanies.map((pilot) => (
                                            <tr key={pilot.id} className="border-b">
                                                <td className="px-3 py-2 font-medium">{pilot.company_name}</td>
                                                <td className="px-3 py-2">{pilot.industry_sector || '-'}</td>
                                                <td className="px-3 py-2">{pilot.status}</td>
                                                <td className="px-3 py-2">
                                                    {pilot.validation_result}
                                                    {pilot.validation_signed_at ? ` • ${pilot.validation_signed_at}` : ''}
                                                    {pilot.validation_evidence_ref ? ` • ${pilot.validation_evidence_ref}` : ''}
                                                </td>
                                                <td className="px-3 py-2">{pilot.contact_name || pilot.contact_email || '-'}</td>
                                                <td className="px-3 py-2">
                                                    {(pilot.pilot_start_date || '-') + ' → ' + (pilot.pilot_end_date || '-')}
                                                </td>
                                                <td className="px-3 py-2 space-x-2">
                                                    {pilot.status !== 'completed' && (
                                                        <Button size="sm" variant="outline" onClick={() => markPilotAsCompleted(pilot)}>
                                                            {t('Mark Completed')}
                                                        </Button>
                                                    )}
                                                    <Button size="sm" variant="destructive" onClick={() => removePilotCompany(pilot.id)}>
                                                        {t('Remove')}
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4 bg-white space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <h4 className="font-semibold">{t('Legal and Commercial Attestation')}</h4>
                                <Button size="sm" onClick={saveAttestation} disabled={saving} className="gap-2">
                                    {saving ? t('Saving...') : t('Save Attestation')}
                                </Button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Legal Review Status')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.legal_review_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, legal_review_status: e.target.value }))}
                                    >
                                        <option value="pending">{t('Pending')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="approved">{t('Approved')}</option>
                                        <option value="rejected">{t('Rejected')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Legal Review Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.legal_reviewed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, legal_reviewed_at: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Commercial Readiness Status')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.commercial_readiness_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, commercial_readiness_status: e.target.value }))}
                                    >
                                        <option value="pending">{t('Pending')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="approved">{t('Approved')}</option>
                                        <option value="rejected">{t('Rejected')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Commercial Review Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.commercial_reviewed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, commercial_reviewed_at: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Pilot Status')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.pilot_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, pilot_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Pilot Completion Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.pilot_completed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, pilot_completed_at: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Pilot Company Count')}</label>
                                    <input
                                        type="number"
                                        min={0}
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.pilot_company_count ?? 0}
                                        onChange={(e) => setForm((prev) => ({ ...prev, pilot_company_count: Number(e.target.value || 0) }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Payroll Sector Validation')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.payroll_sector_validation_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, payroll_sector_validation_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Payroll Sector Validation Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.payroll_sector_validation_completed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, payroll_sector_validation_completed_at: e.target.value }))}
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-xs text-gray-500 mb-1">{t('Payroll Sector Validation Notes')}</label>
                                    <textarea
                                        className="w-full border rounded px-3 py-2 text-sm min-h-[84px]"
                                        value={form.payroll_sector_validation_notes || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, payroll_sector_validation_notes: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Accounting Local Validation')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.accounting_local_validation_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, accounting_local_validation_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Accounting Local Validation Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.accounting_local_validation_completed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, accounting_local_validation_completed_at: e.target.value }))}
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-xs text-gray-500 mb-1">{t('Accounting Local Validation Notes')}</label>
                                    <textarea
                                        className="w-full border rounded px-3 py-2 text-sm min-h-[84px]"
                                        value={form.accounting_local_validation_notes || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, accounting_local_validation_notes: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E Sales Flow')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.e2e_sales_flow_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_sales_flow_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E Purchase Flow')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.e2e_purchase_flow_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_purchase_flow_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E POS Flow')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.e2e_pos_flow_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_pos_flow_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E Payroll Flow')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.e2e_payroll_flow_status}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_payroll_flow_status: e.target.value }))}
                                    >
                                        <option value="not_started">{t('Not Started')}</option>
                                        <option value="in_progress">{t('In Progress')}</option>
                                        <option value="completed">{t('Completed')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E Completion Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.e2e_completed_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_completed_at: e.target.value }))}
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-xs text-gray-500 mb-1">{t('E2E Notes')}</label>
                                    <textarea
                                        className="w-full border rounded px-3 py-2 text-sm min-h-[84px]"
                                        value={form.e2e_notes || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, e2e_notes: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Formal Approval')}</label>
                                    <select
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.go_live_approved}
                                        onChange={(e) => setForm((prev) => ({ ...prev, go_live_approved: e.target.value }))}
                                    >
                                        <option value="off">{t('Not Approved')}</option>
                                        <option value="on">{t('Approved')}</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">{t('Formal Approval Date')}</label>
                                    <input
                                        type="date"
                                        className="w-full border rounded px-3 py-2 text-sm"
                                        value={form.go_live_approved_at || ''}
                                        onChange={(e) => setForm((prev) => ({ ...prev, go_live_approved_at: e.target.value }))}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-3 py-2 text-left">{t('Check')}</th>
                                        <th className="px-3 py-2 text-left">{t('Status')}</th>
                                        <th className="px-3 py-2 text-left">{t('Critical')}</th>
                                        <th className="px-3 py-2 text-left">{t('Details')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.checks.map((check) => (
                                        <tr key={check.code} className="border-b">
                                            <td className="px-3 py-2 font-medium">{check.label}</td>
                                            <td className="px-3 py-2">
                                                <span className={`inline-flex px-2 py-1 rounded-full text-xs font-semibold ${statusClassMap[check.status]}`}>
                                                    {check.status}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2">{check.critical ? t('Yes') : t('No')}</td>
                                            <td className="px-3 py-2 text-gray-700">{check.details}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
