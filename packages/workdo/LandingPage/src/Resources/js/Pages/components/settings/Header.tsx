import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Layout } from 'lucide-react';

interface CustomPage {
    id: number;
    title: string;
    slug: string;
}

interface HeaderProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
    customPages: CustomPage[];
}

export default function Header({ data, getSectionData, updateSectionData, updateSectionVisibility, customPages }: HeaderProps) {
    const { t } = useTranslation();
    
    const navigationItems = getSectionData('header').navigation_items || [];
    
    const addNavigationItem = () => {
        const newItems = [...navigationItems, { text: '', href: '', type: 'link' }];
        updateSectionData('header', { navigation_items: newItems });
    };
    
    const updateNavigationItem = (index: number, field: string, value: string) => {
        const newItems = [...navigationItems];
        newItems[index] = { ...newItems[index], [field]: value };
        updateSectionData('header', { navigation_items: newItems });
    };
    
    const removeNavigationItem = (index: number) => {
        const newItems = navigationItems.filter((_: any, i: number) => i !== index);
        updateSectionData('header', { navigation_items: newItems });
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-green-100 rounded-lg">
                                <Layout className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Header Navigation')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Logo and navigation menu')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.header !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('header', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Header Variant')}</Label>
                        <Select
                            value={getSectionData('header').variant || 'header1'}
                            onValueChange={(value) => updateSectionData('header', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Header Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="header1">{t('Standard')}</SelectItem>
                                <SelectItem value="header2">{t('Centered')}</SelectItem>
                                <SelectItem value="header3">{t('Minimal')}</SelectItem>
                                <SelectItem value="header4">{t('Transparent')}</SelectItem>
                                <SelectItem value="header5">{t('Gradient')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Company Name')}</Label>
                        <Input
                            value={getSectionData('header').company_name || ''}
                            onChange={(e) => updateSectionData('header', { company_name: e.target.value })}
                            placeholder={t('ERPGo SaaS')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('CTA Button Text')}</Label>
                        <Input
                            value={getSectionData('header').cta_text || ''}
                            onChange={(e) => updateSectionData('header', { cta_text: e.target.value })}
                            placeholder={t('Get Started')}
                        />
                    </div>
                     <div className="space-y-4">
                        <Label>{t('Display Options')}</Label>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="flex items-center space-x-2">
                                <Switch
                                    checked={getSectionData('header').enable_pricing_link !== false}
                                    onCheckedChange={(checked) => updateSectionData('header', { enable_pricing_link: checked })}
                                />
                                <span className="text-sm">{t('Enable Pricing Link')}</span>
                            </div>
                        </div>
                     </div>
                    
                    <div className="space-y-4">
                        <Label>{t('Navigation Menu')}</Label>
                        <Repeater
                            fields={[
                                { name: 'text', label: t('Menu Text'), type: 'text', placeholder: t('Menu Text'), required: true },
                                { 
                                    name: 'type', 
                                    label: t('Type'), 
                                    type: 'select', 
                                    options: [
                                        { value: 'link', label: t('Link') },
                                        { value: 'page', label: t('Page') }
                                    ],
                                    required: true
                                },
                                { 
                                    name: 'href', 
                                    label: t('URL/Page'), 
                                    type: 'conditional',
                                    dependsOn: 'type',
                                    conditions: {
                                        link: { type: 'text', placeholder: t('Enter URL (e.g., #features, https://example.com)') },
                                        page: { 
                                            type: 'select', 
                                            placeholder: t('Select Page'),
                                            options: customPages.map(page => ({ value: `/page/${page.slug}`, label: page.title }))
                                        }
                                    }
                                },
                                { 
                                    name: 'target', 
                                    label: t('Open in New Tab'), 
                                    type: 'checkbox'
                                }
                            ]}
                            value={navigationItems.map((item: any, index: number) => ({
                                id: `header-nav-${index}`,
                                text: item.text || '',
                                type: item.type || 'link',
                                href: item.href || '',
                                target: item.target === '_blank' || item.target === true
                            }))}
                            onChange={(items) => {
                                const navigation_items = items.map(({ id, ...item }) => ({
                                    text: item.text,
                                    type: item.type,
                                    href: item.href,
                                    target: item.target ? '_blank' : '_self'
                                }));
                                updateSectionData('header', { navigation_items });
                            }}
                            addButtonText={t('Add Navigation Item')}
                            deleteTooltipText={t('Delete Navigation Item')}
                            minItems={0}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}