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

interface CashfreeSettings {
  cashfree_key: string;
  cashfree_secret: string;
  cashfree_enabled: string;
  cashfree_mode: string;
  [key: string]: any;
}

interface CashfreeSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function CashfreeSettings({ userSettings, auth }: CashfreeSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-cashfree-settings');
  const [settings, setSettings] = useState<CashfreeSettings>({
    cashfree_key: userSettings?.cashfree_key || '',
    cashfree_secret: userSettings?.cashfree_secret || '',
    cashfree_enabled: userSettings?.cashfree_enabled || 'off',
    cashfree_mode: userSettings?.cashfree_mode || 'sandbox',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        cashfree_key: userSettings?.cashfree_key || '',
        cashfree_secret: userSettings?.cashfree_secret || '',
        cashfree_enabled: userSettings?.cashfree_enabled || 'off',
        cashfree_mode: userSettings?.cashfree_mode || 'sandbox',
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
      cashfree_enabled: settings.cashfree_enabled === 'on' ? 'on' : 'off'
    };

    router.post(route('cashfree.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Cashfree settings');
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
            {t('Cashfree Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Cashfree payment gateway settings')}
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
          {/* Enable/Disable Cashfree */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="cashfree_enabled" className="text-base font-medium">
                {t('Enable Cashfree')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable Cashfree payment gateway')}
              </p>
            </div>
            <Switch
              id="cashfree_enabled"
              checked={settings.cashfree_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('cashfree_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.cashfree_enabled === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Cashfree Mode */}
                  <div className="space-y-3">
                    <Label>{t('Cashfree Mode')}</Label>
                    <RadioGroup
                      value={settings.cashfree_mode}
                      onValueChange={(value) => handleSelectChange('cashfree_mode', value)}
                      disabled={!canEdit}
                      className="flex gap-6"
                    >
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="sandbox" id="cashfree-sandbox" />
                        <Label htmlFor="cashfree-sandbox">{t('Sandbox')}</Label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value="production" id="cashfree-production" />
                        <Label htmlFor="cashfree-production">{t('Production')}</Label>
                      </div>
                    </RadioGroup>
                    <p className="text-xs text-muted-foreground">
                      {settings.cashfree_mode === 'sandbox'
                        ? t('Use sandbox credentials for development and testing')
                        : t('Use production credentials for live transactions')
                      }
                    </p>
                  </div>

                  {/* Cashfree App ID */}
                  <div className="space-y-3">
                    <Label htmlFor="cashfree_key">{t('Cashfree App ID')}</Label>
                    <Input
                      id="cashfree_key"
                      name="cashfree_key"
                      value={is_demo ? '****************' : settings.cashfree_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter Cashfree App ID')}
                      disabled={is_demo || !canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Cashfree App ID for API integration')}
                    </p>
                  </div>

                  {/* Cashfree Secret Key */}
                  <div className="space-y-3">
                    <Label htmlFor="cashfree_secret">{t('Cashfree Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="cashfree_secret"
                        name="cashfree_secret"
                        type={showSecret ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.cashfree_secret}
                        onChange={handleInputChange}
                        placeholder={t('Enter Cashfree secret key')}
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
                      {t('Cashfree secret key for secure API communication')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get Cashfree API credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://merchant.cashfree.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('Cashfree Merchant')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your Cashfree account or create a new one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Developers → API Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Create a new app or select existing one')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the App ID and Secret Key from your app')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
                      <span>{t('Select "Sandbox" mode for testing or "Production" mode for live transactions')}</span>
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