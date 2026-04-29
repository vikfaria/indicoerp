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

interface FedapayPaymentSettings {
  fedapay_public_key: string;
  fedapay_secret_key: string;
  fedapay_enabled: string;
  fedapay_mode: string;
  [key: string]: any;
}

interface FedapayPaymentSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function FedapayPaymentSettings({ userSettings, auth }: FedapayPaymentSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showPublicKey, setShowPublicKey] = useState(false);
  const [showSecretKey, setShowSecretKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-fedapay-settings');
  const [settings, setSettings] = useState<FedapayPaymentSettings>({
    fedapay_public_key: userSettings?.fedapay_public_key || '',
    fedapay_secret_key: userSettings?.fedapay_secret_key || '',
    fedapay_enabled: userSettings?.fedapay_enabled || 'off',
    fedapay_mode: userSettings?.fedapay_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        fedapay_public_key: userSettings?.fedapay_public_key || '',
        fedapay_secret_key: userSettings?.fedapay_secret_key || '',
        fedapay_enabled: userSettings?.fedapay_enabled || 'off',
        fedapay_mode: userSettings?.fedapay_mode || 'sandbox',
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
      fedapay_enabled: settings.fedapay_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('fedapay.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save FedaPay Payment settings');
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
            {t('FedaPay Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure FedaPay payment gateway settings')}
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
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="fedapay_enabled" className="text-base font-medium">
                {t('Enable FedaPay')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable FedaPay payment gateway')}
              </p>
            </div>
            <Switch
              id="fedapay_enabled"
              checked={settings.fedapay_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('fedapay_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.fedapay_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-6">
                  <div className="space-y-3">
                    <Label>{t('FedaPay Mode')}</Label>
                    <RadioGroup
                      value={settings.fedapay_mode}
                      onValueChange={(value) => handleSelectChange('fedapay_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="fedapay-sandbox" />
                        <Label htmlFor="fedapay-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="fedapay-live" />
                        <Label htmlFor="fedapay-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.fedapay_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="fedapay_public_key">{t('Public Key')}</Label>
                    <div className="relative">
                      <Input
                        id="fedapay_public_key"
                        name="fedapay_public_key"
                        type={showPublicKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.fedapay_public_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter FedaPay public key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowPublicKey(!showPublicKey)}
                      >
                        {showPublicKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Your FedaPay public key')}
                    </p>
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="fedapay_secret_key">{t('Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="fedapay_secret_key"
                        name="fedapay_secret_key"
                        type={showSecretKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.fedapay_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter FedaPay secret key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowSecretKey(!showSecretKey)}
                      >
                        {showSecretKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Your FedaPay secret key')}
                    </p>
                  </div>
                </div>

                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get FedaPay credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://fedapay.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('FedaPay Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your FedaPay account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Settings → API Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy both the Public Key and Secret Key')}</span>
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
