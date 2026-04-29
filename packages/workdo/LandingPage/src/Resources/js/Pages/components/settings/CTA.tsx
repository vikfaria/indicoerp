import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';

interface CTAProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
}

export default function CTA({ data, getSectionData, updateSectionData, updateSectionVisibility }: CTAProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-orange-100 rounded-lg">
                                <ArrowUpDown className="h-5 w-5 text-orange-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Call to Action')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Final conversion section')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.cta !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('cta', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('CTA Variant')}</Label>
                        <Select
                            value={getSectionData('cta').variant || 'cta1'}
                            onValueChange={(value) => updateSectionData('cta', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select CTA Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cta1">{t('Centered')}</SelectItem>
                                <SelectItem value="cta2">{t('Split')}</SelectItem>
                                <SelectItem value="cta3">{t('Card')}</SelectItem>
                                <SelectItem value="cta4">{t('Gradient')}</SelectItem>
                                <SelectItem value="cta5">{t('Minimal')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Main Title')}</Label>
                        <Input
                            value={getSectionData('cta').title || ''}
                            onChange={(e) => updateSectionData('cta', { title: e.target.value })}
                            placeholder={t('Ready to Transform Your Business?')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Subtitle')}</Label>
                        <Textarea
                            value={getSectionData('cta').subtitle || ''}
                            onChange={(e) => updateSectionData('cta', { subtitle: e.target.value })}
                            placeholder={t('Join thousands of businesses already using ERPGo SaaS to streamline their operations.')}
                            rows={3}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>{t('Primary Button Text')}</Label>
                            <Input
                                value={getSectionData('cta').primary_button || ''}
                                onChange={(e) => updateSectionData('cta', { primary_button: e.target.value })}
                                placeholder={t('Start Free Trial')}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Secondary Button Text')}</Label>
                            <Input
                                value={getSectionData('cta').secondary_button || ''}
                                onChange={(e) => updateSectionData('cta', { secondary_button: e.target.value })}
                                placeholder={t('Contact Sales')}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label className="flex items-center gap-2">
                                {t('Primary Button Link')}
                            </Label>
                            <Input
                                value={getSectionData('cta').primary_button_link || ''}
                                onChange={(e) => updateSectionData('cta', { primary_button_link: e.target.value })}
                                placeholder="https://example.com/signup"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label className="flex items-center gap-2">
                                {t('Secondary Button Link')}
                            </Label>
                            <Input
                                value={getSectionData('cta').secondary_button_link || ''}
                                onChange={(e) => updateSectionData('cta', { secondary_button_link: e.target.value })}
                                placeholder="https://example.com/contact"
                            />
                        </div>
                    </div>
                    {getSectionData('cta').variant === 'cta2' && (
                        <div className="space-y-2">
                            <Label className="flex items-center gap-2">
                                {t('CTA Image (Split Layout)')}
                            </Label>
                            <MediaPicker
                                value={getSectionData('cta').image || ''}
                                onChange={(value) => updateSectionData('cta', { image: value })}
                                accept="image/*"
                                placeholder={t('Select CTA Image')}
                            />
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}