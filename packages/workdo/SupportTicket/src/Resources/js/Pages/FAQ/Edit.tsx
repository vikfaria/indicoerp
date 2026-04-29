import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { toast } from 'sonner';


interface FAQ {
  id: number;
  title: string;
  description: string;
}

interface EditFAQProps {
  faq: FAQ;
  onSuccess: () => void;
}

export default function Edit({ faq, onSuccess }: EditFAQProps) {
  const { t } = useTranslation();
  
  const { data, setData, put, processing, errors } = useForm({
    title: faq.title,
    description: faq.description
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(route('support-ticket-faq.update', faq.id), {
      onSuccess: () => {
        onSuccess();
      }
    });
  };

  return (
    <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{t('Edit FAQ')}</DialogTitle>
      </DialogHeader>
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <Label htmlFor="title">{t('Question')}</Label>
          <Input
            id="title"
            value={data.title}
            onChange={(e) => setData('title', e.target.value)}
            placeholder={t('Enter FAQ question')}
            required
          />
          <InputError message={errors.title} />
        </div>

        <div>
          <Label htmlFor="description" required>{t('Answer')} *</Label>
          <RichTextEditor
            content={data.description}
            onChange={(value) => setData('description', value)}
            placeholder={t('Enter FAQ answer')}
          />
          <InputError message={errors.description} />
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