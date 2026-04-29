import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { MessageSquare } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDateTime } from '@/utils/helpers';
import { Deal } from '../types';

interface DiscussionsProps {
    deal: Deal;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Discussions({ deal, onRegisterAddHandler }: DiscussionsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => setDiscussionModalOpen(true));
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [discussionModalOpen, setDiscussionModalOpen] = useState(false);
    const [discussionForm, setDiscussionForm] = useState({ message: '' });

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
        <>
            <div className="space-y-3 max-h-[75vh] overflow-y-auto">
                {deal.discussions && deal.discussions.length > 0 ? (
                    deal.discussions.map((discussion: any, index: number) => {
                        return (
                            <div key={index} className="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 rounded-r-lg p-5 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className="bg-green-100 p-2 rounded-full">
                                            <MessageSquare className="h-4 w-4 text-green-600" />
                                        </div>
                                        <div>
                                            <p className="font-semibold text-gray-900">{discussion.creator?.name || 'Unknown User'}</p>
                                            <p className="text-xs text-gray-500">{formatDateTime(discussion.created_at)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white rounded-lg p-4 border border-gray-100">
                                    <div className="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                                        {discussion.comment}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                ) : (
                    <NoRecordsFound
                        icon={MessageSquare}
                        title={t('No Discussions found')}
                        description={t('Get started by adding your first discussion.')}
                        onCreateClick={() => setDiscussionModalOpen(true)}
                        createButtonText={t('Add Message')}
                        className="h-auto"
                    />
                )}
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