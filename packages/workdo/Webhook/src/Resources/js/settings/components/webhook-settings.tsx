import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Trash2, Plus, Edit, Webhook as WebhookIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { getPackageAlias } from '@/utils/helpers';

interface Webhook {
  id: number;
  method: string;
  url: string;
  action: number;
  is_active: boolean;
  webhook_module: {
    id: number;
    module: string;
    submodule: string;
  };
}

interface WebhookModule {
  id: number;
  module: string;
  submodule: string;
}

interface WebhookSettingsProps {
  webhooks?: Webhook[];
  webhookModules?: Record<string, WebhookModule[]>;
}

const WebhookSettings: React.FC<WebhookSettingsProps> = ({ 
  webhooks: initialWebhooks = [], 
  webhookModules: initialWebhookModules = {} 
}) => {
  const { t } = useTranslation();
  const { props } = usePage(); // Add this to fix conditional hook calls
  const [webhooks, setWebhooks] = useState<Webhook[]>(initialWebhooks);
  const [webhookModules, setWebhookModules] = useState<Record<string, WebhookModule[]>>(initialWebhookModules);
  const [loading, setLoading] = useState(false);
  const [isAddingWebhook, setIsAddingWebhook] = useState(false);
  const [editingWebhook, setEditingWebhook] = useState<Webhook | null>(null);
  const [formData, setFormData] = useState({ method: 'POST', action: '', url: '' });
  const [submitting, setSubmitting] = useState(false);
  const [deleteDialog, setDeleteDialog] = useState({ isOpen: false, webhookId: 0 });
  
  React.useEffect(() => {
    fetchWebhookData();
  }, []);
  
  const fetchWebhookData = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('webhook.settings.index'), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        setWebhooks(data.webhooks || []);
        setWebhookModules(data.webhookModules || {});
      }
    } catch (error) {
      console.error('Failed to fetch webhook data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleAddWebhook = () => {
    setIsAddingWebhook(true);
    setEditingWebhook(null);
  };

  const handleEditWebhook = (webhook: Webhook) => {
    setEditingWebhook(webhook);
    setFormData({
      method: webhook.method,
      action: webhook.action.toString(),
      url: webhook.url
    });
    setIsAddingWebhook(false);
  };

  const handleCancel = () => {
    setIsAddingWebhook(false);
    setEditingWebhook(null);
    setFormData({ method: 'POST', action: '', url: '' });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.method || !formData.action || !formData.url) return;
    
    setSubmitting(true);
    
    if (editingWebhook) {
      router.put(route('webhook.settings.update', editingWebhook.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
          fetchWebhookData();
          handleCancel();
        },
        onError: () => {},
        onFinish: () => setSubmitting(false)
      });
    } else {
      router.post(route('webhook.settings.store'), formData, {
        preserveScroll: true,
        onSuccess: () => {
          fetchWebhookData();
          handleCancel();
        },
        onError: () => {},
        onFinish: () => setSubmitting(false)
      });
    }
  };
  
  const openDeleteDialog = (webhookId: number) => {
    setDeleteDialog({ isOpen: true, webhookId });
  };
  
  const closeDeleteDialog = () => {
    setDeleteDialog({ isOpen: false, webhookId: 0 });
  };
  
  const confirmDelete = async () => {
    router.delete(route('webhook.settings.destroy', deleteDialog.webhookId), {
      preserveScroll: true,
      onSuccess: () => {
        fetchWebhookData();
        closeDeleteDialog();
      },
      onError: () => {}
    });
  };
  
  const handleToggle = async (webhookId: number) => {
    router.put(route('webhook.settings.toggle', webhookId), {}, {
      preserveScroll: true,
      onSuccess: () => {
        fetchWebhookData();
      },
      onError: () => {}
    });
  };



  if (loading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
        <span className="ml-2">{t('Loading...')}</span>
      </div>
    );
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <WebhookIcon className="h-5 w-5" />
            {t('Webhook Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure webhooks to receive real-time notifications when events occur in your system')}
          </p>
        </div>
        <Button className="order-2 rtl:order-1" onClick={handleAddWebhook} size="sm">
          <Plus className="h-4 w-4 mr-2" />
          {t('Add Webhook')}
        </Button>
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {/* Webhook List */}
          <div>
            <h3 className="text-base font-medium mb-4">{t('Configured Webhooks')}</h3>
            {webhooks.length === 0 ? (
              <div className="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                <WebhookIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-muted-foreground">
                  {t('No webhooks configured yet. Add your first webhook to get started.')}
                </p>
              </div>
            ) : (
              <div className="max-h-[400px] overflow-y-auto space-y-3 pr-2">
                {webhooks.map((webhook) => (
                  <div key={webhook.id} className={`flex items-center justify-between p-4 border rounded-lg ${webhook.is_active ? 'bg-gray-50/50' : 'bg-gray-100/50 opacity-75'}`}>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <Badge variant="outline">{webhook.method}</Badge>
                        <span className="font-medium">{webhook.webhook_module?.submodule || 'Unknown Event'}</span>
                        <Badge variant="secondary">{getPackageAlias(webhook.webhook_module?.module || 'Unknown', props)}</Badge>
                        <Badge variant={webhook.is_active ? 'default' : 'secondary'} className="text-xs">
                          {webhook.is_active ? t('Active') : t('Inactive')}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground truncate">{webhook.url}</p>
                    </div>
                    <div className="flex items-center gap-3">
                      <div className="flex items-center gap-2">
                        <Switch
                          checked={webhook.is_active}
                          onCheckedChange={() => handleToggle(webhook.id)}
                        />
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleEditWebhook(webhook)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          className="text-destructive hover:text-destructive"
                          onClick={() => openDeleteDialog(webhook.id)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Add/Edit Webhook Form */}
          {(isAddingWebhook || editingWebhook) && (
            <div className="border-t pt-6">
              <h3 className="text-base font-medium mb-4">
                {editingWebhook ? t('Edit Webhook') : t('Add New Webhook')}
              </h3>
              <form className="space-y-4" onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="method">{t('Method')}</Label>
                    <Select value={formData.method} onValueChange={(value) => setFormData({...formData, method: value})}>
                      <SelectTrigger>
                        <SelectValue placeholder={t('Select method')} />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="GET">GET</SelectItem>
                        <SelectItem value="POST">POST</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="action">{t('Event')}</Label>
                    <Select value={formData.action} onValueChange={(value) => setFormData({...formData, action: value})}>
                      <SelectTrigger>
                        <SelectValue placeholder={t('Select event')} />
                      </SelectTrigger>
                      <SelectContent>
                        {Object.entries(webhookModules).map(([module, actions]) => (
                          <div key={module}>
                            <div className="px-2 py-1 text-sm font-medium text-muted-foreground">
                              {getPackageAlias(module, props)}
                            </div>
                            {actions.map((action) => (
                              <SelectItem key={action.id} value={action.id.toString()}>
                                {action.submodule}
                              </SelectItem>
                            ))}
                          </div>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="url">{t('Webhook URL')}</Label>
                  <Input
                    id="url"
                    type="url"
                    placeholder="https://your-app.com/webhook"
                    value={formData.url}
                    onChange={(e) => setFormData({...formData, url: e.target.value})}
                    required
                  />
                  <p className="text-xs text-muted-foreground">
                    {t('Enter the URL where webhook notifications should be sent')}
                  </p>
                </div>
                <div className="flex justify-end gap-2 pt-4">
                  <Button type="button" variant="outline" onClick={handleCancel}>
                    {t('Cancel')}
                  </Button>
                  <Button type="submit" disabled={submitting}>
                    {submitting ? t('Saving...') : (editingWebhook ? t('Update Webhook') : t('Create Webhook'))}
                  </Button>
                </div>
              </form>
            </div>
          )}
        </div>
      </CardContent>
      
      <ConfirmationDialog
        open={deleteDialog.isOpen}
        onOpenChange={closeDeleteDialog}
        title={t('Delete Webhook')}
        message={t('Are you sure you want to delete this webhook? This action cannot be undone.')}
        confirmText={t('Delete')}
        onConfirm={confirmDelete}
        variant="destructive"
      />
    </Card>
  );
};

export default WebhookSettings;