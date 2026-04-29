import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import InputError from '@/components/ui/input-error';


interface QuickLink {
    id: number;
    title: string;
    icon: string;
    link: string;
}

interface EditModalProps {
    quickLink: QuickLink;
    onSuccess: () => void;
}

export default function EditModal({ quickLink, onSuccess }: EditModalProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm({
        title: quickLink.title,
        icon: quickLink.icon,
        link: quickLink.link
    });

    const iconOptions = [
        { value: 'BookOpen', label: 'BookOpen' },
        { value: 'Video', label: 'Video' },
        { value: 'Lightbulb', label: 'Lightbulb' },
        { value: 'Code', label: 'Code' },
        { value: 'MessageCircle', label: 'MessageCircle' },
        { value: 'FileText', label: 'FileText' },
        { value: 'HelpCircle', label: 'HelpCircle' },
        { value: 'Settings', label: 'Settings' },
        { value: 'Download', label: 'Download' },
        { value: 'ExternalLink', label: 'ExternalLink' }
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('support-ticket.quick-links.update', quickLink.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Edit Quick Link')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="title">{t('Title')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label htmlFor="icon">{t('Icon')}</Label>
                    <Select value={data.icon} onValueChange={(value) => setData('icon', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select icon')} />
                        </SelectTrigger>
                        <SelectContent>
                            {iconOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.icon} />
                </div>

                <div>
                    <Label htmlFor="link">{t('Link')}</Label>
                    <Input
                        id="link"
                        value={data.link}
                        onChange={(e) => setData('link', e.target.value)}
                        placeholder={t('Enter link URL')}
                        required
                    />
                    <InputError message={errors.link} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
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