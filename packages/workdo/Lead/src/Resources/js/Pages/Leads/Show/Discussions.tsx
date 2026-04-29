import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { MessageSquare } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDateTime } from '@/utils/helpers';
import { Lead } from '../types';

interface DiscussionsProps {
    lead: Lead;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Discussions({ lead, onRegisterAddHandler }: DiscussionsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => setDiscussionModalOpen(true));
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [discussionModalOpen, setDiscussionModalOpen] = useState(false);
    const [discussionForm, setDiscussionForm] = useState({ message: '' });

    const handleDiscussionSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('lead.leads.store-discussion', lead.id), discussionForm, {
            onSuccess: () => {
                setDiscussionForm({ message: '' });
                setDiscussionModalOpen(false);
            }
        });
    };

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={lead.discussions || []}
                        columns={[
                            {
                                key: 'creator.name',
                                header: t('User'),
                                render: (value: string, discussion: any) => discussion.creator?.name || '-'
                            },
                            {
                                key: 'comment',
                                header: t('Message'),
                                render: (value: string) => {
                                    if (!value) return '-';
                                    return (
                                        <span className="text-sm text-gray-600 truncate max-w-xs" title={value}>
                                            {value.length > 50 ? `${value.substring(0, 50)}...` : value}
                                        </span>
                                    );
                                }
                            },
                            {
                                key: 'created_at',
                                header: t('Date'),
                                render: (value: string) => formatDateTime(value)
                            }
                        ]}
                        className="rounded-none"
                        emptyState={
                            <NoRecordsFound
                                icon={MessageSquare}
                                title={t('No Discussions found')}
                                description={t('Get started by adding your first discussion.')}
                                onCreateClick={() => setDiscussionModalOpen(true)}
                                createButtonText={t('Add Message')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={discussionModalOpen} onOpenChange={setDiscussionModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Add Message')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleDiscussionSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="message">{t('Message')}</Label>
                            <Textarea
                                id="message"
                                value={discussionForm.message}
                                onChange={(e) => setDiscussionForm({...discussionForm, message: e.target.value})}
                                placeholder={t('Enter your message')}
                                rows={4}
                                required
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setDiscussionModalOpen(false)}>{t('Cancel')}</Button>
                            <Button type="submit">{t('Save')}</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}