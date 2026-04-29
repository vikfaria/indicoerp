import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Eye, FileText as FileTextIcon, Download, FileImage, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditDocument from './Edit';
import View from './View';
import Action from './Action';
import NoRecordsFound from '@/components/no-records-found';
import { Document, DocumentsIndexProps, DocumentFilters, DocumentModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const pageProps = usePage<DocumentsIndexProps>().props;
    const { cases, accounts, contacts, caseTypes, imageUrlPrefix } = pageProps;
    const { documents, auth, documentcategories, users } = usePage<DocumentsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<DocumentFilters>({
        title: urlParams.get('title') || '',
        document_category_id: urlParams.get('document_category_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<DocumentModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Document | null>(null);
    const [actionItem, setActionItem] = useState<Document | null>(null);

    const [showFilters, setShowFilters] = useState(false);



    const pageButtons = usePageButtons('googleDriveBtn', { module: 'Document', settingKey: 'GoogleDrive Document' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Document', settingKey: 'OneDrive Document' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.documents.destroy',
        defaultMessage: t('Are you sure you want to delete this document?')
    });

    const handleFilter = () => {
        router.get(route('hrm.documents.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.documents.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            title: '',
            document_category_id: '',
        });
        router.get(route('hrm.documents.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Document | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Title'),
            sortable: true
        },
        {
            key: 'document_category_id',
            header: t('Document Category'),
            sortable: false,
            render: (value: string, row: any) => {
                const modelData = documentcategories?.find(item => item.id.toString() === value?.toString());
                return (
                    <span className="px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                        {modelData?.document_type || value || '-'}
                    </span>
                );
            }
        },
        {
            key: 'effective_date',
            header: t('Effective Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'uploaded_by',
            header: t('Uploaded By'),
            sortable: false,
            render: (value: any, row: any) => row.uploaded_by?.name || '-'
        },
        {
            key: 'approved_by',
            header: t('Approved By'),
            sortable: false,
            render: (value: any, row: any) => row.approved_by?.name || '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: any) => {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'approve': 'bg-green-100 text-green-800',
                    'reject': 'bg-red-100 text-red-800'
                };
                const statusLabels = { 'pending': 'Pending', 'approve': 'Approved', 'reject': 'Rejected' };
                const status = statusLabels[value] || value;
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value] || 'bg-gray-100 text-gray-800'}`}>
                        {t(status)}
                    </span>
                );
            }
        },
        {
            key: 'document',
            header: t('Document'),
            sortable: false,
            render: (_: any, document: Document) => (
                document.document ? (
                    <a href={getImagePath(document.document)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <FileImage className="h-4 w-4" />
                    </a>
                ) : '-'
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-hrm-documents', 'manage-hrm-documents-status', 'download-hrm-documents', 'edit-hrm-documents', 'delete-hrm-documents'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, docItem: Document) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                      
                        {auth.user?.permissions?.includes('manage-hrm-documents-status') && docItem.status === 'pending' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setActionItem(docItem)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Action')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {docItem.document && (
                            <>
                                {auth.user?.permissions?.includes('download-hrm-documents') && docItem.document && docItem.status === 'approve' && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    const link = document.createElement('a');
                                                    link.href = `${imageUrlPrefix}/${docItem.document}`;
                                                    link.download = docItem.document.split('/').pop() || 'download';
                                                    link.click();
                                                }}
                                                className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700"
                                            >
                                                <Download className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Download')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                            </>
                        )}
                        {auth.user?.permissions?.includes('view-hrm-documents') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(docItem)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-hrm-documents') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', docItem)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-hrm-documents') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(docItem.id)}
                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Delete')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                { label: t('Documents') }
            ]}
            pageTitle={t('Manage Documents')}
            pageActions={
                <TooltipProvider>
                    <div className="flex items-center gap-2">
                        {pageButtons.map((button) => (
                            <div key={button.id} >
                                {button.component}
                            </div>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <div key={button.id} >
                                {button.component}
                            </div>
                        ))}
                        {auth.user?.permissions?.includes('create-hrm-documents') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                </TooltipProvider>
            }
        >
            <Head title={t('Documents')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({ ...filters, title: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Documents...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="hrm.documents.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="hrm.documents.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.document_category_id].filter(f => f !== '' && f !== null && f !== undefined).length;
                                    return activeFilters > 0 && (
                                        <span className="absolute -top-2 -right-2 bg-primary text-primary-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                                            {activeFilters}
                                        </span>
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                </CardContent>

                {/* Advanced Filters */}
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Document Category')}</label>
                                <Select value={filters.document_category_id} onValueChange={(value) => setFilters({ ...filters, document_category_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Document Category')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        {documentcategories?.map((item: any) => (
                                            <SelectItem key={item.id} value={item.id.toString()}>
                                                {item.document_type}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                {/* Table Content */}
                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={documents?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={FileTextIcon}
                                            title={t('No Documents found')}
                                            description={t('Get started by creating your first Document.')}
                                            hasFilters={!!(filters.title || filters.document_category_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-hrm-documents"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Document')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {documents?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {documents?.data?.map((document) => (
                                        <Card key={document.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <FileTextIcon className="h-6 w-6 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-lg truncate">{document.title || 'Untitled Document'}</h3>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Document Category')}</p>
                                                        <p className="font-medium text-xs">{documentcategories?.find(item => item.id.toString() === document.document_category_id?.toString())?.document_type || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Effective Date')}</p>
                                                        <p className="font-medium text-xs">{document.effective_date ? formatDate(document.effective_date) : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                        <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${
                                                            document.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                            document.status === 'approve' ? 'bg-green-100 text-green-800' :
                                                            document.status === 'reject' ? 'bg-red-100 text-red-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {t(document.status === 'pending' ? 'Pending' : document.status === 'approve' ? 'Approved' : document.status === 'reject' ? 'Rejected' : document.status)}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Uploaded By')}</p>
                                                        <p className="font-medium text-xs">{document.uploaded_by?.name || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="mb-4">
                                                    <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Approved By')}</p>
                                                    <p className="font-medium text-xs">{document.approved_by?.name || '-'}</p>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                   
                                                    {auth.user?.permissions?.includes('manage-hrm-documents-status') && document.status === 'pending' && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setActionItem(document)} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700">
                                                                    <Play className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Action')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('download-hrm-documents') && document.document && document.status === 'approve' && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => {
                                                                        const link = document.createElement('a');
                                                                        link.href = `${imageUrlPrefix}/${document.document}`;
                                                                        link.download = document.document.split('/').pop() || 'download';
                                                                        link.click();
                                                                    }}
                                                                    className="h-9 w-9 p-0 text-orange-600 hover:text-orange-700"
                                                                >
                                                                    <Download className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Download')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-hrm-documents') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setViewingItem(document)} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-hrm-documents') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', document)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-hrm-documents') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(document.id)}
                                                                    className="h-9 w-9 p-0 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Delete')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TooltipProvider>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={FileTextIcon}
                                    title={t('No Documents found')}
                                    description={t('Get started by creating your first Document.')}
                                    hasFilters={!!(filters.title || filters.document_category_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-hrm-documents"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Document')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={documents || { data: [], links: [], meta: {} }}
                        routeName="hrm.documents.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditDocument
                        document={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View document={viewingItem} />}
            </Dialog>

            <Dialog open={!!actionItem} onOpenChange={() => setActionItem(null)}>
                {actionItem && <Action document={actionItem} onSuccess={() => setActionItem(null)} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Document')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}