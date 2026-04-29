import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Video, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';

interface ZoomMeetingSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function ZoomMeetingSettings({ userSettings = {}, auth }: ZoomMeetingSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-zoom-meeting-settings');
  
  const [settings, setSettings] = useState({
    zoom_enabled: userSettings?.zoom_enabled === 'on',
    zoom_api_key: userSettings?.zoom_api_key || '',
    zoom_api_secret: userSettings?.zoom_api_secret || '',
    zoom_account_id: userSettings?.zoom_account_id || ''
  });

  useEffect(() => {
    setSettings({
      zoom_enabled: userSettings?.zoom_enabled === 'on',
      zoom_api_key: userSettings?.zoom_api_key || '',
      zoom_api_secret: userSettings?.zoom_api_secret || '',
      zoom_account_id: userSettings?.zoom_account_id || ''
    });
  }, [userSettings]);

  const handleSettingsChange = (field: string, value: string | boolean) => {
    setSettings(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('zoom-meeting.settings.update'), {
      settings: {
        ...settings,
        zoom_enabled: settings.zoom_enabled ? 'on' : 'off'
      }
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
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
            <Video className="h-5 w-5" />
            {t('Zoom Meeting Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Zoom Meeting integration and API settings')}
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
          {/* Enable/Disable Zoom */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="zoom_enabled" className="text-base font-medium">
                {t('Enable Zoom Integration')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Allow meetings to be created via Zoom')}
              </p>
            </div>
            <Switch
              id="zoom_enabled"
              checked={settings.zoom_enabled}
              onCheckedChange={(checked) => handleSettingsChange('zoom_enabled', checked)}
              disabled={!canEdit}
            />
          </div>

          {settings.zoom_enabled && (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="space-y-6">
                  <div className="space-y-3">
                    <Label htmlFor="zoom_api_key">{t('Client ID')}</Label>
                    <Input
                      id="zoom_api_key"
                      value={settings.zoom_api_key}
                      onChange={(e) => handleSettingsChange('zoom_api_key', e.target.value)}
                      placeholder={t('Enter Zoom Client ID')}
                      disabled={!canEdit}
                      type="password"
                    />
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="zoom_api_secret">{t('Client Secret')}</Label>
                    <Input
                      id="zoom_api_secret"
                      value={settings.zoom_api_secret}
                      onChange={(e) => handleSettingsChange('zoom_api_secret', e.target.value)}
                      placeholder={t('Enter Zoom Client Secret')}
                      disabled={!canEdit}
                      type="password"
                    />
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="zoom_account_id">{t('Account ID')}</Label>
                    <Input
                      id="zoom_account_id"
                      value={settings.zoom_account_id}
                      onChange={(e) => handleSettingsChange('zoom_account_id', e.target.value)}
                      placeholder={t('Enter Zoom Account ID')}
                      disabled={!canEdit}
                    />
                  </div>
                </div>

                <div className="border rounded-lg p-4 bg-blue-50/50 border-blue-200">
                  <h4 className="font-medium mb-2 text-blue-900">{t('Setup Instructions')}</h4>
                  <div className="space-y-2 text-sm text-blue-800">
                    <p>{t('1. Go to')} <a href="https://marketplace.zoom.us/develop/create" target="_blank" rel="noopener noreferrer" className="text-blue-600 underline hover:text-blue-800">{t('Zoom Marketplace')}</a> {t('and create a Server-to-Server OAuth app')}</p>
                    <p>{t('2. In App Credentials, copy the Account ID, Client ID, and Client Secret')}</p>
                    <p>{t('3. Add scopes: meeting:write, meeting:read, user:read')}</p>
                    <p>{t('4. Paste the credentials above and enable the integration')}</p>
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