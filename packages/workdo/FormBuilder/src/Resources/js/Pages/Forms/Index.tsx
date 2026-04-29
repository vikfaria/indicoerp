import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { DataTable } from '@/components/ui/data-table';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Pagination } from '@/components/ui/pagination';
import NoRecordsFound from '@/components/no-records-found';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import { Plus, Edit as EditIcon, Trash2, BarChart3, FileText, Link, Copy, Zap } from 'lucide-react';
import { formatDateTime } from '@/utils/helpers';

interface Form {
  id: number;
  name: string;
  code: string;
  is_active: boolean;
  responses_count: number;
  fields_count: number;
  created_at: string;
}

interface FormsIndexProps {
  forms: {
    data: Form[];
    links: any;
    meta: any;
  };
  auth: any;
}

export default function FormsIndex({ forms, auth }: FormsIndexProps) {
  const { t } = useTranslation();
  const urlParams = new URLSearchParams(window.location.search);

  const [filters, setFilters] = useState({
    name: urlParams.get('name') || ''
  });

  const [perPage] = useState(urlParams.get('per_page') || '10');
  const [sortField, setSortField] = useState(urlParams.get('sort') || '');
  const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
  const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
  const [copiedCode, setCopiedCode] = useState<string | null>(null);


  const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
    routeName: 'formbuilder.forms.destroy',
    defaultMessage: t('Are you sure you want to delete this form?')
  });

  const handleFilter = () => {
    router.get(route('formbuilder.forms.index'), {
      ...filters,
      per_page: perPage,
      sort: sortField,
      direction: sortDirection,
      view: viewMode
    }, {
      preserveState: true,
      replace: true
    });
  };

  const handleSort = (field: string) => {
    const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
    setSortField(field);
    setSortDirection(direction);
    router.get(route('formbuilder.forms.index'), {
      ...filters,
      per_page: perPage,
      sort: field,
      direction,
      view: viewMode
    }, {
      preserveState: true,
      replace: true
    });
  };

  const clearFilters = () => {
    setFilters({ name: '' });
    router.get(route('formbuilder.forms.index'), { per_page: perPage, view: viewMode });
  };

  const copyFormLink = async (formCode: string) => {
    const formUrl = route('formbuilder.public.form.show', formCode);
    try {
      await navigator.clipboard.writeText(formUrl);
      setCopiedCode(formCode);
      setTimeout(() => setCopiedCode(null), 2000);
    } catch (err) {
      console.error('Failed to copy: ', err);
    }
  };

  const tableColumns = [
    {
      key: 'name',
      header: t('Name'),
      sortable: true
    },
    {
      key: 'fields_count',
      header: t('Fields'),
      sortable: true
    },
    {
      key: 'responses_count',
      header: t('Responses'),
      sortable: true
    },
    {
      key: 'is_active',
      header: t('Status'),
      sortable: true,
      render: (value: boolean) => (
        <span className={`px-2 py-1 rounded-full text-sm ${value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
          }`}>
          {value ? t('Active') : t('Inactive')}
        </span>
      )
    },
    {
      key: 'created_at',
      header: t('Created At'),
      sortable: true,
      render: (value: string) => formatDateTime(value)
    },
    {
      key: 'actions',
      header: t('Actions'),
      render: (_: any, form: Form) => (
        <div className="flex gap-1">
          <TooltipProvider>
            <Tooltip >
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => copyFormLink(form.code)}
                  className={`h-8 w-8 p-0 transition-colors ${copiedCode === form.code
                    ? 'text-green-600 hover:text-green-700'
                    : 'text-purple-600 hover:text-purple-700'
                    }`}
                >
                  {copiedCode === form.code ? <Copy className="h-4 w-4" /> : <Link className="h-4 w-4" />}
                </Button>
              </TooltipTrigger>
              <TooltipContent><p>{copiedCode === form.code ? t('Copied!') : t('Copy Link')}</p></TooltipContent>
            </Tooltip>
            {auth.user?.permissions?.includes('view-formbuilder-responses') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.responses', form.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                    <BarChart3 className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent><p>{t('Responses')}</p></TooltipContent>
              </Tooltip>
            )}
            {auth.user?.permissions?.includes('manage-formbuilder-conversions') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.conversion', form.id))} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                    <Zap className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent><p>{t('Convert')}</p></TooltipContent>
              </Tooltip>
            )}
            {auth.user?.permissions?.includes('edit-formbuilder-form') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.edit', form.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                    <EditIcon className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent><p>{t('Edit')}</p></TooltipContent>
              </Tooltip>
            )}
            {auth.user?.permissions?.includes('delete-formbuilder-form') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => openDeleteDialog(form.id)}
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
      breadcrumbs={[{ label: t('Form Builder') }]}
      pageTitle={t('Manage Form Builder')}
      pageActions={
        <div className="flex gap-2">
          <TooltipProvider>
            {auth.user?.permissions?.includes('create-formbuilder-form') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button size="sm" onClick={() => router.visit(route('formbuilder.forms.create'))}>
                    <Plus className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{t('Create')}</p>
                </TooltipContent>
              </Tooltip>
            )}
          </TooltipProvider>
        </div>
      }
    >
      <Head title={t('Form List')} />

      <Card className="shadow-sm">
        <CardContent className="p-6 border-b bg-gray-50/50">
          <div className="flex items-center justify-between gap-4">
            <div className="flex-1 max-w-md">
              <SearchInput
                value={filters.name}
                onChange={(value) => setFilters({ ...filters, name: value })}
                onSearch={handleFilter}
                placeholder={t('Search forms...')}
              />
            </div>
            <div className="flex items-center gap-3">
              <ListGridToggle
                currentView={viewMode}
                routeName="formbuilder.forms.index"
                filters={{ ...filters, per_page: perPage }}
              />
              <PerPageSelector
                routeName="formbuilder.forms.index"
                filters={{ ...filters, view: viewMode }}
              />
            </div>
          </div>
        </CardContent>

        <CardContent className="p-0">
          {viewMode === 'list' ? (
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
              <div className="min-w-[800px]">
                <DataTable
                  data={forms.data}
                  columns={tableColumns}
                  onSort={handleSort}
                  sortKey={sortField}
                  sortDirection={sortDirection as 'asc' | 'desc'}
                  className="rounded-none"
                  emptyState={
                    <NoRecordsFound
                      icon={FileText}
                      title={t('No forms found')}
                      description={t('Get started by creating your first form.')}
                      hasFilters={!!filters.name}
                      onClearFilters={clearFilters}
                      createPermission="create-formbuilder"
                      onCreateClick={() => router.visit(route('formbuilder.forms.create'))}
                      createButtonText={t('Create Form')}
                      className="h-auto"
                    />
                  }
                />
              </div>
            </div>
          ) : (
            <div className="overflow-auto max-h-[70vh] p-6">
              {forms.data?.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                  {forms.data?.map((form) => (
                    <Card key={form.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                      <div className="absolute top-0 right-0 w-0 h-0 border-l-[20px] border-l-transparent border-t-[20px] border-t-primary/20"></div>
                      <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                        <div className="flex items-center gap-3">
                          <div className="p-2 bg-primary/10 rounded-lg">
                            <FileText className="h-5 w-5 text-primary" />
                          </div>
                          <div className="min-w-0 flex-1">
                            <h3 className="font-semibold text-sm text-gray-900">{form.name}</h3>
                          </div>
                        </div>
                      </div>
                      <div className="p-4 flex-1 min-h-0">
                        <div className="grid grid-cols-2 gap-4 mb-4">
                          <div className="text-xs min-w-0">
                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Fields')}</p>
                            <p className="font-medium text-xs">{form.fields_count}</p>
                          </div>
                          <div className="text-xs min-w-0">
                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Responses')}</p>
                            <p className="font-medium text-xs">{form.responses_count}</p>
                          </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div className="text-xs min-w-0">
                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                            <span className={`px-2 py-1 rounded-full text-xs ${form.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                              }`}>
                              {form.is_active ? t('Active') : t('Inactive')}
                            </span>
                          </div>
                          <div className="text-xs min-w-0">
                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Created')}</p>
                            <p className="font-medium text-xs">{formatDateTime(form.created_at)}</p>
                          </div>
                        </div>
                      </div>
                      <div className="flex justify-between gap-1 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                        <div className="flex gap-1">
                          <TooltipProvider>
                            <Tooltip >
                              <TooltipTrigger asChild>
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => copyFormLink(form.code)}
                                  className={`h-8 w-8 p-0 transition-colors ${copiedCode === form.code
                                    ? 'text-green-600 hover:text-green-700'
                                    : 'text-purple-600 hover:text-purple-700'
                                    }`}
                                >
                                  {copiedCode === form.code ? <Copy className="h-4 w-4" /> : <Link className="h-4 w-4" />}
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent><p>{copiedCode === form.code ? t('Copied!') : t('Copy Link')}</p></TooltipContent>
                            </Tooltip>
                            {auth.user?.permissions?.includes('view-formbuilder-responses') && (
                              <Tooltip >
                                <TooltipTrigger asChild>
                                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.responses', form.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                    <BarChart3 className="h-4 w-4" />
                                  </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Responses')}</p></TooltipContent>
                              </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('manage-formbuilder-conversions') && (
                              <Tooltip >
                                <TooltipTrigger asChild>
                                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.conversion', form.id))} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                    <Zap className="h-4 w-4" />
                                  </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Convert')}</p></TooltipContent>
                              </Tooltip>
                            )}
                          </TooltipProvider>
                        </div>
                        <div className="flex gap-1">
                          <TooltipProvider>
                            {auth.user?.permissions?.includes('edit-formbuilder-form') && (
                              <Tooltip >
                                <TooltipTrigger asChild>
                                  <Button variant="ghost" size="sm" onClick={() => router.visit(route('formbuilder.forms.edit', form.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                    <EditIcon className="h-4 w-4" />
                                  </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                              </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('delete-formbuilder-form') && (
                              <Tooltip >
                                <TooltipTrigger asChild>
                                  <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => openDeleteDialog(form.id)}
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
                      </div>
                    </Card>
                  ))}
                </div>
              ) : (
                <NoRecordsFound
                  icon={FileText}
                  title={t('No forms found')}
                  description={t('Get started by creating your first form.')}
                  hasFilters={!!filters.name}
                  onClearFilters={clearFilters}
                  createPermission="create-formbuilder"
                  onCreateClick={() => router.visit(route('formbuilder.forms.create'))}
                  createButtonText={t('Create Form')}
                />
              )}
            </div>
          )}
        </CardContent>

        <CardContent className="px-4 py-2 border-t bg-gray-50/30">
          <Pagination
            data={forms}
            routeName="formbuilder.forms.index"
            filters={{ ...filters, per_page: perPage, view: viewMode }}
          />
        </CardContent>
      </Card>

      <ConfirmationDialog
        open={deleteState.isOpen}
        onOpenChange={closeDeleteDialog}
        title={t('Delete Form')}
        message={deleteState.message}
        confirmText={t('Delete')}
        onConfirm={confirmDelete}
        variant="destructive"
      />
    </AuthenticatedLayout>
  );
}