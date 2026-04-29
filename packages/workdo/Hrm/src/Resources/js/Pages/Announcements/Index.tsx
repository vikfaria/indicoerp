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
import { Plus, Edit as EditIcon, Trash2, Eye, Megaphone as MegaphoneIcon, Download, FileImage, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditAnnouncement from './Edit';
import View from './View';
import UpdateStatus from './UpdateStatus';
import NoRecordsFound from '@/components/no-records-found';
import { Announcement, AnnouncementsIndexProps, AnnouncementFilters, AnnouncementModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { announcements, auth, announcementcategories } = usePage<AnnouncementsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<AnnouncementFilters>({
        title: urlParams.get('title') || '',
        description: urlParams.get('description') || '',
        priority: urlParams.get('priority') || '',
        status: urlParams.get('status') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<AnnouncementModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Announcement | null>(null);
    const [statusModalState, setStatusModalState] = useState<{
        isOpen: boolean;
        announcement: Announcement | null;
    }>({ isOpen: false, announcement: null });

    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.announcements.destroy',
        defaultMessage: t('Are you sure you want to delete this announcement?')
    });

    const handleFilter = () => {
        router.get(route('hrm.announcements.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.announcements.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            title: '',
            description: '',
            priority: '',
            status: '',
        });
        router.get(route('hrm.announcements.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Announcement | null = null) => {
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
            key: 'announcement_category.announcement_category',
            header: t('Announcement Category '),
            sortable: false,
            render: (value: any, row: any) => row.announcement_category?.announcement_category || '-'
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            sortable: false,
            render: (value: string) => {
                if (!value) return '-';
                const isOverdue = new Date(value) < new Date();

                return (
                    <span className={isOverdue ? 'text-red-600' : ''}>
                        {formatDate(value)}
                    </span>
                );
            }
        },
        {
            key: 'priority',
            header: t('Priority'),
            sortable: false,
            render: (value: string) => {
                const priorityColors = {
                    'low': 'bg-slate-100 text-slate-800',
                    'medium': 'bg-blue-100 text-blue-800',
                    'high': 'bg-orange-100 text-orange-800',
                    'urgent': 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${priorityColors[value as keyof typeof priorityColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Unknown')}
                    </span>
                );
            }
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusColors = {
                    'active': 'bg-green-100 text-green-800',
                    'inactive': 'bg-red-100 text-red-800',
                    'draft': 'bg-blue-100 text-blue-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Unknown')}
                    </span>
                );
            }
        },
        {
            key: 'approved_by',
            header: t('Approved By'),
            sortable: false,
            render: (value: any, row: any) => row.approved_by?.name ? String(row.approved_by.name) : '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-announcements-status', 'view-announcements', 'edit-announcements', 'delete-announcements'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, announcement: Announcement) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-announcements-status') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setStatusModalState({ isOpen: true, announcement })} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Update Status')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-announcements') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(announcement)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-announcements') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', announcement)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-announcements') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(announcement.id)}
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
                { label: t('Announcements') }
            ]}
            pageTitle={t('Manage Announcements')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-announcements') && (
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
                </TooltipProvider>
            }
        >
            <Head title={t('Announcements')} />

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
                                placeholder={t('Search Announcements...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="hrm.announcements.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="hrm.announcements.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.priority, filters.status].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Priority')}</label>
                                <Select value={filters.priority} onValueChange={(value) => setFilters({ ...filters, priority: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Priority')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">{t('Low')}</SelectItem>
                                        <SelectItem value="medium">{t('Medium')}</SelectItem>
                                        <SelectItem value="high">{t('High')}</SelectItem>
                                        <SelectItem value="urgent">{t('Urgent')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="active">{t('Active')}</SelectItem>
                                        <SelectItem value="inactive">{t('Inactive')}</SelectItem>
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
                                    data={announcements?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={MegaphoneIcon}
                                            title={t('No Announcements found')}
                                            description={t('Get started by creating your first Announcement.')}
                                            hasFilters={!!(filters.title || filters.description || filters.priority || filters.status)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-announcements"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Announcement')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {announcements?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    {announcements?.data?.map((announcement) => (
                                        <Card key={announcement.id} className="p-6 hover:shadow-md transition-shadow">
                                            <div className="flex items-center justify-between mb-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg">
                                                        <MegaphoneIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-lg">{announcement.title}</h3>
                                                </div>
                                            </div>
                                            <div className="space-y-3 mb-6">
                                                <div className="text-sm">
                                                    <p className="text-muted-foreground mb-1">{t('Start Date')}</p>
                                                    <p className="font-medium">{announcement.start_date ? formatDate(announcement.start_date) : '-'}</p>
                                                </div>
                                                <div className="text-sm">
                                                    <p className="text-muted-foreground mb-1">{t('End Date')}</p>
                                                    <p className={`font-medium ${announcement.end_date && new Date(announcement.end_date) < new Date() ? 'text-red-600' : ''}`}>{announcement.end_date ? formatDate(announcement.end_date) : '-'}</p>
                                                </div>
                                                <div className="flex items-center justify-between mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Priority')}</p>
                                                        {announcement.priority ? (
                                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${announcement.priority === 'low' ? 'bg-slate-100 text-slate-800' :
                                                                announcement.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                                                                    announcement.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                                                        'bg-red-100 text-red-800'
                                                                }`}>
                                                                {t(announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1))}
                                                            </span>
                                                        ) : '-'}
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Status')}</p>
                                                        {announcement.status ? (
                                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${announcement.status === 'active' ? 'bg-green-100 text-green-800' :
                                                                announcement.status === 'inactive' ? 'bg-red-100 text-red-800' :
                                                                    'bg-blue-100 text-blue-800'
                                                                }`}>
                                                                {t(announcement.status.charAt(0).toUpperCase() + announcement.status.slice(1))}
                                                            </span>
                                                        ) : '-'}
                                                    </div>
                                                </div>
                                                <div className="text-sm">
                                                    <p className="text-muted-foreground mb-1">{t('Announcement Category')}</p>
                                                    <p className="font-medium">{announcement.announcement_category?.announcement_category || '-'}</p>
                                                </div>
                                            </div>

                                            <div className="flex justify-end gap-2 pt-4 border-t">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('manage-announcements-status') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setStatusModalState({ isOpen: true, announcement })} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700 hover:bg-purple-50">
                                                                    <Play className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Update Status')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-announcements') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setViewingItem(announcement)} className="h-9 w-9 p-0 text-green-600 hover:text-green-700 hover:bg-green-50">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-announcements') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', announcement)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-announcements') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(announcement.id)}
                                                                    className="h-9 w-9 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
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
                                    icon={MegaphoneIcon}
                                    title={t('No Announcements found')}
                                    description={t('Get started by creating your first Announcement.')}
                                    hasFilters={!!(filters.title || filters.description || filters.priority || filters.status)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-announcements"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Announcement')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={announcements || { data: [], links: [], meta: {} }}
                        routeName="hrm.announcements.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditAnnouncement
                        announcement={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View announcement={viewingItem} />}
            </Dialog>

            <Dialog open={statusModalState.isOpen} onOpenChange={() => setStatusModalState({ isOpen: false, announcement: null })}>
                {statusModalState.announcement && <UpdateStatus announcement={statusModalState.announcement} onSuccess={() => setStatusModalState({ isOpen: false, announcement: null })} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Announcement')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}