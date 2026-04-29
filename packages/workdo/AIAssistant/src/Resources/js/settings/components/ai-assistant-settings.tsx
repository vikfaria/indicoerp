import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Bot, Save, Eye, EyeOff } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';

interface AIAssistantSettingsProps {
    userSettings?: Record<string, string>;
    auth?: any;
}

interface Provider {
    name: string;
    models: string[];
}

export default function AIAssistantSettings({ userSettings = {}, auth }: AIAssistantSettingsProps) {
    const { t } = useTranslation();
    const { is_demo } = usePage().props as any;
    const [isLoading, setIsLoading] = useState(false);
    const [providers, setProviders] = useState<Record<string, Provider>>({});
    const [showApiKey, setShowApiKey] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('edit-ai-assistant-settings');

    const [aiSettings, setAiSettings] = useState({
        ai_provider: userSettings?.ai_provider || '',
        ai_model: userSettings?.ai_model || '',
        ai_api_key: userSettings?.ai_api_key || ''
    });

    useEffect(() => {
        setAiSettings({
            ai_provider: userSettings?.ai_provider || '',
            ai_model: userSettings?.ai_model || '',
            ai_api_key: userSettings?.ai_api_key || ''
        });
    }, [userSettings]);

    useEffect(() => {
        fetch(route('ai-assistant.settings.index'))
            .then(response => response.json())
            .then(data => {
                setProviders(data.providers || {});
            })
            .catch(error => console.error('Error fetching AI providers:', error));
    }, []);

    const handleSettingsChange = (field: string, value: string) => {
        setAiSettings(prev => ({
            ...prev,
            [field]: value
        }));

        // Reset model when provider changes
        if (field === 'ai_provider') {
            setAiSettings(prev => ({
                ...prev,
                ai_model: ''
            }));
        }
    };

    const saveAISettings = () => {
        setIsLoading(true);

        router.post(route('ai-assistant.settings.store'), {
            settings: aiSettings
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                setIsLoading(false);
                const successMessage = (page.props.flash as any)?.success;
                const errorMessage = (page.props.flash as any)?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                }
            },
            onError: (errors) => {
                setIsLoading(false);
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save AI Assistant settings');
                toast.error(errorMessage);
            }
        });
    };

    const selectedProvider = providers[aiSettings.ai_provider];

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div className="order-1 rtl:order-2">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Bot className="h-5 w-5" />
                        {t('AI Assistant Settings')}
                    </CardTitle>
                    <p className="text-sm text-muted-foreground mt-1">
                        {t('Configure AI provider and model settings')}
                    </p>
                </div>
                {canEdit && (
                    <Button className="order-2 rtl:order-1" onClick={saveAISettings} disabled={isLoading} size="sm">
                        <Save className="h-4 w-4 mr-2" />
                        {isLoading ? t('Saving...') : t('Save Changes')}
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {/* AI Provider Selection */}
                    <div className="space-y-3">
                        <Label htmlFor="ai_provider">{t('AI Provider')}</Label>
                        <Select
                            value={aiSettings.ai_provider}
                            onValueChange={(value) => handleSettingsChange('ai_provider', value)}
                            disabled={!canEdit}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select AI Provider')} />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(providers).map(([key, provider]) => (
                                    <SelectItem key={key} value={key}>
                                        {provider.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Model Selection */}
                    {selectedProvider && (
                        <div className="space-y-3">
                            <Label htmlFor="ai_model">{t('AI Model')}</Label>
                            <Select
                                value={aiSettings.ai_model}
                                onValueChange={(value) => handleSettingsChange('ai_model', value)}
                                disabled={!canEdit}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select AI Model')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {selectedProvider.models.map((model) => (
                                        <SelectItem key={model} value={model}>
                                            {model}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {/* API Key */}
                    <div className="space-y-3">
                        <Label htmlFor="ai_api_key">{t('API Key')}</Label>
                        <div className="relative">
                            <Input
                                id="ai_api_key"
                                type={showApiKey ? 'text' : 'password'}
                                value={is_demo ? '********************************' : aiSettings.ai_api_key}
                                onChange={(e) => handleSettingsChange('ai_api_key', e.target.value)}
                                placeholder={t('Enter API Key')}
                                disabled={is_demo || !canEdit}
                                className="pr-10"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                                onClick={() => setShowApiKey(!showApiKey)}
                                disabled={!canEdit}
                            >
                                {showApiKey ? (
                                    <EyeOff className="h-4 w-4 text-gray-500" />
                                ) : (
                                    <Eye className="h-4 w-4 text-gray-500" />
                                )}
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
