import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Pagination } from '@/components/ui/pagination';
import { Trash2, Mail, User, Calendar, Eye } from 'lucide-react';
import { formatDateTime } from '@/utils/helpers';

interface Contact {
    id: number;
    name?: string;
    first_name: string;
    last_name: string;
    email: string;
    subject: string;
    message: string;
    created_at: string;
}

interface ContactIndexProps {
    contacts: {
        data: Contact[];
        links: any[];
        meta: any;
    };
}

export default function Index({ contacts }: ContactIndexProps) {
    const { t } = useTranslation();
    const urlParams = new URLSearchParams(window.location.search);
    
    const [filters, setFilters] = useState({
        search: urlParams.get('search') || '',
    });
    
    const [perPage, setPerPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(null);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'support-ticket-contact.destroy',
        defaultMessage: t('Are you sure you want to delete this contact?')
    });

    const handleViewContact = (contact: Contact) => {
        setSelectedContact(contact);
        setViewModalOpen(true);
    };

    const handleFilter = () => {
        router.get(route('support-ticket-contact.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handlePerPageChange = (newPerPage: string) => {
        setPerPage(newPerPage);
        router.get(route('support-ticket-contact.index'), {...filters, per_page: newPerPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('support-ticket-contact.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true,
            render: (_: any, contact: Contact) => `${contact.first_name} ${contact.last_name}`
        },
        {
            key: 'email',
            header: t('Email'),
            sortable: true
        },
        {
            key: 'subject',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'message',
            header: t('Message'),
            render: (value: string) => {
                const text = value?.replace(/<[^>]*>/g, '') || '';
                return text.length > 50 ? text.substring(0, 50) + '...' : text;
            }
        },
        {
            key: 'created_at',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDateTime(value)
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, contact: Contact) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    onClick={() => handleViewContact(contact)}
                                >
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                    onClick={() => openDeleteDialog(contact.id)}
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
                {label: t('Contact')}
            ]}
            pageTitle={t('Manage Contact')}
        >
            <Head title="Manage Contact" />
            
            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search contacts...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="support-ticket-contact.index"
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
                            data={contacts.data}
                            columns={tableColumns}
                            onSort={handleSort}
                            sortKey={sortField}
                            sortDirection={sortDirection as 'asc' | 'desc'}
                            className="rounded-none"
                            emptyState={
                                <div className="flex flex-col items-center justify-center py-12">
                                    <div className="text-center">
                                        <Mail className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">{t('No contacts found')}</h3>
                                        <p className="text-gray-500">{t('No contact messages have been received yet.')}</p>
                                    </div>
                                </div>
                            }
                        />
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={contacts || { data: [], links: [], meta: {} }}
                        routeName="support-ticket-contact.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Contact')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            <Dialog open={viewModalOpen} onOpenChange={setViewModalOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader className="pb-4 border-b">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-primary/10 rounded-lg">
                                <Mail className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <DialogTitle className="text-xl font-semibold">{t('Contact Details')}</DialogTitle>
                                <p className="text-sm text-muted-foreground">
                                    {selectedContact?.name || `${selectedContact?.first_name || ''} ${selectedContact?.last_name || ''}`.trim() || 'Contact Message'}
                                </p>
                            </div>
                        </div>
                    </DialogHeader>
                    
                    {selectedContact && (
                        <div className="overflow-y-auto flex-1 p-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                {/* Left Column */}
                                <div className="space-y-4">
                                    {/* Contact Information */}
                                    <Card>
                                        <CardContent className="p-4">
                                            <h3 className="font-semibold text-sm mb-3 text-gray-800">{t('Contact Information')}</h3>
                                            <div className="grid grid-cols-1 gap-3 text-sm">
                                                <div>
                                                    <span className="text-gray-500">{t('Full Name')}</span>
                                                    <p className="mt-1 font-medium">
                                                        {selectedContact.name || `${selectedContact.first_name || ''} ${selectedContact.last_name || ''}`.trim() || '-'}
                                                    </p>
                                                </div>
                                                <div>
                                                    <span className="text-gray-500">{t('Email Address')}</span>
                                                    <p className="mt-1 font-medium">{selectedContact.email || '-'}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Message Details */}
                                    <Card>
                                        <CardContent className="p-4">
                                            <h3 className="font-semibold text-sm mb-3 text-gray-800">{t('Message Details')}</h3>
                                            <div className="space-y-3">
                                                <div>
                                                    <span className="text-gray-500 text-sm">{t('Subject')}</span>
                                                    <p className="mt-1 font-medium">{selectedContact.subject || '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-gray-500 text-sm">{t('Received Date')}</span>
                                                    <div className="flex items-center gap-2 mt-1">
                                                        <Calendar className="h-4 w-4 text-gray-400" />
                                                        <p className="font-medium">{formatDateTime(selectedContact.created_at)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Right Column */}
                                <div className="space-y-4">
                                    {/* Message Content */}
                                    <Card>
                                        <CardContent className="p-4">
                                            <h3 className="font-semibold text-sm mb-3 text-gray-800">{t('Message Content')}</h3>
                                            <div className="bg-gray-50 rounded-lg p-4 border min-h-[200px]">
                                                <p className="text-gray-900 leading-relaxed whitespace-pre-wrap text-sm">
                                                    {selectedContact.message || 'No message content'}
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}