import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { Share2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface SocialSettingsProps {
    getSocialData: () => any;
    updateSocialData: (updates: any) => void;
}

export default function SocialSettings({ getSocialData, updateSocialData }: SocialSettingsProps) {
    const { t } = useTranslation();
    const socialData = getSocialData();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-violet-100 rounded-lg">
                            <Share2 className="h-5 w-5 text-violet-600" />
                        </div>
                        <div>
                            <CardTitle>{t('Social Sharing')}</CardTitle>
                            <p className="text-sm text-gray-500">{t('Open Graph and social preview content')}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Open Graph Title')}</Label>
                        <Input
                            value={socialData.og_title || ''}
                            onChange={(e) => updateSocialData({ og_title: e.target.value })}
                            placeholder={t('Índico ERP - Sistema de Gestão Completo para Empresas em Moçambique')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Open Graph Description')}</Label>
                        <Textarea
                            value={socialData.og_description || ''}
                            onChange={(e) => updateSocialData({ og_description: e.target.value })}
                            placeholder={t('Description shown when the landing page is shared')}
                            rows={4}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Open Graph Image')}</Label>
                        <MediaPicker
                            value={socialData.og_image || ''}
                            onChange={(value) => updateSocialData({ og_image: value })}
                            placeholder={t('Select social preview image')}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>{t('Social Site Name')}</Label>
                        <Input
                            value={socialData.site_name || ''}
                            onChange={(e) => updateSocialData({ site_name: e.target.value })}
                            placeholder="Índico ERP"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
