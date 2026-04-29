import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import { Edit } from 'lucide-react';
import ConversionSetup from '../../components/ConversionSetup';

interface ConversionPageProps {
  form: {
    id: number;
    name: string;
  };
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
  users: Array<{ id: number; name: string; }>;
  lead_pipelines?: Array<{ id: number; name: string; }>;
  deal_pipelines?: Array<{ id: number; name: string; }>;
  clients?: Array<{ id: number; name: string; }>;
  accounts?: Array<{ id: number; name: string; }>;
  opportunity_stages?: Array<{ id: number; name: string; }>;
  contract_types?: Array<{ id: number; name: string; }>;
  books?: Array<{ id: number; name: string; }>;
  auth: any;
}

export default function ConversionPage({ form, auth, conversion, available_modules, form_fields, users, lead_pipelines, deal_pipelines, clients, accounts, opportunity_stages, contract_types, books }: ConversionPageProps) {
  const { t } = useTranslation();

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Form Builder'), url: route('formbuilder.forms.index') },
        { label: t('Convert To') },
      ]}
      pageTitle={`${t('Convert To')} - ${form.name}`}
      backUrl={route('formbuilder.forms.index')}
      pageActions={
        <div className="flex gap-2">
          <TooltipProvider>
            {auth.user?.permissions?.includes('edit-formbuilder-form') && (
              <Tooltip >
                <TooltipTrigger asChild>
                  <Button size="sm" onClick={() => router.visit(route('formbuilder.forms.edit', form.id))}>
                    <Edit className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{t('Edit')}</p>
                </TooltipContent>
              </Tooltip>
            )}
          </TooltipProvider>
        </div>
      }
    >
      <Head title={`${t('Convert To')} - ${form.name}`} />

      <ConversionSetup
        formId={form.id}
        auth={auth}
        initialData={{
          conversion,
          available_modules,
          form_fields,
          users,
          lead_pipelines,
          deal_pipelines,
          clients,
          accounts,
          opportunity_stages,
          contract_types,
          books,
        }}
      />
    </AuthenticatedLayout>
  );
}