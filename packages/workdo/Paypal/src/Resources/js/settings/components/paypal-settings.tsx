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

interface PaypalSettings {
  paypal_client_id: string;
  paypal_secret_key: string;
  paypal_enabled: string;
  paypal_mode: string;
  [key: string]: any;
}

interface PaypalSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function PaypalSettings({ userSettings, auth }: PaypalSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-paypal-settings');
  const [settings, setSettings] = useState<PaypalSettings>({
    paypal_client_id: userSettings?.paypal_client_id || '',
    paypal_secret_key: userSettings?.paypal_secret_key || '',
    paypal_enabled: userSettings?.paypal_enabled || 'off',
    paypal_mode: userSettings?.paypal_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        paypal_client_id: userSettings?.paypal_client_id || '',
        paypal_secret_key: userSettings?.paypal_secret_key || '',
        paypal_enabled: userSettings?.paypal_enabled || 'off',
        paypal_mode: userSettings?.paypal_mode || 'sandbox',
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
      paypal_enabled: settings.paypal_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('paypal.settings.update'), {
      settings: payload
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
        router.reload({ only: ['globalSettings'] });
      },
      onError: () => {
        setIsLoading(false);
      }
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <CreditCard className="h-5 w-5" />
            {t('PayPal Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure PayPal payment gateway settings')}
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
          {/* Enable/Disable PayPal */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="paypal_enabled" className="text-base font-medium">
                {t('Enable PayPal')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable PayPal payment gateway')}
              </p>
            </div>
            <Switch
              id="paypal_enabled"
              checked={settings.paypal_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('paypal_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.paypal_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* PayPal Mode */}
                  <div className="space-y-3">
                    <Label>{t('PayPal Mode')}</Label>
                    <RadioGroup
                      value={settings.paypal_mode}
                      onValueChange={(value) => handleSelectChange('paypal_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="paypal-sandbox" />
                        <Label htmlFor="paypal-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="paypal-live" />
                        <Label htmlFor="paypal-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.paypal_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* PayPal Client ID */}
                  <div className="space-y-3">
                    <Label htmlFor="paypal_client_id">{t('PayPal Client ID')}</Label>
                    <Input
                      id="paypal_client_id"
                      name="paypal_client_id"
                      value={is_demo ? '****************' : settings.paypal_client_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter PayPal client ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('PayPal client ID for API integration')}
                    </p>
                  </div>

                  {/* PayPal Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="paypal_secret_key">{t('PayPal Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="paypal_secret_key"
                        name="paypal_secret_key"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.paypal_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter PayPal secret key')}
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
                      {t('PayPal secret key for secure API communication')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get PayPal API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://developer.paypal.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">PayPal Developer</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your PayPal account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to My Apps & Credentials')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Create a new app or select existing one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the Client ID and Secret from your app')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
                      <span>{t('Select \"Sandbox\" mode for testing or \"Live\" mode for production')}</span>
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