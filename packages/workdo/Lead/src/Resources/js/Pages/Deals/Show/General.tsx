import { useTranslation } from 'react-i18next';
import { Deal } from '../types';
import { formatDateTime, formatCurrency } from '@/utils/helpers';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { router } from '@inertiajs/react';
import { Loader2, Plus } from 'lucide-react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useFormFields } from '@/hooks/useFormFields';

interface GeneralProps {
    deal: Deal;
    onStatusChange: (status: string) => void;
}

export default function General({ deal, onStatusChange }: GeneralProps) {
    const { t } = useTranslation();
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [emailModalOpen, setEmailModalOpen] = useState(false);
    const [discussionModalOpen, setDiscussionModalOpen] = useState(false);
    const [emailForm, setEmailForm] = useState({ to: '', subject: '', description: '' });
    const [discussionForm, setDiscussionForm] = useState({ message: '' });

    const emailSubjectAI = useFormFields('aiField', emailForm, (field, value) => {
        setEmailForm(prev => ({ ...prev, [field]: value }));
    }, {}, 'create', 'subject', 'Subject', 'lead', 'deal_email');

    const [emailEditorKey, setEmailEditorKey] = useState(0);
    const emailDescriptionAI = useFormFields('aiField', emailForm, (field, value) => {
        setEmailForm(prev => ({ ...prev, [field]: value }));
        setEmailEditorKey(prev => prev + 1);
    }, {}, 'create', 'description', 'Description', 'lead', 'deal_email');

    const handleEmailSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('lead.deals.store-email', deal.id), emailForm, {
            onSuccess: () => {
                setEmailForm({ to: '', subject: '', description: '' });
                setEmailModalOpen(false);
            }
        });
    };

    const handleDiscussionSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('lead.deals.store-discussion', deal.id), discussionForm, {
            onSuccess: () => {
                setDiscussionForm({ message: '' });
                setDiscussionModalOpen(false);
            }
        });
    };

    return (
        <div className="space-y-8">
            {/* Header Section */}
            <div className="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-100">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <h1 className="text-3xl font-bold text-gray-900">{deal.name}</h1>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                            deal.status === 'Won' ? 'bg-green-100 text-green-800' :
                            deal.status === 'Loss' ? 'bg-red-100 text-red-800' :
                            'bg-blue-100 text-blue-800'
                        }`}>
                            {deal.status}
                        </span>
                    </div>
                    <Select
                        value={deal.status}
                        onValueChange={(value) => {
                            setIsChangingStatus(true);
                            onStatusChange(value);
                            setTimeout(() => {
                                setIsChangingStatus(false);
                            }, 1000);
                        }}
                        disabled={isChangingStatus}
                    >
                        <SelectTrigger className="w-36 bg-white shadow-sm">
                            {isChangingStatus ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <SelectValue />
                            )}
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Won">{t('Won')}</SelectItem>
                            <SelectItem value="Loss">{t('Loss')}</SelectItem>
                            <SelectItem value="Active">{t('Active')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div className="text-2xl font-bold text-gray-900">{deal.price ? formatCurrency(deal.price) : formatCurrency(0)}</div>
                    <div className="text-sm text-gray-500">{t('Deal Value')}</div>
                </div>
                <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div className="text-2xl font-bold text-blue-600">{deal.sources?.length || 0}</div>
                    <div className="text-sm text-gray-500">{t('Sources')}</div>
                </div>
                <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div className="text-2xl font-bold text-green-600">{deal.products?.length || 0}</div>
                    <div className="text-sm text-gray-500">{t('Products')}</div>
                </div>
                <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div className="text-2xl font-bold text-indigo-600">{deal.tasks?.length || 0}</div>
                    <div className="text-sm text-gray-500">{t('Tasks')}</div>
                </div>
            </div>

            {/* Details Section */}
            <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">{t('Deal Information')}</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('Pipeline')}</label>
                        <p className="text-sm font-medium text-gray-900">{deal.pipeline?.name || '-'}</p>
                    </div>
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('Stage')}</label>
                        <p className="text-sm font-medium text-gray-900">{deal.stage?.name || '-'}</p>
                    </div>
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('Creator')}</label>
                        <p className="text-sm font-medium text-gray-900">{deal.creator?.name || '-'}</p>
                    </div>
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('Created')}</label>
                        <p className="text-sm font-medium text-gray-900">{deal.created_at ? formatDateTime(deal.created_at) : '-'}</p>
                    </div>
                    <div className="space-y-1">
                        <label className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('Labels')}</label>
                        <p className="text-sm font-medium text-gray-900">
                            <span className="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">
                                {deal.labels ? deal.labels.split(',').length : 0} {t('Labels')}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {/* Notes Section */}
            <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('Notes')}</h3>
                <div className="bg-gray-50 p-4 rounded-lg">
                    <RichTextEditor
                        content={deal.notes || ''}
                        onChange={(content) => {
                            router.put(route('lead.deals.update', deal.id), {
                                notes: content
                            });
                        }}
                        placeholder={t('Add notes...')}
                        className="min-h-[300px]"
                    />
                </div>
            </div>

            {/* Emails and Discussions Section */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Emails */}
                <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-semibold text-gray-900">{t('Emails')}</h3>
                        <TooltipProvider>
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => setEmailModalOpen(true)}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Send Email')}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <div className="space-y-3 max-h-[400px] overflow-y-auto">
                        {deal.emails && deal.emails.length > 0 ? (
                            deal.emails.map((email: any, index: number) => {
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
                                const cleanText = stripHtmlAndDecode(email.description);
                                return (
                                    <div key={index} className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <div className="bg-gray-100 p-1 rounded-full">
                                                    <svg className="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p className="font-medium text-sm text-gray-900">{email.to}</p>
                                                    <p className="text-xs text-gray-500">{formatDateTime(email.created_at)}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="bg-white rounded-lg p-3 border border-gray-100">
                                            <h4 className="font-semibold text-gray-800 mb-2 text-sm">{email.subject}</h4>
                                            <div className="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap">
                                                {cleanText}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        ) : (
                            <p className="text-gray-500 text-sm text-center py-4">{t('No emails found')}</p>
                        )}
                    </div>
                </div>

                {/* Discussions */}
                <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-semibold text-gray-900">{t('Discussions')}</h3>
                        <TooltipProvider>
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => setDiscussionModalOpen(true)}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Add Message')}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <div className="space-y-3 max-h-[400px] overflow-y-auto">
                        {deal.discussions && deal.discussions.length > 0 ? (
                            deal.discussions.map((discussion: any, index: number) => {
                                return (
                                    <div key={index} className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <div className="bg-gray-100 p-1 rounded-full">
                                                    <svg className="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p className="font-medium text-sm text-gray-900">{discussion.creator?.name || 'Unknown User'}</p>
                                                    <p className="text-xs text-gray-500">{formatDateTime(discussion.created_at)}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="bg-white rounded-lg p-3 border border-gray-100">
                                            <div className="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap">
                                                {discussion.comment}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        ) : (
                            <p className="text-gray-500 text-sm text-center py-4">{t('No discussions found')}</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Email Modal */}
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
                        <div className="flex gap-2 items-end">
                            <div className="flex-1">
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
                            {emailSubjectAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="description">{t('Description')}</Label>
                                <div className="flex gap-2">
                                    {emailDescriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                                </div>
                            </div>
                            <RichTextEditor
                                key={`email-editor-${emailEditorKey}`}
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

            {/* Discussion Modal */}
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
        </div>
    );
}
