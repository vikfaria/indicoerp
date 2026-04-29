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

interface MidtransSettings {
  midtrans_mode: string;
  midtrans_secret_key: string;
  midtrans_enabled: string;
  [key: string]: any;
}

interface MidtransSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function MidtransSettings({ userSettings, auth }: MidtransSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecretKey, setShowSecretKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-midtrans-settings');
  
  const [settings, setSettings] = useState<MidtransSettings>({
    midtrans_mode: userSettings?.midtrans_mode || 'sandbox',
    midtrans_secret_key: userSettings?.midtrans_secret_key || '',
    midtrans_enabled: userSettings?.midtrans_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        midtrans_mode: userSettings?.midtrans_mode || 'sandbox',
        midtrans_secret_key: userSettings?.midtrans_secret_key || '',
        midtrans_enabled: userSettings?.midtrans_enabled || 'off',
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
      midtrans_enabled: settings.midtrans_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('midtrans.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save midtrans settings');
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
            {t('Midtrans Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Midtrans payment gateway settings')}
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
          {/* Enable/Disable Midtrans */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="midtrans_enabled" className="text-base font-medium">
                {t('Enable Midtrans')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Midtrans payment gateway')}
              </p>
            </div>
            <Switch
              id="midtrans_enabled"
              checked={settings.midtrans_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('midtrans_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.midtrans_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Midtrans Environment */}
                  <div className="space-y-3">
                    <Label>{t('Midtrans Environment')}</Label>
                    <RadioGroup
                      value={settings.midtrans_mode}
                      onValueChange={(value) => handleSelectChange('midtrans_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="midtrans-sandbox" />
                        <Label htmlFor="midtrans-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="midtrans-live" />
                        <Label htmlFor="midtrans-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.midtrans_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="midtrans_secret_key">{t('Midtrans Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="midtrans_secret_key"
                        name="midtrans_secret_key"
                        type={showSecretKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.midtrans_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Midtrans secret key')}
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
                      {t('Midtrans secret key for server-side integration')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Midtrans API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://dashboard.midtrans.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Midtrans Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Midtrans account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Go to Settings > Access Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the Server Key (Secret Key)')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Select "Sandbox" mode for testing or "Live" mode for production transactions')}</span>
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