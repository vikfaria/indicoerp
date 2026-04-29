import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Upload, X, Download } from 'lucide-react';
import { Ticket, TicketCategory } from './types';

interface EditTicketProps {
  ticket: Ticket & {
    attachments?: Array<{ name: string; path: string }>;
  };
  categories: TicketCategory[];
  staff: { id: number; name: string }[];
  clients: { id: number; name: string }[];
  vendors: { id: number; name: string }[];
}

interface TicketFormData {
  name: string;
  email: string;
  user_id?: number;
  account_type: 'staff' | 'client' | 'vendor' | 'custom';
  category: number;
  subject: string;
  status: 'open' | 'in_progress' | 'closed' | 'on_hold';
  description: string;
  attachments: File[];
  note?: string;
}

export default function Edit() {
  const { t } = useTranslation();
  const { ticket, categories, staff, clients, vendors } = usePage<EditTicketProps>().props;
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [existingAttachments, setExistingAttachments] = useState(
    ticket.attachments || []
  );

  const { data, setData, put, processing, errors } = useForm<TicketFormData>({
    name: ticket.name || '',
    email: ticket.email || '',
    user_id: ticket.user_id ? parseInt(ticket.user_id) : undefined,
    account_type: ticket.account_type || 'custom',
    category: ticket.category || 0,
    subject: ticket.subject || '',
    status: ticket.status || 'open',
    description: ticket.description || '',
    attachments: [],
    note: ticket.note || ''
  });

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const files = Array.from(e.target.files);
      setSelectedFiles(prev => [...prev, ...files]);
      setData('attachments', [...selectedFiles, ...files]);
    }
  };

  const removeFile = (index: number) => {
    const newFiles = selectedFiles.filter((_, i) => i !== index);
    setSelectedFiles(newFiles);
    setData('attachments', newFiles);
  };

  const removeExistingAttachment = (index: number) => {
    // Call API to remove attachment
    window.location.href = route('support-tickets.attachment.destroy', [ticket.id, index]);
  };

  const getUserOptions = () => {
    switch (data.account_type) {
      case 'staff':
        return staff;
      case 'client':
        return clients;
      case 'vendor':
        return vendors;
      default:
        return [];
    }
  };

  const handleUserChange = (userId: string) => {
    const user = getUserOptions().find(u => u.id === parseInt(userId));
    if (user) {
      setData(prev => ({
        ...prev,
        user_id: user.id,
        name: user.name,
        email: user.email || prev.email
      }));
    }
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => {
      if (key === 'attachments') {
        selectedFiles.forEach((file, index) => {
          formData.append(`attachments[${index}]`, file);
        });
      } else if (value !== null && value !== undefined) {
        formData.append(key, value.toString());
      }
    });

    put(route('support-tickets.update', ticket.id), {
      data: formData,
      forceFormData: true,
      onSuccess: () => {
        // Handle success
      }
    });
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
         { label: t('Support Tickets'), url: route('support-tickets.index') },
        { label: t('Edit Ticket') }
      ]}
      pageTitle={t('Edit Ticket #:id', { id: ticket.ticket_id })}
      backUrl={route('support-tickets.index')}
    >
      <Head title={t('Edit Ticket')} />

      <Card>
        <CardHeader>
          <CardTitle>{t('Edit Ticket Information')}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-6">
            {/* Account Type Selection */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="account_type">{t('Account Type')}</Label>
                <Select 
                  value={data.account_type} 
                  onValueChange={(value: any) => setData('account_type', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="custom">{t('Custom')}</SelectItem>
                    <SelectItem value="staff">{t('Staff')}</SelectItem>
                    <SelectItem value="client">{t('Client')}</SelectItem>
                    <SelectItem value="vendor">{t('Vendor')}</SelectItem>
                  </SelectContent>
                </Select>
                <InputError message={errors.account_type} />
              </div>

              {data.account_type !== 'custom' && (
                <div>
                  <Label htmlFor="user_id">{t('Select User')}</Label>
                  <Select 
                    value={data.user_id?.toString() || ''} 
                    onValueChange={handleUserChange}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('Select a user')} />
                    </SelectTrigger>
                    <SelectContent>
                      {getUserOptions().map(user => (
                        <SelectItem key={user.id} value={user.id.toString()}>
                          {user.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={errors.user_id} />
                </div>
              )}
            </div>

            {/* Name and Email */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name" required>{t('Name')}</Label>
                <Input
                  id="name"
                  value={data.name}
                  onChange={(e) => setData('name', e.target.value)}
                  placeholder={t('Enter name')}
                  disabled={data.account_type !== 'custom'}
                  required
                />
                <InputError message={errors.name} />
              </div>

              <div>
                <Label htmlFor="email" required>{t('Email')}</Label>
                <Input
                  id="email"
                  type="email"
                  value={data.email}
                  onChange={(e) => setData('email', e.target.value)}
                  placeholder={t('Enter email')}
                  disabled={data.account_type !== 'custom'}
                  required
                />
                <InputError message={errors.email} />
              </div>
            </div>

            {/* Category and Status */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="category" required>{t('Category')}</Label>
                <Select 
                  value={data.category.toString()} 
                  onValueChange={(value) => setData('category', parseInt(value))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select category')} />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map(category => (
                      <SelectItem key={category.id} value={category.id.toString()}>
                        <div className="flex items-center gap-2">
                          <div 
                            className="w-3 h-3 rounded-full" 
                            style={{ backgroundColor: category.color }}
                          />
                          {category.name}
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={errors.category} />
              </div>

              <div>
                <Label htmlFor="status" required>{t('Status')}</Label>
                <Select 
                  value={data.status} 
                  onValueChange={(value: any) => setData('status', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="open">{t('Open')}</SelectItem>
                    <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                    <SelectItem value="on_hold">{t('On Hold')}</SelectItem>
                    <SelectItem value="closed">{t('Closed')}</SelectItem>
                  </SelectContent>
                </Select>
                <InputError message={errors.status} />
              </div>
            </div>

            {/* Subject */}
            <div>
              <Label htmlFor="subject" required>{t('Subject')}</Label>
              <Input
                id="subject"
                value={data.subject}
                onChange={(e) => setData('subject', e.target.value)}
                placeholder={t('Enter ticket subject')}
                required
              />
              <InputError message={errors.subject} />
            </div>

            {/* Description */}
            <div>
              <Label htmlFor="description" required>{t('Description')}</Label>
              <Textarea
                id="description"
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
                placeholder={t('Enter ticket description')}
                rows={4}
                required
              />
              <InputError message={errors.description} />
            </div>

            {/* Existing Attachments */}
            {existingAttachments.length > 0 && (
              <div>
                <Label>{t('Current Attachments')}</Label>
                <div className="mt-2 space-y-2">
                  {existingAttachments.map((attachment, index) => (
                    <div key={index} className="flex items-center justify-between p-2 bg-gray-100 rounded">
                      <div className="flex items-center gap-2">
                        <Download className="w-4 h-4" />
                        <a 
                          href={`/storage/${attachment.path}`} 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="text-sm text-blue-600 hover:underline"
                        >
                          {attachment.name}
                        </a>
                      </div>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeExistingAttachment(index)}
                        className="text-red-600 hover:text-red-700"
                      >
                        <X className="w-4 h-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* New File Attachments */}
            <div>
              <Label>{t('Add New Attachments')}</Label>
              <div className="mt-2">
                <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                  <div className="flex flex-col items-center justify-center pt-5 pb-6">
                    <Upload className="w-8 h-8 mb-4 text-gray-500" />
                    <p className="mb-2 text-sm text-gray-500">
                      <span className="font-semibold">{t('Click to upload')}</span> {t('or drag and drop')}
                    </p>
                    <p className="text-xs text-gray-500">{t('PNG, JPG, PDF up to 10MB')}</p>
                  </div>
                  <input
                    type="file"
                    className="hidden"
                    multiple
                    onChange={handleFileChange}
                    accept=".png,.jpg,.jpeg,.pdf,.doc,.docx"
                  />
                </label>
              </div>

              {selectedFiles.length > 0 && (
                <div className="mt-4 space-y-2">
                  {selectedFiles.map((file, index) => (
                    <div key={index} className="flex items-center justify-between p-2 bg-gray-100 rounded">
                      <span className="text-sm">{file.name}</span>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeFile(index)}
                      >
                        <X className="w-4 h-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              )}
              <InputError message={errors.attachments} />
            </div>

            {/* Note */}
            <div>
              <Label htmlFor="note">{t('Internal Note')}</Label>
              <Textarea
                id="note"
                value={data.note}
                onChange={(e) => setData('note', e.target.value)}
                placeholder={t('Add internal note (optional)')}
                rows={3}
              />
              <InputError message={errors.note} />
            </div>

            {/* Submit Buttons */}
            <div className="flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => window.history.back()}
              >
                {t('Cancel')}
              </Button>
              <Button type="submit" disabled={processing}>
                {processing ? t('Updating...') : t('Update Ticket')}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}