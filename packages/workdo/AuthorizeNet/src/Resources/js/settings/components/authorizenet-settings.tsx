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

interface AuthorizeNetSettings {
  authorizenet_merchant_login_id: string;
  authorizenet_merchant_transaction_key: string;
  authorizenet_enabled: string;
  authorizenet_mode: string;
  [key: string]: any;
}

interface AuthorizeNetSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function AuthorizeNetSettings({ userSettings, auth }: AuthorizeNetSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-authorizenet-settings');
  const [settings, setSettings] = useState<AuthorizeNetSettings>({
    authorizenet_merchant_login_id: userSettings?.authorizenet_merchant_login_id || '',
    authorizenet_merchant_transaction_key: userSettings?.authorizenet_merchant_transaction_key || '',
    authorizenet_enabled: userSettings?.authorizenet_enabled || 'off',
    authorizenet_mode: userSettings?.authorizenet_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        authorizenet_merchant_login_id: userSettings?.authorizenet_merchant_login_id || '',
        authorizenet_merchant_transaction_key: userSettings?.authorizenet_merchant_transaction_key || '',
        authorizenet_enabled: userSettings?.authorizenet_enabled || 'off',
        authorizenet_mode: userSettings?.authorizenet_mode || 'sandbox',
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

  const handleSelectChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    const payload = {
      ...settings,
      authorizenet_enabled: settings.authorizenet_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('authorizenet.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save AuthorizeNet settings');
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
            {t('AuthorizeNet Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure AuthorizeNet payment gateway settings')}
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
          {/* Enable/Disable AuthorizeNet */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="authorizenet_enabled" className="text-base font-medium">
                {t('Enable AuthorizeNet')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable AuthorizeNet payment gateway')}
              </p>
            </div>
            <Switch
              id="authorizenet_enabled"
              checked={settings.authorizenet_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('authorizenet_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.authorizenet_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* AuthorizeNet Mode */}
                  <div className="space-y-3">
                    <Label>{t('AuthorizeNet Mode')}</Label>
                    <RadioGroup
                      value={settings.authorizenet_mode}
                      onValueChange={(value) => handleSelectChange('authorizenet_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="authorizenet-sandbox" />
                        <Label htmlFor="authorizenet-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="live" id="authorizenet-live" />
                        <Label htmlFor="authorizenet-live">{t('Live')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.authorizenet_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use live credentials for production transactions')
                      }
                    </p>
                  </div>

                  {/* Merchant Login ID */}
                  <div className="space-y-3">
                    <Label htmlFor="authorizenet_merchant_login_id">{t('Merchant Login ID')}</Label>
                    <Input
                      id="authorizenet_merchant_login_id"
                      name="authorizenet_merchant_login_id"
                      value={is_demo ? '****************' : settings.authorizenet_merchant_login_id}
                      onChange={handleInputChange}
                      placeholder={t('Enter AuthorizeNet Merchant Login ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('AuthorizeNet Merchant Login ID for API integration')}
                    </p>
                  </div>

                  {/* Merchant Transaction Key */}
                  <div className="space-y-3">
                    <Label htmlFor="authorizenet_merchant_transaction_key">{t('Merchant Transaction Key')}</Label>
                    <div className="relative">
                      <Input
                        id="authorizenet_merchant_transaction_key"
                        name="authorizenet_merchant_transaction_key"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.authorizenet_merchant_transaction_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter AuthorizeNet Merchant Transaction Key')}
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
                      {t('AuthorizeNet Merchant Transaction Key for secure API integration')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get AuthorizeNet API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://account.authorize.net/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('AuthorizeNet Account')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your AuthorizeNet merchant account')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Account → Settings → API Credentials & Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Create a new app or select existing one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the "API Login ID" to the Merchant Login ID field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
                      <span>{t('Copy the "Transaction Key" to the Merchant Transaction Key field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('7.')} </span>
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