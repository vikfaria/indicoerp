import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Plus, ShieldCheck, ToggleLeft, ToggleRight, Hash, FileText } from 'lucide-react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Series {
    id: number; series_code: string; fiscal_year: number;
    doc_type_code: string; doc_type_name: string;
    last_sequence: number; last_hash: string | null;
    is_active: boolean; valid_from: string; valid_to: string;
    assigned_user_id?: number | null;
    terminal_code?: string | null;
    fiscal_regime_code?: string | null;
}
interface DocType { id: number; code: string; name: string; }
interface CompanyUser { id: number; label: string; }

export default function DocumentSeriesIndex() {
    const { t } = useTranslation();
    const { series, documentTypes, companyUsers, currentYear } = usePage<{
        series: Series[]; documentTypes: DocType[]; companyUsers: CompanyUser[]; currentYear: number;
    }>().props;

    const [open, setOpen] = useState(false);
    const form = useForm({
        fiscal_document_type_id: '',
        series_code: 'A',
        fiscal_year: currentYear.toString(),
        assigned_user_id: 'none',
        terminal_code: '',
        fiscal_regime_code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const payload = {
            ...form.data,
            assigned_user_id: form.data.assigned_user_id === 'none' ? null : form.data.assigned_user_id,
        };

        form.transform(() => payload);
        form.post(route('sce.fiscal.series.store'), {
            onSuccess: () => { setOpen(false); form.reset(); },
        });
    };

    const toggleActive = (id: number) => router.post(route('sce.fiscal.series.toggle', id));
    const verifyChain = (id: number) => router.post(route('sce.fiscal.series.verify', id));

    // Group by year
    const byYear = series.reduce<Record<number, Series[]>>((acc, s) => {
        (acc[s.fiscal_year] ||= []).push(s);
        return acc;
    }, {});

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Fiscal') }, { label: t('Séries Documentais') }]}
            pageTitle={t('Gestão de Séries Documentais')}
            pageActions={
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button size="sm"><Plus className="h-4 w-4 mr-1" /> {t('Nova Série')}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={submit}>
                            <DialogHeader>
                                <DialogTitle>{t('Criar Nova Série Documental')}</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div>
                                    <Label>{t('Tipo de Documento')}</Label>
                                    <Select
                                        value={form.data.fiscal_document_type_id}
                                        onValueChange={v => form.setData('fiscal_document_type_id', v)}
                                    >
                                        <SelectTrigger><SelectValue placeholder={t('Seleccionar...')} /></SelectTrigger>
                                        <SelectContent>
                                            {documentTypes.map(dt => (
                                                <SelectItem key={dt.id} value={dt.id.toString()}>
                                                    {dt.code} — {dt.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.fiscal_document_type_id && (
                                        <p className="text-xs text-red-500 mt-1">{form.errors.fiscal_document_type_id}</p>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label>{t('Código da Série')}</Label>
                                        <Input
                                            value={form.data.series_code}
                                            onChange={e => form.setData('series_code', e.target.value.toUpperCase())}
                                            maxLength={5} placeholder="A"
                                        />
                                        <p className="text-xs text-muted-foreground mt-1">{t('Ex: A, B, C, POS1')}</p>
                                        {form.errors.series_code && (
                                            <p className="text-xs text-red-500 mt-1">{form.errors.series_code}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label>{t('Exercício Fiscal')}</Label>
                                        <Input
                                            type="number" value={form.data.fiscal_year}
                                            onChange={e => form.setData('fiscal_year', e.target.value)}
                                            min={2020} max={2099}
                                        />
                                        {form.errors.fiscal_year && (
                                            <p className="text-xs text-red-500 mt-1">{form.errors.fiscal_year}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label>{t('Utilizador (opcional)')}</Label>
                                        <Select
                                            value={form.data.assigned_user_id}
                                            onValueChange={v => form.setData('assigned_user_id', v)}
                                        >
                                            <SelectTrigger><SelectValue placeholder={t('Todos')} /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">{t('Todos')}</SelectItem>
                                                {companyUsers.map(user => (
                                                    <SelectItem key={user.id} value={user.id.toString()}>
                                                        {user.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {form.errors.assigned_user_id && (
                                            <p className="text-xs text-red-500 mt-1">{form.errors.assigned_user_id}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label>{t('Terminal (opcional)')}</Label>
                                        <Input
                                            value={form.data.terminal_code}
                                            onChange={e => form.setData('terminal_code', e.target.value.toUpperCase())}
                                            maxLength={50}
                                            placeholder="POS01"
                                        />
                                        {form.errors.terminal_code && (
                                            <p className="text-xs text-red-500 mt-1">{form.errors.terminal_code}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label>{t('Regime Fiscal (opcional)')}</Label>
                                        <Input
                                            value={form.data.fiscal_regime_code}
                                            onChange={e => form.setData('fiscal_regime_code', e.target.value.toUpperCase())}
                                            maxLength={50}
                                            placeholder="NORMAL"
                                        />
                                        {form.errors.fiscal_regime_code && (
                                            <p className="text-xs text-red-500 mt-1">{form.errors.fiscal_regime_code}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setOpen(false)}>{t('Cancelar')}</Button>
                                <Button type="submit" disabled={form.processing}>{t('Criar')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            }
        >
            <Head title={t('Séries Documentais')} />
            <AccountingSuiteNavigation section="fiscal" className="mb-4" />

            {series.length === 0 ? (
                <Card className="bg-gradient-to-br from-gray-50 to-gray-100">
                    <CardContent className="p-8 text-center">
                        <FileText className="h-12 w-12 mx-auto mb-3 text-gray-400" />
                        <p className="text-sm text-muted-foreground mb-3">
                            {t('Nenhuma série documental configurada.')}
                        </p>
                        <p className="text-xs text-muted-foreground mb-4">
                            {t('As séries são criadas automaticamente ao emitir o primeiro documento, ou pode criá-las manualmente.')}
                        </p>
                        <Button size="sm" onClick={() => setOpen(true)}>
                            <Plus className="h-4 w-4 mr-1" /> {t('Criar Primeira Série')}
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-6">
                    {Object.entries(byYear)
                        .sort(([a], [b]) => Number(b) - Number(a))
                        .map(([year, yearSeries]) => (
                            <div key={year}>
                                <h3 className="text-sm font-semibold mb-2 text-muted-foreground flex items-center gap-2">
                                    <span className="px-2 py-0.5 bg-gray-100 rounded text-xs">{year}</span>
                                    <span>{yearSeries.length} {t('séries')}</span>
                                </h3>
                                <Card>
                                    <CardContent className="p-0">
                                        <table className="w-full text-sm">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="p-3 text-left">{t('Tipo')}</th>
                                                    <th className="p-3 text-left">{t('Série')}</th>
                                                    <th className="p-3 text-left">{t('Dimensão')}</th>
                                                    <th className="p-3 text-center">{t('Último Nº')}</th>
                                                    <th className="p-3 text-center">{t('Hash')}</th>
                                                    <th className="p-3 text-center">{t('Estado')}</th>
                                                    <th className="p-3 text-center">{t('Validade')}</th>
                                                    <th className="p-3 text-right">{t('Acções')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {yearSeries.map(s => (
                                                    <tr key={s.id} className="border-t hover:bg-gray-50/50">
                                                        <td className="p-3">
                                                            <div className="flex items-center gap-2">
                                                                <Badge variant="outline" className="font-mono text-xs">{s.doc_type_code}</Badge>
                                                                <span className="text-xs text-muted-foreground">{s.doc_type_name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="p-3 font-mono font-bold">{s.series_code}</td>
                                                        <td className="p-3 text-xs text-muted-foreground">
                                                            <div>{s.assigned_user_id ? `${t('User')}: #${s.assigned_user_id}` : `${t('User')}: *`}</div>
                                                            <div>{t('Terminal')}: {s.terminal_code || '*'}</div>
                                                            <div>{t('Regime')}: {s.fiscal_regime_code || '*'}</div>
                                                        </td>
                                                        <td className="p-3 text-center font-mono">
                                                            {s.last_sequence > 0 ? s.last_sequence : '—'}
                                                        </td>
                                                        <td className="p-3 text-center font-mono text-xs text-muted-foreground">
                                                            {s.last_hash || '—'}
                                                        </td>
                                                        <td className="p-3 text-center">
                                                            {s.is_active
                                                                ? <Badge className="bg-green-100 text-green-700 border-0 text-xs">{t('Activa')}</Badge>
                                                                : <Badge className="bg-gray-100 text-gray-500 border-0 text-xs">{t('Inactiva')}</Badge>
                                                            }
                                                        </td>
                                                        <td className="p-3 text-center text-xs text-muted-foreground">
                                                            {s.valid_from} — {s.valid_to}
                                                        </td>
                                                        <td className="p-3 text-right">
                                                            <div className="flex gap-1 justify-end">
                                                                <Button
                                                                    size="sm" variant="ghost" title={t('Verificar cadeia hash')}
                                                                    onClick={() => verifyChain(s.id)}
                                                                    disabled={s.last_sequence === 0}
                                                                >
                                                                    <ShieldCheck className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    size="sm" variant="ghost"
                                                                    title={s.is_active ? t('Desactivar') : t('Activar')}
                                                                    onClick={() => toggleActive(s.id)}
                                                                >
                                                                    {s.is_active
                                                                        ? <ToggleRight className="h-4 w-4 text-green-600" />
                                                                        : <ToggleLeft className="h-4 w-4 text-gray-400" />
                                                                    }
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </CardContent>
                                </Card>
                            </div>
                        ))
                    }
                </div>
            )}
        </AuthenticatedLayout>
    );
}
