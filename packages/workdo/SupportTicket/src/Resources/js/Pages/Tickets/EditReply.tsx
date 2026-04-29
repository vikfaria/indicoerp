import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import MediaPicker from '@/components/MediaPicker';
import { Edit, Send, Download, Paperclip, ChevronDown, ChevronUp, User, Calendar, Tag, AlertCircle, MessageSquare, StickyNote } from 'lucide-react';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

interface Attachment {
  name: string;
  path: string;
}

interface Conversion {
  id: number;
  description: string;
  sender: string;
  created_at: string;
  attachments: Attachment[];
  replyBy: { name: string };
}

interface Category {
  id: number;
  name: string;
  color: string;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface TicketData {
  id: number;
  ticket_id: string;
  name: string;
  email: string;
  user_id?: number;
  account_type: string;
  category: number;
  subject: string;
  status: string;
  description: string;
  note?: string;
  attachments: Attachment[];
  fields?: Record<string, any>;
  category_info?: Category;
  conversions: Conversion[];
  created_at: string;
  updated_at: string;
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

interface EditReplyProps {
  ticket: TicketData;
  categories: Category[];
  staff: User[];
  clients: User[];
  vendors: User[];
  allFields: Field[];
  customFields: Field[];
}

const isEditorEmpty = (htmlContent: string) => {
  if (!htmlContent) return true;
  const stripped = htmlContent.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, '').trim();
  if (stripped) return false;
  return !htmlContent.includes('<img') && !htmlContent.includes('<iframe');
};

export default function EditReply({ ticket, categories, staff, clients, vendors, allFields, customFields }: EditReplyProps) {
  const { t } = useTranslation();
  const [isEditOpen, setIsEditOpen] = useState(false);

  const [conversations, setConversations] = useState<Conversion[]>(ticket.conversions || []);


  const { data: editData, setData: setEditData, put, processing: editProcessing, errors: editErrors } = useForm({
    name: ticket.name,
    email: ticket.email,
    user_id: ticket.user_id ? ticket.user_id.toString() : '',
    account_type: ticket.account_type,
    category: ticket.category?.toString() || '',
    subject: ticket.subject,
    status: ticket.status,
    description: ticket.description,
    fields: ticket.fields || {} as Record<string, any>
  });

  const { data: replyData, setData: setReplyData, post, processing: replyProcessing, errors: replyErrors, reset } = useForm({
    description: '',
    attachments: [] as string[],
  });

  const { data: noteData, setData: setNoteData, post: postNote, processing: noteProcessing, errors: noteErrors } = useForm({
    note: ticket.note || '',
  });

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(route('support-tickets.update', ticket.id), {
      onSuccess: () => {
        setIsEditOpen(false);
      }
    });
  };

  const handleReplySubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (isEditorEmpty(replyData.description) && (!replyData.attachments || replyData.attachments.length === 0)) {
      return;
    }

    post(route('support-ticket.admin-send-conversion.store', ticket.id), {
      onSuccess: () => {
        // Add new conversation to state immediately
        const newConversation = {
          id: Date.now(),
          description: replyData.description,
          sender: 'admin',
          created_at: new Date().toISOString(),
          attachments: replyData.attachments.map(path => {
            const fileName = path.split('/').pop() || 'file';
            const media = path.includes('media/') ? fileName : fileName;
            return {
              name: fileName.replace(/^\d+_/, ''), // Remove timestamp prefix
              path: media
            };
          }),
          replyBy: { name: 'Admin' }
        };
        setConversations(prev => [...prev, newConversation]);
        reset();
      }
    });
  };

  const handleNoteSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    postNote(route('support-ticket.note.store', ticket.id));
  };

  const handleAccountTypeChange = (value: string) => {
    setEditData('account_type', value);
    setEditData('user_id', '');
  };

  const handleUserChange = (value: string) => {
    setEditData('user_id', value);
    
    // Find user and update name/email
    let user = null;
    if (editData.account_type === 'staff') {
      user = staff.find(u => u.id.toString() === value);
    } else if (editData.account_type === 'client') {
      user = clients.find(u => u.id.toString() === value);
    } else if (editData.account_type === 'vendor') {
      user = vendors.find(u => u.id.toString() === value);
    }
    
    if (user) {
      setEditData('name', user.name);
      setEditData('email', user.email);
    }
  };

  const getStatusColor = (status: string) => {
    const colors = {
      'open': 'bg-green-100 text-green-800',
      'Open': 'bg-green-100 text-green-800',
      'In Progress': 'bg-blue-100 text-blue-800',
      'closed': 'bg-red-100 text-red-800',
      'Closed': 'bg-red-100 text-red-800',
      'On Hold': 'bg-yellow-100 text-yellow-800'
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Support Tickets'), url: route('support-tickets.index') },
        {label: t('Edit & Reply')}
      ]}
      pageTitle={`${t('Ticket')} - ${ticket.ticket_id}`}
      backUrl={route('support-tickets.index')}
    >
      <Head title={`${t('Edit Ticket')} - ${ticket.ticket_id}`} />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Ticket Header */}
          <Card className="border-l-4 border-l-blue-500">
            <CardHeader className="pb-4">
              <div className="flex items-start justify-between">
                <div className="space-y-2">
                  <div className="flex items-center gap-2">
                    <Badge variant="outline" className="text-xs">
                      #{ticket.ticket_id}
                    </Badge>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(ticket.status)}`}>
                      {ticket.status}
                    </span>
                  </div>
                  <h1 className="text-2xl font-bold text-gray-900">{ticket.subject}</h1>
                  <div className="flex items-center gap-4 text-sm text-gray-600">
                    <div className="flex items-center gap-1">
                      <User className="h-4 w-4" />
                      {ticket.name}
                    </div>
                    <div className="flex items-center gap-1">
                      <Calendar className="h-4 w-4" />
                      {formatDate(ticket.created_at)}
                    </div>
                    <div className="flex items-center gap-1">
                      <Tag className="h-4 w-4" />
                      {ticket.category_info?.name || 'No Category'}
                    </div>
                  </div>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setIsEditOpen(!isEditOpen)}
                  className="flex items-center gap-2"
                >
                  <Edit className="h-4 w-4" />
                  {t('Edit')}
                  {isEditOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                </Button>
              </div>
            </CardHeader>
          </Card>

          {/* Edit Form */}
          {isEditOpen && (
            <Card className="border-orange-200 bg-orange-50/30">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-orange-800">
                  <Edit className="h-5 w-5" />
                  {t('Edit Ticket Information')}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleEditSubmit} className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label>{t('Account Type')}</Label>
                      <div className="flex gap-4 mt-2">
                        {['custom', 'staff', 'client', 'vendor'].map((type) => (
                          <label key={type} className="flex items-center gap-2">
                            <input
                              type="radio"
                              name="account_type"
                              value={type}
                              checked={editData.account_type === type}
                              onChange={(e) => handleAccountTypeChange(e.target.value)}
                            />
                            {t(type.charAt(0).toUpperCase() + type.slice(1))}
                          </label>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* All Fields ordered by 'order' field */}
                  <div className="grid grid-cols-12 gap-4">
                    {allFields && allFields.length > 0 ? allFields.sort((a, b) => a.order - b.order).map((field) => {
                      if (field.custom_id == 1) { // Name field
                        return editData.account_type === 'custom' ? (
                          <div key={field.id} className="col-span-6">
                            <Label htmlFor="name" required={field.is_required}>{t(field.name)}</Label>
                            <Input
                              id="name"
                              value={editData.name}
                              onChange={(e) => setEditData('name', e.target.value)}
                              error={editErrors.name}
                            />
                          </div>
                        ) : (
                          <div key={field.id} className="col-span-6">
                            <Label required>{t('Select User')}</Label>
                            <Select value={editData.user_id} onValueChange={handleUserChange}>
                              <SelectTrigger>
                                <SelectValue placeholder={`Select ${editData.account_type}`} />
                              </SelectTrigger>
                              <SelectContent>
                                {(editData.account_type === 'staff' ? staff :
                                  editData.account_type === 'client' ? clients : vendors
                                ).map((user) => (
                                  <SelectItem key={user.id} value={user.id.toString()}>
                                    {user.name}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          </div>
                        );
                      }
                      
                      if (field.custom_id == 2) { // Email field
                        return (
                          <div key={field.id} className="col-span-6">
                            <Label htmlFor="email" required={field.is_required}>{t(field.name)}</Label>
                            <Input
                              id="email"
                              type="email"
                              value={editData.email}
                              onChange={(e) => setEditData('email', e.target.value)}
                              error={editErrors.email}
                            />
                          </div>
                        );
                      }
                      
                      if (field.custom_id == 3) { // Category field
                        return (
                          <div key={field.id} className="col-span-6">
                            <Label required={field.is_required}>{t(field.name)}</Label>
                            <Select value={editData.category} onValueChange={(value) => setEditData('category', value)}>
                              <SelectTrigger>
                                <SelectValue placeholder={t(field.placeholder)} />
                              </SelectTrigger>
                              <SelectContent>
                                {categories.map((category) => (
                                  <SelectItem key={category.id} value={category.id.toString()}>
                                    {category.name}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
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
                                value={editData.subject}
                                onChange={(e) => setEditData('subject', e.target.value)}
                                error={editErrors.subject}
                              />
                            </div>
                            {/* Status field - show after subject */}
                            <div className="col-span-6">
                              <Label required>{t('Status')}</Label>
                              <Select value={editData.status} onValueChange={(value) => setEditData('status', value)}>
                                <SelectTrigger>
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="open">{t('Open')}</SelectItem>
                                  <SelectItem value="In Progress">{t('In Progress')}</SelectItem>
                                  <SelectItem value="On Hold">{t('On Hold')}</SelectItem>
                                  <SelectItem value="Closed">{t('Closed')}</SelectItem>
                                </SelectContent>
                              </Select>
                            </div>
                          </>
                        );
                      }
                      
                      if (field.custom_id == 5) { // Description field
                        return (
                          <div key={field.id} className="col-span-12">
                            <Label htmlFor="description" required={field.is_required}>{t(field.name)}</Label>
                            <RichTextEditor
                              content={editData.description}
                              onChange={(value) => setEditData('description', value)}
                            />
                          </div>
                        );
                      }
                      
                      // Handle custom fields (custom_id > 6)
                      if (field.custom_id > 6) {
                        if (field.type === 'text') {
                          return (
                            <div key={field.id} className="col-span-6">
                              <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                              <Input
                                id={`field-${field.id}`}
                                value={editData.fields[field.id] || ''}
                                onChange={(e) => setEditData('fields', {...editData.fields, [field.id]: e.target.value})}
                                placeholder={t(field.placeholder)}
                                required={field.is_required}
                              />
                            </div>
                          );
                        }
                        
                        if (field.type === 'email') {
                          return (
                            <div key={field.id} className="col-span-6">
                              <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                              <Input
                                id={`field-${field.id}`}
                                type="email"
                                value={editData.fields[field.id] || ''}
                                onChange={(e) => setEditData('fields', {...editData.fields, [field.id]: e.target.value})}
                                placeholder={t(field.placeholder)}
                                required={field.is_required}
                              />
                            </div>
                          );
                        }
                        
                        if (field.type === 'number') {
                          return (
                            <div key={field.id} className="col-span-6">
                              <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                              <Input
                                id={`field-${field.id}`}
                                type="number"
                                value={editData.fields[field.id] || ''}
                                onChange={(e) => setEditData('fields', {...editData.fields, [field.id]: e.target.value})}
                                placeholder={t(field.placeholder)}
                                required={field.is_required}
                              />
                            </div>
                          );
                        }
                        
                        if (field.type === 'date') {
                          return (
                            <div key={field.id} className="col-span-6">
                              <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                              <Input
                                id={`field-${field.id}`}
                                type="date"
                                value={editData.fields[field.id] || ''}
                                onChange={(e) => setEditData('fields', {...editData.fields, [field.id]: e.target.value})}
                                placeholder={t(field.placeholder)}
                                required={field.is_required}
                              />
                            </div>
                          );
                        }
                        
                        if (field.type === 'textarea') {
                          return (
                            <div key={field.id} className="col-span-12">
                              <Label htmlFor={`field-${field.id}`} required={field.is_required}>{t(field.name)}</Label>
                              <RichTextEditor
                                content={editData.fields[field.id] || ''}
                                onChange={(value) => setEditData('fields', {...editData.fields, [field.id]: value})}
                                placeholder={t(field.placeholder)}
                              />
                            </div>
                          );
                        }
                      }
                      
                      return null;
                    }) : null}
                  </div>

                  <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                      {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={editProcessing}>
                      {editProcessing ? t('Updating...') : t('Update Ticket')}
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          )}

          {/* Original Ticket */}
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center gap-3">
                <Avatar className="h-10 w-10">
                  <AvatarFallback className="bg-blue-500 text-white">
                    {ticket.name.charAt(0).toUpperCase()}
                  </AvatarFallback>
                </Avatar>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold">{ticket.name}</span>
                    <Badge variant="outline" className="text-xs">Customer</Badge>
                  </div>
                  <p className="text-sm text-gray-600">{ticket.email} • {formatDate(ticket.created_at)}</p>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="prose max-w-none" dangerouslySetInnerHTML={{ __html: ticket.description }} />
              
              {ticket.attachments && ticket.attachments.length > 0 && (
                <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                  <div className="flex items-center gap-2 mb-3">
                    <Paperclip className="h-4 w-4 text-blue-600" />
                    <span className="font-medium text-blue-800">{t('Attachments')}</span>
                  </div>
                  <div className="space-y-2">
                    {ticket.attachments.map((attachment, index) => (
                      <div key={index} className="flex items-center justify-between p-2 bg-white rounded border">
                        <span className="text-sm">{attachment.name}</span>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-8 w-8 p-0 text-blue-600 hover:text-blue-800"
                          onClick={() => {
                            fetch(route('support-ticket.attachment.download'), {
                              method: 'POST',
                              headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                              },
                              body: JSON.stringify({ path: attachment.path })
                            })
                            .then(response => response.json())
                            .then(data => {
                              if (data.status && data.file_url) {
                                const link = document.createElement('a');
                                link.href = data.file_url;
                                link.download = attachment.name;
                                link.click();
                              }
                            });
                          }}
                        >
                          <Download className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Conversations */}
          {conversations && conversations.map((conversion) => (
            <Card key={conversion.id} className={conversion.sender === 'admin' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-gray-300'}>
              <CardHeader className="pb-3">
                <div className="flex items-center gap-3">
                  <Avatar className="h-10 w-10">
                    <AvatarFallback className={conversion.sender === 'admin' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white'}>
                      {conversion.sender === 'admin' ? 'A' : 'U'}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold">{conversion.replyBy?.name || 'User'}</span>
                      <Badge variant={conversion.sender === 'admin' ? 'default' : 'outline'} className="text-xs">
                        {conversion.sender === 'admin' ? 'Admin' : 'Customer'}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{formatDate(conversion.created_at)}</p>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="prose max-w-none" dangerouslySetInnerHTML={{ __html: conversion.description }} />
                
                {conversion.attachments && conversion.attachments.length > 0 && (
                  <div className={`mt-4 p-4 rounded-lg border ${
                    conversion.sender === 'admin' ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'
                  }`}>
                    <div className="flex items-center gap-2 mb-3">
                      <Paperclip className={`h-4 w-4 ${
                        conversion.sender === 'admin' ? 'text-green-600' : 'text-gray-600'
                      }`} />
                      <span className={`font-medium ${
                        conversion.sender === 'admin' ? 'text-green-800' : 'text-gray-800'
                      }`}>{t('Attachments')}</span>
                    </div>
                    <div className="space-y-2">
                      {conversion.attachments.map((attachment, index) => (
                        <div key={index} className="flex items-center justify-between p-2 bg-white rounded border">
                          <span className="text-sm">{attachment.name}</span>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-8 w-8 p-0 text-blue-600 hover:text-blue-800"
                            onClick={() => {
                              fetch(route('support-ticket.attachment.download'), {
                                method: 'POST',
                                headers: {
                                  'Content-Type': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                },
                                body: JSON.stringify({ path: attachment.path })
                              })
                              .then(response => response.json())
                              .then(data => {
                                if (data.status && data.file_url) {
                                  const link = document.createElement('a');
                                  link.href = data.file_url;
                                  link.download = attachment.name;
                                  link.click();
                                }
                              });
                            }}
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}

          {/* Reply Form */}
          <Card className="border-green-200 bg-green-50/30">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-green-800">
                <MessageSquare className="h-5 w-5" />
                {t('Add Reply')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleReplySubmit} className="space-y-4">
                <div>
                  <Label className="text-green-800">{t('Reply Message')}</Label>
                  <RichTextEditor
                    content={replyData.description}
                    onChange={(value) => setReplyData('description', value)}
                    placeholder={t('Write your reply here...')}
                    className="bg-white"
                  />
                  {replyErrors.description && (
                    <p className="text-sm text-red-600 mt-1">{replyErrors.description}</p>
                  )}
                </div>

                <div>
                  <Label className="text-green-800">{t('Attachments')}</Label>
                  <MediaPicker
                    value={replyData.attachments}
                    onChange={(value) => setReplyData('attachments', Array.isArray(value) ? value : [value].filter(Boolean))}
                    multiple={true}
                    placeholder={t('Select attachments')}
                    showPreview={true}
                  />
                </div>

                <div className="flex justify-end">
                  <Button type="submit" disabled={replyProcessing || isEditorEmpty(replyData.description)} className="bg-green-600 hover:bg-green-700">
                    <Send className="h-4 w-4 mr-2" />
                    {replyProcessing ? t('Sending...') : t('Send Reply')}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Ticket Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm">
                <AlertCircle className="h-4 w-4" />
                {t('Ticket Information')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t('Status')}:</span>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(ticket.status)}`}>
                    {ticket.status}
                  </span>
                </div>
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t('Priority')}:</span>
                  <Badge variant="outline">Normal</Badge>
                </div>
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t('Category')}:</span>
                  <span className="text-sm font-medium">{ticket.category_info?.name || 'None'}</span>
                </div>
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t('Created')}:</span>
                  <span className="text-sm">{new Date(ticket.created_at).toLocaleDateString()}</span>
                </div>
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t('Updated')}:</span>
                  <span className="text-sm">{new Date(ticket.updated_at).toLocaleDateString()}</span>
                </div>
                {/* Custom Fields Display */}
                {customFields && customFields.length > 0 && customFields.map((field) => (
                  <div key={field.id}>
                    <Separator />
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">{field.name}:</span>
                      <span className="text-sm font-medium">
                        {ticket.fields && ticket.fields[field.id] ? ticket.fields[field.id] : '-'}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Customer Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm">
                <User className="h-4 w-4" />
                {t('Customer Information')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div>
                  <p className="font-medium">{ticket.name}</p>
                  <p className="text-sm text-gray-600">{ticket.email}</p>
                </div>
                <Separator />
                <div>
                  <span className="text-sm text-gray-600">{t('Account Type')}:</span>
                  <Badge variant="outline" className="ml-2 text-xs">
                    {ticket.account_type}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Note Section */}
          <Card className="border-yellow-200 bg-yellow-50/30">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm text-yellow-800">
                <StickyNote className="h-4 w-4" />
                {t('Internal Note')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleNoteSubmit} className="space-y-4">
                <div>
                  <RichTextEditor
                    content={noteData.note}
                    onChange={(value) => setNoteData('note', value)}
                    placeholder={t('Add internal note...')}
                    className="bg-white min-h-[120px]"
                  />
                  {noteErrors.note && (
                    <p className="text-sm text-red-600 mt-1">{noteErrors.note}</p>
                  )}
                </div>
                <Button 
                  type="submit" 
                  disabled={noteProcessing} 
                  size="sm" 
                  className="w-full bg-yellow-600 hover:bg-yellow-700"
                >
                  <StickyNote className="h-4 w-4 mr-2" />
                  {noteProcessing ? t('Saving...') : t('Save Note')}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}