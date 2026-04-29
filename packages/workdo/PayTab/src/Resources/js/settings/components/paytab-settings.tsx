import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { CreditCard, Save, Eye, EyeOff } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';

interface PayTabSettings {
  paytab_profile_id: string;
  paytab_server_key: string;
  paytab_region: string;
  paytab_payment_is_on: string;
  [key: string]: any;
}

interface PayTabSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function PayTabSettings({ userSettings, auth }: PayTabSettingsProps) {
  const { t } = useTranslation();
  const { is_demo } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const [showServerKey, setShowServerKey] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-paytab-settings');
  const [settings, setSettings] = useState<PayTabSettings>({
    paytab_profile_id: userSettings?.paytab_profile_id || '',
    paytab_server_key: userSettings?.paytab_server_key || '',
    paytab_region: userSettings?.paytab_region || 'GLOBAL',
    paytab_payment_is_on: userSettings?.paytab_payment_is_on || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        paytab_profile_id: userSettings?.paytab_profile_id || '',
        paytab_server_key: userSettings?.paytab_server_key || '',
        paytab_region: userSettings?.paytab_region || 'GLOBAL',
        paytab_payment_is_on: userSettings?.paytab_payment_is_on || 'off',
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
      paytab_payment_is_on: settings.paytab_payment_is_on === 'on' ? 'on' : 'off'
    };

    router.post(route('paytab.settings.update'), {
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
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save PayTab settings');
        toast.error(errorMessage);
      }
    });
  };

  const regionOptions = [
    { value: 'GLOBAL', label: t('GLOBAL - Global') },
    { value: 'EGY', label: t('EGY - Egypt') },
    { value: 'JOR', label: t('JOR - Jordan') },
    { value: 'OMN', label: t('OMN - Kuwait') },
    { value: 'SAU', label: t('SAU - Saudi Arabia') },
    { value: 'ARE', label: t('ARE - United Arab Emirates') },
  ];

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <CreditCard className="h-5 w-5" />
            {t('PayTab Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure PayTab payment gateway settings')}
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
          {/* Enable/Disable PayTab */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="paytab_payment_is_on" className="text-base font-medium">
                {t('Enable PayTab')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable or disable PayTab payment gateway')}
              </p>
            </div>
            <Switch
              id="paytab_payment_is_on"
              checked={settings.paytab_payment_is_on === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('paytab_payment_is_on', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.paytab_payment_is_on === 'on' && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Side - Form Fields */}
                <div className="lg:col-span-2 space-y-6">
                  {/* PayTab Profile ID */}
                  <div className="space-y-3">
                    <Label htmlFor="paytab_profile_id">{t('PayTab Profile Id')}</Label>
                    <Input
                      id="paytab_profile_id"
                      name="paytab_profile_id"
                      value={is_demo ? '****************' : settings.paytab_profile_id}
                      onChange={handleInputChange}
                      placeholder={t('PayTab Profile Id')}
                      disabled={is_demo || !canEdit || settings.paytab_payment_is_on === 'off'}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('PayTab profile ID for merchant identification')}
                    </p>
                  </div>

                  {/* PayTab Server Key */}
                  <div className="space-y-3">
                    <Label htmlFor="paytab_server_key">{t('PayTab Server Key')}</Label>
                    <div className="relative">
                      <Input
                        id="paytab_server_key"
                        name="paytab_server_key"
                        type={showServerKey ? 'text' : 'password'}
                        value={is_demo ? '****************' : settings.paytab_server_key}
                        onChange={handleInputChange}
                        placeholder={t('PayTab Server Key')}
                        disabled={is_demo || !canEdit || settings.paytab_payment_is_on === 'off'}
                        className="pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowServerKey(!showServerKey)}
                      >
                        {showServerKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t('PayTab server key for API authentication')}
                    </p>
                  </div>

                  {/* PayTab Region */}
                  <div className="space-y-3">
                    <Label htmlFor="paytab_region">{t('PayTab Region')}</Label>
                    <Select
                      value={settings.paytab_region}
                      onValueChange={(value) => handleSelectChange('paytab_region', value)}
                      disabled={!canEdit || settings.paytab_payment_is_on === 'off'}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder={t('Select PayTab Region')} />
                      </SelectTrigger>
                      <SelectContent>
                        {regionOptions.map((option) => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <p className="text-xs text-muted-foreground">
                      {t('Select the PayTab region for your merchant account')}
                    </p>
                  </div>
                </div>

                {/* Right Side - Guide */}
                <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
                  <h4 className="font-medium mb-3 text-blue-900 dark:text-blue-100">
                    {t('How to get PayTab credentials')}
                  </h4>
                  <div className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('1.')} </span>
                      <span>{t('Go to')} <a href="https://www.paytabs.com/" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline">{t('PayTabs Dashboard')}</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('2.')} </span>
                      <span>{t('Sign in to your PayTabs merchant account')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('3.')} </span>
                      <span>{t('Navigate to Developers → API Keys')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('4.')} </span>
                      <span>{t('Copy the \"Profile ID\" to the first field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('5.')} </span>
                      <span>{t('Copy the \"Server Key\" to the second field above')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">{t('6.')} </span>
                      <span>{t('Select your merchant region from the dropdown')}</span>
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