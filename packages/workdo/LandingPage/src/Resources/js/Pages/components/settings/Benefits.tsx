import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { CheckCircle } from 'lucide-react';

interface BenefitsProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Benefits({ data, getSectionData, updateSectionData, updateSectionVisibility }: BenefitsProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-green-100 rounded-lg">
                                <CheckCircle className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Benefits Section')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Expandable benefits accordion')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.benefits !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('benefits', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label>{t('Benefits Variant')}</Label>
                        <Select
                            value={getSectionData('benefits').variant || 'benefits1'}
                            onValueChange={(value) => updateSectionData('benefits', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Benefits Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="benefits1">{t('Accordion')}</SelectItem>
                                <SelectItem value="benefits2">{t('Cards')}</SelectItem>
                                <SelectItem value="benefits3">{t('List')}</SelectItem>
                                <SelectItem value="benefits4">{t('Timeline')}</SelectItem>
                                <SelectItem value="benefits5">{t('Tabs')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Title')}</Label>
                        <Input
                            value={getSectionData('benefits').title || ''}
                            onChange={(e) => updateSectionData('benefits', { title: e.target.value })}
                            placeholder={t('Why Choose ERPGo SaaS?')}
                        />
                    </div>
                    
                    <div className="space-y-4">
                        <Label>{t('Benefits List')}</Label>
                        <Repeater
                            fields={[
                                { name: 'title', label: t('Benefit Title'), type: 'text', placeholder: t('Benefit title'), required: true },
                                { name: 'description', label: t('Benefit Description'), type: 'textarea', placeholder: t('Benefit description'), required: true }
                            ]}
                            value={(getSectionData('benefits').benefits || []).map((benefit: any, index: number) => ({
                                id: `benefit-${index}`,
                                title: benefit.title || '',
                                description: benefit.description || ''
                            }))}
                            onChange={(items) => {
                                const benefits = items.map(({ id, ...item }) => item);
                                updateSectionData('benefits', { benefits });
                            }}
                            addButtonText={t('Add Benefit')}
                            deleteTooltipText={t('Delete Benefit')}
                            minItems={1}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}