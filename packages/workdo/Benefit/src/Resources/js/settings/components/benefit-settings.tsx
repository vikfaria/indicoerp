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

interface BenefitSettings {
  benefit_api_key: string;
  benefit_secret_key: string;
  benefit_processing_channel_id: string;
  benefit_enabled: string;
  [key: string]: any;
}

interface BenefitSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function BenefitSettings({ userSettings, auth }: BenefitSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecretKey, setShowSecretKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-benefit-settings');
  
  const [settings, setSettings] = useState<BenefitSettings>({
    benefit_api_key: userSettings?.benefit_api_key || '',
    benefit_secret_key: userSettings?.benefit_secret_key || '',
    benefit_processing_channel_id: userSettings?.benefit_processing_channel_id || '',
    benefit_enabled: userSettings?.benefit_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        benefit_api_key: userSettings?.benefit_api_key || '',
        benefit_secret_key: userSettings?.benefit_secret_key || '',
        benefit_processing_channel_id: userSettings?.benefit_processing_channel_id || '',
        benefit_enabled: userSettings?.benefit_enabled || 'off',
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
      benefit_enabled: settings.benefit_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('benefit.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save benefit settings');
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
            {t('Benefit Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Benefit payment gateway settings')}
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
          {/* Enable/Disable Benefit */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="benefit_enabled" className="text-base font-medium">
                {t('Enable Benefit')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Benefit payment gateway')}
              </p>
            </div>
            <Switch
              id="benefit_enabled"
              checked={settings.benefit_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('benefit_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.benefit_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* API Key */}
                  <div className="space-y-3">
                    <Label htmlFor="benefit_api_key">{t('API Key')}</Label>
                    <Input
                      id="benefit_api_key"
                      name="benefit_api_key"
                      value={is_demo ? '****************' : settings.benefit_api_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter Benefit API Key')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Benefit API key for payment processing')}
                    </p>
                  </div>

                  {/* Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="benefit_secret_key">{t('Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="benefit_secret_key"
                        name="benefit_secret_key"
                        type={showSecretKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.benefit_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Benefit Secret Key')}
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
                      {t('Benefit secret key for secure payment processing')}
                    </p>
                  </div>

                  {/* Processing Channel ID */}
                  <div className="space-y-3">
                    <Label htmlFor="benefit_processing_channel_id">{t('Channel ID')}</Label>
                    <Input
                      id="benefit_processing_channel_id"
                      name="benefit_processing_channel_id"
                      value={is_demo ? '****************' : settings.benefit_processing_channel_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter Processing Channel ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Processing channel ID from your Checkout.com dashboard')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Benefit API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://www.checkout.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Benefit')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Checkout.com account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to API Keys section in your dashboard')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the Public Key and Secret Key')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Get Processing Channel ID from Channels section')}</span>
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