import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Calendar, CheckCircle2, Clock, AlertCircle, RefreshCw, Download } from 'lucide-react';

interface FiscalEvent { id: number; code: string; title: string; obligation_type: string; due_date: string; reference_period: string; status: string; }

const typeColors: Record<string, string> = { vat: 'bg-blue-100 text-blue-700', irpc: 'bg-purple-100 text-purple-700', irps: 'bg-teal-100 text-teal-700', inss: 'bg-orange-100 text-orange-700', withholding: 'bg-pink-100 text-pink-700', saft: 'bg-indigo-100 text-indigo-700', annual_accounts: 'bg-emerald-100 text-emerald-700', other: 'bg-gray-100 text-gray-600' };

export default function FiscalCalendarIndex() {
    const { t } = useTranslation();
    const { events, year } = usePage<{ events: FiscalEvent[]; year: number }>().props;

    const generateCalendar = () => router.post(route('sce.fiscal.generate-calendar'), { year });
    const exportCalendar = () => window.location.assign(route('sce.fiscal.calendar.export', { year }));
    const completeEvent = (id: number) => router.post(route('sce.fiscal.complete-event', id));

    const pending = events.filter(e => e.status === 'pending');
    const overdue = events.filter(e => e.status === 'pending' && new Date(e.due_date) < new Date());
    const completed = events.filter(e => e.status === 'completed');

    const grouped = events.reduce((acc, e) => {
        const month = new Date(e.due_date).toLocaleString('pt', { month: 'long', year: 'numeric' });
        if (!acc[month]) acc[month] = [];
        acc[month].push(e);
        return acc;
    }, {} as Record<string, FiscalEvent[]>);

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Fiscal') }, { label: t('Calendário Fiscal') }]} pageTitle={`${t('Calendário Fiscal')} ${year}`}
            pageActions={
                <div className="flex items-center gap-2">
                    <Button size="sm" variant="outline" onClick={exportCalendar}>
                        <Download className="h-4 w-4 mr-1" /> {t('Exportar CSV')}
                    </Button>
                    <Button size="sm" variant="outline" onClick={generateCalendar}>
                        <RefreshCw className="h-4 w-4 mr-1" /> {t('Gerar Calendário')}
                    </Button>
                </div>
            }>
            <Head title={t('Calendário Fiscal')} />
            <AccountingSuiteNavigation section="fiscal" className="mb-4" />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
                    <CardContent className="p-4"><div className="flex items-center gap-3"><AlertCircle className="h-8 w-8 text-red-500" /><div><p className="text-xs text-red-600">{t('Em Atraso')}</p><p className="text-2xl font-bold text-red-800">{overdue.length}</p></div></div></CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200">
                    <CardContent className="p-4"><div className="flex items-center gap-3"><Clock className="h-8 w-8 text-yellow-500" /><div><p className="text-xs text-yellow-600">{t('Pendentes')}</p><p className="text-2xl font-bold text-yellow-800">{pending.length}</p></div></div></CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
                    <CardContent className="p-4"><div className="flex items-center gap-3"><CheckCircle2 className="h-8 w-8 text-green-500" /><div><p className="text-xs text-green-600">{t('Concluídas')}</p><p className="text-2xl font-bold text-green-800">{completed.length}</p></div></div></CardContent>
                </Card>
            </div>

            {events.length === 0 ? (
                <Card><CardContent className="p-12 text-center">
                    <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                    <h3 className="text-lg font-semibold mb-2">{t('Sem eventos fiscais')}</h3>
                    <Button onClick={generateCalendar}><RefreshCw className="h-4 w-4 mr-2" /> {t('Gerar Calendário')}</Button>
                </CardContent></Card>
            ) : (
                <div className="space-y-6">
                    {Object.entries(grouped).map(([month, monthEvents]) => (
                        <Card key={month}>
                            <CardHeader className="py-3"><CardTitle className="text-base capitalize">{month}</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {monthEvents.map(event => {
                                        const isOverdue = event.status === 'pending' && new Date(event.due_date) < new Date();
                                        return (
                                            <div key={event.id} className={`flex items-center gap-4 px-4 py-3 ${isOverdue ? 'bg-red-50/50' : ''}`}>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <Badge className={`border-0 text-[10px] ${typeColors[event.obligation_type] || typeColors.other}`}>{event.obligation_type.toUpperCase()}</Badge>
                                                        <span className="text-sm font-medium">{event.title}</span>
                                                    </div>
                                                    <p className={`text-xs ${isOverdue ? 'text-red-600 font-semibold' : 'text-muted-foreground'}`}>{t('Prazo')}: {new Date(event.due_date).toLocaleDateString('pt')}</p>
                                                </div>
                                                {event.status === 'completed' ? (
                                                    <Badge className="bg-green-100 text-green-700 border-0"><CheckCircle2 className="h-3 w-3 mr-1" /> {t('Concluída')}</Badge>
                                                ) : (
                                                    <Button size="sm" variant={isOverdue ? 'destructive' : 'outline'} onClick={() => completeEvent(event.id)}>
                                                        <CheckCircle2 className="h-3 w-3 mr-1" /> {t('Concluir')}
                                                    </Button>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
