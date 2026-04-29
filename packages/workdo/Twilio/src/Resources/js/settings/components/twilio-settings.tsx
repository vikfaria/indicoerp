import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { Phone, Save, Eye, EyeOff } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getPackageAlias } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';

interface Notification {
    id: number;
    module: string;
    type: string;
    action: string;
    status: string;
    permissions: string;
}

interface TwilioSettingsProps {
    userSettings?: Record<string, string>;
    auth?: any;
}

export default function TwilioSettings({ userSettings = {}, auth }: TwilioSettingsProps) {
    const { t } = useTranslation();
    const activatedPackages = auth?.user?.activatedPackages || [];
    const [twilioNotifications, setTwilioNotifications] = useState<Record<string, any>>({});
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('edit-twilio-settings');

    const [twilioSettings, setTwilioSettings] = useState({
        twilio_notification_is: userSettings?.twilio_notification_is === 'on',
        twilio_sid: userSettings?.twilio_sid || '',
        twilio_token: userSettings?.twilio_token || '',
        twilio_from: userSettings?.twilio_from || ''
    });

    const [showToken, setShowToken] = useState(false);
    const [notificationSettings, setNotificationSettings] = useState<Record<string, string>>({});

    useEffect(() => {
        setTwilioSettings({
            twilio_notification_is: userSettings?.twilio_notification_is === 'on',
            twilio_sid: userSettings?.twilio_sid || '',
            twilio_token: userSettings?.twilio_token || '',
            twilio_from: userSettings?.twilio_from || ''
        });

        fetch(route('twilio.settings.index'))
            .then(response => response.json())
            .then(data => {
                setTwilioNotifications(data.twilioNotifications || {});

                const initial: Record<string, string> = {};
                Object.values(data.twilioNotifications || {}).forEach((moduleNotifications: any) => {
                    moduleNotifications.forEach((notification: Notification) => {
                        const key = `Twilio ${notification.action}`;
                        initial[key] = userSettings?.[key] || 'off';
                    });
                });
                setNotificationSettings(initial);
            })
            .catch(error => console.error('Error fetching twilio notifications:', error));
    }, [userSettings]);

    const handleSettingsChange = (field: string, value: string | boolean) => {
        setTwilioSettings(prev => ({
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

    const saveTwilioSettings = () => {
        setIsLoading(true);

        router.post(route('twilio.settings.store'), {
            settings: {
                ...twilioSettings,
                ...notificationSettings,
                twilio_notification_is: twilioSettings.twilio_notification_is ? 'on' : 'off'
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
                        <Phone className="h-5 w-5" />
                        {t('Twilio Settings')}
                    </CardTitle>
                    <p className="text-sm text-muted-foreground mt-1">
                        {t('Configure Twilio integration and SMS settings')}
                    </p>
                </div>
                {canEdit && (
                    <Button className="order-2 rtl:order-1" onClick={saveTwilioSettings} disabled={isLoading} size="sm">
                        <Save className="h-4 w-4 mr-2" />
                        {isLoading ? t('Saving...') : t('Save Changes')}
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {/* Enable/Disable Twilio */}
                    <div className="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <Label htmlFor="twilio_notification_is" className="text-base font-medium">
                                {t('Enable Twilio Integration')}
                            </Label>
                            <p className="text-sm text-muted-foreground mt-1">
                                {t('Allow SMS notifications to be sent via Twilio')}
                            </p>
                        </div>
                        <Switch
                            id="twilio_notification_is"
                            checked={twilioSettings.twilio_notification_is}
                            onCheckedChange={(checked) => handleSettingsChange('twilio_notification_is', checked)}
                            disabled={!canEdit}
                        />
                    </div>

                    {twilioSettings.twilio_notification_is && (
                        <div className="space-y-4">
                            <div className="space-y-3">
                                <Label htmlFor="twilio_sid">{t('Twilio Account SID')}</Label>
                                <Input
                                    id="twilio_sid"
                                    value={twilioSettings.twilio_sid}
                                    onChange={(e) => handleSettingsChange('twilio_sid', e.target.value)}
                                    placeholder={t('Enter your Twilio Account SID')}
                                    disabled={!canEdit}
                                />
                            </div>

                            <div className="space-y-3">
                                <Label htmlFor="twilio_token">{t('Twilio Auth Token')}</Label>
                                <div className="relative">
                                    <Input
                                        id="twilio_token"
                                        type={showToken ? 'text' : 'password'}
                                        value={twilioSettings.twilio_token}
                                        onChange={(e) => handleSettingsChange('twilio_token', e.target.value)}
                                        placeholder={t('Enter your Twilio Auth Token')}
                                        disabled={!canEdit}
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowToken(!showToken)}
                                        className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                    >
                                        {showToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <Label htmlFor="twilio_from">{t('From Phone Number')}</Label>
                                <Input
                                    id="twilio_from"
                                    value={twilioSettings.twilio_from}
                                    onChange={(e) => handleSettingsChange('twilio_from', e.target.value)}
                                    placeholder={t('Enter your Twilio phone number (e.g., +1234567890)')}
                                    disabled={!canEdit}
                                />
                            </div>

                            {(() => {
                                const filteredModules = Object.keys(twilioNotifications || {}).filter(module =>
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
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        {(twilioNotifications[module] || []).map((notification: Notification) => (
                                                            <div key={notification.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                                <span className="text-sm font-medium">
                                                                    {notification.action}
                                                                </span>
                                                                <Switch
                                                                    checked={notificationSettings[`Twilio ${notification.action}`] === 'on'}
                                                                    onCheckedChange={(checked) => handleNotificationToggle(`Twilio ${notification.action}`, checked)}
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
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
