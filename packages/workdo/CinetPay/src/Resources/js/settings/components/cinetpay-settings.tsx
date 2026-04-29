import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { CreditCard, Save, Eye, EyeOff } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';

interface CinetPaySettings {
  cinetpay_api_key: string;
  cinetpay_site_id: string;
  cinetpay_enabled: string;
  [key: string]: any;
}

interface CinetPaySettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function CinetPaySettings({ userSettings, auth }: CinetPaySettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showApiKey, setShowApiKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-cinetpay-settings');
  const [settings, setSettings] = useState<CinetPaySettings>({
    cinetpay_api_key: userSettings?.cinetpay_api_key || '',
    cinetpay_site_id: userSettings?.cinetpay_site_id || '',
    cinetpay_enabled: userSettings?.cinetpay_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        cinetpay_api_key: userSettings?.cinetpay_api_key || '',
        cinetpay_site_id: userSettings?.cinetpay_site_id || '',
        cinetpay_enabled: userSettings?.cinetpay_enabled || 'off',
      });
    }
  }, [userSettings]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleSwitchChange = (name: string, checked: boolean) => {
    setSettings(prev => ({ ...prev, [name]: checked ? 'on' : 'off' }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    const payload = {
      ...settings,
      cinetpay_enabled: settings.cinetpay_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('cinetpay.settings.update'), {
      settings: payload
    }, {
      preserveScroll: true,
      onSuccess: (page) => {
        setIsLoading(false);
        const successMessage = (page.props.flash as any)?.success;
        const errorMessage = (page.props.flash as any)?.error;

        if (successMessage) {
          toast.success(successMessage);
          router.reload({ only: ['globalSettings'] });
        } else if (errorMessage) {
          toast.error(errorMessage);
        }
      },
      onError: (errors) => {
        setIsLoading(false);
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save CinetPay settings');
        toast.error(errorMessage);
      }
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <CreditCard className="h-5 w-5" />
            {t('CinetPay Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure CinetPay payment gateway settings')}
          </p>
        </div>
        {canEdit && (
          <Button className="order-2 rtl:order-1" onClick={saveSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {/* Enable/Disable CinetPay */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="cinetpay_enabled" className="text-base font-medium">
                {t('Enable CinetPay')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable CinetPay payment gateway')}
              </p>
            </div>
            <Switch
              id="cinetpay_enabled"
              checked={settings.cinetpay_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('cinetpay_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.cinetpay_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* CinetPay API Key */}
                  <div className="space-y-3">
                    <Label htmlFor="cinetpay_api_key">{t('API Key')}</Label>
                    <div className="relative">
                      <Input
                        id="cinetpay_api_key"
                        name="cinetpay_api_key"
                        type={showApiKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.cinetpay_api_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter CinetPay API key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowApiKey(!showApiKey)}
                      >
                        {showApiKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('CinetPay API key for secure payment processing')}
                    </p>
                  </div>

                  {/* CinetPay Site ID */}
                  <div className="space-y-3">
                    <Label htmlFor="cinetpay_site_id">{t('Site ID')}</Label>
                    <Input
                      id="cinetpay_site_id"
                      name="cinetpay_site_id"
                      value={is_demo ? '****************' : settings.cinetpay_site_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter CinetPay Site ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('CinetPay Site ID for merchant identification')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get CinetPay credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://cinetpay.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('CinetPay Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your CinetPay account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Settings → API Configuration')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the "API Key" to the first field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the "Site ID" to the second field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
                      <span>{t('Use test credentials for development and live credentials for production')}</span>
                    </div>
                  </div>
                </div>
              </div>
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}