import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileSignature, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

interface ContractSettings {
  contract_prefix: string;
  [key: string]: any;
}

interface ContractSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function ContractSettings({ userSettings, auth }: ContractSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('manage-contract-settings');
  const [settings, setSettings] = useState<ContractSettings>({
    contract_prefix: userSettings?.contract_prefix || '#CON',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        contract_prefix: userSettings?.contract_prefix || '#CON',
      });
    }
  }, [userSettings]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('contract-settings.store'), {
      settings: {
        contract_prefix: settings.contract_prefix
      }
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
        <div>
          <CardTitle className="flex items-center gap-2 text-lg">
            <FileSignature className="h-5 w-5" />
            {t('Contract Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure contract numbering and general settings')}
          </p>
        </div>
        {canEdit && (
          <Button onClick={saveSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          <div className="space-y-3">
            <Label htmlFor="contract_prefix">{t('Contract Prefix')}</Label>
            <Input
              id="contract_prefix"
              name="contract_prefix"
              value={settings.contract_prefix}
              onChange={handleInputChange}
              placeholder={t('Enter contract prefix (e.g., CON)')}
              maxLength={10}
              disabled={!canEdit}
            />
            <p className="text-xs text-muted-foreground">
              {t('This prefix will be used for generating contract numbers (e.g., CON0001)')}
            </p>
          </div>

          <div className="border rounded-lg p-4 bg-muted/30">
            <h4 className="font-medium mb-2 flex items-center gap-2">
              <FileSignature className="h-4 w-4" />
              {t('Contract Configuration Summary')}
            </h4>
            <div className="space-y-1 text-sm text-muted-foreground">
              <div>{t('Contract Prefix')}: <span className="font-medium text-foreground">{settings.contract_prefix}</span></div>
              <div>{t('Next Contract Number')}: <span className="font-medium text-foreground">{settings.contract_prefix}0001</span></div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}