import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { toast } from 'sonner';

interface Category {
  id: number;
  title: string;
}

interface CreateKnowledgeProps {
  categories: Category[];
  onSuccess: () => void;
}

export default function Create({ categories, onSuccess }: CreateKnowledgeProps) {
  const { t } = useTranslation();
  
  const { data, setData, post, processing, errors } = useForm({
    title: '',
    description: '',
    category: ''
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('support-ticket-knowledge.store'), {
      onSuccess: () => onSuccess()
    });
  };

  return (
    <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto z-50">
      <DialogHeader>
        <DialogTitle>{t('Create Knowledge Base')}</DialogTitle>
      </DialogHeader>
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <Label htmlFor="title">{t('Title')}</Label>
          <Input
            id="title"
            value={data.title}
            onChange={(e) => setData('title', e.target.value)}
            placeholder={t('Enter knowledge base title')}
            required
          />
          <InputError message={errors.title} />
        </div>

        <div>
          <Label htmlFor="category" required>{t('Category')}</Label>
          <Select value={data.category} onValueChange={(value) => setData('category', value)}>
            <SelectTrigger>
              <SelectValue placeholder={t('Select Category')} />
            </SelectTrigger>
            <SelectContent>
              {categories.map((category) => (
                <SelectItem key={category.id} value={category.title}>
                  {category.title}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={errors.category} />
        </div>

        <div className="relative z-10">
          <Label htmlFor="description" required>{t('Description')}</Label>
          <div className="mt-2">
            <RichTextEditor
              value={data.description}
              onChange={(value) => setData('description', value)}
              placeholder={t('Enter knowledge base description')}
            />
          </div>
          <InputError message={errors.description} />
        </div>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onSuccess}>
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