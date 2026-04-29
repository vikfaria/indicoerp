import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Mail } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDateTime } from '@/utils/helpers';
import { Deal } from '../types';

interface EmailsProps {
    deal: Deal;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Emails({ deal, onRegisterAddHandler }: EmailsProps) {

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
        router.post(route('lead.deals.store-email', deal.id), emailForm, {
            onSuccess: () => {
                setEmailForm({ to: '', subject: '', description: '' });
                setEmailModalOpen(false);
            }
        });
    };

    return (
        <>
            <div className="space-y-4 max-h-[75vh] overflow-y-auto">
                {deal.emails && deal.emails.length > 0 ? (
                    deal.emails.map((email: any, index: number) => {
                        const cleanText = stripHtmlAndDecode(email.description);
                        return (
                            <div key={index} className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="flex items-center gap-2">
                                        <Mail className="h-4 w-4 text-blue-500" />
                                        <span className="font-medium text-gray-900">{email.to}</span>
                                    </div>
                                    <span className="text-xs text-gray-500">{formatDateTime(email.created_at)}</span>
                                </div>
                                <h4 className="font-semibold text-gray-800 mb-2">{email.subject}</h4>
                                <p className="text-sm text-gray-600 leading-relaxed">
                                    {cleanText}
                                </p>
                            </div>
                        );
                    })
                ) : (
                    <NoRecordsFound
                        icon={Mail}
                        title={t('No Emails sent')}
                        description={t('Get started by sending your first email.')}
                        onCreateClick={() => setEmailModalOpen(true)}
                        createButtonText={t('Send Email')}
                        className="h-auto"
                    />
                )}
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