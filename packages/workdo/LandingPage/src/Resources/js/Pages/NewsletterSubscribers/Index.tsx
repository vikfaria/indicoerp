import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { SearchInput } from "@/components/ui/search-input";
import { Pagination } from "@/components/ui/pagination";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import NoRecordsFound from '@/components/no-records-found';
import { Download, Trash2, Mail } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { formatDate } from '@/utils/helpers';

interface NewsletterSubscriber {
    id: number;
    email: string;
    subscribed_at: string;
    ip_address?: string;
    country?: string;
    city?: string;
    region?: string;
    country_code?: string;
    isp?: string;
    org?: string;
    timezone?: string;
    latitude?: number;
    longitude?: number;
    browser?: string;
    os?: string;
    device?: string;
}

interface IndexProps {
    subscribers: {
        data: NewsletterSubscriber[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}

export default function Index({ subscribers }: IndexProps) {
    const { t } = useTranslation();
    const { auth } = usePage<{auth: {user: any}}>().props;
    const urlParams = new URLSearchParams(window.location.search);
    
    const [filters, setFilters] = useState({
        email: urlParams.get('email') || ''
    });
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'newsletter-subscribers.destroy',
        defaultMessage: t('Are you sure you want to delete this subscriber?')
    });

    const handleFilter = () => {
        router.get(route('newsletter-subscribers.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('newsletter-subscribers.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ email: '' });
        router.get(route('newsletter-subscribers.index'), {per_page: perPage});
    };

    const handleExport = () => {
        window.open(route('newsletter-subscribers.export', filters));
    };

    const tableColumns = [
        {
            key: 'email',
            header: t('Email'),
            sortable: true,
            render: (value: string) => (
                <div className="flex items-center gap-2">
                    <Mail className="h-4 w-4 text-gray-400" />
                    <span className="font-medium">{value}</span>
                </div>
            )
        },
        {
            key: 'ip_address',
            header: t('IP Address'),
            sortable: true,
            render: (value: string) => (
                <span className="text-sm text-gray-600 font-mono">
                    {value || '-'}
                </span>
            )
        },
        {
            key: 'details',
            header: t('Location & Device'),
            render: (_: any, subscriber: NewsletterSubscriber) => (
                <div className="text-sm space-y-1">
                    <div>{subscriber.city ? `${subscriber.city}, ${subscriber.country}` : 'Unknown'}</div>
                    <div className="text-gray-500">{subscriber.browser} on {subscriber.os}</div>
                    <div className="text-gray-500 capitalize">{subscriber.device}</div>
                    {subscriber.isp && <div className="text-gray-500">ISP: {subscriber.isp}</div>}
                    {subscriber.org && <div className="text-gray-500">Org: {subscriber.org}</div>}
                    {subscriber.timezone && <div className="text-gray-500">TZ: {subscriber.timezone}</div>}
                </div>
            )
        },
        {
            key: 'subscribed_at',
            header: t('Subscribed At'),
            sortable: true,
            render: (value: string) => (
                <span className="text-sm text-gray-600">
                    {formatDate(value)}
                </span>
            )
        },
        ...(auth.user?.permissions?.includes('delete-newsletter-subscribers') ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, subscriber: NewsletterSubscriber) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => openDeleteDialog(subscriber.id)}
                                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
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
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Newsletter Subscribers') }
            ]}
            pageTitle={t('Manage Newsletter Subscribers')}
            pageActions={
                <div className="flex gap-2">
                    {auth.user?.permissions?.includes('export-newsletter-subscribers') && (
                        <TooltipProvider>
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        size="sm"
                                        onClick={handleExport}
                                    >
                                        <Download className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Export')}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    )}
                </div>
            }
        >
            <Head title={t('Newsletter Subscribers')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.email}
                                onChange={(value) => setFilters({...filters, email: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search subscribers...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="newsletter-subscribers.index"
                                filters={filters}
                            />
                        </div>
                    </div>
                </CardContent>

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[1000px]">
                            <DataTable
                                data={subscribers.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Mail}
                                        title={t('No newsletter subscribers found')}
                                        description={t('No subscribers have signed up yet.')}
                                        hasFilters={!!filters.email}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={subscribers}
                        routeName="newsletter-subscribers.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Newsletter Subscriber')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}