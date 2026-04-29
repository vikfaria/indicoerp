import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { ArrowUpDown, GripVertical } from 'lucide-react';

interface OrderProps {
    data: any;
    setData: (key: string, value: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Order({ data, setData, updateSectionVisibility }: OrderProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-indigo-100 rounded-lg">
                            <ArrowUpDown className="h-5 w-5 text-indigo-600" />
                        </div>
                        <div>
                            <CardTitle>{t('Section Order')}</CardTitle>
                            <p className="text-sm text-gray-500">{t('Drag and drop to reorder sections on your landing page')}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        {(data.config_sections?.section_order || []).map((sectionKey, index) => {
                            const sectionNames = {
                                header: t('Header'),
                                hero: t('Hero'),
                                stats: t('Stats'),
                                features: t('Features'),
                                modules: t('Modules'),
                                benefits: t('Benefits'),
                                gallery: t('Gallery'),
                                cta: t('CTA'),
                                footer: t('Footer')
                            };
                            
                            const isEnabled = data.config_sections?.section_visibility?.[sectionKey] !== false;
                            
                            return (
                                <div
                                    key={sectionKey}
                                    draggable
                                    onDragStart={(e) => {
                                        e.dataTransfer.setData('text/plain', index.toString());
                                    }}
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                    }}
                                    onDrop={(e) => {
                                        e.preventDefault();
                                        const dragIndex = parseInt(e.dataTransfer.getData('text/plain'));
                                        const currentOrder = [...(data.config_sections?.section_order || [])];
                                        const draggedItem = currentOrder[dragIndex];
                                        currentOrder.splice(dragIndex, 1);
                                        currentOrder.splice(index, 0, draggedItem);
                                        setData('config_sections', {
                                            ...data.config_sections,
                                            section_order: currentOrder
                                        });
                                    }}
                                    className={`flex items-center gap-3 p-4 border rounded-lg transition-all cursor-move ${
                                        isEnabled ? 'bg-white border-gray-200 hover:shadow-md' : 'bg-gray-50 border-gray-300 opacity-60'
                                    }`}
                                >
                                    <GripVertical className="h-5 w-5 text-gray-400" />
                                    <div className="flex-1 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <span className="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
                                                {index + 1}
                                            </span>
                                            <div>
                                                <h4 className="font-medium text-gray-900">{sectionNames[sectionKey] || sectionKey}</h4>
                                                <p className="text-sm text-gray-500">
                                                    {isEnabled ? t('Enabled') : t('Disabled')}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Label className="text-sm">{t('Enable')}</Label>
                                            <Switch
                                                checked={isEnabled}
                                                onCheckedChange={(checked) => updateSectionVisibility(sectionKey, checked)}
                                            />
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}