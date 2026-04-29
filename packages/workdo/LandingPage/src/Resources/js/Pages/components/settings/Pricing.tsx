import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { useEffect } from 'react';

interface PricingProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (key: string, visible: boolean) => void;
}

export default function Pricing({ data, getSectionData, updateSectionData, updateSectionVisibility }: PricingProps) {
    const { t } = useTranslation();
    const sectionData = getSectionData('pricing') || {};
    const isVisible = data.config_sections?.section_visibility?.pricing !== false;

    useEffect(() => {
        // Initialize pricing settings with default values if they don't exist
        const defaultSettings = {
            title: 'Subscription Setting',
            subtitle: 'Choose the perfect subscription plan for your business needs',
            default_subscription_type: 'pre-package',
            default_price_type: 'monthly',
            show_pre_package: true,
            show_monthly_yearly_toggle: true,
            empty_message: 'No plans available. Check back later for new pricing plans.'
        };
        
        // Only update if pricing section is empty or missing keys
        const currentData = getSectionData('pricing');
        const hasAllKeys = Object.keys(defaultSettings).every(key => key in currentData);
        
        if (!hasAllKeys) {
            updateSectionData('pricing', { ...defaultSettings, ...currentData });
        }
    }, []);

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>{t('Pricing Page Settings')}</CardTitle>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* Page Title and Subtitle */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="pricing-title">{t('Page Title')}</Label>
                            <Input
                                id="pricing-title"
                                value={sectionData.title || 'Subscription Setting'}
                                onChange={(e) => updateSectionData('pricing', { title: e.target.value })}
                                placeholder={t('Enter page title')}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pricing-subtitle">{t('Page Subtitle')}</Label>
                            <Textarea
                                id="pricing-subtitle"
                                value={sectionData.subtitle || 'Choose the perfect subscription plan for your business needs'}
                                onChange={(e) => updateSectionData('pricing', { subtitle: e.target.value })}
                                placeholder={t('Enter page subtitle')}
                                rows={3}
                            />
                        </div>
                    </div>

                    {/* Default Settings */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="pricing-subscription-type">{t('Default Subscription Type')}</Label>
                            <Select
                                value={sectionData.default_subscription_type || 'pre-package'}
                                onValueChange={(value) => updateSectionData('pricing', { default_subscription_type: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pre-package">{t('Pre Package Subscription')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pricing-price-type">{t('Default Price Type')}</Label>
                            <Select
                                value={sectionData.default_price_type || 'monthly'}
                                onValueChange={(value) => updateSectionData('pricing', { default_price_type: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="monthly">{t('Monthly')}</SelectItem>
                                    <SelectItem value="yearly">{t('Yearly')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Empty State Message */}
                    <div className="space-y-2">
                        <Label htmlFor="pricing-empty-message">{t('Empty State Message')}</Label>
                        <Textarea
                            id="pricing-empty-message"
                            value={sectionData.empty_message || 'No plans available. Check back later for new pricing plans.'}
                            onChange={(e) => updateSectionData('pricing', { empty_message: e.target.value })}
                            placeholder={t('Message to show when no plans are available')}
                            rows={3}
                        />
                    </div>

                    {/* Display Options */}
                    <div className="space-y-4">
                        <Label>{t('Display Options')}</Label>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="show-pre-package"
                                    checked={sectionData.show_pre_package !== false}
                                    onCheckedChange={(checked) => updateSectionData('pricing', { show_pre_package: checked })}
                                />
                                <Label htmlFor="show-pre-package">{t('Show Pre Package Subscription')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="show-monthly-yearly-toggle"
                                    checked={sectionData.show_monthly_yearly_toggle !== false}
                                    onCheckedChange={(checked) => updateSectionData('pricing', { show_monthly_yearly_toggle: checked })}
                                />
                                <Label htmlFor="show-monthly-yearly-toggle">{t('Show Monthly/Yearly Toggle')}</Label>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}