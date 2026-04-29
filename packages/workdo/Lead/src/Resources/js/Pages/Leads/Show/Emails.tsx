import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Mail } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDateTime } from '@/utils/helpers';
import { Lead } from '../types';

interface EmailsProps {
    lead: Lead;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Emails({ lead, onRegisterAddHandler }: EmailsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => setEmailModalOpen(true));
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [emailModalOpen, setEmailModalOpen] = useState(false);
    const [emailForm, setEmailForm] = useState({ to: '', subject: '', description: '' });

    const stripHtmlAndDecode = (html: string) => {
        if (!html) return '';
        return html
            .replace(/<[^>]*>/g, '')
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#39;/g, "'")
            .replace(/&nbsp;/g, ' ');
    };

    const handleEmailSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('lead.leads.store-email', lead.id), emailForm, {
            onSuccess: () => {
                setEmailForm({ to: '', subject: '', description: '' });
                setEmailModalOpen(false);
            }
        });
    };

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={lead.emails || []}
                        columns={[
                            {
                                key: 'to',
                                header: t('To'),
                            },
                            {
                                key: 'subject',
                                header: t('Subject'),
                            },
                            {
                                key: 'description',
                                header: t('Description'),
                                render: (value: string) => {
                                    if (!value) return '-';
                                    const cleanText = stripHtmlAndDecode(value);
                                    return (
                                        <span className="text-sm text-gray-600 truncate max-w-xs" title={cleanText}>
                                            {cleanText.length > 30 ? `${cleanText.substring(0, 30)}...` : cleanText}
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
                                icon={Mail}
                                title={t('No Emails sent')}
                                description={t('Get started by sending your first email.')}
                                onCreateClick={() => setEmailModalOpen(true)}
                                createButtonText={t('Send Email')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={emailModalOpen} onOpenChange={setEmailModalOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{t('Send Email')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEmailSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="to">{t('To')}</Label>
                            <Input
                                id="to"
                                type="email"
                                value={emailForm.to}
                                onChange={(e) => setEmailForm({...emailForm, to: e.target.value})}
                                placeholder={t('Enter email address')}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="subject">{t('Subject')}</Label>
                            <Input
                                id="subject"
                                type="text"
                                value={emailForm.subject}
                                onChange={(e) => setEmailForm({...emailForm, subject: e.target.value})}
                                placeholder={t('Enter subject')}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="description">{t('Description')}</Label>
                            <RichTextEditor
                                content={emailForm.description}
                                onChange={(content) => setEmailForm({...emailForm, description: content})}
                                placeholder={t('Enter email content')}
                                className="mt-1"
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setEmailModalOpen(false)}>{t('Cancel')}</Button>
                            <Button type="submit">{t('Send Email')}</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}