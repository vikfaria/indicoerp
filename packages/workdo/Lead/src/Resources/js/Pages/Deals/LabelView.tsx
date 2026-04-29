import { useState } from 'react';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useTranslation } from 'react-i18next';
import { usePage, useForm } from '@inertiajs/react';
import { Tag } from 'lucide-react';
import { Deal } from './types';

interface DealLabelViewProps {
    deal: Deal;
    onSuccess?: () => void;
}

interface Label {
    id: number;
    name: string;
    color: string;
    pipeline_id?: number;
    pipeline?: {
        id: number;
        name: string;
    };
}

export default function DealLabelView({ deal, onSuccess }: DealLabelViewProps) {
    const { t } = useTranslation();
    const { labels } = usePage().props as { labels: Label[] };
    
    // Filter labels for current deal's pipeline only
    const pipelineLabels = labels?.filter(label => label.pipeline_id === deal.pipeline_id) || [];
    const [selectedLabels, setSelectedLabels] = useState<{[key: number]: boolean}>(() => {
        const selected: {[key: number]: boolean} = {};
        if (deal.labels) {
            const labelIds = deal.labels.split(',').map(Number).filter(Boolean);
            labelIds.forEach(id => {
                selected[id] = true;
            });
        }
        return selected;
    });
    
    const { data, setData, patch, processing } = useForm({
        labels: deal.labels || ''
    });

    const handleLabelChange = (labelId: number, checked: boolean) => {
        const newSelected = { ...selectedLabels };
        if (checked) {
            newSelected[labelId] = true;
        } else {
            delete newSelected[labelId];
        }
        setSelectedLabels(newSelected);
        const labelIds = Object.keys(newSelected).filter(key => newSelected[parseInt(key)]);
        
        setData('labels', labelIds.join(','));
    };

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('lead.deals.update-labels', deal.id), {
            onSuccess: () => {
                onSuccess?.();
            }
        });
    };

    return (
        <DialogContent className="max-w-md max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-purple-100 rounded-lg">
                        <Tag className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Deal Labels')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{deal.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6">
                <div className="space-y-4">
                    {pipelineLabels.map((label) => (
                        <div key={label.id} className="flex items-center space-x-3">
                            <Checkbox
                                id={`label-${label.id}`}
                                checked={selectedLabels[label.id] || false}
                                onCheckedChange={(checked) => handleLabelChange(label.id, !!checked)}
                            />
                            <label htmlFor={`label-${label.id}`} className="text-sm font-medium cursor-pointer">
                                <div 
                                    className="px-3 py-1 rounded text-white text-sm font-medium" 
                                    style={{ backgroundColor: label.color }}
                                >
                                    {label.name}
                                </div>
                            </label>
                        </div>
                    ))}
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Button variant="outline" onClick={onSuccess}>
                    {t('Cancel')}
                </Button>
                <Button onClick={handleSave} disabled={processing}>
                    {t('Save')}
                </Button>
            </div>
        </DialogContent>
    );
}