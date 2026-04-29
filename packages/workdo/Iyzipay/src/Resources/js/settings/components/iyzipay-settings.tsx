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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

interface IyzipaySettings {
  iyzipay_api_key: string;
  iyzipay_secret_key: string;
  iyzipay_enabled: string;
  iyzipay_mode: string;
  [key: string]: any;
}

interface IyzipaySettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function IyzipaySettings({ userSettings, auth }: IyzipaySettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-iyzipay-settings');
  const [settings, setSettings] = useState<IyzipaySettings>({
    iyzipay_api_key: userSettings?.company_iyzipay_api_key || userSettings?.iyzipay_api_key || '',
    iyzipay_secret_key: userSettings?.company_iyzipay_secret_key || userSettings?.iyzipay_secret_key || '',
    iyzipay_enabled: userSettings?.iyzipay_payment_is_on || userSettings?.iyzipay_enabled || 'off',
    iyzipay_mode: userSettings?.company_iyzipay_mode || userSettings?.iyzipay_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        iyzipay_api_key: userSettings?.company_iyzipay_api_key || userSettings?.iyzipay_api_key || '',
        iyzipay_secret_key: userSettings?.company_iyzipay_secret_key || userSettings?.iyzipay_secret_key || '',
        iyzipay_enabled: userSettings?.iyzipay_payment_is_on || userSettings?.iyzipay_enabled || 'off',
        iyzipay_mode: userSettings?.company_iyzipay_mode || userSettings?.iyzipay_mode || 'sandbox',
      });
    }
  }, [userSettings]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleSwitchChange = (name: string, checked: boolean) => {
    setSettings(prev => ({ ...prev, [name]: checked ? 'on' : 'off' }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    const payload = {
      ...settings,
      iyzipay_enabled: settings.iyzipay_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('iyzipay.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Iyzipay settings');
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
            {t('Iyzipay Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Iyzipay payment gateway settings')}
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
          {/* Enable/Disable Iyzipay */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="iyzipay_enabled" className="text-base font-medium">
                {t('Enable Iyzipay')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Iyzipay payment gateway')}
              </p>
            </div>
            <Switch
              id="iyzipay_enabled"
              checked={settings.iyzipay_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('iyzipay_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.iyzipay_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Iyzipay Mode */}
                  <div className="space-y-3">
                    <Label>{t('Iyzipay Mode')}</Label>
                    <RadioGroup
                      value={settings.iyzipay_mode}
                      onValueChange={(value) => handleSelectChange('iyzipay_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="iyzipay-sandbox" />
                        <Label htmlFor="iyzipay-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="iyzipay-live" />
                        <Label htmlFor="iyzipay-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.iyzipay_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* Iyzipay API Key */}
                  <div className="space-y-3">
                    <Label htmlFor="iyzipay_api_key">{t('Iyzipay API Key')}</Label>
                    <Input
                      id="iyzipay_api_key"
                      name="iyzipay_api_key"
                      value={is_demo ? '****************' : settings.iyzipay_api_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter Iyzipay API key')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Iyzipay API key for integration')}
                    </p>
                  </div>

                  {/* Iyzipay Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="iyzipay_secret_key">{t('Iyzipay Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="iyzipay_secret_key"
                        name="iyzipay_secret_key"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.iyzipay_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Iyzipay secret key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowSecret(!showSecret)}
                      >
                        {showSecret ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Iyzipay secret key for secure API communication')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Iyzipay API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://merchant.iyzipay.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Iyzipay Merchant Panel')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Iyzipay account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Settings > API & Webhook')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the API Key and Secret Key')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Select "Sandbox" mode for testing or "Live" mode for production')}</span>
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