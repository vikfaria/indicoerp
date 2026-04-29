import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { DataTable } from '@/components/ui/data-table';
import { SearchInput } from '@/components/ui/search-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import { Eye, Trash2, FileText, Link, Copy } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/utils/helpers';

interface FormResponse {
  id: number;
  response_data: Record<string, any>;
  created_at: string;
}

interface Form {
  id: number;
  name: string;
  code: string;
}

interface ResponsesProps {
  form: Form & { fields: Array<{ id: number; label: string; type: string }> };
  responses: any;
  auth: any;
}

export default function Responses({ form, responses, auth }: ResponsesProps) {
  const { t } = useTranslation();
  const urlParams = new URLSearchParams(window.location.search);

  const [search, setSearch] = useState(urlParams.get('search') || '');
  const [perPage] = useState(urlParams.get('per_page') || '10');
  const [viewResponse, setViewResponse] = useState<FormResponse | null>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [copiedLink, setCopiedLink] = useState(false);


  const handleDelete = () => {
    if (deleteId) {
      router.delete(route('formbuilder.forms.responses.destroy', [form.id, deleteId]), {
        preserveScroll: true,
        onSuccess: () => setDeleteId(null)
      });
    }
  };

  const handleSearch = () => {
    router.get(route('formbuilder.forms.responses', form.id), {
      search,
      per_page: perPage
    }, {
      preserveState: true,
      replace: true
    });
  };

  const clearSearch = () => {
    setSearch('');
    router.get(route('formbuilder.forms.responses', form.id), { per_page: perPage });
  };

  const copyFormLink = async () => {
    const formUrl = route('formbuilder.public.form.show', form.code);
    try {
      await navigator.clipboard.writeText(formUrl);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 2000);
    } catch (err) {
      console.error('Failed to copy: ', err);
    }
  };

  const fieldColumns = form.fields?.slice(0, 4).map(field => ({
    key: `field_${field.id}`,
    header: field.label,
    sortable: false,
    render: (_: any, response: FormResponse) => {
      const value = response.response_data[field.id];
      if (value === undefined || value === null || value === '') return '-';
      if (typeof value === 'boolean') return value ? t('Yes') : t('No');
      return String(value).length > 50 ? String(value).substring(0, 50) + '...' : String(value);
    }
  })) || [];

  const tableColumns = [
    ...fieldColumns,
    {
      key: 'created_at',
      header: t('Submitted At'),
      sortable: false,
      render: (value: string) => formatDateTime(value)
    },
    {
      key: 'actions',
      header: t('Actions'),
      render: (_: any, response: FormResponse) => (
        <div className="flex gap-1">
          <TooltipProvider>
            <Tooltip >
              <TooltipTrigger asChild>
                <Button variant="ghost" size="sm" onClick={() => setViewResponse(response)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                  <Eye className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent><p>{t('View')}</p></TooltipContent>
            </Tooltip>
            {auth.user?.permissions?.includes('delete-formbuilder-responses') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setDeleteId(response.id)}
                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent><p>{t('Delete')}</p></TooltipContent>
              </Tooltip>
            )}
          </TooltipProvider>
        </div>
      )
    }
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Form Builder'), url: route('formbuilder.forms.index') },
        { label: form.name }
      ]}
      pageTitle={t('Form Responses')}
      backUrl={route('formbuilder.forms.index')}
      pageActions={
        <TooltipProvider>
          <Tooltip >
            <TooltipTrigger asChild>
              <Button
                size="sm"
                variant="outline"
                onClick={copyFormLink}
              >
                {copiedLink ? <Copy className="h-4 w-4" /> : <Link className="h-4 w-4" />}
              </Button>
            </TooltipTrigger>
            <TooltipContent><p>{copiedLink ? t('Copied!') : t('Copy')}</p></TooltipContent>
          </Tooltip>
        </TooltipProvider>
      }
    >
      <Head title={t('Form Responses')} />

      <Card className="shadow-sm">
        <CardContent className="p-6 border-b bg-gray-50/50">
          <div className="flex items-center justify-between gap-4">
            <div className="flex-1 max-w-md">
              <SearchInput
                value={search}
                onChange={(value) => setSearch(value)}
                onSearch={handleSearch}
                placeholder={t('Search responses...')}
              />
            </div>
            <div className="flex items-center gap-3">
              <Select value={perPage} onValueChange={(value) => {
                router.get(route('formbuilder.forms.responses', form.id), {
                  search, per_page: value, page: 1
                }, { preserveState: false, replace: true });
              }}>
                <SelectTrigger className="w-32">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10">{t('10 per page')}</SelectItem>
                  <SelectItem value="25">{t('25 per page')}</SelectItem>
                  <SelectItem value="50">{t('50 per page')}</SelectItem>
                  <SelectItem value="100">{t('100 per page')}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>

        <CardContent className="p-0">
          <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
            <div className="min-w-[800px]">
              <DataTable
                data={responses?.data || []}
                columns={tableColumns}
                className="rounded-none"
                emptyState={
                  <NoRecordsFound
                    icon={FileText}
                    title={t('No responses found')}
                    description={t('No one has submitted this form yet.')}
                    hasFilters={!!search}
                    onClearFilters={clearSearch}
                    className="h-auto"
                  />
                }
              />
            </div>
          </div>
        </CardContent>

        <CardContent className="px-4 py-2 border-t bg-gray-50/30">
          {responses?.total > 0 && (
            <div className="flex items-center justify-between px-2 py-4">
              <div className="text-sm text-muted-foreground">
                {t('Showing')} {responses.from} {t('to')} {responses.to} {t('of')} {responses.total} {t('results')}
              </div>
              <div className="flex items-center space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => router.get(route('formbuilder.forms.responses', form.id), {
                    search, per_page: perPage, page: responses.current_page - 1
                  }, { preserveState: true, replace: true })}
                  disabled={responses.current_page === 1}
                >
                  <ChevronLeft className="h-4 w-4" />
                  {t('Previous')}
                </Button>
                {Array.from({ length: Math.min(5, responses.last_page) }, (_, i) => {
                  const page = i + Math.max(1, responses.current_page - 2);
                  if (page > responses.last_page) return null;
                  return (
                    <Button
                      key={page}
                      variant={page === responses.current_page ? "default" : "outline"}
                      size="sm"
                      onClick={() => router.get(route('formbuilder.forms.responses', form.id), {
                        search, per_page: perPage, page
                      }, { preserveState: true, replace: true })}
                    >
                      {page}
                    </Button>
                  );
                })}
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => router.get(route('formbuilder.forms.responses', form.id), {
                    search, per_page: perPage, page: responses.current_page + 1
                  }, { preserveState: true, replace: true })}
                  disabled={responses.current_page === responses.last_page}
                >
                  {t('Next')}
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* View Response Modal */}
      <Dialog open={!!viewResponse} onOpenChange={() => setViewResponse(null)}>
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          {viewResponse && (
            <>
              <DialogHeader className="mb-3">
                <DialogTitle>{form.name}</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div>
                  <Label className="text-sm font-medium text-gray-700">{t('Submitted At')}</Label>
                  <p className="mt-1 text-sm text-gray-900">{formatDateTime(viewResponse.created_at)}</p>
                </div>

                {form.fields.map((field, index) => {
                  const value = viewResponse.response_data[field.id];
                  return (
                    <div key={index}>
                      <Label className="text-sm font-medium text-gray-700">{field.label}</Label>
                      <p className="mt-1 text-sm text-gray-900">
                        {typeof value === 'boolean' ? (
                          value ? t('Yes') : t('No')
                        ) : value !== undefined && value !== null && value !== '' ? (
                          String(value)
                        ) : (
                          <span className="text-gray-500">{t('No response')}</span>
                        )}
                      </p>
                    </div>
                  );
                })}


              </div>
            </>
          )}
        </DialogContent>
      </Dialog>

      <ConfirmationDialog
        open={!!deleteId}
        onOpenChange={() => setDeleteId(null)}
        title={t('Delete Response')}
        message={t('Are you sure you want to delete this response? This action cannot be undone.')}
        confirmText={t('Delete')}
        onConfirm={handleDelete}
        variant="destructive"
      />
    </AuthenticatedLayout>
  );
}
