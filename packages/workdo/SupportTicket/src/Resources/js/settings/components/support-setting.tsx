import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Headphones, Save, Plus, Trash2, GripVertical } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface TicketField {
  id?: number;
  custom_id?: number;
  name: string;
  placeholder: string;
  type: string;
  is_required: boolean;
  width: string;
  order: number;
}

interface SupportSettings {
  faq_is_on: boolean;
  knowledge_base_is_on: boolean;
  [key: string]: any;
}

interface SupportSettingsProps {
  userSettings?: Record<string, string>;
  fields?: TicketField[];
  auth?: any;
}

const fieldTypes = {
  'text': 'Text',
  'email': 'Email', 
  'number': 'Number',
  'date': 'Date',
  'textarea': 'Textarea',
  'file': 'File',
  'select': 'Select'
};

const widthOptions = {
  '3': '25%',
  '4': '33%', 
  '6': '50%',
  '8': '66%',
  '12': '100%'
};

export default function SupportSettings({ userSettings, fields: initialFields, auth }: SupportSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = true; // Always allow editing for now
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  
  const [settings, setSettings] = useState<SupportSettings>({
    faq_is_on: userSettings?.faq_is_on === 'on' || false,
    knowledge_base_is_on: userSettings?.knowledge_base_is_on === 'on' || false,
  });

  const [fields, setFields] = useState<TicketField[]>([]);

  useEffect(() => {
    if (userSettings) {
      setSettings({
        faq_is_on: userSettings?.faq_is_on === 'on' || false,
        knowledge_base_is_on: userSettings?.knowledge_base_is_on === 'on' || false,
      });
    }
    
    // Load fields directly and sort by order
    fetch(route('support-ticket.fields.get'))
      .then(response => response.json())
      .then(data => {
        const sortedFields = (data.fields || []).sort((a, b) => a.order - b.order);
        setFields(sortedFields);
      })
      .catch(() => setFields([]));
  }, [userSettings]);

  const handleSwitchChange = (name: string, checked: boolean) => {
    setSettings(prev => ({ ...prev, [name]: checked }));
  };

  const addField = () => {
    const newField: TicketField = {
      name: '',
      placeholder: '',
      type: 'text',
      is_required: true,
      width: '12',
      order: fields.length
    };
    setFields(prev => [...prev, newField]);
  };

  const updateField = (index: number, field: Partial<TicketField>) => {
    setFields(prev => prev.map((f, i) => i === index ? { ...f, ...field } : f));
  };

  const removeField = (index: number) => {
    const field = fields[index];
    if (field.custom_id && field.custom_id <= 6) {
      return;
    }
    if (confirm(t('Are you sure?'))) {
      setFields(prev => prev.filter((_, i) => i !== index));
    }
  };

  const handleDragStart = (e: React.DragEvent, index: number) => {
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    if (draggedIndex === null || draggedIndex === dropIndex) return;

    const items = [...fields];
    const draggedItem = items[draggedIndex];
    items.splice(draggedIndex, 1);
    items.splice(dropIndex, 0, draggedItem);

    const updatedItems = items.map((item, index) => ({
      ...item,
      order: index
    }));

    setFields(updatedItems);
    setDraggedIndex(null);
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('support-ticket.settings.update'), {
      settings: {
        ...settings,
        fields: fields
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
            <Headphones className="h-5 w-5" />
            {t('Support Setting')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure support ticket module settings and custom fields')}
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
          {/* FAQ and Knowledge Base Toggles */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="faq_is_on">{t('Enable FAQ')}</Label>
                  <p className="text-xs text-muted-foreground">
                    {t('Enable or disable the FAQ functionality')}
                  </p>
                </div>
                <Switch
                  id="faq_is_on"
                  checked={settings.faq_is_on}
                  onCheckedChange={(checked) => handleSwitchChange('faq_is_on', checked)}
                  disabled={!canEdit}
                />
              </div>
            </div>

            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="knowledge_base_is_on">{t('Enable Knowledge Base')}</Label>
                  <p className="text-xs text-muted-foreground">
                    {t('Enable or disable the Knowledge Base functionality')}
                  </p>
                </div>
                <Switch
                  id="knowledge_base_is_on"
                  checked={settings.knowledge_base_is_on}
                  onCheckedChange={(checked) => handleSwitchChange('knowledge_base_is_on', checked)}
                  disabled={!canEdit}
                />
              </div>
            </div>
          </div>

          {/* Custom Fields Section */}
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="font-medium">{t('Ticket Fields Settings')}</h4>
                <p className="text-sm text-muted-foreground">
                  {t('You can easily change order of fields using drag & drop.')}
                </p>
              </div>
              {canEdit && (
                <TooltipProvider>
                  <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                      <Button onClick={addField} size="sm" className="text-white">
                        <Plus className="h-4 w-4" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>{t('Create')}</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              )}
            </div>

            <div className="border rounded-lg overflow-hidden">
              <div className="bg-gray-50 px-4 py-3 border-b">
                <div className="grid grid-cols-12 gap-4 text-sm font-medium text-gray-700">
                  <div className="col-span-1"></div>
                  <div className="col-span-2">{t('Labels')}</div>
                  <div className="col-span-2">{t('Placeholder')}</div>
                  <div className="col-span-2">{t('Type')}</div>
                  <div className="col-span-2">{t('Required')}</div>
                  <div className="col-span-2">{t('Width')}</div>
                  <div className="col-span-1"></div>
                </div>
              </div>

              <div>
                {fields.map((field, index) => (
                  <div
                    key={index}
                    draggable={canEdit}
                    onDragStart={(e) => handleDragStart(e, index)}
                    onDragOver={handleDragOver}
                    onDrop={(e) => handleDrop(e, index)}
                    className={`px-4 py-3 border-b bg-white ${canEdit ? 'cursor-move' : ''} hover:bg-gray-50`}
                  >
                    <div className="grid grid-cols-12 gap-4 items-center">
                      <div className="col-span-1">
                        {canEdit && <GripVertical className="h-4 w-4 text-gray-400" />}
                      </div>
                      
                      <div className="col-span-2">
                        <Input
                          value={field.name}
                          onChange={(e) => updateField(index, { name: e.target.value })}
                          placeholder={t('Field name')}
                          disabled={!canEdit}
                          required
                        />
                      </div>
                      
                      <div className="col-span-2">
                        <Input
                          value={field.placeholder}
                          onChange={(e) => updateField(index, { placeholder: e.target.value })}
                          placeholder={t('Placeholder text')}
                          disabled={!canEdit}
                          required
                        />
                      </div>
                      
                      <div className="col-span-2">
                        <Select
                          value={field.type}
                          onValueChange={(value) => updateField(index, { type: value })}
                          disabled={!canEdit || (field.custom_id && field.custom_id <= 6)}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {Object.entries(fieldTypes).map(([key, value]) => {
                              if ((key === 'file' || key === 'select') && (!field.custom_id || field.custom_id > 6)) {
                                return null;
                              }
                              return (
                                <SelectItem key={key} value={key}>
                                  {value}
                                </SelectItem>
                              );
                            })}
                          </SelectContent>
                        </Select>
                      </div>
                      
                      <div className="col-span-2">
                        <Select
                          value={field.is_required ? '1' : '0'}
                          onValueChange={(value) => updateField(index, { is_required: value === '1' })}
                          disabled={!canEdit}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="1">{t('Yes')}</SelectItem>
                            <SelectItem value="0">{t('No')}</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      
                      <div className="col-span-2">
                        <Select
                          value={field.width}
                          onValueChange={(value) => updateField(index, { width: value })}
                          disabled={!canEdit}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {Object.entries(widthOptions).map(([key, value]) => (
                              <SelectItem key={key} value={key}>
                                {value}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      
                      <div className="col-span-1">
                        {canEdit && (!field.custom_id || field.custom_id > 6) && (
                          <TooltipProvider>
                            <Tooltip delayDuration={0}>
                              <TooltipTrigger asChild>
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => removeField(index)}
                                  className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                >
                                  <Trash2 className="h-4 w-4" />
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>{t('Delete')}</p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>


        </div>
      </CardContent>
    </Card>
  );
}