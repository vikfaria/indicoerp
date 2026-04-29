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

interface PayfastPaymentSettings {
  payfast_merchant_id: string;
  payfast_merchant_key: string;
  payfast_salt_passphrase: string;
  payfast_enabled: string;
  payfast_mode: string;
  [key: string]: any;
}

interface PayfastPaymentSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function PayfastPaymentSettings({ userSettings, auth }: PayfastPaymentSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showMerchantKey, setShowMerchantKey] = useState(false);
  const [showPassphrase, setShowPassphrase] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-payfast-settings');
  const [settings, setSettings] = useState<PayfastPaymentSettings>({
    payfast_merchant_id: userSettings?.payfast_merchant_id || '',
    payfast_merchant_key: userSettings?.payfast_merchant_key || '',
    payfast_salt_passphrase: userSettings?.payfast_salt_passphrase || '',
    payfast_enabled: userSettings?.payfast_enabled || 'off',
    payfast_mode: userSettings?.payfast_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        payfast_merchant_id: userSettings?.payfast_merchant_id || '',
        payfast_merchant_key: userSettings?.payfast_merchant_key || '',
        payfast_salt_passphrase: userSettings?.payfast_salt_passphrase || '',
        payfast_enabled: userSettings?.payfast_enabled || 'off',
        payfast_mode: userSettings?.payfast_mode || 'sandbox',
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
      payfast_enabled: settings.payfast_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('payfast.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Payfast Payment settings');
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
            {t('Payfast Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Payfast payment gateway settings')}
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
          {/* Enable/Disable Payfast */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="payfast_enabled" className="text-base font-medium">
                {t('Enable Payfast')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Payfast payment gateway')}
              </p>
            </div>
            <Switch
              id="payfast_enabled"
              checked={settings.payfast_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('payfast_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.payfast_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Payfast Mode */}
                  <div className="space-y-3">
                    <Label>{t('Payfast Mode')}</Label>
                    <RadioGroup
                      value={settings.payfast_mode}
                      onValueChange={(value) => handleSelectChange('payfast_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="payfast-sandbox" />
                        <Label htmlFor="payfast-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="payfast-live" />
                        <Label htmlFor="payfast-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.payfast_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* Merchant ID */}
                  <div className="space-y-3">
                    <Label htmlFor="payfast_merchant_id">{t('Merchant ID')}</Label>
                    <Input
                      id="payfast_merchant_id"
                      name="payfast_merchant_id"
                      type="text"
                      value={is_demo ? '****************' : settings.payfast_merchant_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter Payfast merchant ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Your Payfast merchant ID')}
                    </p>
                  </div>

                  {/* Merchant Key */}
                  <div className="space-y-3">
                    <Label htmlFor="payfast_merchant_key">{t('Merchant Key')}</Label>
                    <div className="relative">
                      <Input
                        id="payfast_merchant_key"
                        name="payfast_merchant_key"
                        type={showMerchantKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.payfast_merchant_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Payfast merchant key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowMerchantKey(!showMerchantKey)}
                      >
                        {showMerchantKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Your Payfast merchant key')}
                    </p>
                  </div>

                  {/* Salt Passphrase */}
                  <div className="space-y-3">
                    <Label htmlFor="payfast_salt_passphrase">{t('Salt Passphrase')}</Label>
                    <div className="relative">
                      <Input
                        id="payfast_salt_passphrase"
                        name="payfast_salt_passphrase"
                        type={showPassphrase ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.payfast_salt_passphrase}
                        onChange={handleInputChange}
                        placeholder={t('Enter Payfast salt passphrase')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowPassphrase(!showPassphrase)}
                      >
                        {showPassphrase ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Payfast salt passphrase for additional security')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Payfast credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://www.payfast.co.za/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Payfast Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Payfast account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Settings → Integration')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the Merchant ID and Merchant Key')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Set up a salt passphrase for additional security')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
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