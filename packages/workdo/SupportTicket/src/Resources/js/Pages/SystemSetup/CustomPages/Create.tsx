import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import InputError from '@/components/ui/input-error';

interface CreateProps {
    onSuccess: () => void;
}

export default function Create({ onSuccess }: CreateProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        contents: '',
        enable_page_footer: 'on'
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.custom-pages.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Create Custom Page')}</DialogTitle>
                <DialogDescription>
                    {t('Create a new custom page for your website')}
                </DialogDescription>
            </DialogHeader>
            
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title">{t('Title')}</Label>
                    <Input
                        id="title"
                        type="text"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter page title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter page description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div>
                    <Label htmlFor="contents" required>{t('Contents')}</Label>
                    <RichTextEditor
                        content={data.contents}
                        onChange={(content) => setData('contents', content)}
                        placeholder={t('Enter page contents')}
                        className="[&_.ProseMirror]:min-h-[300px]"
                        required
                    />
                    <InputError message={errors.contents} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="enable_page_footer"
                        checked={data.enable_page_footer === 'on'}
                        onCheckedChange={(checked) => setData('enable_page_footer', checked ? 'on' : 'off')}
                    />
                    <Label htmlFor="enable_page_footer">{t('Enable Page Footer')}</Label>
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}