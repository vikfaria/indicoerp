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

interface StripeSettings {
  stripe_key: string;
  stripe_secret: string;
  stripe_enabled: string;
  [key: string]: any;
}

interface StripeSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function StripeSettings({ userSettings, auth }: StripeSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-stripe-settings');
  const [settings, setSettings] = useState<StripeSettings>({
    stripe_key: userSettings?.stripe_key || '',
    stripe_secret: userSettings?.stripe_secret || '',
    stripe_enabled: userSettings?.stripe_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        stripe_key: userSettings?.stripe_key || '',
        stripe_secret: userSettings?.stripe_secret || '',
        stripe_enabled: userSettings?.stripe_enabled || 'off',
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
      stripe_enabled: settings.stripe_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('stripe.settings.update'), {
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
            {t('Stripe Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Stripe payment gateway settings')}
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
          {/* Enable/Disable Stripe */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="stripe_enabled" className="text-base font-medium">
                {t('Enable Stripe')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Stripe payment gateway')}
              </p>
            </div>
            <Switch
              id="stripe_enabled"
              checked={settings.stripe_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('stripe_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.stripe_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Stripe Key */}
                  <div className="space-y-3">
                    <Label htmlFor="stripe_key">{t('Stripe Key')}</Label>
                    <Input
                      id="stripe_key"
                      name="stripe_key"
                      value={is_demo ? '****************' : settings.stripe_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter Stripe key')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Stripe key for client-side integration')}
                    </p>
                  </div>

                  {/* Stripe Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="stripe_secret">{t('Stripe Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="stripe_secret"
                        name="stripe_secret"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.stripe_secret}
                        onChange={handleInputChange}
                        placeholder={t('Enter Stripe secret key')}
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
                      {t('Stripe secret key for server-side integration')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Stripe API keys')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://dashboard.stripe.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">Stripe Dashboard</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Stripe account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Developers → API keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the "Publishable key" to the first field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Reveal and copy the "Secret key" to the second field above')}</span>
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