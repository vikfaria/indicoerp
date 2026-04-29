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
import { Plus, Edit as EditIcon, Trash2, Eye, Video as VideoIcon, Download, FileImage, Play, Users, ChevronDown, ExternalLink } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditZoomMeeting from './Edit';
import Show from './Show';

import NoRecordsFound from '@/components/no-records-found';
import { ZoomMeeting, ZoomMeetingsIndexProps, ZoomMeetingFilters, ZoomMeetingModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { zoommeetings, auth, users } = usePage<ZoomMeetingsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ZoomMeetingFilters>({
        title: urlParams.get('title') || '',
        description: urlParams.get('description') || '',
        meeting_id: urlParams.get('meeting_id') || '',
        status: urlParams.get('status') || '',
        host_video: urlParams.get('host_video') || '',
        participant_video: urlParams.get('participant_video') || '',
        recording: urlParams.get('recording') || '',
        date_range: urlParams.get('date_range') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<ZoomMeetingModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [showModal, setShowModal] = useState<{isOpen: boolean; data: ZoomMeeting | null}>({
        isOpen: false,
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'zoommeeting.zoom-meetings.destroy',
        defaultMessage: t('Are you sure you want to delete this zoommeeting?')
    });

    const handleFilter = () => {
        router.get(route('zoommeeting.zoom-meetings.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('zoommeeting.zoom-meetings.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const updateStatus = (meetingId: number, newStatus: string) => {
        router.patch(route('zoommeeting.zoom-meetings.update-status', meetingId), {
            status: newStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Status updated successfully
            }
        });
    };

    const clearFilters = () => {
        setFilters({
            title: '',
            description: '',
            meeting_id: '',
            status: '',
            host_video: '',
            participant_video: '',
            recording: '',
            date_range: '',
        });
        router.get(route('zoommeeting.zoom-meetings.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: ZoomMeeting | null = null) => {
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
            key: 'start_time',
            header: t('Start Time'),
            sortable: false,
            render: (value: string) => value ? formatDateTime(value) : '-'
        },
        {
            key: 'duration',
            header: t('Duration'),
            sortable: false,
            render: (value: number) => value ? `${value} minutes` : '-'
        },
        {
            key: 'host.name',
            header: t('Host Name'),
            sortable: false,
            render: (value: any, row: any) => {
                if (!row.host?.name) return '-';
                return (
                    <div className="flex items-center gap-2">
                        <img
                            src={getImagePath(row.host?.avatar || '')}
                            alt={row.host.name}
                            className="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm cursor-pointer hover:scale-110 transition-transform"
                        />
                        <span className="text-sm">{row.host.name}</span>
                    </div>
                );
            }
        },
        {
            key: 'participants',
            header: t('Participants'),
            sortable: false,
            render: (value: string[] | string, row: any) => {
                if (!value) return '-';
                let items = [];
                if (typeof value === 'string') {
                    try {
                        items = JSON.parse(value);
                    } catch {
                        items = [value];
                    }
                } else if (Array.isArray(value)) {
                    items = value;
                }
                if (items.length === 0) return '-';
                const modelData = users || [];
                return (
                    <div className="flex items-center -space-x-2">
                        <TooltipProvider>
                            {items.slice(0, 4).map((item: any, index: number) => {
                                const modelItem = modelData.find((m: any) => m.id.toString() === item?.toString());
                                const userName = modelItem?.name || item;
                                return (
                                    <Tooltip key={index} delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <img
                                                src={getImagePath(modelItem?.avatar || '')}
                                                alt={userName}
                                                className="w-8 h-8 rounded-full object-cover border-2 border-white cursor-pointer hover:scale-110 hover:z-10 transition-all duration-200"
                                            />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{userName}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                );
                            })}
                        </TooltipProvider>
                        {items.length > 4 && (
                            <div className="w-8 h-8 bg-gray-200 border-2 border-white rounded-full flex items-center justify-center">
                                <span className="text-xs font-medium text-gray-600">+{items.length - 4}</span>
                            </div>
                        )}
                    </div>
                );
            }
        },
         {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: any, row: any) => {
                const statusColors = {
                    "Scheduled": "bg-blue-100 text-blue-800",
                    "Started": "bg-green-100 text-green-800",
                    "Ended": "bg-gray-100 text-gray-800",
                    "Cancelled": "bg-red-100 text-red-800"
                };

                if (auth.user?.permissions?.includes('update-zoom-meeting-status')) {
                    return (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className={`px-2 py-1 rounded-full text-sm h-auto ${statusColors[value] || 'bg-gray-100 text-gray-800'}`}>
                                    {value} <ChevronDown className="h-3 w-3 ml-1" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, 'Scheduled')}>
                                    {t('Scheduled')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, 'Started')}>
                                    {t('Started')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, 'Ended')}>
                                    {t('Ended')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, 'Cancelled')}>
                                    {t('Cancelled')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                }

                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value] || 'bg-gray-100 text-gray-800'}`}>
                        {value}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-zoom-meetings', 'delete-zoom-meetings', 'join-zoom-meetings', 'start-zoom-meetings'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, zoommeeting: ZoomMeeting) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {zoommeeting.meeting_id && ['Scheduled', 'Started'].includes(zoommeeting.status) && (
                            <>
                                {auth.user?.permissions?.includes('join-zoom-meetings') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => window.open(zoommeeting.join_url || `https://zoom.us/j/${zoommeeting.meeting_id}`, '_blank')}
                                                className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700"
                                            >
                                                <ExternalLink className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Join Meeting')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user?.permissions?.includes('start-zoom-meetings') && (zoommeeting.host_id === auth.user?.id || auth.user?.type === 'company') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => window.open(zoommeeting.start_url || `https://zoom.us/s/${zoommeeting.meeting_id}`, '_blank')}
                                                className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700"
                                            >
                                                <Play className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Start Meeting')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </>
                        )}
                        {auth.user?.permissions?.includes('view-zoom-meetings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setShowModal({isOpen: true, data: zoommeeting})} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-zoom-meetings') && zoommeeting.status === 'Scheduled' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', zoommeeting)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-zoom-meetings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(zoommeeting.id)}
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
                {label: t('Zoom Meetings')}
            ]}
            pageTitle={t('Manage Zoom Meetings')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-zoom-meetings') && (
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
            <Head title={t('Zoom Meetings')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({...filters, title: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Zoom Meetings...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="zoommeeting.zoom-meetings.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="zoommeeting.zoom-meetings.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.host_video, filters.participant_video, filters.recording, filters.date_range].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Scheduled">{t('Scheduled')}</SelectItem>
                                        <SelectItem value="Started">{t('Started')}</SelectItem>
                                        <SelectItem value="Ended">{t('Ended')}</SelectItem>
                                        <SelectItem value="Cancelled">{t('Cancelled')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DateRangePicker
                                    value={filters.date_range}
                                    onChange={(value) => setFilters({...filters, date_range: value})}
                                    placeholder={t('Select Date Range')}
                                />
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
                            <div className="min-w-[1000px]">
                            <DataTable
                                data={zoommeetings?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={VideoIcon}
                                        title={t('No Zoom Meetings found')}
                                        description={t('Get started by creating your first ZoomMeeting.')}
                                        hasFilters={!!(filters.title || filters.description || filters.meeting_id || filters.status || filters.host_video || filters.participant_video || filters.recording || filters.date_range)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-zoom-meetings"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Zoom Meeting')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {zoommeetings?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {zoommeetings?.data?.map((zoommeeting) => (
                                        <Card key={zoommeeting.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Arrow decoration */}
                                            <div className="absolute top-0 right-0 w-0 h-0 border-l-[20px] border-l-transparent border-t-[20px] border-t-primary/20"></div>
                                            {/* Header */}
                                            <div className="p-4 border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg">
                                                        <VideoIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm text-gray-900">{zoommeeting.title}</h3>
                                                        <p className="text-xs font-medium text-primary">{zoommeeting.meeting_id || '-'}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Start Time')}</p>
                                                        <p className="font-medium text-xs">{zoommeeting.start_time ? formatDateTime(zoommeeting.start_time) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Duration')}</p>
                                                        <p className="font-medium text-xs">{zoommeeting.duration ? `${zoommeeting.duration} min` : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Host')}</p>
                                                        {zoommeeting.host?.name ? (
                                                            <div className="flex items-center gap-2">
                                                                <img
                                                                    src={getImagePath(zoommeeting.host?.avatar || '')}
                                                                    alt={zoommeeting.host.name}
                                                                    className="w-6 h-6 rounded-full object-cover border border-white"
                                                                />
                                                                <span className="font-medium text-xs">{zoommeeting.host.name}</span>
                                                            </div>
                                                        ) : (
                                                            <p className="font-medium text-xs">-</p>
                                                        )}
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                        {(() => {
                                                            const statusColors = {
                                                                "Scheduled": "bg-blue-100 text-blue-800",
                                                                "Started": "bg-green-100 text-green-800",
                                                                "Ended": "bg-gray-100 text-gray-800",
                                                                "Cancelled": "bg-red-100 text-red-800"
                                                            };
                                                            return (
                                                                <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${statusColors[zoommeeting.status] || 'bg-gray-100 text-gray-800'}`}>
                                                                    {zoommeeting.status}
                                                                </span>
                                                            );
                                                        })()}
                                                    </div>
                                                </div>

                                                <div className="text-xs min-w-0">
                                                    <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Participants')} ({(() => {
                                                        let items = [];
                                                        if (typeof zoommeeting.participants === 'string') {
                                                            try {
                                                                items = JSON.parse(zoommeeting.participants);
                                                            } catch {
                                                                items = [zoommeeting.participants];
                                                            }
                                                        } else if (Array.isArray(zoommeeting.participants)) {
                                                            items = zoommeeting.participants;
                                                        }
                                                        return items.length;
                                                    })()})</p>
                                                    <div className="flex items-center gap-1">
                                                        {(() => {
                                                            let items = [];
                                                            if (typeof zoommeeting.participants === 'string') {
                                                                try {
                                                                    items = JSON.parse(zoommeeting.participants);
                                                                } catch {
                                                                    items = [zoommeeting.participants];
                                                                }
                                                            } else if (Array.isArray(zoommeeting.participants)) {
                                                                items = zoommeeting.participants;
                                                            }
                                                            const modelData = users || [];
                                                            if (items.length === 0) return <span className="text-xs text-gray-500">No participants</span>;
                                                            return (
                                                                <div className="flex items-center -space-x-1">
                                                                    <TooltipProvider>
                                                                        {items.slice(0, 3).map((item: any, index: number) => {
                                                                            const modelItem = modelData.find((m: any) => m.id.toString() === item?.toString());
                                                                            const userName = modelItem?.name || item;
                                                                            return (
                                                                                <Tooltip key={index} delayDuration={0}>
                                                                                    <TooltipTrigger asChild>
                                                                                        <img
                                                                                            src={getImagePath(modelItem?.avatar || '')}
                                                                                            alt={userName}
                                                                                            className="w-6 h-6 rounded-full object-cover border border-white cursor-pointer hover:scale-110 hover:z-10 transition-all duration-200"
                                                                                        />
                                                                                    </TooltipTrigger>
                                                                                    <TooltipContent>
                                                                                        <p>{userName}</p>
                                                                                    </TooltipContent>
                                                                                </Tooltip>
                                                                            );
                                                                        })}
                                                                    </TooltipProvider>
                                                                    {items.length > 3 && (
                                                                        <div className="w-6 h-6 bg-gray-200 border border-white rounded-full flex items-center justify-center">
                                                                            <span className="text-xs font-medium text-gray-600">+{items.length - 3}</span>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            );
                                                        })()}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                    {zoommeeting.meeting_id && ['Scheduled', 'Started'].includes(zoommeeting.status) && (
                                                        <>
                                                            {auth.user?.permissions?.includes('join-zoom-meetings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => window.open(zoommeeting.join_url || `https://zoom.us/j/${zoommeeting.meeting_id}`, '_blank')}
                                                                            className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700 hover:bg-purple-50"
                                                                        >
                                                                            <ExternalLink className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Join Meeting')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('start-zoom-meetings') && (zoommeeting.host_id === auth.user?.id || auth.user?.type === 'company') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => window.open(zoommeeting.start_url || `https://zoom.us/s/${zoommeeting.meeting_id}`, '_blank')}
                                                                            className="h-9 w-9 p-0 text-orange-600 hover:text-orange-700 hover:bg-orange-50"
                                                                        >
                                                                            <Play className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Start Meeting')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                        </>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-zoom-meetings') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setShowModal({isOpen: true, data: zoommeeting})} className="h-9 w-9 p-0 text-green-600 hover:text-green-700 hover:bg-green-50">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-zoom-meetings') && zoommeeting.status === 'Scheduled' && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', zoommeeting)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-zoom-meetings') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(zoommeeting.id)}
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
                                    icon={VideoIcon}
                                    title={t('No Zoom Meetings found')}
                                    description={t('Get started by creating your first ZoomMeeting.')}
                                    hasFilters={!!(filters.title || filters.description || filters.meeting_id || filters.status || filters.host_video || filters.participant_video || filters.recording || filters.date_range)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-zoom-meetings"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create ZoomMeeting')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={zoommeetings || { data: [], links: [], meta: {} }}
                        routeName="zoommeeting.zoom-meetings.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditZoomMeeting
                        zoommeeting={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete ZoomMeeting')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            {showModal.data && (
                <Show
                    isOpen={showModal.isOpen}
                    onClose={() => setShowModal({isOpen: false, data: null})}
                    zoommeeting={showModal.data}
                    users={users}
                />
            )}
        </AuthenticatedLayout>
    );
}