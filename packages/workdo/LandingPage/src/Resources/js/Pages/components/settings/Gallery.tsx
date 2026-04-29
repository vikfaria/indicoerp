import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Image } from 'lucide-react';

interface GalleryProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function Gallery({ data, getSectionData, updateSectionData, updateSectionVisibility }: GalleryProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-indigo-100 rounded-lg">
                                <Image className="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Image Gallery')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Product showcase slider')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.gallery !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('gallery', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label>{t('Gallery Variant')}</Label>
                        <Select
                            value={getSectionData('gallery').variant || 'gallery1'}
                            onValueChange={(value) => updateSectionData('gallery', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Gallery Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="gallery1">{t('Slider')}</SelectItem>
                                <SelectItem value="gallery2">{t('Grid')}</SelectItem>
                                <SelectItem value="gallery3">{t('Stacked')}</SelectItem>
                                <SelectItem value="gallery4">{t('Carousel')}</SelectItem>
                                <SelectItem value="gallery5">{t('Lightbox')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Title')}</Label>
                        <Input
                            value={getSectionData('gallery').title || ''}
                            onChange={(e) => updateSectionData('gallery', { title: e.target.value })}
                            placeholder={t('Gallery')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Section Subtitle')}</Label>
                        <Input
                            value={getSectionData('gallery').subtitle || ''}
                            onChange={(e) => updateSectionData('gallery', { subtitle: e.target.value })}
                            placeholder={t('Explore our product in action')}
                        />
                    </div>
                    
                    <div className="space-y-4">
                        <Label>{t('Gallery Images')}</Label>
                        <Repeater
                            fields={[
                                { name: 'image', label: t('Image'), type: 'image', placeholder: t('Select image...'), required: true }
                            ]}
                            value={(getSectionData('gallery').images || []).map((image: string, index: number) => ({
                                id: `image-${index}`,
                                image: image
                            }))}
                            onChange={(items) => {
                                const images = items.map(item => item.image);
                                updateSectionData('gallery', { images });
                            }}
                            addButtonText={t('Add Image')}
                            deleteTooltipText={t('Delete Image')}
                            minItems={1}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}