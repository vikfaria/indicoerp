import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowRight, CheckCircle } from 'lucide-react';
import { route } from 'ziggy-js';

interface ConversionSetupProps {
  formId: number;
  auth: any;
  initialData?: {
    conversion?: {
      module_name: string;
      submodule_name?: string;
      is_active: boolean;
      field_mappings: Record<string, number>;
    };
    available_modules: Record<string, any>;
    form_fields: Array<{
      id: number;
      label: string;
      type: string;
    }>;
    users?: Array<{
      id: number;
      name: string;
    }>;
  };
}

export default function ConversionSetup({ formId, auth, initialData }: ConversionSetupProps) {
  const { t } = useTranslation();
  const [selectedModule, setSelectedModule] = useState<string>('');
  const [selectedSubmodule, setSelectedSubmodule] = useState<string>('');
  const [moduleFields, setModuleFields] = useState<Record<string, any>>({});

  const { data, setData, post, processing, errors } = useForm({
    module_name: initialData?.conversion?.module_name || '',
    submodule_name: initialData?.conversion?.submodule_name || '',
    is_active: initialData?.conversion?.is_active || false,
    field_mappings: initialData?.conversion?.field_mappings || {},
  });



  useEffect(() => {
    if (initialData?.conversion) {
      setSelectedModule(initialData.conversion.module_name);
      setSelectedSubmodule(initialData.conversion.submodule_name || '');
    }
  }, [initialData]);

  useEffect(() => {
    if (selectedModule && initialData?.available_modules[selectedModule]) {
      const moduleData = initialData.available_modules[selectedModule];
      if (selectedSubmodule && moduleData[selectedSubmodule]) {
        setModuleFields(moduleData[selectedSubmodule]);
        setData('submodule_name', selectedSubmodule);
      } else if (typeof moduleData === 'object' && !Array.isArray(moduleData)) {
        // If no submodule selected, use first available submodule
        const firstSubmodule = Object.keys(moduleData)[0];
        setSelectedSubmodule(firstSubmodule);
        setData('submodule_name', firstSubmodule);
        setModuleFields(moduleData[firstSubmodule]);
      }
    }
  }, [selectedModule, selectedSubmodule, initialData, setData]);

  const handleModuleChange = (moduleName: string) => {
    if (moduleName === 'no_module') {
      setSelectedModule('');
      setSelectedSubmodule('');
      setData('module_name', '');
      setData('submodule_name', '');
      setData('field_mappings', {});
    } else {
      setSelectedModule(moduleName);
      setSelectedSubmodule('');
      setData('module_name', moduleName);
      setData('submodule_name', '');
      setData('field_mappings', {});
    }
  };

  const handleSubmoduleChange = (submoduleName: string) => {
    if (submoduleName === 'no_submodule') {
      setSelectedSubmodule('');
      setData('submodule_name', '');
      setData('field_mappings', {});
    } else {
      setSelectedSubmodule(submoduleName);
      setData('submodule_name', submoduleName);
      setData('field_mappings', {});
    }
  };

  const handleFieldMapping = (moduleFieldKey: string, formFieldId: string) => {
    const newMappings = { ...data.field_mappings };
    if (formFieldId === 'not_mapped' || formFieldId === '') {
      delete newMappings[moduleFieldKey];
    } else {
      if (hasStringOptions(moduleFieldKey)) {
        newMappings[moduleFieldKey] = formFieldId;
      } else {
        newMappings[moduleFieldKey] = parseInt(formFieldId);
      }
    }
    setData('field_mappings', newMappings);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validate that all fields are mapped if conversion is active
    if (data.is_active && selectedModule && Object.keys(moduleFields).length > 0) {
      const unmappedFields = Object.keys(moduleFields).filter(fieldKey => {
        const mapping = data.field_mappings[fieldKey];
        return !mapping ||
          mapping === '' ||
          mapping === 'not_mapped' ||
          (Array.isArray(mapping) && mapping.length === 0);
      });

      if (unmappedFields.length > 0) {
        const fieldNames = unmappedFields.map(key =>
          typeof moduleFields[key] === 'string' ? moduleFields[key] : moduleFields[key]?.label || key
        ).join(', ');

        toast.error(t('Please map all required fields: {{fields}}', {
          fields: fieldNames
        }));
        return;
      }
    }

    post(route('formbuilder.forms.conversion.update', formId), {
      onSuccess: () => {
      },
    });
  };



  const getFieldOptions = (fieldKey: string) => {
    const fieldConfig = initialData?.available_modules?.[selectedModule]?.[selectedSubmodule]?.[fieldKey];
    return fieldConfig?.options || null;
  };

  const hasStringOptions = (fieldKey: string) => {
    const options = getFieldOptions(fieldKey);
    return options && options.length > 0 && typeof options[0]?.id === 'string';
  };


  const getAvailableModules = () => {
    return Object.keys(initialData?.available_modules || {});
  };

  const getAvailableSubmodules = () => {
    if (!selectedModule || !initialData?.available_modules[selectedModule]) return [];
    const moduleData = initialData.available_modules[selectedModule];
    if (Array.isArray(moduleData)) return [];
    return Object.keys(moduleData);
  };

  return (
    <Card className="w-full">
      <CardContent className="pt-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Activation */}
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
            <div>
              <Label htmlFor="is_active" className="text-sm font-medium text-gray-900">
                {t('Enable Conversion')}
              </Label>
              <p className="text-xs text-gray-600 mt-1">
                {t('Automatically create CRM records from form submissions')}
              </p>
            </div>
            <Switch
              id="is_active"
              checked={data.is_active}
              onCheckedChange={(checked) => setData('is_active', checked)}
            />
          </div>
          {/* Module and Submodule Selection */}
          <div className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="module" className="text-sm font-medium mb-2 block">{t('Select Module')}</Label>
                <Select value={selectedModule} onValueChange={handleModuleChange}>
                  <SelectTrigger className="h-10">
                    <SelectValue placeholder={t('Choose a module')} />
                  </SelectTrigger>
                  <SelectContent>
                    {getAvailableModules().map((module) => (
                      <SelectItem key={module} value={module}>
                        {module}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {getAvailableSubmodules().length > 0 && (
                <div>
                  <Label htmlFor="submodule" className="text-sm font-medium mb-2 block">{t('Select Submodule')}</Label>
                  <Select value={selectedSubmodule} onValueChange={handleSubmoduleChange}>
                    <SelectTrigger className="h-10">
                      <SelectValue placeholder={t('Choose a submodule')} />
                    </SelectTrigger>
                    <SelectContent>
                      {getAvailableSubmodules().map((submodule) => (
                        <SelectItem key={submodule} value={submodule}>
                          {submodule}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
            </div>


          </div>

          <Separator />

          {/* Field Mapping */}
          {selectedModule && Object.keys(moduleFields).length > 0 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <Label className="text-base font-medium">{t('Field Mapping')}</Label>
                <Badge variant="secondary">
                  {Object.keys(data.field_mappings).length} / {Object.keys(moduleFields).length} {t('mapped')}
                </Badge>
              </div>

              <div className="space-y-3">
                {Object.entries(moduleFields).map(([fieldKey, fieldConfig]) => (
                  <div key={fieldKey} className="flex items-center gap-4 p-3 border rounded-lg">
                    <div className="flex-1">
                      <Label className="text-sm font-medium">{typeof fieldConfig === 'string' ? fieldConfig : fieldConfig?.label || fieldKey}</Label>
                      {typeof fieldConfig === 'object' && fieldConfig?.type && (
                        <div className="mt-1">
                          <Badge variant="outline" className="text-xs">
                            {fieldConfig.type}
                          </Badge>
                        </div>
                      )}
                    </div>
                    <ArrowRight className="h-4 w-4 text-gray-400" />
                    <div className="flex-1">
                      {fieldConfig?.multiple ? (
                        <MultiSelectEnhanced
                          options={getFieldOptions(fieldKey)?.map((option) => ({
                            value: option.id.toString(),
                            label: option.name
                          })) || initialData?.form_fields
                            .filter((field) => {
                              const moduleFieldType = typeof fieldConfig === 'object' && fieldConfig?.type;
                              return !moduleFieldType || field.type === moduleFieldType;
                            })
                            .map((field) => ({
                              value: field.id.toString(),
                              label: field.label
                            })) || []}
                          value={(() => {
                            const currentValue = data.field_mappings[fieldKey];
                            if (Array.isArray(currentValue)) {
                              return currentValue.map(String);
                            } else if (currentValue !== undefined && currentValue !== null && currentValue !== '') {
                              return [String(currentValue)];
                            }
                            return [];
                          })()}
                          onValueChange={(values) => {
                            if (values.length === 0) {
                              const newMappings = { ...data.field_mappings };
                              delete newMappings[fieldKey];
                              setData('field_mappings', newMappings);
                            } else {
                              const processedValues = hasStringOptions(fieldKey)
                                ? values
                                : values.map(v => parseInt(v));
                              setData('field_mappings', {
                                ...data.field_mappings,
                                [fieldKey]: processedValues
                              });
                            }
                          }}
                          placeholder={t('Select multiple options')}
                          searchable={true}
                          disabled={!auth.user?.permissions?.includes('edit-formbuilder-conversions')}
                        />
                      ) : (
                        <Select
                          value={data.field_mappings[fieldKey] ? String(data.field_mappings[fieldKey]) : 'not_mapped'}
                          onValueChange={(value) => handleFieldMapping(fieldKey, value)}
                          disabled={!auth.user?.permissions?.includes('edit-formbuilder-conversions')}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder={t('Select form field')} />
                          </SelectTrigger>
                          <SelectContent searchable={true}>
                            <SelectItem value="not_mapped">{t('Not mapped')}</SelectItem>
                            {getFieldOptions(fieldKey)?.map((option) => (
                              <SelectItem key={option.id} value={option.id.toString()}>
                                {option.name}
                              </SelectItem>
                            )) || (
                                initialData?.form_fields
                                  .filter((field) => {
                                    const moduleFieldType = typeof fieldConfig === 'object' && fieldConfig?.type;
                                    return !moduleFieldType || field.type === moduleFieldType;
                                  })
                                  .map((field) => (
                                    <SelectItem key={field.id} value={field.id.toString()}>
                                      {field.label}
                                    </SelectItem>
                                  ))
                              )}
                          </SelectContent>
                        </Select>
                      )}
                      {(data.field_mappings[fieldKey] && (Array.isArray(data.field_mappings[fieldKey]) ? data.field_mappings[fieldKey].length > 0 : true)) && (
                        <p className="text-xs text-green-600 mt-1">
                          <CheckCircle className="h-3 w-3 inline mr-1" />
                          {t('Mapped')}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Submit Button */}
          {auth.user?.permissions?.includes('edit-formbuilder-conversions') && (
            <div className="flex justify-end">
              <Button
                type="submit"
                disabled={processing}
              >
                {processing ? t('Saving...') : t('Save')}
              </Button>
            </div>
          )}
        </form>
      </CardContent>
    </Card>
  );
}