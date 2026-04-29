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

interface AamarpayPaymentSettings {
  aamarpay_store_id: string;
  aamarpay_signature_key: string;
  aamarpay_enabled: string;
  aamarpay_mode: string;
  [key: string]: any;
}

interface AamarpayPaymentSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function AamarpaySettings({ userSettings, auth }: AamarpayPaymentSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSignatureKey, setShowSignatureKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-aamarpay-settings');
  const [settings, setSettings] = useState<AamarpayPaymentSettings>({
    aamarpay_store_id: userSettings?.aamarpay_store_id || '',
    aamarpay_signature_key: userSettings?.aamarpay_signature_key || '',
    aamarpay_enabled: userSettings?.aamarpay_enabled || 'off',
    aamarpay_mode: userSettings?.aamarpay_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        aamarpay_store_id: userSettings?.aamarpay_store_id || '',
        aamarpay_signature_key: userSettings?.aamarpay_signature_key || '',
        aamarpay_enabled: userSettings?.aamarpay_enabled || 'off',
        aamarpay_mode: userSettings?.aamarpay_mode || 'sandbox',
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
      aamarpay_enabled: settings.aamarpay_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('aamarpay.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Aamarpay settings');
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
            {t('Aamarpay Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Aamarpay payment gateway settings')}
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
          {/* Enable/Disable Aamarpay */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="aamarpay_enabled" className="text-base font-medium">
                {t('Enable Aamarpay')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Aamarpay payment gateway')}
              </p>
            </div>
            <Switch
              id="aamarpay_enabled"
              checked={settings.aamarpay_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('aamarpay_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.aamarpay_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Aamarpay Mode */}
                  <div className="space-y-3">
                    <Label>{t('Aamarpay Mode')}</Label>
                    <RadioGroup
                      value={settings.aamarpay_mode}
                      onValueChange={(value) => handleSelectChange('aamarpay_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="aamarpay-sandbox" />
                        <Label htmlFor="aamarpay-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="aamarpay-live" />
                        <Label htmlFor="aamarpay-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.aamarpay_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* Store ID */}
                  <div className="space-y-3">
                    <Label htmlFor="aamarpay_store_id">{t('Store ID')}</Label>
                    <Input
                      id="aamarpay_store_id"
                      name="aamarpay_store_id"
                      type="text"
                      value={is_demo ? '****************' : settings.aamarpay_store_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter Aamarpay store ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Your Aamarpay store ID')}
                    </p>
                  </div>

                  {/* Signature Key */}
                  <div className="space-y-3">
                    <Label htmlFor="aamarpay_signature_key">{t('Signature Key')}</Label>
                    <div className="relative">
                      <Input
                        id="aamarpay_signature_key"
                        name="aamarpay_signature_key"
                        type={showSignatureKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.aamarpay_signature_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Aamarpay signature key')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowSignatureKey(!showSignatureKey)}
                      >
                        {showSignatureKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Your Aamarpay signature key')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Aamarpay credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://merchant.aamarpay.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Aamarpay Merchant Panel')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Login with your merchant credentials')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to "Developer API" from the left menu')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy your Store ID and Signature Key from the API credentials section')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Use "Sandbox" mode for testing with test credentials or "Live" mode for production')}</span>
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