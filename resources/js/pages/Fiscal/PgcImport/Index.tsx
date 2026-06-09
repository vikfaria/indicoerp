import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Download, CheckCircle2, AlertTriangle, ChevronDown, ChevronRight, Upload, Search } from 'lucide-react';
import { useState } from 'react';

interface Account {
    code: string; name: string; parent: string | null; level: number;
    movement: boolean; balance: string; fs_line: string;
}
interface ClassGroup { class: number; name: string; count: number; accounts: Account[]; }
interface ValidationReport {
    framework: string;
    profile_framework: string | null;
    catalog_count: number;
    company_pgc_count: number;
    legacy_active_count: number;
    missing_classes: number[];
    missing_codes: string[];
    extra_codes: string[];
    class_coverage: Array<{
        class: number;
        label: string;
        official_count: number;
        company_count: number;
    }>;
    warnings: string[];
    errors: string[];
    valid: boolean;
}

interface PageProps extends Record<string, unknown> {
    catalog: ClassGroup[];
    totalCatalog: number;
    importedCount: number;
    validationReport: ValidationReport;
    framework: string;
}

function ValidationSummaryCard({
    label,
    value,
    helper,
    tone = 'slate',
}: {
    label: string;
    value: string | number;
    helper: string;
    tone?: 'slate' | 'amber' | 'red' | 'blue' | 'green';
}) {
    const toneClasses: Record<string, { border: string; bg: string; label: string; value: string; helper: string }> = {
        slate: { border: 'border-slate-200', bg: 'bg-slate-50/70', label: 'text-slate-600', value: 'text-slate-900', helper: 'text-slate-500' },
        amber: { border: 'border-amber-200', bg: 'bg-amber-50/70', label: 'text-amber-700', value: 'text-amber-900', helper: 'text-amber-600' },
        red: { border: 'border-red-200', bg: 'bg-red-50/70', label: 'text-red-700', value: 'text-red-900', helper: 'text-red-600' },
        blue: { border: 'border-blue-200', bg: 'bg-blue-50/70', label: 'text-blue-700', value: 'text-blue-900', helper: 'text-blue-600' },
        green: { border: 'border-green-200', bg: 'bg-green-50/70', label: 'text-green-700', value: 'text-green-900', helper: 'text-green-600' },
    };

    const toneStyle = toneClasses[tone];

    return (
        <div className={`rounded-xl border ${toneStyle.border} ${toneStyle.bg} p-4`}>
            <p className={`text-xs font-medium uppercase tracking-[0.18em] ${toneStyle.label}`}>{label}</p>
            <p className={`mt-2 text-2xl font-bold ${toneStyle.value}`}>{value}</p>
            <p className={`mt-1 text-xs leading-snug ${toneStyle.helper}`}>{helper}</p>
        </div>
    );
}

export default function PgcImportIndex() {
    const { t } = useTranslation();
    const { catalog, totalCatalog, importedCount, validationReport, framework } = usePage<PageProps>().props;
    const issues = validationReport?.errors ?? [];
    const warnings = validationReport?.warnings ?? [];
    const profileFramework = validationReport?.profile_framework ?? null;
    const missingClassLabels = validationReport?.missing_classes
        .map((classNumber) => validationReport.class_coverage.find((item) => item.class === classNumber)?.label ?? `${classNumber}`)
        .filter((label): label is string => Boolean(label));
    const previewCodes = (codes: string[]) => {
        if (codes.length === 0) {
            return t('Nenhuma');
        }

        const preview = codes.slice(0, 4).join(', ');
        return codes.length > 4 ? `${preview}…` : preview;
    };

    const [expanded, setExpanded] = useState<number[]>([]);
    const [search, setSearch] = useState('');

    const toggle = (c: number) => setExpanded(prev => prev.includes(c) ? prev.filter(x => x !== c) : [...prev, c]);
    const importPgc = () => router.post(route('sce.fiscal.pgc.import'), { framework });
    const validatePgc = () => router.post(route('sce.fiscal.pgc.validate'));

    const classColors: Record<number, string> = {
        0: 'from-gray-50 to-gray-100 border-gray-200',
        1: 'from-blue-50 to-blue-100 border-blue-200',
        2: 'from-purple-50 to-purple-100 border-purple-200',
        3: 'from-orange-50 to-orange-100 border-orange-200',
        4: 'from-teal-50 to-teal-100 border-teal-200',
        5: 'from-indigo-50 to-indigo-100 border-indigo-200',
        6: 'from-red-50 to-red-100 border-red-200',
        7: 'from-green-50 to-green-100 border-green-200',
        8: 'from-yellow-50 to-yellow-100 border-yellow-200',
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Fiscal') }, { label: t('Plano de Contas PGC') }]}
            pageTitle={t('Importação do Plano de Contas PGC-MZ')}
            pageActions={
                <div className="flex gap-2">
                    <Button size="sm" variant="outline" onClick={validatePgc}>
                        <Search className="h-4 w-4 mr-1" /> {t('Validar')}
                    </Button>
                    <Button size="sm" onClick={importPgc}>
                        <Upload className="h-4 w-4 mr-1" /> {t('Importar PGC-NIRF')}
                    </Button>
                </div>
            }
        >
            <Head title={t('Plano de Contas PGC')} />
            <AccountingSuiteNavigation section="fiscal" className="mb-4" />

            {/* Summary cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <Download className="h-8 w-8 text-blue-500" />
                            <div>
                                <p className="text-xs text-blue-600">{t('Catálogo PGC-NIRF')}</p>
                                <p className="text-2xl font-bold text-blue-800">{totalCatalog}</p>
                                <p className="text-xs text-blue-500">{t('contas no catálogo')}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className={`bg-gradient-to-br ${importedCount > 0 ? 'from-green-50 to-green-100 border-green-200' : 'from-yellow-50 to-yellow-100 border-yellow-200'}`}>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <CheckCircle2 className={`h-8 w-8 ${importedCount > 0 ? 'text-green-500' : 'text-yellow-500'}`} />
                            <div>
                                <p className={`text-xs ${importedCount > 0 ? 'text-green-600' : 'text-yellow-600'}`}>{t('Importadas')}</p>
                                <p className={`text-2xl font-bold ${importedCount > 0 ? 'text-green-800' : 'text-yellow-800'}`}>{importedCount}</p>
                                <p className={`text-xs ${importedCount > 0 ? 'text-green-500' : 'text-yellow-500'}`}>{t('contas na empresa')}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className={`bg-gradient-to-br ${issues.length === 0 ? 'from-green-50 to-green-100 border-green-200' : 'from-red-50 to-red-100 border-red-200'}`}>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            {issues.length === 0
                                ? <CheckCircle2 className="h-8 w-8 text-green-500" />
                                : <AlertTriangle className="h-8 w-8 text-red-500" />}
                            <div>
                                <p className={`text-xs ${issues.length === 0 ? 'text-green-600' : 'text-red-600'}`}>{t('Validação')}</p>
                                <p className={`text-2xl font-bold ${issues.length === 0 ? 'text-green-800' : 'text-red-800'}`}>
                                    {issues.length === 0 ? '✓' : issues.length}
                                </p>
                                <p className={`text-xs ${issues.length === 0 ? 'text-green-500' : 'text-red-500'}`}>
                                    {issues.length === 0 ? t('Estrutura válida') : t('problemas')}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {warnings.length > 0 && issues.length === 0 && (
                <Card className="mb-6 border-amber-200 bg-amber-50/30">
                    <CardHeader className="py-3">
                        <CardTitle className="text-sm text-amber-700 flex items-center gap-2">
                            <AlertTriangle className="h-4 w-4" /> {t('Avisos')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="divide-y divide-amber-100">
                            {warnings.map((warning) => (
                                <div key={warning} className="px-4 py-2 text-sm text-amber-700">⚠ {warning}</div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {(issues.length > 0 || warnings.length > 0) && (
                <Card className="mb-6 border-slate-200 bg-slate-50/50">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm text-slate-900 flex items-center gap-2">
                            <AlertTriangle className="h-4 w-4 text-slate-600" />
                            {t('Como ler esta validação')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-slate-600 leading-relaxed">
                            {issues.length > 0
                                ? t('A validação encontrou :count problemas. O número inclui classes em falta, contas oficiais em falta e contas fora do catálogo. Use os cartões abaixo para perceber exactamente o que precisa de ser corrigido.', { count: issues.length })
                                : t('A estrutura está válida, mas existem avisos de acompanhamento que devem ser revistos antes do fecho.')
                            }
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                            <ValidationSummaryCard
                                tone={issues.length > 0 ? 'red' : 'green'}
                                label={t('Problemas totais')}
                                value={issues.length}
                                helper={issues.length > 0 ? t('Regras de validação falhadas') : t('Nenhum erro estrutural')}
                            />
                            <ValidationSummaryCard
                                tone={validationReport.missing_classes.length > 0 ? 'amber' : 'green'}
                                label={t('Classes em falta')}
                                value={validationReport.missing_classes.length}
                                helper={validationReport.missing_classes.length > 0
                                    ? `${missingClassLabels.slice(0, 2).join(', ')}${missingClassLabels.length > 2 ? '…' : ''}`
                                    : t('Todas as classes obrigatórias estão presentes')}
                            />
                            <ValidationSummaryCard
                                tone={validationReport.missing_codes.length > 0 ? 'amber' : 'green'}
                                label={t('Contas oficiais em falta')}
                                value={validationReport.missing_codes.length}
                                helper={validationReport.missing_codes.length > 0 ? previewCodes(validationReport.missing_codes) : t('Catálogo importado completo')}
                            />
                            <ValidationSummaryCard
                                tone={validationReport.extra_codes.length > 0 ? 'amber' : 'green'}
                                label={t('Contas fora do catálogo')}
                                value={validationReport.extra_codes.length}
                                helper={validationReport.extra_codes.length > 0 ? previewCodes(validationReport.extra_codes) : t('Sem desvios adicionais')}
                            />
                        </div>
                        {warnings.length > 0 && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
                                <p className="text-sm font-medium text-amber-900">{t('Avisos de contexto')}</p>
                                <ul className="mt-2 space-y-1 text-sm text-amber-800">
                                    {warnings.slice(0, 3).map((warning) => (
                                        <li key={warning}>• {warning}</li>
                                    ))}
                                </ul>
                                {warnings.length > 3 && (
                                    <p className="mt-2 text-xs text-amber-700">
                                        {t('Mostrando apenas os 3 primeiros avisos.')}
                                    </p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Validation issues */}
            {issues.length > 0 && (
                <Card className="mb-6 border-red-200 bg-red-50/30">
                    <CardHeader className="py-3">
                        <CardTitle className="text-sm text-red-700 flex items-center gap-2">
                            <AlertTriangle className="h-4 w-4" /> {t('Problemas de validação detalhados')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="divide-y divide-red-100">
                            {issues.map((issue, i) => (
                                <div key={i} className="px-4 py-2 text-sm text-red-700">⚠ {issue}</div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {profileFramework && profileFramework !== framework && (
                <Card className="mb-6 border-amber-200 bg-amber-50/40">
                    <CardContent className="p-4 text-sm text-amber-900">
                        {t('The active fiscal profile suggests :profile, but this page is validating :framework.', {
                            profile: profileFramework,
                            framework,
                        })}
                    </CardContent>
                </Card>
            )}

            {/* Search */}
            <Card className="mb-4">
                <CardContent className="p-3">
                    <input
                        type="text" value={search}
                        onChange={e => setSearch(e.target.value)}
                        placeholder={t('Pesquisar contas por código ou nome...')}
                        className="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    />
                </CardContent>
            </Card>

            {/* Classes */}
            <div className="space-y-3">
                {catalog.map(group => {
                    const isOpen = expanded.includes(group.class);
                    const filtered = search
                        ? group.accounts.filter(a =>
                            a.code.includes(search) || a.name.toLowerCase().includes(search.toLowerCase()))
                        : group.accounts;

                    if (search && filtered.length === 0) return null;

                    return (
                        <Card key={group.class} className={`bg-gradient-to-br ${classColors[group.class] || classColors[0]}`}>
                            <CardContent className="p-0">
                                <button
                                    onClick={() => toggle(group.class)}
                                    className="w-full flex items-center justify-between p-4 hover:bg-black/5 transition-colors rounded-lg"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-white/60 flex items-center justify-center text-lg font-bold">
                                            {group.class}
                                        </div>
                                        <div className="text-left">
                                            <p className="font-semibold text-sm">{group.name}</p>
                                            <p className="text-xs text-muted-foreground">{group.count} {t('contas')}</p>
                                        </div>
                                    </div>
                                    {isOpen ? <ChevronDown className="h-5 w-5" /> : <ChevronRight className="h-5 w-5" />}
                                </button>

                                {(isOpen || search) && filtered.length > 0 && (
                                    <div className="border-t bg-white/50 rounded-b-lg">
                                        <table className="w-full text-sm">
                                            <thead className="bg-white/70">
                                                <tr>
                                                    <th className="p-2 text-left w-20">{t('Código')}</th>
                                                    <th className="p-2 text-left">{t('Designação')}</th>
                                                    <th className="p-2 text-center w-16">{t('Nível')}</th>
                                                    <th className="p-2 text-center w-20">{t('Saldo')}</th>
                                                    <th className="p-2 text-center w-20">{t('Tipo')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {filtered.map(account => (
                                                    <tr key={account.code} className="border-t border-gray-100 hover:bg-white/30">
                                                        <td className="p-2 font-mono font-bold text-xs">{account.code}</td>
                                                        <td className="p-2 text-xs" style={{ paddingLeft: `${(account.level - 1) * 16 + 8}px` }}>
                                                            {account.name}
                                                        </td>
                                                        <td className="p-2 text-center text-xs text-muted-foreground">{account.level}</td>
                                                        <td className="p-2 text-center">
                                                            <Badge variant="outline" className="text-[10px]">
                                                                {account.balance === 'debit' ? 'D' : 'C'}
                                                            </Badge>
                                                        </td>
                                                        <td className="p-2 text-center">
                                                            {account.movement
                                                                ? <Badge className="bg-green-100 text-green-700 border-0 text-[10px]">{t('Mov.')}</Badge>
                                                                : <Badge className="bg-gray-100 text-gray-500 border-0 text-[10px]">{t('Sint.')}</Badge>
                                                            }
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </AuthenticatedLayout>
    );
}
