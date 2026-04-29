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
import { Database, Trash2 } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { Lead } from '../types';

interface SourcesProps {
    lead: Lead;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Sources({ lead, onRegisterAddHandler }: SourcesProps) {

    useEffect(() => {
        onRegisterAddHandler(() => openSourceModal());
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [sourceModalOpen, setSourceModalOpen] = useState(false);
    const [availableSources, setAvailableSources] = useState([]);
    const [selectedSources, setSelectedSources] = useState([]);
    const [sourceNames, setSourceNames] = useState({});
    const [sourceDeleteState, setSourceDeleteState] = useState({ isOpen: false, sourceId: null, message: '' });

    const fetchAvailableSources = async () => {
        try {
            const response = await fetch(route('lead.leads.available-sources', lead.id));
            const sources = await response.json();
            setAvailableSources(sources.map((source: any) => ({
                value: source.id.toString(),
                label: source.name
            })));
        } catch (error) {
        }
    };

    const handleAssignSources = () => {
        if (selectedSources.length === 0) return;

        router.post(route('lead.leads.assign-sources', lead.id), {
            source_ids: selectedSources.map(id => parseInt(id))
        }, {
            onSuccess: () => {
                setSourceModalOpen(false);
                setSelectedSources([]);
            }
        });
    };

    const openSourceModal = () => {
        fetchAvailableSources();
        setSourceModalOpen(true);
    };

    const openSourceDeleteDialog = (sourceId: string) => {
        setSourceDeleteState({
            isOpen: true,
            sourceId,
            message: t('Are you sure you want to delete this source?')
        });
    };

    const closeSourceDeleteDialog = () => {
        setSourceDeleteState({ isOpen: false, sourceId: null, message: '' });
    };

    const confirmSourceDelete = () => {
        if (sourceDeleteState.sourceId) {
            router.delete(route('lead.leads.remove-source', {lead: lead.id, source: sourceDeleteState.sourceId}));
            closeSourceDeleteDialog();
        }
    };

    useEffect(() => {
        const fetchSourceNames = async () => {
            if (lead.sources) {
                try {
                    const response = await fetch(route('lead.leads.available-sources', lead.id));
                    const sources = await response.json();
                    const names = {};
                    sources.forEach(source => {
                        names[source.id] = source.name;
                    });
                    setSourceNames(names);
                } catch (error) {
                }
            }
        };
        fetchSourceNames();
    }, [lead.sources]);

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={lead.sources ? [...new Set(lead.sources.split(',').filter(Boolean).map(id => id.trim()))].map((sourceId: string, index: number) => ({ id: sourceId, key: `source-${sourceId}-${index}`, name: sourceNames[sourceId] || '' })) : []}
                        columns={[
                            {
                                key: 'name',
                                header: t('Source Name'),
                                render: (value: string, source: any) => source.name || ''
                            },
                            {
                                key: 'actions',
                                header: t('Action'),
                                render: (_: any, source: any) => (
                                    <div className="flex gap-1">
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => {
                                                        openSourceDeleteDialog(source.id);
                                                    }} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Delete Source')}</p>
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
                                icon={Database}
                                title={t('No Sources added')}
                                description={t('Get started by adding sources to this lead.')}
                                onCreateClick={() => openSourceModal()}
                                createButtonText={t('Add Sources')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={sourceModalOpen} onOpenChange={setSourceModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Add Sources')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label>{t('Select Sources')}</Label>
                            <MultiSelectEnhanced
                                options={availableSources}
                                value={selectedSources}
                                onValueChange={setSelectedSources}
                                placeholder={t('Select sources')}
                                searchable={true}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setSourceModalOpen(false)}>{t('Cancel')}</Button>
                            <Button onClick={handleAssignSources} disabled={selectedSources.length === 0}>{t('Save')}</Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={sourceDeleteState.isOpen}
                onOpenChange={closeSourceDeleteDialog}
                title={t('Delete Source')}
                message={sourceDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmSourceDelete}
                variant="destructive"
            />
        </>
    );
}
