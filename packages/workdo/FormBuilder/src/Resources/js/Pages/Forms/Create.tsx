import { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Plus, Trash2, ArrowUp, ArrowDown, Settings, Eye, Save, Type, Hash, Calendar, FileText, CheckSquare, List, Radio, Upload, Phone, Mail, Lock, Link, Clock } from 'lucide-react';

interface FormField {
  id: string;
  label: string;
  type: string;
  required: boolean;
  placeholder?: string;
  options?: string[];
  order: number;
}

const getFieldTypes = (t: any) => [
  { value: 'text', label: t('Text Input'), icon: Type },
  { value: 'email', label: t('Email'), icon: Mail },
  { value: 'number', label: t('Number'), icon: Hash },
  { value: 'tel', label: t('Phone'), icon: Phone },
  { value: 'url', label: t('URL'), icon: Link },
  { value: 'password', label: t('Password'), icon: Lock },
  { value: 'textarea', label: t('Textarea'), icon: FileText },
  { value: 'select', label: t('Select Dropdown'), icon: List },
  { value: 'radio', label: t('Radio Buttons'), icon: Radio },
  { value: 'checkbox', label: t('Checkbox'), icon: CheckSquare },
  { value: 'date', label: t('Date'), icon: Calendar },
  { value: 'time', label: t('Time'), icon: Clock },
];

export default function CreateForm() {
  const { t } = useTranslation();
  const [fields, setFields] = useState<FormField[]>([]);
  const [defaultLayout, setDefaultLayout] = useState<'single' | 'two-column' | 'card'>('single');
  const fieldTypes = getFieldTypes(t);

  const { data, setData, post, processing, errors } = useForm({
    name: '',
    is_active: true,
    default_layout: 'single',
    fields: [],
  });

  useEffect(() => {
    setData('fields', fields);
  }, [fields, setData]);

  const addFieldType = (type: string) => {
    const fieldType = fieldTypes.find(ft => ft.value === type);
    const newField: FormField = {
      id: Date.now().toString(),
      label: fieldType?.label + ' ' + t('Field') || t('New Field'),
      type: type,
      required: false,
      placeholder: t('Enter {{field}}', { field: fieldType?.label.toLowerCase() || t('text') }),
      options: needsOptions(type) ? [t('Option 1'), t('Option 2')] : [],
      order: fields.length,
    };
    setFields([...fields, newField]);
  };

  const updateField = (id: string, updates: Partial<FormField>) => {
    setFields(fields.map(field => {
      if (field.id === id) {
        const updatedField = { ...field, ...updates };
        if (updates.label && (!field.placeholder || field.placeholder === t('Enter {{field}}', { field: field.label?.toLowerCase() }))) {
          updatedField.placeholder = t('Enter {{field}}', { field: updates.label.toLowerCase() });
        }
        return updatedField;
      }
      return field;
    }));
  };

  const removeField = (id: string) => {
    setFields(fields.filter(field => field.id !== id));
  };

  const moveField = (id: string, direction: 'up' | 'down') => {
    const index = fields.findIndex(field => field.id === id);
    if (
      (direction === 'up' && index > 0) ||
      (direction === 'down' && index < fields.length - 1)
    ) {
      const newFields = [...fields];
      const targetIndex = direction === 'up' ? index - 1 : index + 1;
      [newFields[index], newFields[targetIndex]] = [newFields[targetIndex], newFields[index]];

      // Update order property for all fields
      const updatedFields = newFields.map((field, idx) => ({
        ...field,
        order: idx
      }));

      setFields(updatedFields);
    }
  };

  const addOption = (fieldId: string) => {
    updateField(fieldId, {
      options: [...(fields.find(f => f.id === fieldId)?.options || []), '']
    });
  };

  const updateOption = (fieldId: string, optionIndex: number, value: string) => {
    const field = fields.find(f => f.id === fieldId);
    if (field?.options) {
      const newOptions = [...field.options];
      newOptions[optionIndex] = value;
      updateField(fieldId, { options: newOptions });
    }
  };

  const removeOption = (fieldId: string, optionIndex: number) => {
    const field = fields.find(f => f.id === fieldId);
    if (field?.options) {
      updateField(fieldId, { options: field.options.filter((_, idx) => idx !== optionIndex) });
    }
  };

  const moveOption = (fieldId: string, optionIndex: number, direction: 'up' | 'down') => {
    const field = fields.find(f => f.id === fieldId);
    if (field?.options) {
      const options = [...field.options];
      const targetIndex = direction === 'up' ? optionIndex - 1 : optionIndex + 1;

      if (targetIndex >= 0 && targetIndex < options.length) {
        [options[optionIndex], options[targetIndex]] = [options[targetIndex], options[optionIndex]];
        updateField(fieldId, { options });
      }
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('formbuilder.forms.store'), {
      onSuccess: () => router.visit(route('formbuilder.forms.index')),
    });
  };

  const needsOptions = (type: string) => ['select', 'radio'].includes(type);

  return (
    <AuthenticatedLayout
      breadcrumbs={[{ label: t('Form Builder'), url: route('formbuilder.forms.index') }, { label: t('Create') }]}
      pageTitle={t('Form Builder')}
      pageActions={
        <div className="flex gap-2">
          <Button type="button" variant="outline" onClick={() => router.visit(route('formbuilder.forms.index'))}>
            {t('Cancel')}
          </Button>
          <Button type="submit" form="form-builder" disabled={processing}>
            {t('Save')}
          </Button>
        </div>
      }
    >
      <Head title={t('Form Builder')} />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Form Configuration Panel */}
        <div className="lg:col-span-1">
          <div className="sticky top-6 space-y-4">
            {/* Form Settings */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Settings className="h-5 w-5" />
                  {t('Form Configuration')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label htmlFor="name">{t('Form Name')}</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    placeholder={t('Enter form name')}
                    required
                  />
                </div>
                <div className="flex items-center justify-between">
                  <Label htmlFor="is_active">{t('Enable Form')}</Label>
                  <Switch
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) => setData('is_active', checked)}
                  />
                </div>
                <div>
                  <Label htmlFor="default_layout">{t('Default Layout')}</Label>
                  <Select
                    value={data.default_layout}
                    onValueChange={(value) => {
                      setData('default_layout', value);
                      setDefaultLayout(value as 'single' | 'two-column' | 'card');
                    }}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="single">{t('Single Column')}</SelectItem>
                      <SelectItem value="two-column">{t('Two Column')}</SelectItem>
                      <SelectItem value="card">{t('Card Layout')}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Separator />
                <div className="space-y-2">
                  <Label>{t('Form Statistics')}</Label>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <div className="bg-blue-50 p-2 rounded">
                      <div className="font-medium text-blue-900">{fields.length}</div>
                      <div className="text-blue-600">{t('Fields')}</div>
                    </div>
                    <div className="bg-green-50 p-2 rounded">
                      <div className="font-medium text-green-900">{fields.filter(f => f.required).length}</div>
                      <div className="text-green-600">{t('Required')}</div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Available Field Types */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Plus className="h-5 w-5" />
                  {t('Available Field Types')}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  {fieldTypes.map((type) => {
                    const Icon = type.icon;
                    const count = fields.filter(f => f.type === type.value).length;
                    return (
                      <div
                        key={type.value}
                        onClick={() => addFieldType(type.value)}
                        className="relative flex flex-col items-center py-3 px-2 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary/10 cursor-pointer transition-all group min-h-[70px]"
                      >
                        {count > 0 && (
                          <Badge className="absolute -top-1 -right-1 bg-primary text-white text-xs h-5 w-5 rounded-full p-0 flex items-center justify-center font-medium">
                            {count}
                          </Badge>
                        )}
                        <div className="w-6 h-6 bg-gray-100 rounded flex items-center justify-center mb-1 group-hover:bg-primary/20">
                          <Icon className="w-3 h-3 text-gray-600 group-hover:text-primary" />
                        </div>
                        <div className="text-center">
                          <div className="font-medium text-xs text-gray-900 leading-tight">{t(type.label)}</div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Form Builder Panel */}
        <div className="lg:col-span-2">
          <form id="form-builder" onSubmit={handleSubmit}>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  <span className="flex items-center gap-2">
                    <Eye className="h-5 w-5" />
                    {t('Form Preview')}
                  </span>
                  <Badge variant="secondary">{fields.length} {t('fields')}</Badge>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">


                {/* Fields */}
                {fields.length === 0 ? (
                  <div className="text-center py-16 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                    <div className="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                      <Plus className="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 className="text-xl font-semibold text-gray-900 mb-2">{t('Start Building Your Form')}</h3>
                    <p className="text-gray-500 mb-6">{t('Click on any field type from the sidebar to add it to your form')}</p>
                    <div className="flex justify-center space-x-2">
                      <div className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{t('Easy Setup')}</div>
                      <div className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">{t('Click to Add')}</div>
                    </div>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {fields.map((field, index) => {
                      const fieldTypeInfo = fieldTypes.find(t => t.value === field.type);
                      const FieldIcon = fieldTypeInfo?.icon || Type;

                      return (
                        <div key={field.id} className="group relative">
                          {/* Field Card */}
                          <div className="bg-white border-2 border-gray-100 rounded-xl p-6 hover:border-primary/50 hover:shadow-lg transition-all duration-200">

                            {/* Field Header with Controls */}
                            <div className="flex items-center justify-between mb-6">
                              <div className="flex items-center space-x-3">
                                <div className="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
                                  <FieldIcon className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                  <div className="flex items-center space-x-2">
                                    <span className="text-sm font-semibold text-gray-900">{t("Field")} {index + 1}</span>
                                    {field.required && (
                                      <Badge variant="destructive" className="text-xs px-2 py-0.5">
                                        {t('Required')}
                                      </Badge>
                                    )}
                                  </div>
                                  <span className="text-xs text-gray-500">{fieldTypeInfo?.label}</span>
                                </div>
                              </div>

                              {/* Field Controls */}
                              <div className="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <TooltipProvider>
                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => moveField(field.id, 'up')}
                                        disabled={index === 0}
                                        className="w-8 h-8 p-0 hover:bg-primary/10"
                                      >
                                        <ArrowUp className="w-4 h-4 text-gray-500" />
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Move Up')}</p></TooltipContent>
                                  </Tooltip>
                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => moveField(field.id, 'down')}
                                        disabled={index === fields.length - 1}
                                        className="w-8 h-8 p-0 hover:bg-primary/10"
                                      >
                                        <ArrowDown className="w-4 h-4 text-gray-500" />
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Move Down')}</p></TooltipContent>
                                  </Tooltip>
                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeField(field.id)}
                                        className="w-8 h-8 p-0 text-destructive hover:text-destructive"
                                      >
                                        <Trash2 className="w-4 h-4" />
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                  </Tooltip>
                                </TooltipProvider>
                              </div>
                            </div>

                            {/* Field Configuration Row */}
                            <div className="grid grid-cols-12 gap-4 mb-4 align-center">
                              <div className="col-span-5">
                                <Label className="text-sm font-medium text-gray-700 mb-2 block">{t('Field Label')}</Label>
                                <Input
                                  value={field.label}
                                  onChange={(e) => updateField(field.id, { label: e.target.value })}
                                  placeholder={t('Enter field label')}
                                  className="border-gray-200 focus:border-primary"
                                />
                              </div>
                              <div className="col-span-5">
                                <Label className="text-sm font-medium text-gray-700 mb-2 block">{t('Placeholder Text')}</Label>
                                <Input
                                  value={field.placeholder || ''}
                                  onChange={(e) => updateField(field.id, { placeholder: e.target.value })}
                                  placeholder={t('Enter placeholder text')}
                                  className="border-gray-200 focus:border-primary"
                                />
                              </div>
                              <div className="col-span-2 flex items-end mb-3">
                                <div className="flex items-center space-x-2 w-full justify-center">
                                  <Label className="text-sm font-medium text-gray-700">{t('Required')}</Label>
                                  <Switch
                                    checked={field.required}
                                    onCheckedChange={(checked) => updateField(field.id, { required: checked })}
                                  />
                                </div>
                              </div>
                            </div>

                            {/* Options Configuration */}
                            {needsOptions(field.type) && (
                              <div className="mt-4 pt-4 border-t border-gray-200">
                                <div className="flex items-center justify-between mb-4">
                                  <Label className="text-sm font-medium text-gray-700">{t('Field Options')}</Label>
                                  <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => addOption(field.id)}
                                    className="text-primary border-primary/30 hover:bg-primary/10"
                                  >
                                    <Plus className="w-4 h-4 mr-1" />
                                    {t('Add Option')}
                                  </Button>
                                </div>
                                <div className="space-y-2">
                                  {field.options?.map((option, optionIndex) => (
                                    <div key={optionIndex} className="flex items-center space-x-2">
                                      <div className="flex flex-col space-y-1">
                                        <TooltipProvider>
                                          <Tooltip>
                                            <TooltipTrigger asChild>
                                              <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => moveOption(field.id, optionIndex, 'up')}
                                                disabled={optionIndex === 0}
                                                className="w-6 h-6 p-0"
                                              >
                                                <ArrowUp className="w-3 h-3" />
                                              </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>{t('Move Up')}</p></TooltipContent>
                                          </Tooltip>
                                          <Tooltip>
                                            <TooltipTrigger asChild>
                                              <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => moveOption(field.id, optionIndex, 'down')}
                                                disabled={optionIndex === (field.options?.length || 0) - 1}
                                                className="w-6 h-6 p-0"
                                              >
                                                <ArrowDown className="w-3 h-3" />
                                              </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>{t('Move Down')}</p></TooltipContent>
                                          </Tooltip>
                                        </TooltipProvider>
                                      </div>
                                      <Input
                                        value={option}
                                        onChange={(e) => updateOption(field.id, optionIndex, e.target.value)}
                                        placeholder={`Option ${optionIndex + 1}`}
                                        className="flex-1"
                                      />
                                      <TooltipProvider>
                                        <Tooltip>
                                          <TooltipTrigger asChild>
                                            <Button
                                              type="button"
                                              variant="ghost"
                                              size="sm"
                                              onClick={() => removeOption(field.id, optionIndex)}
                                              className="text-destructive hover:text-destructive"
                                            >
                                              <Trash2 className="w-4 h-4" />
                                            </Button>
                                          </TooltipTrigger>
                                          <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                        </Tooltip>
                                      </TooltipProvider>
                                    </div>
                                  ))}
                                </div>
                              </div>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}
              </CardContent>
            </Card>
          </form>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}