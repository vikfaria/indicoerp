import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Users, Trash2 } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { getImagePath } from '@/utils/helpers';
import { Deal } from '../types';

interface ClientsProps {
    deal: Deal;
    availableClients: any[];
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Clients({ deal, availableClients, onRegisterAddHandler }: ClientsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => openClientModal());
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [clientModalOpen, setClientModalOpen] = useState(false);
    const [availableClientsState, setAvailableClientsState] = useState([]);
    const [selectedClients, setSelectedClients] = useState([]);
    const [clientDeleteState, setClientDeleteState] = useState({ isOpen: false, clientId: null, message: '' });

    const formatAvailableClients = () => {
        setAvailableClientsState(availableClients.map((client: any) => ({
            value: client.id.toString(),
            label: client.name
        })));
    };

    const handleAssignClients = () => {
        if (selectedClients.length === 0) return;
        
        router.post(route('lead.deals.assign-clients', deal.id), {
            client_ids: selectedClients.map(id => parseInt(id))
        }, {
            onSuccess: () => {
                setClientModalOpen(false);
                setSelectedClients([]);
            }
        });
    };

    const openClientModal = () => {
        formatAvailableClients();
        setClientModalOpen(true);
    };

    const openClientDeleteDialog = (clientId: number) => {
        setClientDeleteState({
            isOpen: true,
            clientId,
            message: t('Are you sure you want to remove this client?')
        });
    };

    const closeClientDeleteDialog = () => {
        setClientDeleteState({ isOpen: false, clientId: null, message: '' });
    };

    const confirmClientDelete = () => {
        if (clientDeleteState.clientId) {
            router.delete(route('lead.deals.remove-client', {deal: deal.id, client: clientDeleteState.clientId}));
            closeClientDeleteDialog();
        }
    };

    return (
        <>

            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={deal.client_deals ? (Array.isArray(deal.client_deals) ? deal.client_deals : []) : []}
                        columns={[
                            {
                                key: 'client.avatar',
                                header: t('Avatar'),
                                render: (value: string, clientDeal: any) => {
                                    const client = clientDeal.client;
                                    return (
                                        <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden">
                                            {client?.avatar ? (
                                                <img 
                                                    src={getImagePath(client.avatar)} 
                                                    alt={client.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <div className="h-full w-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                                                    {client?.name?.charAt(0)?.toUpperCase() || 'C'}
                                                </div>
                                            )}
                                        </div>
                                    );
                                }
                            },
                            {
                                key: 'client.name',
                                header: t('Client Name'),
                                render: (value: string, clientDeal: any) => clientDeal.client?.name || '-'
                            },
                            {
                                key: 'actions',
                                header: t('Action'),
                                render: (_: any, clientDeal: any) => (
                                    <div className="flex gap-1">
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => {
                                                        openClientDeleteDialog(clientDeal.client?.id);
                                                    }} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Delete Client')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                )
                            }
                        ]}
                        className="rounded-none"
                        emptyState={
                            <NoRecordsFound
                                icon={Users}
                                title={t('No Clients added')}
                                description={t('Get started by adding clients to this deal.')}
                                onCreateClick={() => openClientModal()}
                                createButtonText={t('Add Clients')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={clientModalOpen} onOpenChange={setClientModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Add Clients')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label>{t('Select Clients')}</Label>
                            <MultiSelectEnhanced
                                options={availableClientsState}
                                value={selectedClients}
                                onValueChange={setSelectedClients}
                                placeholder={t('Select clients')}
                                searchable={true}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setClientModalOpen(false)}>{t('Cancel')}</Button>
                            <Button onClick={handleAssignClients} disabled={selectedClients.length === 0}>{t('Save')}</Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={clientDeleteState.isOpen}
                onOpenChange={closeClientDeleteDialog}
                title={t('Remove Client')}
                message={clientDeleteState.message}
                confirmText={t('Remove')}
                onConfirm={confirmClientDelete}
                variant="destructive"
            />
        </>
    );
}