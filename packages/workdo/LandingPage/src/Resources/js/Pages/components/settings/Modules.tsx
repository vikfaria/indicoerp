import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Monitor } from 'lucide-react';

interface ModulesProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Modules({ data, getSectionData, updateSectionData, updateSectionVisibility }: ModulesProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-purple-100 rounded-lg">
                                <Monitor className="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Business Modules')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Tabbed modules showcase section')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.modules !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('modules', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label>{t('Modules Variant')}</Label>
                        <Select
                            value={getSectionData('modules').variant || 'modules1'}
                            onValueChange={(value) => updateSectionData('modules', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Modules Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="modules1">{t('Tabs')}</SelectItem>
                                <SelectItem value="modules2">{t('Cards')}</SelectItem>
                                <SelectItem value="modules3">{t('Accordion')}</SelectItem>
                                <SelectItem value="modules4">{t('Slider')}</SelectItem>
                                <SelectItem value="modules5">{t('Grid')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Title')}</Label>
                        <Input
                            value={getSectionData('modules').title || ''}
                            onChange={(e) => updateSectionData('modules', { title: e.target.value })}
                            placeholder={t('Complete Business Solutions')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Subtitle')}</Label>
                        <Textarea
                            value={getSectionData('modules').subtitle || ''}
                            onChange={(e) => updateSectionData('modules', { subtitle: e.target.value })}
                            placeholder={t('Discover our comprehensive modules designed to streamline every aspect of your business operations')}
                            rows={3}
                        />
                    </div>
                    
                    <div className="space-y-4">
                        <Label>{t('Modules List')}</Label>
                        <Repeater
                            fields={[
                                { name: 'key', label: t('Module Key'), type: 'text', placeholder: t('sales'), required: true },
                                { name: 'label', label: t('Module Label'), type: 'text', placeholder: t('Sales & Accounting'), required: true },
                                { name: 'title', label: t('Module Title'), type: 'text', placeholder: t('Account Helps You Simplify Your Accounting and Billing'), required: true },
                                { name: 'description', label: t('Module Description'), type: 'textarea', placeholder: t('Module description'), required: true },
                                { name: 'image', label: t('Module Image'), type: 'image', placeholder: t('Select module image...'), required: true }
                            ]}
                            value={(getSectionData('modules').modules || []).map((module: any, index: number) => ({
                                id: `module-${index}`,
                                key: module.key || '',
                                label: module.label || '',
                                title: module.title || '',
                                description: module.description || '',
                                image: module.image || ''
                            }))}
                            onChange={(items) => {
                                const modules = items.map(({ id, ...item }) => item);
                                updateSectionData('modules', { modules });
                            }}
                            addButtonText={t('Add Module')}
                            deleteTooltipText={t('Delete Module')}
                            minItems={1}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}