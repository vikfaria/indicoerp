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


interface Knowledge {
  id: number;
  title: string;
  description: string;
  category?: string;
}

interface Category {
  id: number;
  title: string;
}

interface EditKnowledgeProps {
  knowledge: Knowledge;
  categories: Category[];
  onSuccess: () => void;
}

export default function Edit({ knowledge, categories, onSuccess }: EditKnowledgeProps) {
  const { t } = useTranslation();
  
  const { data, setData, put, processing, errors } = useForm({
    title: knowledge.title,
    description: knowledge.description,
    category: knowledge.category || ''
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(route('support-ticket-knowledge.update', knowledge.id), {
      onSuccess: () => onSuccess()
    });
  };

  return (
    <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{t('Edit Knowledge Base')}</DialogTitle>
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

        <div>
          <Label htmlFor="description" required>{t('Description')}</Label>
          <RichTextEditor
            content={data.description}
            onChange={(value) => setData('description', value)}
            placeholder={t('Enter knowledge base description')}
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