import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Settings as SettingsIcon } from 'lucide-react';

interface StatsProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Stats({ data, getSectionData, updateSectionData, updateSectionVisibility }: StatsProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 rounded-lg">
                                <SettingsIcon className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Statistics Section')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Key business metrics and numbers')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.stats !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('stats', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Stats Variant')}</Label>
                        <Select
                            value={getSectionData('stats').variant || 'stats1'}
                            onValueChange={(value) => updateSectionData('stats', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Stats Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="stats1">{t('Colored Background')}</SelectItem>
                                <SelectItem value="stats2">{t('Cards')}</SelectItem>
                                <SelectItem value="stats3">{t('Minimal')}</SelectItem>
                                <SelectItem value="stats4">{t('Circular')}</SelectItem>
                                <SelectItem value="stats5">{t('Gradient')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-4">
                        <Label>{t('Statistics')}</Label>
                        <Repeater
                            fields={[
                                { name: 'label', label: t('Label'), type: 'text', placeholder: t('Stat label'), required: true },
                                { name: 'value', label: t('Value'), type: 'text', placeholder: t('Stat value'), required: true }
                            ]}
                            value={(() => {
                                const existingStats = getSectionData('stats');
                                if (existingStats.stats && existingStats.stats.length > 0) {
                                    return existingStats.stats.map((stat: any, index: number) => ({
                                        id: `stat-${index}`,
                                        label: stat.label || '',
                                        value: stat.value || ''
                                    }));
                                }
                                return [
                                    { id: 'stat-0', label: 'Businesses Trust Us', value: '20,000+' },
                                    { id: 'stat-1', label: 'Uptime Guarantee', value: '99.9%' },
                                    { id: 'stat-2', label: 'Customer Support', value: '24/7' },
                                    { id: 'stat-3', label: 'Countries Worldwide', value: '70+' }
                                ];
                            })()}
                            onChange={(items) => {
                                const stats = items.map(({ id, ...item }) => item);
                                updateSectionData('stats', { stats });
                            }}
                            addButtonText={t('Add Statistic')}
                            deleteTooltipText={t('Delete Statistic')}
                            minItems={1}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}