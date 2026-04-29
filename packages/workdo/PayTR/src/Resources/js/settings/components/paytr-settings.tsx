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

interface PayTRSettings {
  paytr_merchant_id: string;
  paytr_merchant_key: string;
  paytr_merchant_salt: string;
  paytr_mode: string;
  paytr_enabled: string;
  [key: string]: any;
}

interface PayTRSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function PayTRSettings({ userSettings, auth }: PayTRSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showKey, setShowKey] = useState(false);
  const [showSalt, setShowSalt] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-paytr-settings');
  const [settings, setSettings] = useState<PayTRSettings>({
    paytr_merchant_id: userSettings?.paytr_merchant_id || '',
    paytr_merchant_key: userSettings?.paytr_merchant_key || '',
    paytr_merchant_salt: userSettings?.paytr_merchant_salt || '',
    paytr_mode: userSettings?.paytr_mode || 'sandbox',
    paytr_enabled: userSettings?.paytr_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        paytr_merchant_id: userSettings?.paytr_merchant_id || '',
        paytr_merchant_key: userSettings?.paytr_merchant_key || '',
        paytr_merchant_salt: userSettings?.paytr_merchant_salt || '',
        paytr_mode: userSettings?.paytr_mode || 'sandbox',
        paytr_enabled: userSettings?.paytr_enabled || 'off',
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
      paytr_enabled: settings.paytr_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('paytr.settings.update'), {
      settings: payload
    }, {
      preserveScroll: true,
      onSuccess: (page) => {
        setIsLoading(false);
        const successMessage = (page.props.flash as any)?.success;
        const errorMessage = (page.props.flash as any)?.error;

        if (successMessage) {
          toast.success(successMessage);
        } else if (errorMessage) {
          toast.error(errorMessage);
        } else {
          toast.success(t('PayTR settings saved successfully'));
        }
        router.reload({ only: ['globalSettings'] });
      },
      onError: (errors) => {
        setIsLoading(false);
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save PayTR settings');
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
            {t('PayTR Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure PayTR payment gateway settings')}
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
          {/* Enable/Disable PayTR */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="paytr_enabled" className="text-base font-medium">
                {t('Enable PayTR')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable PayTR payment gateway')}
              </p>
            </div>
            <Switch
              id="paytr_enabled"
              checked={settings.paytr_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('paytr_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.paytr_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* PayTR Mode */}
                  <div className="space-y-3">
                    <Label>{t('PayTR Mode')}</Label>
                    <RadioGroup
                      value={settings.paytr_mode}
                      onValueChange={(value) => handleSelectChange('paytr_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="paytr-sandbox" />
                        <Label htmlFor="paytr-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="paytr-live" />
                        <Label htmlFor="paytr-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.paytr_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* PayTR Merchant ID */}
                  <div className="space-y-3">
                    <Label htmlFor="paytr_merchant_id">{t('Merchant ID')}</Label>
                    <Input
                      id="paytr_merchant_id"
                      name="paytr_merchant_id"
                      value={is_demo ? '****************' : settings.paytr_merchant_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter PayTR Merchant ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('PayTR Merchant ID for API integration')}
                    </p>
                  </div>

                  {/* PayTR Merchant Key */}
                  <div className="space-y-3">
                    <Label htmlFor="paytr_merchant_key">{t('Merchant Key')}</Label>
                    <div className="relative">
                      <Input
                        id="paytr_merchant_key"
                        name="paytr_merchant_key"
                        type={showKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.paytr_merchant_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter PayTR Merchant Key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowKey(!showKey)}
                      >
                        {showKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('PayTR Merchant Key for secure API communication')}
                    </p>
                  </div>

                  {/* PayTR Merchant Salt */}
                  <div className="space-y-3">
                    <Label htmlFor="paytr_merchant_salt">{t('Merchant Salt')}</Label>
                    <div className="relative">
                      <Input
                        id="paytr_merchant_salt"
                        name="paytr_merchant_salt"
                        type={showSalt ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.paytr_merchant_salt}
                        onChange={handleInputChange}
                        placeholder={t('Enter PayTR Merchant Salt')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowSalt(!showSalt)}
                      >
                        {showSalt ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('PayTR Merchant Salt for transaction security')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get PayTR API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://www.paytr.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('PayTR Panel')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your PayTR merchant account')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to API Settings')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy your Merchant ID, Merchant Key, and Merchant Salt')}</span>
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