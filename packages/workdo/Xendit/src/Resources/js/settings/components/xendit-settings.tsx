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

interface XenditSettings {
  xendit_key: string;
  xendit_token: string;
  xendit_enabled: string;
  [key: string]: any;
}

interface XenditSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function XenditSettings({ userSettings, auth }: XenditSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showToken, setShowToken] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-xendit-settings');
  const [settings, setSettings] = useState<XenditSettings>({
    xendit_key: userSettings?.xendit_key || '',
    xendit_token: userSettings?.xendit_token || '',
    xendit_enabled: userSettings?.xendit_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        xendit_key: userSettings?.xendit_key || '',
        xendit_token: userSettings?.xendit_token || '',
        xendit_enabled: userSettings?.xendit_enabled || 'off',
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
      xendit_enabled: settings.xendit_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('xendit.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save xendit settings');
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
            {t('Xendit Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Xendit payment gateway settings')}
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
          {/* Enable/Disable Xendit */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="xendit_enabled" className="text-base font-medium">
                {t('Enable Xendit')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Xendit payment gateway')}
              </p>
            </div>
            <Switch
              id="xendit_enabled"
              checked={settings.xendit_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('xendit_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.xendit_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Xendit Key */}
                  <div className="space-y-3">
                    <Label htmlFor="xendit_key">{t('Xendit Key')}</Label>
                    <Input
                      id="xendit_key"
                      name="xendit_key"
                      value={is_demo ? '****************' : settings.xendit_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter Xendit key')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Xendit public key for client-side integration')}
                    </p>
                  </div>

                  {/* Xendit Token */}
                  <div className="space-y-3">
                    <Label htmlFor="xendit_token">{t('Xendit Token')}</Label>
                    <div className="relative">
                      <Input
                        id="xendit_token"
                        name="xendit_token"
                        type={showToken ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.xendit_token}
                        onChange={handleInputChange}
                        placeholder={t('Enter Xendit token')}
                        disabled={is_demo || !canEdit}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowToken(!showToken)}
                      >
                        {showToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('Xendit secret token for server-side integration')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Xendit API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://dashboard.xendit.co/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Xendit Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Xendit account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Settings → API Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the "Public Key" to the first field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the "Secret Key" to the second field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
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