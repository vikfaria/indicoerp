
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Send, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
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

interface TelegramSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function TelegramSettings({ userSettings = {}, auth }: TelegramSettingsProps) {
  const { t } = useTranslation();
  const activatedPackages = auth?.user?.activatedPackages || [];
  const [telegramNotifications, setTelegramNotifications] = useState<Record<string, any>>({});
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-telegram-settings');

  const [telegramSettings, setTelegramSettings] = useState({
    telegram_notification_is: userSettings?.telegram_notification_is === 'on',
    telegram_bot_token: userSettings?.telegram_bot_token || '',
    telegram_chat_id: userSettings?.telegram_chat_id || ''
  });

  const [notificationSettings, setNotificationSettings] = useState<Record<string, string>>({});

  useEffect(() => {
    setTelegramSettings({
      telegram_notification_is: userSettings?.telegram_notification_is === 'on',
      telegram_bot_token: userSettings?.telegram_bot_token || '',
      telegram_chat_id: userSettings?.telegram_chat_id || ''
    });

    fetch(route('telegram.settings.index'))
      .then(response => response.json())
      .then(data => {
        setTelegramNotifications(data.telegramNotifications || {});

        const initial: Record<string, string> = {};
        Object.values(data.telegramNotifications || {}).forEach((moduleNotifications: any) => {
          moduleNotifications.forEach((notification: Notification) => {
            const key = `Telegram ${notification.action}`;
            initial[key] = userSettings?.[key] || 'off';
          });
        });
        setNotificationSettings(initial);
      })
      .catch(error => console.error('Error fetching telegram notifications:', error));
  }, [userSettings]);

  const handleSettingsChange = (field: string, value: string | boolean) => {
    setTelegramSettings(prev => ({
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

  const saveTelegramSettings = () => {
    setIsLoading(true);

    router.post(route('telegram.settings.store'), {
      settings: {
        ...telegramSettings,
        ...notificationSettings,
        telegram_notification_is: telegramSettings.telegram_notification_is ? 'on' : 'off'
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
            <Send className="h-5 w-5" />
            {t('Telegram Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Telegram bot integration settings')}
          </p>
        </div>
        {canEdit && (
          <Button className="order-2 rtl:order-1" onClick={saveTelegramSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {/* Enable/Disable Telegram */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="telegram_notification_is" className="text-base font-medium">
                {t('Enable Telegram Integration')}
              </Label>
              <p className="text-sm text-muted-foreground mt-1">
                {t('Allow notifications to be sent to Telegram')}
              </p>
            </div>
            <Switch
              id="telegram_notification_is"
              checked={telegramSettings.telegram_notification_is}
              onCheckedChange={(checked) => handleSettingsChange('telegram_notification_is', checked)}
              disabled={!canEdit}
            />
          </div>

          {telegramSettings.telegram_notification_is && (
            <>
              <div className="space-y-3">
                <Label htmlFor="telegram_bot_token">{t('Telegram Bot Token')}</Label>
                <Input
                  id="telegram_bot_token"
                  value={telegramSettings.telegram_bot_token}
                  onChange={(e) => handleSettingsChange('telegram_bot_token', e.target.value)}
                  placeholder={t('Enter Telegram bot token')}
                  disabled={!canEdit}
                />
              </div>

              <div className="space-y-3">
                <Label htmlFor="telegram_chat_id">{t('Telegram Chat ID')}</Label>
                <Input
                  id="telegram_chat_id"
                  value={telegramSettings.telegram_chat_id}
                  onChange={(e) => handleSettingsChange('telegram_chat_id', e.target.value)}
                  placeholder={t('Enter Telegram chat ID')}
                  disabled={!canEdit}
                />
              </div>

              {Object.keys(telegramNotifications || {}).filter(module =>
                module.toLowerCase() === 'general' || activatedPackages.includes(module)
              ).length > 0 && (
                <div className="space-y-3">
                  <Label>{t('Notification Settings')}</Label>
                  <Tabs defaultValue={Object.keys(telegramNotifications || {})[0]}>
                    <TabsList className="flex-wrap h-auto">
                      {Object.keys(telegramNotifications || {}).filter(module =>
                        module.toLowerCase() === 'general' || activatedPackages.includes(module)
                      ).map((module) => (
                        <TabsTrigger key={module} value={module} className="capitalize">
                          {getPackageAlias(module)}
                        </TabsTrigger>
                      ))}
                    </TabsList>
                    {Object.keys(telegramNotifications || {}).filter(module =>
                      module.toLowerCase() === 'general' || activatedPackages.includes(module)
                    ).map((module) => (
                      <TabsContent key={module} value={module}>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          {(telegramNotifications[module] || []).map((notification: Notification) => (
                            <div key={notification.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                              <span className="text-sm font-medium">
                                {notification.action}
                              </span>
                              <Switch
                                checked={notificationSettings[`Telegram ${notification.action}`] === 'on'}
                                onCheckedChange={(checked) => handleNotificationToggle(`Telegram ${notification.action}`, checked)}
                                disabled={!canEdit}
                              />
                            </div>
                          ))}
                        </div>
                      </TabsContent>
                    ))}
                  </Tabs>
                </div>
              )}
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
