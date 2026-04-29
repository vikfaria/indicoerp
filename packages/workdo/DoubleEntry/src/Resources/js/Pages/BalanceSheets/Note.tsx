import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface NoteProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    balanceSheetId: number;
}

export default function Note({ open, onOpenChange, balanceSheetId }: NoteProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        note_title: '',
        note_content: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('double-entry.balance-sheets.add-note', balanceSheetId), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            }
        });
    };

    const handleClose = () => {
        reset();
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('Add Note')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="note_title">{t('Note Title')}</Label>
                        <Input
                            id="note_title"
                            value={data.note_title}
                            onChange={(e) => setData('note_title', e.target.value)}
                            className={errors.note_title ? 'border-red-500' : ''}
                            placeholder={t('Enter note title')}
                            required
                        />
                        {errors.note_title && <p className="text-red-500 text-sm">{errors.note_title}</p>}
                    </div>
                    <div>
                        <Label htmlFor="note_content">{t('Note Content')}</Label>
                        <Textarea
                            id="note_content"
                            value={data.note_content}
                            onChange={(e) => setData('note_content', e.target.value)}
                            className={errors.note_content ? 'border-red-500' : ''}
                            placeholder={t('Enter note content')}
                            rows={4}
                            required
                        />
                        {errors.note_content && <p className="text-red-500 text-sm">{errors.note_content}</p>}
                    </div>
                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? t('Adding...') : t('Add Note')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
