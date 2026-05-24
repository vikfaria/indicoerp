import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle2, Circle, Clock, Lock, Play, AlertTriangle } from 'lucide-react';
import { useState } from 'react';

interface CheckItem { id: number; check_name: string; status: string; completed_at: string | null; }
interface Period { id: number; period_number: number; period_name: string; status: string; }

export default function MonthlyClosingIndex() {
    const { t } = useTranslation();
    const { periods, checklists, currentYear, currentMonth } = usePage<{ periods: Period[]; checklists: CheckItem[]; currentYear: string; currentMonth: number; }>().props;
    const [month, setMonth] = useState(currentMonth);

    const completedCount = checklists.filter(c => c.status !== 'pending').length;
    const totalCount = checklists.length;
    const progress = totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : 0;
    const currentPeriod = periods.find(p => p.period_number === month);

    const handlePeriodChange = (m: number) => { setMonth(m); router.get(route('sce.monthly-closing.index'), { year: currentYear, month: m }, { preserveState: true, replace: true }); };
    const startClosing = () => router.post(route('sce.monthly-closing.start'), { year: currentYear, month });
    const completeCheck = (id: number) => router.post(route('sce.monthly-closing.complete-check', id));
    const finalize = () => router.post(route('sce.monthly-closing.finalize'), { year: currentYear, month });

    const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: new Date(2000, i).toLocaleString('pt', { month: 'long' }) }));

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade SCE') }, { label: t('Fecho Mensal') }]} pageTitle={t('Fecho Mensal')}>
            <Head title={t('Fecho Mensal')} />
            <div className="flex items-center gap-4 mb-6">
                <Select value={String(month)} onValueChange={v => handlePeriodChange(Number(v))}>
                    <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
                    <SelectContent>{months.map(m => <SelectItem key={m.value} value={String(m.value)}>{m.label.charAt(0).toUpperCase() + m.label.slice(1)}</SelectItem>)}</SelectContent>
                </Select>
                <span className="text-lg font-semibold">{currentYear}</span>
                {currentPeriod && <Badge className={currentPeriod.status === 'closed' ? 'bg-gray-100 text-gray-600' : currentPeriod.status === 'closing' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'}>{currentPeriod.status.toUpperCase()}</Badge>}
            </div>

            <Card className="mb-6">
                <CardContent className="p-4">
                    <div className="flex gap-1 overflow-x-auto pb-2">
                        {periods.filter(p => p.period_number >= 1 && p.period_number <= 12).map(p => (
                            <button key={p.id} onClick={() => handlePeriodChange(p.period_number)}
                                className={`flex flex-col items-center px-3 py-2 rounded-lg min-w-[60px] transition-all ${p.period_number === month ? 'bg-primary text-primary-foreground shadow-md scale-105' : 'hover:bg-muted/50'}`}>
                                <span className="text-[10px] font-medium">{p.period_name?.substring(0, 3) || `P${p.period_number}`}</span>
                                {p.status === 'closed' ? <Lock className="h-3.5 w-3.5 mt-1" /> : p.status === 'closing' ? <Clock className="h-3.5 w-3.5 mt-1 text-yellow-500" /> : <Circle className="h-3.5 w-3.5 mt-1" />}
                            </button>
                        ))}
                    </div>
                </CardContent>
            </Card>

            {checklists.length === 0 ? (
                <Card><CardContent className="p-12 text-center">
                    <AlertTriangle className="h-12 w-12 text-yellow-400 mx-auto mb-4" />
                    <h3 className="text-lg font-semibold mb-2">{t('Sem checklist para este período')}</h3>
                    <p className="text-muted-foreground mb-4">{t('Inicie o processo de fecho.')}</p>
                    <Button onClick={startClosing}><Play className="h-4 w-4 mr-2" /> {t('Iniciar Fecho')}</Button>
                </CardContent></Card>
            ) : (
                <>
                    <Card className="mb-6"><CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium">{t('Progresso')}: {completedCount}/{totalCount}</span>
                            <span className="text-sm font-bold text-primary">{progress}%</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-3">
                            <div className="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500" style={{ width: `${progress}%` }} />
                        </div>
                    </CardContent></Card>

                    <div className="space-y-3">
                        {checklists.map((check, i) => (
                            <Card key={check.id} className={`transition-all ${check.status !== 'pending' ? 'border-green-200 bg-green-50/30' : 'hover:shadow-md'}`}>
                                <CardContent className="p-4 flex items-center gap-4">
                                    <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold ${check.status !== 'pending' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500'}`}>
                                        {check.status !== 'pending' ? <CheckCircle2 className="h-5 w-5" /> : i + 1}
                                    </div>
                                    <div className="flex-1">
                                        <p className={`font-medium ${check.status !== 'pending' ? 'line-through text-muted-foreground' : ''}`}>{check.check_name}</p>
                                        {check.completed_at && <p className="text-xs text-muted-foreground mt-1">{new Date(check.completed_at).toLocaleString('pt')}</p>}
                                    </div>
                                    {check.status === 'pending' && <Button size="sm" variant="outline" onClick={() => completeCheck(check.id)}><CheckCircle2 className="h-4 w-4 mr-1" /> {t('Concluir')}</Button>}
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {progress === 100 && (
                        <div className="mt-6 text-center">
                            <Button size="lg" className="bg-gradient-to-r from-green-600 to-green-700 shadow-lg" onClick={finalize}>
                                <Lock className="h-5 w-5 mr-2" /> {t('Fechar Período')}
                            </Button>
                        </div>
                    )}
                </>
            )}
        </AuthenticatedLayout>
    );
}
