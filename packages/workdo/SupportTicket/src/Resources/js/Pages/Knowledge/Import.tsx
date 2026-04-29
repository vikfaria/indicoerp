import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Upload, Download } from 'lucide-react';
import { toast } from 'sonner';

export default function Import() {
  const { t } = useTranslation();
  const [csvData, setCsvData] = useState<string[][]>([]);
  const [csvHeaders, setCsvHeaders] = useState<string[]>([]);
  const [columnMapping, setColumnMapping] = useState<{[key: string]: number}>({});
  const [showMapping, setShowMapping] = useState(false);
  const [isImporting, setIsImporting] = useState(false);

  const { data, setData, post, processing } = useForm({
    file: null as File | null
  });

  const handleFileUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!data.file) {
      toast.error(t('Please select a file'));
      return;
    }

    const formData = new FormData();
    formData.append('file', data.file);

    try {
      const response = await fetch(route('knowledge.import'), {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });

      const result = await response.json();
      
      if (result.error) {
        toast.error(result.error);
      } else {
        // Parse CSV preview data
        const parser = new DOMParser();
        const doc = parser.parseFromString(result.html, 'text/html');
        const table = doc.querySelector('table');
        
        if (table) {
          const rows = Array.from(table.querySelectorAll('tr'));
          const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent || '');
          const dataRows = rows.slice(1).map(row => 
            Array.from(row.querySelectorAll('td')).map(td => td.textContent || '')
          );
          
          setCsvHeaders(headers);
          setCsvData(dataRows);
          setShowMapping(true);
        }
      }
    } catch (error) {
      toast.error(t('Failed to upload file'));
    }
  };

  const handleColumnMapping = (columnIndex: number, fieldName: string) => {
    const newMapping = { ...columnMapping };
    
    // Remove existing mapping for this field
    Object.keys(newMapping).forEach(key => {
      if (newMapping[key] === columnIndex) {
        delete newMapping[key];
      }
    });
    
    if (fieldName) {
      newMapping[fieldName] = columnIndex;
    }
    
    setColumnMapping(newMapping);
  };

  const handleImport = async () => {
    if (Object.keys(columnMapping).length < 2) {
      toast.error(t('Please map at least title and description columns'));
      return;
    }

    setIsImporting(true);

    try {
      const response = await fetch(route('knowledge.import.data'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          title: columnMapping.title,
          description: columnMapping.description,
          category: csvData.map(row => row[columnMapping.category || 0])
        })
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(t('Knowledge base imported successfully'));
        window.location.href = route('support-ticket-knowledge.index');
      } else {
        toast.error(result.message || t('Import failed'));
      }
    } catch (error) {
      toast.error(t('Import failed'));
    } finally {
      setIsImporting(false);
    }
  };

  const downloadSample = () => {
    const csvContent = "title,description,category\nSample Knowledge Title,Sample knowledge description content,General";
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sample_knowledge.csv';
    a.click();
    window.URL.revokeObjectURL(url);
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Support Tickets'), href: route('support-ticket-knowledge.index') },
        { label: t('Knowledge Base'), href: route('support-ticket-knowledge.index') },
        { label: t('Import') }
      ]}
      pageTitle={t('Import Knowledge Base')}
    >
      <Head title={t('Import Knowledge Base')} />

      <Card>
        <CardHeader>
          <CardTitle>{t('Import Knowledge Base')}</CardTitle>
        </CardHeader>
        <CardContent>
          {!showMapping ? (
            <form onSubmit={handleFileUpload} className="space-y-4">
              <div>
                <Label className="text-red-600">{t('Download Sample Knowledge CSV File')}</Label>
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm" 
                  onClick={downloadSample}
                  className="ml-2"
                >
                  <Download className="h-4 w-4" />
                </Button>
              </div>
              
              <div>
                <Label htmlFor="file">{t('Select CSV File')}</Label>
                <Input
                  id="file"
                  type="file"
                  accept=".csv"
                  onChange={(e) => setData('file', e.target.files?.[0] || null)}
                  required
                />
              </div>
              
              <div className="flex gap-2">
                <Button type="submit" disabled={processing}>
                  <Upload className="h-4 w-4 mr-2" />
                  {processing ? t('Uploading...') : t('Upload')}
                </Button>
                <Button 
                  type="button" 
                  variant="outline" 
                  onClick={() => window.history.back()}
                >
                  {t('Cancel')}
                </Button>
              </div>
            </form>
          ) : (
            <div className="space-y-4">
              <div className="overflow-auto max-h-96 border rounded">
                <table className="w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-50">
                      {csvHeaders.map((header, index) => (
                        <th key={index} className="border p-2 text-left">
                          <div className="space-y-2">
                            <div className="font-medium">{header}</div>
                            <Select 
                              value={Object.keys(columnMapping).find(key => columnMapping[key] === index) || ''}
                              onValueChange={(value) => handleColumnMapping(index, value)}
                            >
                              <SelectTrigger className="w-full">
                                <SelectValue placeholder={t('Select field')} />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="">{t('Skip')}</SelectItem>
                                <SelectItem value="title">{t('Title')}</SelectItem>
                                <SelectItem value="description">{t('Description')}</SelectItem>
                                <SelectItem value="category">{t('Category')}</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {csvData.slice(0, 10).map((row, rowIndex) => (
                      <tr key={rowIndex}>
                        {row.map((cell, cellIndex) => (
                          <td key={cellIndex} className="border p-2">{cell}</td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              
              <div className="flex gap-2">
                <Button 
                  onClick={handleImport} 
                  disabled={isImporting || Object.keys(columnMapping).length < 2}
                >
                  {isImporting ? t('Importing...') : t('Import')}
                </Button>
                <Button 
                  variant="outline" 
                  onClick={() => window.history.back()}
                >
                  {t('Cancel')}
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}