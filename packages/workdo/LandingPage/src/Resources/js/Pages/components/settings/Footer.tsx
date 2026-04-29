import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Repeater } from '@/components/ui/repeater';
import { Layout, Plus, Trash2 } from 'lucide-react';

interface CustomPage {
    id: number;
    title: string;
    slug: string;
}

interface FooterProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (sectionKey: string, visible: boolean) => void;
    customPages: CustomPage[];
}

export default function Footer({ data, getSectionData, updateSectionData, updateSectionVisibility, customPages = [] }: FooterProps) {
    const { t } = useTranslation();
    
    const navigationSections = getSectionData('footer').navigation_sections || [];
    
    const addSection = () => {
        const newSections = [...navigationSections, { title: '', links: [] }];
        updateSectionData('footer', { navigation_sections: newSections });
    };
    
    const updateSection = (sectionIndex: number, field: string, value: any) => {
        const newSections = [...navigationSections];
        newSections[sectionIndex] = { ...newSections[sectionIndex], [field]: value };
        updateSectionData('footer', { navigation_sections: newSections });
    };
    
    const removeSection = (sectionIndex: number) => {
        const newSections = navigationSections.filter((_: any, i: number) => i !== sectionIndex);
        updateSectionData('footer', { navigation_sections: newSections });
    };
    
    const addLink = (sectionIndex: number) => {
        const newSections = [...navigationSections];
        newSections[sectionIndex].links = [...(newSections[sectionIndex].links || []), { text: '', href: '', type: 'link' }];
        updateSectionData('footer', { navigation_sections: newSections });
    };
    
    const removeLink = (sectionIndex: number, linkIndex: number) => {
        const newSections = [...navigationSections];
        newSections[sectionIndex].links = newSections[sectionIndex].links.filter((_: any, i: number) => i !== linkIndex);
        updateSectionData('footer', { navigation_sections: newSections });
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-gray-100 rounded-lg">
                                <Layout className="h-5 w-5 text-gray-600" />
                            </div>
                            <div>
                                <CardTitle>{t('Footer Content')}</CardTitle>
                                <p className="text-sm text-gray-500">{t('Footer information and links')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm">{t('Enable Section')}</Label>
                            <Switch
                                checked={data.config_sections?.section_visibility?.footer !== false}
                                onCheckedChange={(checked) => updateSectionVisibility('footer', checked)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Footer Variant')}</Label>
                        <Select
                            value={getSectionData('footer').variant || 'footer1'}
                            onValueChange={(value) => updateSectionData('footer', { variant: value })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Footer Style')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="footer1">{t('Standard')}</SelectItem>
                                <SelectItem value="footer2">{t('Minimal')}</SelectItem>
                                <SelectItem value="footer3">{t('Centered')}</SelectItem>
                                <SelectItem value="footer4">{t('Split')}</SelectItem>
                                <SelectItem value="footer5">{t('Modern')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Company Description')}</Label>
                        <Textarea
                            value={getSectionData('footer').description || ''}
                            onChange={(e) => updateSectionData('footer', { description: e.target.value })}
                            placeholder={t('The complete business management solution for modern enterprises.')}
                            rows={3}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4">
                        <div className="space-y-2">
                            <Label>{t('Newsletter Title')}</Label>
                            <Input
                                value={getSectionData('footer').newsletter_title || ''}
                                onChange={(e) => updateSectionData('footer', { newsletter_title: e.target.value })}
                                placeholder={t('Join Our Community')}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Newsletter Description')}</Label>
                            <Textarea
                                value={getSectionData('footer').newsletter_description || ''}
                                onChange={(e) => updateSectionData('footer', { newsletter_description: e.target.value })}
                                placeholder={t('We build modern web tools to help you jump-start your daily business work.')}
                                rows={2}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Newsletter Button Text')}</Label>
                            <Input
                                value={getSectionData('footer').newsletter_button_text || ''}
                                onChange={(e) => updateSectionData('footer', { newsletter_button_text: e.target.value })}
                                placeholder={t('Subscribe')}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Copyright Text')}</Label>
                            <Input
                                value={getSectionData('footer').copyright_text || ''}
                                onChange={(e) => updateSectionData('footer', { copyright_text: e.target.value })}
                                placeholder={`© ${new Date().getFullYear()} Company Name. All rights reserved.`}
                            />
                        </div>
                    </div>
                    
                    <div className="space-y-6">
                        <Label>{t('Navigation Sections')}</Label>
                        <Repeater
                            fields={[
                                { name: 'title', label: t('Section Title'), type: 'text', placeholder: t('Section Title'), required: true }
                            ]}
                            value={navigationSections.map((section: any, index: number) => ({
                                id: `section-${index}`,
                                title: section.title || ''
                            }))}
                            onChange={(items) => {
                                const navigation_sections = items.map(({ id, ...item }, index) => ({
                                    ...item,
                                    links: navigationSections[index]?.links || []
                                }));
                                updateSectionData('footer', { navigation_sections });
                            }}
                            addButtonText={t('Add Navigation Section')}
                            deleteTooltipText={t('Delete Navigation Section')}
                            minItems={0}
                            renderCustomField={(item, index, updateItem) => (
                                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <Label className="text-sm font-medium">{t('Section Links')}</Label>
                                    <div className="mt-2">
                                        <Repeater
                                            fields={[
                                                { name: 'text', label: t('Link Text'), type: 'text', placeholder: t('Link Text'), required: true },
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
                                            value={(navigationSections[index]?.links || []).map((link: any, linkIndex: number) => ({
                                                id: `footer-link-${index}-${linkIndex}`,
                                                text: link.text || '',
                                                type: link.type || 'link',
                                                href: link.href || '',
                                                target: link.target === '_blank' || link.target === true
                                            }))}
                                            onChange={(linkItems) => {
                                                const newSections = [...navigationSections];
                                                newSections[index] = {
                                                    ...newSections[index],
                                                    links: linkItems.map(({ id, ...linkItem }) => ({
                                                        text: linkItem.text,
                                                        type: linkItem.type,
                                                        href: linkItem.href,
                                                        target: linkItem.target ? '_blank' : '_self'
                                                    }))
                                                };
                                                updateSectionData('footer', { navigation_sections: newSections });
                                            }}
                                            addButtonText={t('Add Link')}
                                            deleteTooltipText={t('Delete Link')}
                                            minItems={0}
                                        />
                                    </div>
                                </div>
                            )}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}