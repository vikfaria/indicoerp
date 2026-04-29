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

interface YooKassaSettings {
  yookassa_shop_id: string;
  yookassa_secret_key: string;
  yookassa_enabled: string;
  [key: string]: any;
}

interface YooKassaSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function YooKassaSettings({ userSettings, auth }: YooKassaSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecretKey, setShowSecretKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-yookassa-settings');
  
  const [settings, setSettings] = useState<YooKassaSettings>({
    yookassa_shop_id: userSettings?.yookassa_shop_id || '',
    yookassa_secret_key: userSettings?.yookassa_secret_key || '',
    yookassa_enabled: userSettings?.yookassa_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        yookassa_shop_id: userSettings?.yookassa_shop_id || '',
        yookassa_secret_key: userSettings?.yookassa_secret_key || '',
        yookassa_enabled: userSettings?.yookassa_enabled || 'off',
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
      yookassa_enabled: settings.yookassa_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('yookassa.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save yookassa settings');
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
            {t('YooKassa Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure YooKassa payment gateway settings')}
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
          {/* Enable/Disable YooKassa */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="yookassa_enabled" className="text-base font-medium">
                {t('Enable YooKassa')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable YooKassa payment gateway')}
              </p>
            </div>
            <Switch
              id="yookassa_enabled"
              checked={settings.yookassa_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('yookassa_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.yookassa_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Shop ID */}
                  <div className="space-y-3">
                    <Label htmlFor="yookassa_shop_id">{t('Shop ID')}</Label>
                    <Input
                      id="yookassa_shop_id"
                      name="yookassa_shop_id"
                      value={is_demo ? '****************' : settings.yookassa_shop_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter YooKassa Shop ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('YooKassa shop ID for payment processing')}
                    </p>
                  </div>

                  {/* Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="yookassa_secret_key">{t('Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="yookassa_secret_key"
                        name="yookassa_secret_key"
                        type={showSecretKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.yookassa_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter YooKassa Secret Key')}
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
                      {t('YooKassa secret key for API authentication')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get YooKassa API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://yookassa.ru/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('YooKassa Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your YooKassa account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Go to Settings > API Keys in your dashboard')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the Shop ID from your account settings')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Generate and copy the Secret Key')}</span>
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