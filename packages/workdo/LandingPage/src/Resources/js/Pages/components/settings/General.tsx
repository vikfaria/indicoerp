import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { Type } from 'lucide-react';

interface GeneralProps {
    data: any;
    updateSectionData: (field: string, value: any) => void;
}

export default function General({ data, updateSectionData }: GeneralProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-blue-100 rounded-lg">
                            <Type className="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <CardTitle>{t('Company Information')}</CardTitle>
                            <p className="text-sm text-gray-500">{t('Basic company details for your landing page')}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>{t('Company Name')}</Label>
                            <Input 
                                value={data.company_name || ''}
                                onChange={(e) => updateSectionData('company_name', e.target.value)}
                                placeholder={t('Your Company Name')} 
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Contact Email')}</Label>
                            <Input 
                                type="email" 
                                value={data.contact_email || ''}
                                onChange={(e) => updateSectionData('contact_email', e.target.value)}
                                placeholder="support@company.com" 
                            />
                        </div>
                        <div className="space-y-2">
                            <PhoneInputComponent
                                label={t('Contact Phone')}
                                value={data.contact_phone || ''}
                                onChange={(value) => updateSectionData('contact_phone', value)}
                                placeholder="+1 (555) 123-4567"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('Contact Address')}</Label>
                            <Input 
                                value={data.contact_address || ''}
                                onChange={(e) => updateSectionData('contact_address', e.target.value)}
                                placeholder="123 Business Ave, City, State" 
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}