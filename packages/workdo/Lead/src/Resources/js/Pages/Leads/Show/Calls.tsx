import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Phone, Edit, Trash2 } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatTime } from '@/utils/helpers';
import { Lead } from '../types';

interface CallsProps {
    lead: Lead;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Calls({ lead, onRegisterAddHandler }: CallsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => setCallModalOpen(true));
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [callModalOpen, setCallModalOpen] = useState(false);
    const [editingCall, setEditingCall] = useState<any>(null);
    const [callForm, setCallForm] = useState({
        subject: '',
        call_type: 'Outbound',
        duration: '',
        assignee: '',
        description: '',
        call_result: ''
    });
    const [callDeleteState, setCallDeleteState] = useState({ isOpen: false, callId: null, message: '' });

    const handleCallSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingCall) {
            router.put(route('lead.calls.update', editingCall.id), callForm, {
                onSuccess: () => {
                    setCallForm({ subject: '', call_type: 'Outbound', duration: '', assignee: '', description: '', call_result: '' });
                    setCallModalOpen(false);
                    setEditingCall(null);
                }
            });
        } else {
            router.post(route('lead.calls.store'), {
                lead_id: lead.id,
                ...callForm
            }, {
                onSuccess: () => {
                    setCallForm({ subject: '', call_type: 'Outbound', duration: '', assignee: '', description: '', call_result: '' });
                    setCallModalOpen(false);
                }
            });
        }
    };

    const handleEditCall = (call: any) => {
        setEditingCall(call);
        setCallForm({
            subject: call.subject || '',
            call_type: call.call_type || 'Outbound',
            duration: call.duration || '',
            assignee: call.user_id?.toString() || '',
            description: call.description || '',
            call_result: call.call_result || ''
        });
        setCallModalOpen(true);
    };

    const openCallDeleteDialog = (callId: number) => {
        setCallDeleteState({
            isOpen: true,
            callId,
            message: t('Are you sure you want to delete this call?')
        });
    };

    const closeCallDeleteDialog = () => {
        setCallDeleteState({ isOpen: false, callId: null, message: '' });
    };

    const confirmCallDelete = () => {
        if (callDeleteState.callId) {
            router.delete(route('lead.calls.destroy', callDeleteState.callId));
            closeCallDeleteDialog();
        }
    };

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={lead.calls || []}
                        columns={[
                            {
                                key: 'subject',
                                header: t('Subject'),                               },
                            {
                                key: 'call_type',
                                header: t('Call Type'), 
                                render: (value: string) => (
                                    <Badge variant={value === 'Inbound' ? 'default' : 'secondary'}>
                                        {t(value)}
                                    </Badge>
                                )
                            },
                            {
                                key: 'duration',
                                header: t('Duration'),  
                                render: (value: string) => value ? formatTime(value) : '-'
                            },
                            {
                                key: 'description',
                                header: t('Description'),
                                render: (value: string) => {
                                    if (!value) return '-';
                                    return (
                                        <span className="text-sm text-gray-600 truncate max-w-xs" title={value}>
                                            {value.length > 30 ? `${value.substring(0, 30)}...` : value}
                                        </span>
                                    );
                                }
                            },
                            {
                                key: 'actions',
                                header: t('Action'),
                                render: (_: any, call: any) => (
                                    <div className="flex gap-1">
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => handleEditCall(call)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Edit')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => openCallDeleteDialog(call.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Delete')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                )
                            }
                        ]}
                        className="rounded-none"
                        emptyState={
                            <NoRecordsFound
                                icon={Phone}
                                title={t('No Calls found')}
                                description={t('Get started by adding your first call.')}
                                onCreateClick={() => setCallModalOpen(true)}
                                createButtonText={t('Create Call')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={callModalOpen} onOpenChange={(open) => {
                setCallModalOpen(open);
                if (!open) {
                    setEditingCall(null);
                    setCallForm({ subject: '', call_type: 'Outbound', duration: '', assignee: '', description: '', call_result: '' });
                }
            }}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editingCall ? t('Edit Call') : t('Create Call')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCallSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="subject">{t('Subject')}</Label>
                            <Input
                                id="subject"
                                type="text"
                                value={callForm.subject}
                                onChange={(e) => setCallForm({...callForm, subject: e.target.value})}
                                placeholder={t('Enter call subject')}
                                required
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="call_type">{t('Call Type')}</Label>
                                <Select value={callForm.call_type} onValueChange={(value) => setCallForm({...callForm, call_type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select call type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Inbound">{t('Inbound')}</SelectItem>
                                        <SelectItem value="Outbound">{t('Outbound')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="duration">{t('Duration')}</Label>
                                <Input
                                    id="duration"
                                    required
                                    type="time"
                                    value={callForm.duration}
                                    onChange={(e) => setCallForm({...callForm, duration: e.target.value})}
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="assignee">{t('Assignee')}</Label>
                            <Select value={callForm.assignee} onValueChange={(value) => setCallForm({...callForm, assignee: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select assignee')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {lead.user_leads?.map((userLead: any) => (
                                        <SelectItem key={userLead.user?.id} value={userLead.user?.id?.toString() || ''}>
                                            {userLead.user?.name || ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="description">{t('Description')}</Label>
                            <Textarea
                                id="description"
                                value={callForm.description}
                                onChange={(e) => setCallForm({...callForm, description: e.target.value})}
                                placeholder={t('Enter call description')}
                                rows={4}
                            />
                        </div>
                        <div>
                            <Label htmlFor="call_result">{t('Call Result')}</Label>
                            <RichTextEditor
                                content={callForm.call_result}
                                onChange={(content) => setCallForm({...callForm, call_result: content})}
                                placeholder={t('Enter call result')}
                                className="mt-1"
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setCallModalOpen(false)}>{t('Cancel')}</Button>
                            <Button type="submit">{editingCall ? t('Update') : t('Create')}</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={callDeleteState.isOpen}
                onOpenChange={closeCallDeleteDialog}
                title={t('Delete Call')}
                message={callDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmCallDelete}
                variant="destructive"
            />
        </>
    );
}