import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import FormField from '../../components/FormField';
import { useFormFields } from '@/hooks/useFormFields';

interface FormFieldData {
  id: number;
  label: string;
  type: string;
  required: boolean;
  placeholder?: string;
  options?: string[];
  order: number;
}

interface Form {
  id: number;
  name: string;
  code: string;
  default_layout?: string;
  fields: FormFieldData[];
}

interface PublicFormProps {
  form: Form;
}

export default function PublicForm({ form }: PublicFormProps) {
  const { t } = useTranslation();
  const layout = (form.default_layout as 'single' | 'two-column' | 'card') || 'single';

  const integrationFields = useFormFields('getIntegrationFields', {}, () => { }, {}, 'create', t, 'FormBuilder');

  const { data, setData, post, processing, errors } = useForm(
    form.fields.reduce((acc, field) => {
      acc[`field_${field.id}`] = field.type === 'checkbox' ? false : '';
      return acc;
    }, {} as Record<string, any>)
  );

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('formbuilder.public.form.submit', form.code), {
      onSuccess: () => {
        // Reset form data
        const resetData = form.fields.reduce((acc, field) => {
          acc[`field_${field.id}`] = field.type === 'checkbox' ? false : '';
          return acc;
        }, {} as Record<string, any>);
        setData(resetData);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  };

  const renderSingleLayout = () => (
    <div className="space-y-6">
      {form.fields.sort((a, b) => a.order - b.order).map((field) => (
        <FormField
          key={field.id}
          field={field}
          value={data[`field_${field.id}`]}
          onChange={(value) => setData(`field_${field.id}`, value)}
          error={errors[`field_${field.id}`]}
        />
      ))}
    </div>
  );

  const renderTwoColumnLayout = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      {form.fields.sort((a, b) => a.order - b.order).map((field) => (
        <FormField
          key={field.id}
          field={field}
          value={data[`field_${field.id}`]}
          onChange={(value) => setData(`field_${field.id}`, value)}
          error={errors[`field_${field.id}`]}
        />
      ))}
    </div>
  );

  const renderCardLayout = () => (
    <div className="space-y-4">
      {form.fields.sort((a, b) => a.order - b.order).map((field) => (
        <Card key={field.id}>
          <CardContent className="p-4">
            <FormField
              field={field}
              value={data[`field_${field.id}`]}
              onChange={(value) => setData(`field_${field.id}`, value)}
              error={errors[`field_${field.id}`]}
            />
          </CardContent>
        </Card>
      ))}
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <Head title={form.name} />

      <div className="max-w-2xl mx-auto px-4">

        <Card>
          <CardHeader>
            <CardTitle className="text-2xl">{form.name}</CardTitle>
            <p className="text-gray-600">{t('Please fill out all required fields')}</p>
          </CardHeader>
          <CardContent>

            <form onSubmit={handleSubmit}>
              {errors.form && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                  <p className="text-red-600 text-sm">{errors.form}</p>
                </div>
              )}
              
              {layout === 'single' && renderSingleLayout()}
              {layout === 'two-column' && renderTwoColumnLayout()}
              {layout === 'card' && renderCardLayout()}

              <div className="pt-6 mt-6 border-t">
                <Button
                  type="submit"
                  disabled={processing}
                  className="w-full"
                  size="lg"
                >
                  {processing ? t('Submitting...') : t('Submit Form')}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
      
      {/* Integration Widgets (Tawk.to, WhatsApp, etc.) */}
       {integrationFields.map((field) => (
                <div key={field.id}>
                    {field.component}
                </div>
            ))}
    </div>
  );
}