import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Pagination } from '@/components/ui/pagination';
import { Plus, Edit, Trash2, Upload, Download } from 'lucide-react';
import Create from './Create';
import EditFAQ from './Edit';
import ImportDialog from './ImportDialog';
import { formatDateTime } from '@/utils/helpers';

interface FAQ {
  id: number;
  title: string;
  description: string;
  created_at: string;
}

interface Props {
  faqs: {
    data: FAQ[];
    links: any;
    meta: any;
  };
}

export default function Index({ faqs }: Props) {
  const { t } = useTranslation();
  const urlParams = new URLSearchParams(window.location.search);
  
  const [filters, setFilters] = useState({
    search: urlParams.get('search') || '',
  });
  
  const [perPage, setPerPage] = useState(urlParams.get('per_page') || '15');
  const [sortField, setSortField] = useState(urlParams.get('sort') || '');
  const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
  
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [editingFaq, setEditingFaq] = useState<FAQ | null>(null);


  const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
    routeName: 'support-ticket-faq.destroy',
    defaultMessage: t('Are you sure you want to delete this FAQ?')
  });

  const handleCreateSuccess = () => {
    setShowCreateModal(false);
    router.reload();
  };

  const handleEditSuccess = () => {
    setShowEditModal(false);
    setEditingFaq(null);
    router.reload();
  };

  const handleFilter = () => {
    router.get(route('support-ticket-faq.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
      preserveState: true,
      replace: true
    });
  };

  const handlePerPageChange = (newPerPage: string) => {
    setPerPage(newPerPage);
    router.get(route('support-ticket-faq.index'), {...filters, per_page: newPerPage, sort: sortField, direction: sortDirection}, {
      preserveState: true,
      replace: true
    });
  };

  const handleSort = (field: string) => {
    const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
    setSortField(field);
    setSortDirection(direction);
    router.get(route('support-ticket-faq.index'), {...filters, per_page: perPage, sort: field, direction}, {
      preserveState: true,
      replace: true
    });
  };

  const openEditModal = (faq: FAQ) => {
    setEditingFaq(faq);
    setShowEditModal(true);
  };

  const tableColumns = [
    {
      key: 'title',
      header: t('Title'),
      sortable: true
    },
    {
      key: 'description',
      header: t('Description'),
      render: (value: string) => {
        const text = value?.replace(/<[^>]*>/g, '') || '';
        return text.length > 100 ? text.substring(0, 100) + '...' : text;
      }
    },
    {
      key: 'created_at',
      header: t('Created'),
      sortable: true,
      render: (value: string) => formatDateTime(value)
    },

    {
      key: 'actions',
      header: t('Actions'),
      render: (_: any, faq: FAQ) => (
        <div className="flex gap-1">
          <TooltipProvider>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button 
                  variant="ghost" 
                  size="sm"
                  onClick={() => openEditModal(faq)}
                  className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                >
                  <Edit className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{t('Edit')}</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button 
                  variant="ghost" 
                  size="sm" 
                  className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                  onClick={() => openDeleteDialog(faq.id)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{t('Delete')}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      )
    }
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        {label: t('Support Tickets'), url: route('dashboard.support-tickets')},
        {label: t('FAQ')}
      ]}
      pageTitle={t('Manage FAQ')}
      pageActions={
        <div className="flex gap-2">
          <TooltipProvider>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="outline" size="sm" onClick={() => setShowImportModal(true)}>
                  <Upload className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{t('Import')}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
          <TooltipProvider>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button size="sm" onClick={() => setShowCreateModal(true)}>
                  <Plus className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{t('Create')}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      }
    >
      <Head title={t('FAQ')} />
      
      <Card className="shadow-sm">
        <CardContent className="p-6 border-b bg-gray-50/50">
          <div className="flex items-center justify-between gap-4">
            <div className="flex-1 max-w-md">
              <SearchInput
                value={filters.search}
                onChange={(value) => setFilters({...filters, search: value})}
                onSearch={handleFilter}
                placeholder={t('Search FAQ...')}
              />
            </div>
            <div className="flex items-center gap-3">
              <PerPageSelector
                routeName="support-ticket-faq.index"
                filters={filters}
                currentPerPage={perPage}
                onPerPageChange={handlePerPageChange}
              />
            </div>
          </div>
        </CardContent>

        <CardContent className="p-0">
          <div className="min-w-[800px]">
            <DataTable
                data={faqs.data}
                columns={tableColumns}
                onSort={handleSort}
                sortKey={sortField}
                sortDirection={sortDirection as 'asc' | 'desc'}
                className="rounded-none"
                emptyState={
                  <div className="flex flex-col items-center justify-center py-12">
                    <div className="text-center">
                      <h3 className="text-lg font-medium text-gray-900 mb-2">{t('No FAQ found')}</h3>
                      <p className="text-gray-500 mb-4">{t('Get started by creating your first FAQ.')}</p>
                      <Button onClick={() => setShowCreateModal(true)}>
                        <Plus className="h-4 w-4 mr-2" />
                        {t('Create FAQ')}
                      </Button>
                    </div>
                  </div>
                }
              />
          </div>
        </CardContent>

        <CardContent className="px-4 py-2 border-t bg-gray-50/30">
          <Pagination
            data={faqs || { data: [], links: [], meta: {} }}
            routeName="support-ticket-faq.index"
            filters={{...filters, per_page: perPage}}
          />
        </CardContent>
      </Card>
      
      <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
        <Create onSuccess={handleCreateSuccess} />
      </Dialog>
      
      <Dialog open={showEditModal} onOpenChange={setShowEditModal}>
        {editingFaq && (
          <EditFAQ 
            faq={editingFaq} 
            onSuccess={handleEditSuccess} 
          />
        )}
      </Dialog>

      <Dialog open={showImportModal} onOpenChange={setShowImportModal}>
        <ImportDialog onSuccess={() => {
          setShowImportModal(false);
          router.reload();
        }} />
      </Dialog>

      <ConfirmationDialog
        open={deleteState.isOpen}
        onOpenChange={closeDeleteDialog}
        title={t('Delete FAQ')}
        message={deleteState.message}
        confirmText={t('Delete')}
        onConfirm={confirmDelete}
        variant="destructive"
      />
    </AuthenticatedLayout>
  );
}