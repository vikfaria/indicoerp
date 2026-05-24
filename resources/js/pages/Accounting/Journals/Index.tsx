import { useState } from 'react';
import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Plus, Edit as EditIcon, Trash2, BookOpen } from 'lucide-react';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';

interface Journal {
    id: number;
    name: string;
    prefix: string;
    type: string;
    is_active: boolean;
    current_sequence: number;
    default_debit_account: string | null;
    default_credit_account: string | null;
    requires_attachment: boolean;
}

const JOURNAL_TYPES = [
    { value: 'cash', label: 'Caixa' },
    { value: 'bank', label: 'Banco' },
    { value: 'sales', label: 'Vendas' },
    { value: 'purchases', label: 'Compras' },
    { value: 'salaries', label: 'Salários' },
    { value: 'adjustments', label: 'Regularizações' },
    { value: 'opening', label: 'Abertura' },
    { value: 'closing', label: 'Encerramento' },
    { value: 'fixed_assets', label: 'Activos Fixos' },
    { value: 'fiscal', label: 'Operações Fiscais' },
    { value: 'general', label: 'Diversos' },
];

export default function JournalsIndex() {
    const { t } = useTranslation();
    const { journals } = usePage<{ journals: Journal[] }>().props;
    const [showCreate, setShowCreate] = useState(false);
    const [editJournal, setEditJournal] = useState<Journal | null>(null);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const createForm = useForm({
        name: '', prefix: '', type: 'general',
        default_debit_account: '', default_credit_account: '',
        requires_attachment: false,
    });

    const editForm = useForm({
        name: '', type: 'general',
        default_debit_account: '', default_credit_account: '',
        requires_attachment: false, is_active: true,
    });

    const openEdit = (journal: Journal) => {
        setEditJournal(journal);
        editForm.setData({
            name: journal.name, type: journal.type,
            default_debit_account: journal.default_debit_account || '',
            default_credit_account: journal.default_credit_account || '',
            requires_attachment: journal.requires_attachment,
            is_active: journal.is_active,
        });
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('sce.journals.store'), {
            onSuccess: () => { setShowCreate(false); createForm.reset(); },
        });
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editJournal) return;
        editForm.put(route('sce.journals.update', editJournal.id), {
            onSuccess: () => setEditJournal(null),
        });
    };

    const handleDelete = () => {
        if (!deleteId) return;
        router.delete(route('sce.journals.destroy', deleteId), {
            onSuccess: () => setDeleteId(null),
        });
    };

    const typeLabel = (type: string) => JOURNAL_TYPES.find(t => t.value === type)?.label || type;

    const columns = [
        { key: 'prefix', header: t('Prefixo'), render: (v: string) => <Badge variant="outline" className="font-mono font-bold">{v}</Badge> },
        { key: 'name', header: t('Nome') },
        { key: 'type', header: t('Tipo'), render: (v: string) => <Badge className="bg-blue-100 text-blue-800 border-0">{typeLabel(v)}</Badge> },
        { key: 'current_sequence', header: t('Sequência'), render: (v: number) => <span className="font-mono text-sm">{v || 0}</span> },
        { key: 'is_active', header: t('Estado'), render: (v: boolean) => v ? <Badge className="bg-green-100 text-green-700 border-0">{t('Activo')}</Badge> : <Badge variant="secondary">{t('Inactivo')}</Badge> },
        {
            key: 'actions', header: t('Acções'),
            render: (_: any, j: Journal) => (
                <div className="flex gap-1">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(j)} className="h-8 w-8 p-0 text-blue-600"><EditIcon className="h-4 w-4" /></Button>
                    <Button variant="ghost" size="sm" onClick={() => setDeleteId(j.id)} className="h-8 w-8 p-0 text-destructive"><Trash2 className="h-4 w-4" /></Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Contabilidade SCE') }, { label: t('Diários') }]}
            pageTitle={t('Diários Contabilísticos')}
            pageActions={
                <Button size="sm" onClick={() => setShowCreate(true)}>
                    <Plus className="h-4 w-4 mr-1" /> {t('Novo Diário')}
                </Button>
            }
        >
            <Head title={t('Diários Contabilísticos')} />

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-blue-600">{t('Total Diários')}</p>
                                <p className="text-2xl font-bold text-blue-800">{journals.length}</p>
                            </div>
                            <BookOpen className="h-8 w-8 text-blue-400" />
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-green-600">{t('Activos')}</p>
                                <p className="text-2xl font-bold text-green-800">{journals.filter(j => j.is_active).length}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-purple-600">{t('Lançamentos Total')}</p>
                                <p className="text-2xl font-bold text-purple-800">{journals.reduce((s, j) => s + (j.current_sequence || 0), 0)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-orange-600">{t('Tipos em Uso')}</p>
                                <p className="text-2xl font-bold text-orange-800">{new Set(journals.map(j => j.type)).size}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-0">
                    <DataTable data={journals} columns={columns} />
                </CardContent>
            </Card>

            {/* Create Dialog */}
            <Dialog open={showCreate} onOpenChange={setShowCreate}>
                <DialogContent className="max-w-lg">
                    <DialogHeader><DialogTitle>{t('Novo Diário')}</DialogTitle></DialogHeader>
                    <form onSubmit={handleCreate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div><Label>{t('Nome')}</Label><Input value={createForm.data.name} onChange={e => createForm.setData('name', e.target.value)} required /></div>
                            <div><Label>{t('Prefixo')}</Label><Input value={createForm.data.prefix} onChange={e => createForm.setData('prefix', e.target.value.toUpperCase())} maxLength={5} required placeholder="VD" /></div>
                        </div>
                        <div>
                            <Label>{t('Tipo')}</Label>
                            <Select value={createForm.data.type} onValueChange={v => createForm.setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {JOURNAL_TYPES.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div><Label>{t('Conta Débito')}</Label><Input value={createForm.data.default_debit_account} onChange={e => createForm.setData('default_debit_account', e.target.value)} placeholder="ex: 11" /></div>
                            <div><Label>{t('Conta Crédito')}</Label><Input value={createForm.data.default_credit_account} onChange={e => createForm.setData('default_credit_account', e.target.value)} placeholder="ex: 211" /></div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" type="button" onClick={() => setShowCreate(false)}>{t('Cancelar')}</Button>
                            <Button type="submit" disabled={createForm.processing}>{t('Criar')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog open={!!editJournal} onOpenChange={() => setEditJournal(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader><DialogTitle>{t('Editar Diário')}</DialogTitle></DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div><Label>{t('Nome')}</Label><Input value={editForm.data.name} onChange={e => editForm.setData('name', e.target.value)} required /></div>
                        <div>
                            <Label>{t('Tipo')}</Label>
                            <Select value={editForm.data.type} onValueChange={v => editForm.setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {JOURNAL_TYPES.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div><Label>{t('Conta Débito')}</Label><Input value={editForm.data.default_debit_account} onChange={e => editForm.setData('default_debit_account', e.target.value)} /></div>
                            <div><Label>{t('Conta Crédito')}</Label><Input value={editForm.data.default_credit_account} onChange={e => editForm.setData('default_credit_account', e.target.value)} /></div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" type="button" onClick={() => setEditJournal(null)}>{t('Cancelar')}</Button>
                            <Button type="submit" disabled={editForm.processing}>{t('Guardar')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)} title={t('Eliminar Diário')} message={t('Tem a certeza que deseja eliminar este diário?')} confirmText={t('Eliminar')} onConfirm={handleDelete} variant="destructive" />
        </AuthenticatedLayout>
    );
}
