import { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import MediaPicker from '@/components/MediaPicker';

interface Category {
  id: number;
  name: string;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface Field {
  id: number;
  name: string;
  type: string;
  placeholder: string;
  width: string;
  is_required: boolean;
  custom_id: string;
}

interface CreateProps {
  categories: Category[];
  staff: User[];
  clients: User[];
  vendors: User[];
  allFields: Field[];
  customFields: Field[];
}

export default function Create({ categories, staff, clients, vendors, allFields, customFields }: CreateProps) {
  const { t } = useTranslation();
  const [accountType, setAccountType] = useState('custom');
  
  const { data, setData, post, processing, errors } = useForm({
    account_type: 'custom',
    name: '',
    email: '',
    user_id: '',
    category: '',
    status: 'In Progress',
    subject: '',
    description: '',
    attachments: [] as string[],
    fields: {} as Record<string, any>
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('support-tickets.store'));
  };

  const handleAccountTypeChange = (value: string) => {
    setAccountType(value);
    setData('account_type', value);
    
    if (value !== 'custom') {
      setData('name', '');
      setData('user_id', '');
      setData('email', '');
    }
  };

  const handleUserSelect = async (userId: string, type: string) => {
    setData('user_id', userId);
    
    // Find user data from the appropriate array
    let selectedUser;
    if (type === 'staff') {
      selectedUser = staff.find(user => user.id.toString() === userId);
    } else if (type === 'client') {
      selectedUser = clients.find(user => user.id.toString() === userId);
    } else if (type === 'vendor') {
      selectedUser = vendors.find(user => user.id.toString() === userId);
    }
    
    if (selectedUser) {
      setData('email', selectedUser.email);
    }
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
         { label: t('Support Tickets'), url: route('support-tickets.index') },
        { label: t('Create') }
      ]}
      pageTitle={t('Create Ticket')}
      backUrl={route('support-tickets.index')}
    >
      <Head title={t('Create Ticket')} />
      
      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>{t('Ticket Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">


            {/* Account Type Selection - always show first */}
            <div className="mb-6">
              <Label>{t('Account Type')}</Label>
              <RadioGroup value={accountType} onValueChange={handleAccountTypeChange} className="flex gap-6 mt-2">
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="custom" id="custom" />
                  <Label htmlFor="custom">{t('Custom')}</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="staff" id="staff" />
                  <Label htmlFor="staff">{t('Staff')}</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="client" id="client" />
                  <Label htmlFor="client">{t('Client')}</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="vendor" id="vendor" />
                  <Label htmlFor="vendor">{t('Vendor')}</Label>
                </div>
              </RadioGroup>
            </div>
            

            



            
            {/* All Fields ordered by 'order' field */}
            {allFields && allFields.length > 0 && (
              <div className="grid grid-cols-12 gap-4">
                {allFields.sort((a, b) => a.order - b.order).map((field) => {
                  const colSpan = field.width === '12' ? 'col-span-12' : 
                                field.width === '6' ? 'col-span-6' : 
                                field.width === '4' ? 'col-span-4' : 
                                field.width === '3' ? 'col-span-3' : 'col-span-12';
                  
                  // Handle default fields by custom_id
                  if (field.custom_id == 1) { // Name field
                    return accountType === 'custom' ? (
                      <div key={field.id} className="col-span-6">
                        <Label htmlFor="name" required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id="name"
                          value={data.name}
                          onChange={(e) => setData('name', e.target.value)}
                          placeholder={t(field.placeholder)}
                          className={errors.name ? 'border-red-500' : ''}
                          required={field.is_required}
                        />
                        {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                      </div>
                    ) : (
                      <div key={field.id} className="col-span-6">
                        <Label required>
                          {accountType === 'staff' && t('Select Staff')}
                          {accountType === 'client' && t('Select Client')}
                          {accountType === 'vendor' && t('Select Vendor')}
                        </Label>
                        <Select 
                          value={data.user_id} 
                          onValueChange={(value) => {
                            handleUserSelect(value, accountType);
                          }}
                        >
                          <SelectTrigger className={errors.user_id ? 'border-red-500' : ''}>
                            <SelectValue placeholder={
                              accountType === 'staff' ? t('Select Staff') :
                              accountType === 'client' ? t('Select Client') :
                              t('Select Vendor')
                            } />
                          </SelectTrigger>
                          <SelectContent>
                            {(accountType === 'staff' ? staff :
                              accountType === 'client' ? clients :
                              vendors)?.map((user) => (
                              <SelectItem key={user.id} value={user.id.toString()}>
                                {user.name}
                              </SelectItem>
                            )) || []}
                          </SelectContent>
                        </Select>
                        {errors.user_id && <p className="text-red-500 text-sm mt-1">{errors.user_id}</p>}
                      </div>
                    );
                  }
                  
                  if (field.custom_id == 2) { // Email field
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label htmlFor="email" required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id="email"
                          type="email"
                          value={data.email}
                          onChange={(e) => setData('email', e.target.value)}
                          placeholder={t(field.placeholder)}
                          readOnly={accountType !== 'custom'}
                          className={errors.email ? 'border-red-500' : ''}
                          required={field.is_required}
                        />
                        {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                      </div>
                    );
                  }
                  
                  if (field.custom_id == 3) { // Category field
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label required={field.is_required}>{t(field.name)}</Label>
                        <Select onValueChange={(value) => setData('category', value)}>
                          <SelectTrigger className={errors.category ? 'border-red-500' : ''}>
                            <SelectValue placeholder={t(field.placeholder)} />
                          </SelectTrigger>
                          <SelectContent>
                            {categories?.map((category) => (
                              <SelectItem key={category.id} value={category.id.toString()}>
                                {category.name}
                              </SelectItem>
                            )) || []}
                          </SelectContent>
                        </Select>
                        {errors.category && <p className="text-red-500 text-sm mt-1">{errors.category}</p>}
                      </div>
                    );
                  }
                  
                  if (field.custom_id == 4) { // Subject field
                    return (
                      <>
                        <div key={field.id} className="col-span-6">
                          <Label htmlFor="subject" required={field.is_required}>{t(field.name)}</Label>
                          <Input
                            id="subject"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            placeholder={t(field.placeholder)}
                            className={errors.subject ? 'border-red-500' : ''}
                            required={field.is_required}
                          />
                          {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                        </div>
                        {/* Status field - show after subject */}
                        <div className="col-span-6">
                          <Label required>{t('Status')}</Label>
                          <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                              <SelectValue placeholder={t('Select Status')} />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="In Progress">{t('In Progress')}</SelectItem>
                              <SelectItem value="On Hold">{t('On Hold')}</SelectItem>
                              <SelectItem value="Closed">{t('Closed')}</SelectItem>
                            </SelectContent>
                          </Select>
                          {errors.status && <p className="text-red-500 text-sm mt-1">{errors.status}</p>}
                        </div>
                      </>
                    );
                  }
                  
                  if (field.custom_id == 5) { // Description field
                    return (
                      <div key={field.id} className="col-span-12">
                        <Label htmlFor="description" required={field.is_required}>{t(field.name)}</Label>
                        <RichTextEditor
                          value={data.description}
                          onChange={(value) => setData('description', value)}
                          placeholder={t(field.placeholder)}
                          rows={3}
                          className={errors.description ? 'border-red-500' : ''}
                        />
                        {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                      </div>
                    );
                  }
                  
                  if (field.custom_id == 6) { // Attachments field
                    return (
                      <div key={field.id} className="col-span-12">
                        <Label>{t(field.name)} <small>({t(field.placeholder)})</small></Label>
                        <MediaPicker
                          value={data.attachments}
                          onChange={(files) => setData('attachments', Array.isArray(files) ? files : [])}
                          placeholder={t('Browse files...')}
                          multiple
                          showPreview={false}
                          className={errors.attachments ? 'border-red-500' : ''}
                        />
                        {errors.attachments && <p className="text-red-500 text-sm mt-1">{errors.attachments}</p>}
                      </div>
                    );
                  }
                  
                  // Handle custom fields (custom_id > 6)
                  if (field.custom_id > 6 && field.type === 'text') {
                    return (
                      <div key={field.id} className="col-span-6">
                        <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id={`field-${field.id}`}
                          value={data.fields[field.id] || ''}
                          onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                          placeholder={t(field.placeholder)}
                          required={field.is_required}
                        />
                      </div>
                    );
                  }
                  
                  if (field.type === 'email') {
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id={`field-${field.id}`}
                          type="email"
                          value={data.fields[field.id] || ''}
                          onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                          placeholder={t(field.placeholder)}
                          required={field.is_required}
                        />
                      </div>
                    );
                  }
                  
                  if (field.type === 'number') {
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id={`field-${field.id}`}
                          type="number"
                          value={data.fields[field.id] || ''}
                          onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                          placeholder={t(field.placeholder)}
                          required={field.is_required}
                        />
                      </div>
                    );
                  }
                  
                  if (field.type === 'date') {
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                        <Input
                          id={`field-${field.id}`}
                          type="date"
                          value={data.fields[field.id] || ''}
                          onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                          placeholder={t(field.placeholder)}
                          required={field.is_required}
                        />
                      </div>
                    );
                  }
                  
                  if (field.type === 'textarea') {
                    return (
                      <div key={field.id} className={colSpan}>
                        <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                        <Textarea
                          id={`field-${field.id}`}
                          value={data.fields[field.id] || ''}
                          onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                          placeholder={t(field.placeholder)}
                          required={field.is_required}
                          rows={3}
                        />
                      </div>
                    );
                  }
                  
                  return null;
                })}
              </div>
            )}

            <div className="flex justify-end gap-2">
              <Button variant="outline" asChild>
                <Link href={route('support-tickets.index')}>{t('Cancel')}</Link>
              </Button>
              <Button type="submit" disabled={processing}>
                {t('Create')}
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>
    </AuthenticatedLayout>
  );
}