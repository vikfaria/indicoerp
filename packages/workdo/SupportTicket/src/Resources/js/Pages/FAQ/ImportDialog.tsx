import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Upload, Download } from 'lucide-react';
import { toast } from 'sonner';

interface ImportDialogProps {
  onSuccess: () => void;
}

export default function ImportDialog({ onSuccess }: ImportDialogProps) {
  const { t } = useTranslation();
  const [isUploading, setIsUploading] = useState(false);

  const { data, setData, processing } = useForm({
    file: null as File | null
  });

  const downloadSample = () => {
    const csvContent = "title,description\nSample FAQ Question,Sample FAQ Answer";
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'faq_sample.csv';
    a.click();
    window.URL.revokeObjectURL(url);
  };

  const handleImport = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!data.file) {
      toast.error(t('Please select a file'));
      return;
    }

    setIsUploading(true);

    try {
      const formData = new FormData();
      formData.append('file', data.file);

      const response = await fetch(route('faq.import.data'), {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });

      const result = await response.json();
      
      if (result.success || response.ok) {
        toast.success(t('FAQ imported successfully'));
        onSuccess();
      } else {
        toast.error(result.message || t('Import failed'));
      }
    } catch (error) {
      toast.error(t('Import failed'));
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{t('Import FAQ')}</DialogTitle>
      </DialogHeader>
      
      <form onSubmit={handleImport} className="space-y-4">
        <div>
          <Label className="text-sm font-medium">{t('Download Sample CSV File')}</Label>
          <div className="mt-2">
            <Button 
              type="button" 
              variant="outline" 
              size="sm" 
              onClick={downloadSample}
            >
              <Download className="h-4 w-4 mr-2" />
              {t('Download Sample')}
            </Button>
          </div>
        </div>
        
        <div>
          <Label htmlFor="file">{t('Select CSV File')}</Label>
          <Input
            id="file"
            type="file"
            accept=".csv"
            onChange={(e) => setData('file', e.target.files?.[0] || null)}
            required
            className="mt-1"
          />
        </div>
        
        <div className="flex gap-2 pt-4">
          <Button type="submit" disabled={isUploading || !data.file}>
            <Upload className="h-4 w-4 mr-2" />
            {isUploading ? t('Importing...') : t('Import')}
          </Button>
        </div>
      </form>
    </DialogContent>
  );
}