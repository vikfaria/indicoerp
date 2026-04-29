import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Globe } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface PageSettingsProps {
    getPageData: () => any;
    updatePageData: (updates: any) => void;
}

export default function PageSettings({ getPageData, updatePageData }: PageSettingsProps) {
    const { t } = useTranslation();
    const pageData = getPageData();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-sky-100 rounded-lg">
                            <Globe className="h-5 w-5 text-sky-600" />
                        </div>
                        <div>
                            <CardTitle>{t('Page Metadata')}</CardTitle>
                            <p className="text-sm text-gray-500">{t('SEO and browser metadata for the landing page')}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Browser Page Title')}</Label>
                        <Input
                            value={pageData.title || ''}
                            onChange={(e) => updatePageData({ title: e.target.value })}
                            placeholder={t('Índico ERP - Sistema de Gestão Completo para Empresas em Moçambique')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Meta Description')}</Label>
                        <Textarea
                            value={pageData.description || ''}
                            onChange={(e) => updatePageData({ description: e.target.value })}
                            placeholder={t('Describe the landing page for search engines')}
                            rows={4}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Meta Keywords')}</Label>
                        <Input
                            value={pageData.keywords || ''}
                            onChange={(e) => updatePageData({ keywords: e.target.value })}
                            placeholder={t('erp moçambique, saft, faturação, rh, crm, pos')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Canonical URL')}</Label>
                        <Input
                            value={pageData.canonical_url || ''}
                            onChange={(e) => updatePageData({ canonical_url: e.target.value })}
                            placeholder="https://indicoerp.com/"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
