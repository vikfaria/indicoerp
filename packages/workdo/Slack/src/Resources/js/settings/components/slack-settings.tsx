import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slack, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getPackageAlias } from '@/utils/helpers';

interface Notification {
  id: number;
  module: string;
  type: string;
  action: string;
  status: string;
  permissions: string;
}


interface SlackSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function SlackSettings({ userSettings = {}, auth }: SlackSettingsProps) {
  const { t } = useTranslation();
  const activatedPackages = auth?.user?.activatedPackages || [];
  const [slackNotifications, setSlackNotifications] = useState<Record<string, any>>({});

  const [isLoading, setIsLoading] = useState(false);
  const canEdit =  auth?.user?.permissions?.includes('edit-slack-settings');

  const [slackSettings, setSlackSettings] = useState({
    slack_notification_is: userSettings?.slack_notification_is === 'on',
    slack_webhook_url: userSettings?.slack_webhook_url || ''
  });

  const [notificationSettings, setNotificationSettings] = useState<Record<string, string>>({});



  useEffect(() => {
    setSlackSettings({
      slack_notification_is: userSettings?.slack_notification_is === 'on',
      slack_webhook_url: userSettings?.slack_webhook_url || ''
    });

    fetch(route('slack.settings.index'))
      .then(response => response.json())
      .then(data => {
        setSlackNotifications(data.slackNotifications || {});

        const initial: Record<string, string> = {};
        Object.values(data.slackNotifications || {}).forEach((moduleNotifications: any) => {
          moduleNotifications.forEach((notification: Notification) => {
            const key = `Slack ${notification.action}`;
            initial[key] = userSettings?.[key] || 'off';
          });
        });
        setNotificationSettings(initial);
      })
      .catch(error => console.error('Error fetching slack notifications:', error));
  }, [userSettings]);

  const handleSettingsChange = (field: string, value: string | boolean) => {
    setSlackSettings(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleNotificationToggle = (key: string, checked: boolean) => {
    setNotificationSettings(prev => ({
      ...prev,
      [key]: checked ? 'on' : 'off'
    }));
  };



  const saveSlackSettings = () => {
    setIsLoading(true);

    router.post(route('slack.settings.store'), {
      settings: {
        ...slackSettings,
        ...notificationSettings,
        slack_notification_is: slackSettings.slack_notification_is ? 'on' : 'off'
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
            <Slack className="h-5 w-5" />
            {t('Slack Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Slack integration and webhook settings')}
          </p>
        </div>
        {canEdit && (
          <Button className="order-2 rtl:order-1" onClick={saveSlackSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {/* Enable/Disable Slack */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="slack_notification_is" className="text-base font-medium">
                {t('Enable Slack Integration')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Allow notifications to be sent to Slack')}
              </p>
            </div>
            <Switch
              id="slack_notification_is"
              checked={slackSettings.slack_notification_is}
              onCheckedChange={(checked) => handleSettingsChange('slack_notification_is', checked)}
              disabled={!canEdit}
            />
          </div>

          {slackSettings.slack_notification_is && (
            <>
              <div className="space-y-3">
                <Label htmlFor="slack_webhook_url">{t('Slack Webhook URL')}</Label>
                <Input
                  id="slack_webhook_url"
                  value={slackSettings.slack_webhook_url}
                  onChange={(e) => handleSettingsChange('slack_webhook_url', e.target.value)}
                  placeholder={t('Enter Slack webhook URL')}
                  disabled={!canEdit}
                />
              </div>

              {(() => {
                const filteredModules = Object.keys(slackNotifications || {}).filter(module =>
                  module.toLowerCase() === 'general' || activatedPackages.includes(module)
                );
                return filteredModules.length > 0 && (
                  <div className="space-y-3">
                    <Label>{t('Notification Settings')}</Label>
                    <Tabs defaultValue={filteredModules[0]}>
                      <TabsList className="flex-wrap h-auto">
                        {filteredModules.map((module) => (
                          <TabsTrigger key={module} value={module} className="capitalize">
                            {getPackageAlias(module)}
                          </TabsTrigger>
                        ))}
                      </TabsList>
                      {filteredModules.map((module) => (
                        <TabsContent key={module} value={module}>
                          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {(slackNotifications[module] || []).map((notification: Notification) => (
                              <div key={notification.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span className="text-sm font-medium">
                                  {notification.action}
                                </span>
                                <Switch
                                  checked={notificationSettings[`Slack ${notification.action}`] === 'on'}
                                  onCheckedChange={(checked) => handleNotificationToggle(`Slack ${notification.action}`, checked)}
                                  disabled={!canEdit}
                                />
                              </div>
                            ))}
                          </div>
                        </TabsContent>
                      ))}
                    </Tabs>
                  </div>
                );
              })()}
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
