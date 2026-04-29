import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { EditAnnouncementCategoryProps, AnnouncementCategoryFormData } from './types';

export default function Edit({ announcementcategory, onSuccess }: EditAnnouncementCategoryProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<AnnouncementCategoryFormData>({
        announcement_category: announcementcategory.announcement_category ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.announcement-categories.update', announcementcategory.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Announcement Category')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="announcement_category">{t('Announcement Category')}</Label>
                    <Input
                        id="announcement_category"
                        type="text"
                        value={data.announcement_category}
                        onChange={(e) => setData('announcement_category', e.target.value)}
                        placeholder={t('Enter Announcement Category')}
                        required
                    />
                    <InputError message={errors.announcement_category} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}