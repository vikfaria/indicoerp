import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ShieldCheck, Save, PencilLine, Trash2, RotateCcw } from 'lucide-react';

type OverrideType = 'feature' | 'limit';

interface OverrideCompany {
  id: number;
  name: string;
  email?: string | null;
}

interface OverrideOption {
  value: string;
  label: string;
  domain?: string;
  description?: string;
  summary?: string;
  unit?: string;
}

interface TenantFeatureOverrideRow {
  id: number;
  company_id: number;
  company_name?: string | null;
  company_email?: string | null;
  override_type: OverrideType;
  override_key: string;
  limit_value?: number | null;
  notes?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  created_by_name?: string | null;
  updated_by_name?: string | null;
}

interface OverrideFormState {
  id: string;
  company_id: string;
  override_type: OverrideType;
  override_key: string;
  limit_value: string;
  notes: string;
}

export default function CompanyOverridesSettings() {
  const { t } = useTranslation();
  const { auth, tenantFeatureOverrides = [], tenantFeatureOverrideCompanies = [], tenantFeatureOverrideFeatureOptions = [], tenantFeatureOverrideLimitOptions = [] } = usePage().props as any;
  const isSuperAdmin = auth?.user?.roles?.includes('superadmin') || auth?.user?.type === 'superadmin';
  const canEdit = isSuperAdmin || auth?.user?.permissions?.includes('manage-company-overrides');

  const companies = tenantFeatureOverrideCompanies as OverrideCompany[];
  const featureOptions = tenantFeatureOverrideFeatureOptions as OverrideOption[];
  const limitOptions = tenantFeatureOverrideLimitOptions as OverrideOption[];
  const overrides = tenantFeatureOverrides as TenantFeatureOverrideRow[];

  const [isLoading, setIsLoading] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<OverrideFormState>({
    id: '',
    company_id: companies[0]?.id ? String(companies[0].id) : '',
    override_type: 'feature',
    override_key: featureOptions[0]?.value ?? '',
    limit_value: '',
    notes: '',
  });

  const currentOptions = useMemo(
    () => (form.override_type === 'feature' ? featureOptions : limitOptions),
    [featureOptions, limitOptions, form.override_type]
  );

  useEffect(() => {
    if (form.company_id === '' && companies.length > 0) {
      setForm(prev => ({ ...prev, company_id: String(companies[0].id) }));
    }
  }, [companies, form.company_id]);

  useEffect(() => {
    if (currentOptions.length === 0) {
      if (form.override_key !== '') {
        setForm(prev => ({ ...prev, override_key: '' }));
      }
      return;
    }

    if (!currentOptions.some(option => option.value === form.override_key)) {
      setForm(prev => ({
        ...prev,
        override_key: currentOptions[0]?.value ?? '',
      }));
    }
    if (form.override_type === 'feature' && form.limit_value !== '') {
      setForm(prev => ({ ...prev, limit_value: '' }));
    }
  }, [currentOptions, form.override_key, form.override_type, form.limit_value]);

  const resetForm = () => {
    setEditingId(null);
    setForm({
      id: '',
      company_id: companies[0]?.id ? String(companies[0].id) : '',
      override_type: 'feature',
      override_key: featureOptions[0]?.value ?? '',
      limit_value: '',
      notes: '',
    });
  };

  const handleEdit = (row: TenantFeatureOverrideRow) => {
    setEditingId(row.id);
    setForm({
      id: String(row.id),
      company_id: String(row.company_id),
      override_type: row.override_type,
      override_key: row.override_key,
      limit_value: row.limit_value === null || row.limit_value === undefined ? '' : String(row.limit_value),
      notes: row.notes ?? '',
    });
  };

  const handleSubmit = () => {
    if (!canSubmit) {
      return;
    }

    setIsLoading(true);

    router.post(route('settings.company-overrides.store'), {
      override: {
        id: form.id ? Number(form.id) : undefined,
        company_id: Number(form.company_id),
        override_type: form.override_type,
        override_key: form.override_key,
        limit_value: form.override_type === 'limit' && form.limit_value !== '' ? Number(form.limit_value) : null,
        notes: form.notes,
      },
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
        resetForm();
        router.reload({
          only: [
            'tenantFeatureOverrides',
            'tenantFeatureOverrideCompanies',
            'tenantFeatureOverrideFeatureOptions',
            'tenantFeatureOverrideLimitOptions',
          ],
        });
      },
      onError: () => {
        setIsLoading(false);
      },
    });
  };

  const handleDelete = (overrideId: number) => {
    if (!canEdit || isLoading) {
      return;
    }

    if (!window.confirm(t('Delete this override?'))) {
      return;
    }

    setIsLoading(true);

    router.delete(route('settings.company-overrides.destroy', overrideId), {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
        if (editingId === overrideId) {
          resetForm();
        }
        router.reload({ only: ['tenantFeatureOverrides'] });
      },
      onError: () => {
        setIsLoading(false);
      },
    });
  };

  const selectedCompany = companies.find(company => String(company.id) === form.company_id);
  const canSubmit = canEdit && !isLoading && form.company_id !== '' && form.override_key !== '' && currentOptions.length > 0;

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <ShieldCheck className="h-5 w-5" />
            {t('Company Overrides')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Grant feature access or lift a limit for a single company without changing the plan.') }
          </p>
        </div>

        {canEdit && (
          <div className="flex items-center gap-2 order-2 rtl:order-1">
            {editingId !== null && (
              <Button variant="outline" size="sm" onClick={resetForm} disabled={isLoading}>
                <RotateCcw className="h-4 w-4 mr-2" />
                {t('Cancel edit')}
              </Button>
            )}
            <Button size="sm" onClick={handleSubmit} disabled={!canSubmit}>
              <Save className="h-4 w-4 mr-2" />
              {isLoading ? t('Saving...') : editingId !== null ? t('Update Override') : t('Save Override')}
            </Button>
          </div>
        )}
      </CardHeader>

      <CardContent className="space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="space-y-4 rounded-lg border p-4">
            <div>
              <h3 className="text-sm font-semibold">{t('Override form')}</h3>
              <p className="text-xs text-muted-foreground">{t('Choose the company and the target feature or limit.')}</p>
            </div>

            <div className="space-y-3">
              <Label>{t('Company')}</Label>
              <Select
                value={form.company_id}
                onValueChange={(value) => setForm(prev => ({ ...prev, company_id: value }))}
                disabled={!canEdit}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t('Select company')} />
                </SelectTrigger>
                <SelectContent>
                  {companies.map((company) => (
                    <SelectItem key={company.id} value={String(company.id)}>
                      <div className="flex flex-col">
                        <span>{company.name}</span>
                        {company.email ? <span className="text-xs text-muted-foreground">{company.email}</span> : null}
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-3">
              <Label>{t('Override type')}</Label>
              <Select
                value={form.override_type}
                onValueChange={(value) => setForm(prev => ({
                  ...prev,
                  override_type: value as OverrideType,
                  override_key: value === 'feature' ? (featureOptions[0]?.value ?? '') : (limitOptions[0]?.value ?? ''),
                  limit_value: value === 'feature' ? '' : prev.limit_value,
                }))}
                disabled={!canEdit}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t('Select type')} />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="feature">{t('Feature')}</SelectItem>
                  <SelectItem value="limit">{t('Limit')}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-3">
              <Label>{t('Feature or limit')}</Label>
              <Select
                value={form.override_key}
                onValueChange={(value) => setForm(prev => ({ ...prev, override_key: value }))}
                disabled={!canEdit || currentOptions.length === 0}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t('Select feature or limit')} />
                </SelectTrigger>
                <SelectContent>
                  {currentOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      <div className="flex flex-col">
                        <span>{option.label}</span>
                        {option.description || option.summary ? (
                          <span className="text-xs text-muted-foreground">{option.description || option.summary}</span>
                        ) : null}
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {currentOptions.length === 0 ? (
                <p className="text-xs text-destructive">{t('No options are available for the selected type.')}</p>
              ) : null}
            </div>

            {form.override_type === 'limit' && (
              <div className="space-y-3">
                <Label>{t('Limit value')}</Label>
                <Input
                  type="number"
                  min="0"
                  value={form.limit_value}
                  onChange={(event) => setForm(prev => ({ ...prev, limit_value: event.target.value }))}
                  placeholder={t('Leave empty for unlimited')}
                  disabled={!canEdit}
                />
                <p className="text-xs text-muted-foreground">{t('Empty value means unlimited access for this limit.')}</p>
              </div>
            )}

            <div className="space-y-3">
              <Label>{t('Notes')}</Label>
              <Textarea
                value={form.notes}
                onChange={(event) => setForm(prev => ({ ...prev, notes: event.target.value }))}
                placeholder={t('Optional reason or approval note')}
                disabled={!canEdit}
                rows={4}
              />
            </div>

            {selectedCompany && (
              <div className="rounded-md bg-muted/40 p-3 text-xs text-muted-foreground">
                {t('Current target')}:
                <span className="ml-2 font-medium text-foreground">{selectedCompany.name}</span>
                {selectedCompany.email ? <span className="ml-2">({selectedCompany.email})</span> : null}
              </div>
            )}
          </div>

          <div className="space-y-4 rounded-lg border p-4">
            <div>
              <h3 className="text-sm font-semibold">{t('How it works')}</h3>
              <p className="text-xs text-muted-foreground">{t('The override is evaluated before the feature or limit gate for the target company.')}</p>
            </div>

            <div className="grid gap-3 text-sm">
              <div className="rounded-md bg-muted/40 p-3">
                <div className="font-medium">{t('Feature override')}</div>
                <div className="text-muted-foreground">{t('Lets a company access a specific feature even if the base plan or module check would block it.')}</div>
              </div>
              <div className="rounded-md bg-muted/40 p-3">
                <div className="font-medium">{t('Limit override')}</div>
                <div className="text-muted-foreground">{t('Lets a company use a custom limit or unlimited access for a single quantity rule.')}</div>
              </div>
              <div className="rounded-md bg-muted/40 p-3">
                <div className="font-medium">{t('Cache invalidation')}</div>
                <div className="text-muted-foreground">{t('Saving or deleting an override refreshes the company cache automatically.')}</div>
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-4">
          <div>
            <h3 className="text-sm font-semibold">{t('Current overrides')}</h3>
            <p className="text-xs text-muted-foreground">{t('Review all active exceptions created for companies.')}</p>
          </div>

          <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
              <thead className="bg-muted/60">
                <tr className="text-left">
                  <th className="px-4 py-3">{t('Company')}</th>
                  <th className="px-4 py-3">{t('Type')}</th>
                  <th className="px-4 py-3">{t('Key')}</th>
                  <th className="px-4 py-3">{t('Value')}</th>
                  <th className="px-4 py-3">{t('Notes')}</th>
                  <th className="px-4 py-3">{t('Updated by')}</th>
                  <th className="px-4 py-3 text-right">{t('Actions')}</th>
                </tr>
              </thead>
              <tbody>
                {overrides.length === 0 ? (
                  <tr>
                    <td className="px-4 py-8 text-center text-muted-foreground" colSpan={7}>
                      {t('No overrides have been created yet.')}
                    </td>
                  </tr>
                ) : (
                  overrides.map((override) => (
                    <tr key={override.id} className="border-t align-top">
                      <td className="px-4 py-3">
                        <div className="font-medium">{override.company_name}</div>
                        {override.company_email ? <div className="text-xs text-muted-foreground">{override.company_email}</div> : null}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={override.override_type === 'feature' ? 'secondary' : 'outline'}>
                          {override.override_type === 'feature' ? t('Feature') : t('Limit')}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 font-mono text-xs">{override.override_key}</td>
                      <td className="px-4 py-3">
                        {override.override_type === 'limit'
                          ? (override.limit_value === null || override.limit_value === undefined ? t('Unlimited') : override.limit_value)
                          : t('Enabled')}
                      </td>
                      <td className="px-4 py-3 max-w-xs text-muted-foreground">
                        {override.notes || '-'}
                      </td>
                      <td className="px-4 py-3">
                        <div>{override.updated_by_name || override.created_by_name || '-'}</div>
                        {override.updated_at ? <div className="text-xs text-muted-foreground">{override.updated_at}</div> : null}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex justify-end gap-2">
                          <Button variant="outline" size="sm" onClick={() => handleEdit(override)} disabled={!canEdit || isLoading}>
                            <PencilLine className="h-4 w-4 mr-1" />
                            {t('Edit')}
                          </Button>
                          <Button variant="destructive" size="sm" onClick={() => handleDelete(override.id)} disabled={!canEdit || isLoading}>
                            <Trash2 className="h-4 w-4 mr-1" />
                            {t('Delete')}
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
