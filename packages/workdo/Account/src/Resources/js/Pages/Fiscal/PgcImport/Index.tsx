import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowRight, CheckCircle2, Database, RefreshCw, TriangleAlert } from 'lucide-react';

interface CatalogAccount {
    code: string;
    name: string;
    parent: string | null;
    level: number;
    movement: boolean;
    balance: string;
    fs_line: string | null;
}

interface CatalogGroup {
    class: number;
    name: string;
    count: number;
    accounts: CatalogAccount[];
}

interface ValidationCoverage {
    class: number;
    label: string;
    official_count: number;
    company_count: number;
}

interface ValidationReport {
    framework: string;
    profile_framework: string | null;
    catalog_count: number;
    company_pgc_count: number;
    legacy_active_count: number;
    missing_classes: number[];
    missing_codes: string[];
    extra_codes: string[];
    class_coverage: ValidationCoverage[];
    warnings: string[];
    errors: string[];
    valid: boolean;
}

interface PageProps {
    catalog: CatalogGroup[];
    totalCatalog: number;
    importedCount: number;
    validationReport: ValidationReport;
    mappingSummary: {
        pending: number;
        mapped: number;
        verified: number;
    };
    framework: string;
    profileFramework: string | null;
    auth: {
        user?: {
            permissions?: string[];
        };
    };
}

const statusBadgeClass = {
    pass: 'bg-green-100 text-green-800',
    warn: 'bg-yellow-100 text-yellow-800',
    fail: 'bg-red-100 text-red-800',
};

export default function Index() {
    const { t } = useTranslation();
    const { catalog, validationReport, mappingSummary, framework, profileFramework, auth } = usePage<PageProps>().props;
    const [workingAction, setWorkingAction] = useState<'import' | 'reconcile' | 'validate' | null>(null);

    const canManage = auth.user?.permissions?.some((permission) => [
        'manage-account',
        'manage-account-reports',
    ].includes(permission)) ?? false;

    const submitAction = (action: 'import' | 'reconcile' | 'validate') => {
        setWorkingAction(action);
        router.post(route(`sce.fiscal.pgc.${action}`), { framework }, {
            preserveScroll: true,
            onFinish: () => setWorkingAction(null),
        });
    };

    const summaryCards = [
        { label: t('Official catalog'), value: validationReport.catalog_count },
        { label: t('Imported PGC'), value: validationReport.company_pgc_count },
        { label: t('Legacy active'), value: validationReport.legacy_active_count },
        { label: t('Warnings'), value: validationReport.warnings.length },
        { label: t('Errors'), value: validationReport.errors.length },
        { label: t('Valid'), value: validationReport.valid ? t('Yes') : t('No') },
    ];

    return (
        <AuthenticatedLayout
                breadcrumbs={[
                    { label: t('Accounting'), url: route('account.index') },
                    { label: t('PGC Moçambique') },
                ]}
            pageTitle={t('PGC Moçambique')}
        >
            <Head title={t('PGC Moçambique')} />

            <div className="space-y-6">
                <Card className="border-dashed shadow-sm">
                    <CardHeader className="space-y-2">
                        <CardTitle className="flex items-center gap-2 text-xl">
                            <Database className="h-5 w-5 text-primary" />
                            {t('PGC-MZ Official Catalog Validation')}
                        </CardTitle>
                        <CardDescription>
                            {t('Import, reconcile and validate the official Mozambique chart of accounts before go-live.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                        {summaryCards.map((card) => (
                            <div key={card.label} className="rounded-lg border bg-background p-4">
                                <div className="text-xs uppercase text-muted-foreground">{card.label}</div>
                                <div className="mt-2 text-2xl font-semibold">{card.value}</div>
                            </div>
                        ))}
                    </CardContent>
                    <CardContent className="flex flex-wrap gap-3">
                        <Button
                            onClick={() => submitAction('import')}
                            disabled={!canManage || workingAction !== null}
                            className="gap-2"
                        >
                            <RefreshCw className={`h-4 w-4 ${workingAction === 'import' ? 'animate-spin' : ''}`} />
                            {workingAction === 'import' ? t('Importing...') : t('Import PGC')}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => submitAction('reconcile')}
                            disabled={!canManage || workingAction !== null}
                            className="gap-2"
                        >
                            <ArrowRight className={`h-4 w-4 ${workingAction === 'reconcile' ? 'animate-pulse' : ''}`} />
                            {workingAction === 'reconcile' ? t('Reconciling...') : t('Generate Reconciliation')}
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => submitAction('validate')}
                            disabled={!canManage || workingAction !== null}
                            className="gap-2"
                        >
                            <CheckCircle2 className={`h-4 w-4 ${workingAction === 'validate' ? 'animate-pulse' : ''}`} />
                            {workingAction === 'validate' ? t('Validating...') : t('Validate Structure')}
                        </Button>
                    </CardContent>
                </Card>

                {profileFramework && profileFramework !== framework && (
                    <Card className="border-amber-200 bg-amber-50">
                        <CardContent className="p-4 text-sm text-amber-900">
                            {t('The active fiscal profile suggests :profile, but this page is validating :framework.', {
                                profile: profileFramework,
                                framework,
                            })}
                        </CardContent>
                    </Card>
                )}

                {validationReport.errors.length > 0 && (
                    <Card className="border-red-200">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg text-red-700">
                                <TriangleAlert className="h-5 w-5" />
                                {t('Validation Errors')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {validationReport.errors.map((error) => (
                                <div key={error} className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                    {error}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {validationReport.warnings.length > 0 && (
                    <Card className="border-amber-200">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg text-amber-700">
                                <TriangleAlert className="h-5 w-5" />
                                {t('Reconciliation Warnings')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {validationReport.warnings.map((warning) => (
                                <div key={warning} className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    {warning}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>{t('Class Coverage')}</CardTitle>
                        <CardDescription>
                            {t('Comparison between the official catalog and the company chart of accounts.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="py-2 text-left">{t('Class')}</th>
                                    <th className="py-2 text-left">{t('Label')}</th>
                                    <th className="py-2 text-left">{t('Official')}</th>
                                    <th className="py-2 text-left">{t('Company')}</th>
                                    <th className="py-2 text-left">{t('Status')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {validationReport.class_coverage.map((item) => {
                                    const matches = item.official_count === item.company_count && item.company_count > 0;

                                    return (
                                        <tr key={item.class} className="border-b align-top">
                                            <td className="py-2 font-mono">{item.class}</td>
                                            <td className="py-2">{item.label}</td>
                                            <td className="py-2">{item.official_count}</td>
                                            <td className="py-2">{item.company_count}</td>
                                            <td className="py-2">
                                                <Badge className={matches ? statusBadgeClass.pass : statusBadgeClass.warn}>
                                                    {matches ? t('Aligned') : t('Review')}
                                                </Badge>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>{t('Migration Mapping Summary')}</CardTitle>
                        <CardDescription>
                            {t('Legacy account reconciliation status created from the automatic mapping rules.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-3">
                        <div className="rounded-lg border bg-background p-4">
                            <div className="text-xs uppercase text-muted-foreground">{t('Pending')}</div>
                            <div className="mt-2 text-2xl font-semibold">{mappingSummary.pending ?? 0}</div>
                        </div>
                        <div className="rounded-lg border bg-background p-4">
                            <div className="text-xs uppercase text-muted-foreground">{t('Mapped')}</div>
                            <div className="mt-2 text-2xl font-semibold">{mappingSummary.mapped ?? 0}</div>
                        </div>
                        <div className="rounded-lg border bg-background p-4">
                            <div className="text-xs uppercase text-muted-foreground">{t('Verified')}</div>
                            <div className="mt-2 text-2xl font-semibold">{mappingSummary.verified ?? 0}</div>
                        </div>
                    </CardContent>
                </Card>

                {catalog.map((group) => (
                    <details key={group.class} className="rounded-lg border bg-background shadow-sm">
                        <summary className="cursor-pointer list-none px-4 py-3 text-sm font-medium">
                            <div className="flex items-center justify-between gap-4">
                                <span>
                                    {t('Class')} {group.class} - {group.name}
                                </span>
                                <Badge variant="secondary">{group.count} {t('accounts')}</Badge>
                            </div>
                        </summary>
                        <div className="border-t px-4 py-4">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 text-left">{t('Code')}</th>
                                            <th className="py-2 text-left">{t('Name')}</th>
                                            <th className="py-2 text-left">{t('Parent')}</th>
                                            <th className="py-2 text-left">{t('Level')}</th>
                                            <th className="py-2 text-left">{t('Movement')}</th>
                                            <th className="py-2 text-left">{t('FS Line')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {group.accounts.map((account) => (
                                            <tr key={`${group.class}-${account.code}`} className="border-b align-top">
                                                <td className="py-2 font-mono">{account.code}</td>
                                                <td className="py-2">{account.name}</td>
                                                <td className="py-2 font-mono">{account.parent || '-'}</td>
                                                <td className="py-2">{account.level}</td>
                                                <td className="py-2">
                                                    <Badge className={account.movement ? statusBadgeClass.pass : statusBadgeClass.warn}>
                                                        {account.movement ? t('Yes') : t('No')}
                                                    </Badge>
                                                </td>
                                                <td className="py-2 font-mono text-xs">{account.fs_line || '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
