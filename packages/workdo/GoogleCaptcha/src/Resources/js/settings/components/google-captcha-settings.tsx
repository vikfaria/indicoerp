import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Shield, Save, Eye, EyeOff } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';

interface GoogleCaptchaSettings {
  recaptcha_version: string;
  recaptcha_site_key: string;
  recaptcha_secret_key: string;
  recaptcha_enabled: string;
  [key: string]: any;
}

interface GoogleCaptchaSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function GoogleCaptchaSettings({ userSettings, auth }: GoogleCaptchaSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-google-captcha-settings');
  const [settings, setSettings] = useState<GoogleCaptchaSettings>({
    recaptcha_version: userSettings?.recaptcha_version || 'v2',
    recaptcha_site_key: userSettings?.recaptcha_site_key || '',
    recaptcha_secret_key: userSettings?.recaptcha_secret_key || '',
    recaptcha_enabled: userSettings?.recaptcha_enabled || 'off',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        recaptcha_version: userSettings?.recaptcha_version || 'v2',
        recaptcha_site_key: userSettings?.recaptcha_site_key || '',
        recaptcha_secret_key: userSettings?.recaptcha_secret_key || '',
        recaptcha_enabled: userSettings?.recaptcha_enabled || 'off',
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

  const handleSwitchChange = (name: string, checked: string) => {
    setSettings(prev => ({ ...prev, [name]: checked }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('google-captcha.settings.update'), {
      settings: settings
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
            <Shield className="h-5 w-5" />
            {t('Google reCAPTCHA Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Google reCAPTCHA for form protection')}
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
          {/* Enable/Disable reCAPTCHA */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="recaptcha_enabled" className="text-base font-medium">
                {t('Enable reCAPTCHA')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Enable Google reCAPTCHA protection for forms')}
              </p>
            </div>
            <Switch
              id="recaptcha_enabled"
              checked={settings.recaptcha_enabled === 'on'}
              onCheckedChange={(checked) => handleSwitchChange('recaptcha_enabled', checked ? 'on' : 'off')}
              disabled={!canEdit}
            />
          </div>

          {settings.recaptcha_enabled === 'on' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Left Side - Form Fields */}
              <div className="lg:col-span-2 space-y-6">
                {/* reCAPTCHA Version */}
                <div className="space-y-3">
                  <Label htmlFor="recaptcha_version">{t('reCAPTCHA Version')}</Label>
                  <Select
                    value={settings.recaptcha_version}
                    onValueChange={(value) => handleSelectChange('recaptcha_version', value)}
                    disabled={!canEdit}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('Select reCAPTCHA version')} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="v2">{t('reCAPTCHA v2')}</SelectItem>
                      <SelectItem value="v3">{t('reCAPTCHA v3')}</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    {t('Choose between reCAPTCHA v2 (checkbox) or v3 (invisible)')}
                  </p>
                </div>

                {/* Site Key and Secret Key */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-3">
                    <Label htmlFor="recaptcha_site_key">{t('Site Key')}</Label>
                    <Input
                      id="recaptcha_site_key"
                      name="recaptcha_site_key"
                      value={settings.recaptcha_site_key}
                      onChange={handleInputChange}
                      placeholder={t('Enter reCAPTCHA Site Key')}
                      disabled={!canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Public site key from Google reCAPTCHA console')}
                    </p>
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="recaptcha_secret_key">{t('Secret Key')}</Label>
                    <div className="relative">
                      <Input
                        id="recaptcha_secret_key"
                        name="recaptcha_secret_key"
                        type={showSecret ? 'text' : 'password'}
                        value={settings.recaptcha_secret_key}
                        onChange={handleInputChange}
                        placeholder={t('Enter reCAPTCHA Secret Key')}
                        disabled={!canEdit}
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
                      {t('Secret key from Google reCAPTCHA console')}
                    </p>
                  </div>
                </div>
              </div>

              {/* Right Side - Setup Guide */}
              <div className="lg:col-span-1">
                <div className="p-4 bg-muted/30 rounded-lg border">
                  <h4 className="font-medium mb-3">{t('How to get Google reCAPTCHA keys')}</h4>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">1.</span>
                      <span>{t('Go to')} <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer" className="underline hover:no-underline text-primary">Google reCAPTCHA Admin Console</a></span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">2.</span>
                      <span>{t('Click "+" to create a new site')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">3.</span>
                      <span>{t('Enter a label and select reCAPTCHA type')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">4.</span>
                      <span>{t('Add your domain to the domains list')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">5.</span>
                      <span>{t('Accept the terms and submit')}</span>
                    </div>
                    <div className="flex items-start gap-2">
                      <span className="font-medium min-w-[20px]">6.</span>
                      <span>{t('Copy the Site Key and Secret Key to the fields above')}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}