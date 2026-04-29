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

interface MolliePaymentSettings {
  mollie_api_key: string;
  mollie_profile_id: string;
  mollie_partner_id: string;
  mollie_enabled: string;
  [key: string]: any;
}

interface MolliePaymentSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function MolliePaymentSettings({ userSettings, auth }: MolliePaymentSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-mollie-settings');
  const [settings, setSettings] = useState<MolliePaymentSettings>({
    mollie_api_key: userSettings?.mollie_api_key || '',
    mollie_profile_id: userSettings?.mollie_profile_id || '',
    mollie_partner_id: userSettings?.mollie_partner_id || '',
    mollie_enabled: userSettings?.mollie_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        mollie_api_key: userSettings?.mollie_api_key || '',
        mollie_profile_id: userSettings?.mollie_profile_id || '',
        mollie_partner_id: userSettings?.mollie_partner_id || '',
        mollie_enabled: userSettings?.mollie_enabled || 'off',
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
      mollie_enabled: settings.mollie_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('mollie.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Mollie Payment settings');
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
            {t('Mollie Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Mollie gateway settings')}
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
          {/* Enable/Disable Mollie Payment */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="mollie_enabled" className="text-base font-medium">
                {t('Enable Mollie')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Mollie Payment gateway')}
              </p>
            </div>
            <Switch
              id="mollie_enabled"
              checked={settings.mollie_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('mollie_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.mollie_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Mollie API Key */}
                  <div className="space-y-3">
                    <Label htmlFor="mollie_api_key">{t('Mollie API Key')}</Label>
                    <div className="relative">
                      <Input
                        id="mollie_api_key"
                        name="mollie_api_key"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.mollie_api_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter Mollie API key')}
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
                      {t('Mollie API key for payment processing')}
                    </p>
                  </div>

                  {/* Mollie Profile ID */}
                  <div className="space-y-3">
                    <Label htmlFor="mollie_profile_id">{t('Mollie Profile ID')}</Label>
                    <Input
                      id="mollie_profile_id"
                      name="mollie_profile_id"
                      type="text"
                      value={is_demo ? '****************' : settings.mollie_profile_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter Mollie Profile ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Mollie Profile ID for payment configuration')}
                    </p>
                  </div>

                  {/* Mollie Partner ID */}
                  <div className="space-y-3">
                    <Label htmlFor="mollie_partner_id">{t('Mollie Partner ID')}</Label>
                    <Input
                      id="mollie_partner_id"
                      name="mollie_partner_id"
                      type="text"
                      value={is_demo ? '****************' : settings.mollie_partner_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter Mollie Partner ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Mollie Partner ID for integration')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Mollie API key')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://www.mollie.com/dashboard" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Mollie Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Mollie account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Developers → API keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the API key to the field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Use test keys for development and live keys for production')}</span>
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