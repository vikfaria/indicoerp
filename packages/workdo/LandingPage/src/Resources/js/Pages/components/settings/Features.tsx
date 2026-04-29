import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Star } from 'lucide-react';

interface FeaturesProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Features({ data, getSectionData, updateSectionData, updateSectionVisibility }: FeaturesProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 rounded-lg">
                                <Star className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Features Content')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Manage your product features')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.features !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('features', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label>{t('Features Variant')}</Label>
                        <Select
                            value={getSectionData('features').variant || 'features1'}
                            onValueChange={(value) => updateSectionData('features', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Features Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="features1">{t('Grid')}</SelectItem>
                                <SelectItem value="features2">{t('List')}</SelectItem>
                                <SelectItem value="features3">{t('Cards')}</SelectItem>
                                <SelectItem value="features4">{t('Split')}</SelectItem>
                                <SelectItem value="features5">{t('Carousel')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Title')}</Label>
                        <Input
                            value={getSectionData('features').title || ''}
                            onChange={(e) => updateSectionData('features', { title: e.target.value })}
                            placeholder={t('Powerful Features')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Subtitle')}</Label>
                        <Textarea
                            value={getSectionData('features').subtitle || ''}
                            onChange={(e) => updateSectionData('features', { subtitle: e.target.value })}
                            placeholder={t('Everything your business needs in one integrated platform')}
                            rows={3}
                        />
                    </div>
                    
                    <div className="space-y-4">
                        <Label>{t('Features List')}</Label>
                        <Repeater
                            fields={[
                                { name: 'title', label: t('Feature Title'), type: 'text', placeholder: t('Feature title'), required: true },
                                { name: 'description', label: t('Feature Description'), type: 'textarea', placeholder: t('Feature description'), required: true },
                                { 
                                    name: 'icon', 
                                    label: t('Icon'), 
                                    type: 'select', 
                                    options: [
                                        { value: 'Building2', label: 'Building' },
                                        { value: 'Calculator', label: 'Calculator' },
                                        { value: 'Users', label: 'Users' },
                                        { value: 'CreditCard', label: 'Credit Card' },
                                        { value: 'UserCheck', label: 'User Check' },
                                        { value: 'FolderOpen', label: 'Folder' }
                                    ],
                                    required: true
                                }
                            ]}
                            value={(getSectionData('features').features || []).map((feature: any, index: number) => ({
                                id: `feature-${index}`,
                                title: feature.title || '',
                                description: feature.description || '',
                                icon: feature.icon || 'Building2'
                            }))}
                            onChange={(items) => {
                                const features = items.map(({ id, ...item }) => item);
                                updateSectionData('features', { features });
                            }}
                            addButtonText={t('Add Feature')}
                            deleteTooltipText={t('Delete Feature')}
                            minItems={1}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}