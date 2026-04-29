import React, { useState } from 'react';
import { useForm, router, Head } from '@inertiajs/react';
import SupportTicketLayout from './Frontend/Layouts/SupportTicketLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Download, Paperclip, Send, Ticket, ThumbsUp } from 'lucide-react';

import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { route } from 'ziggy-js';
import { useTranslation } from 'react-i18next';
import { formatDateTime } from '@/utils/helpers';

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

interface TicketData {
    id: number;
    ticket_id: string;
    name: string;
    email: string;
    subject: string;
    status: string;
    description: string;
    created_at: string;
    attachments: Attachment[];
    conversions: Conversion[];
}

interface ShowProps {
    ticket: TicketData;
    workspace?: any;
    settings?: {
        faq_is_on?: string;
        knowledge_base_is_on?: string;
        privacy_policy_enabled?: boolean;
        terms_conditions_enabled?: boolean;
    };
    brandSettings?: {
        logo_dark?: string;
        favicon?: string;
        titleText?: string;
        footerText?: string;
        privacyEnabled?: boolean;
        termsEnabled?: boolean;
        customPages?: Array<{
            slug: string;
            name: string;
        }>;
    };
    slug: string;
}

export default function Show({ ticket, workspace, settings, brandSettings, slug }: ShowProps) {
    const { t } = useTranslation();
    const [selectedFiles, setSelectedFiles] = useState<FileList | null>(null);
    const [imagePreviews, setImagePreviews] = useState<string[]>([]);
    
    const { data, setData, processing, errors, reset } = useForm({
        reply_description: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('description', data.reply_description);
        
        if (selectedFiles && selectedFiles.length > 0) {
            for (let i = 0; i < selectedFiles.length; i++) {
                formData.append('files[]', selectedFiles[i]);
            }
        }

        // Check if this is admin view or public view
        const isAdminView = window.location.pathname.includes('/support-tickets/');
        
        // Get encrypted ticket ID from URL
        const urlParts = window.location.pathname.split('/');
        const encryptedTicketId = urlParts[urlParts.length - 1];

        
      router.post(route('support-ticket.send-conversion.store', { 
            slug: slug, 
            ticketId: encryptedTicketId 
        }),
        formData,
        {
            onSuccess: () => {
                reset();
                setSelectedFiles(null);
                setImagePreviews([]);
            },
            onError: (errors) => {
            }
        });
    };

    const toggleToolbar = (tool: string) => {
        setActiveToolbar(prev => 
            prev.includes(tool) 
                ? prev.filter(t => t !== tool)
                : [...prev, tool]
        );
    };

    const formatDate = (dateString: string) => {
        return formatDateTime(dateString);
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



    return (
        <SupportTicketLayout 
            title={`Ticket - ${ticket.ticket_id}`}
            settings={settings}
            brandSettings={brandSettings}
        >
            {/* Ticket ID Header */}
            <div className="flex justify-center mb-8">
                <div className="inline-block bg-teal-600 text-white px-6 py-3 rounded-full shadow-lg">
                    <div className="flex items-center space-x-2">
                        <Ticket className="h-5 w-5" />
                        <span className="font-semibold">{t('Ticket')} - {ticket.ticket_id}</span>
                    </div>
                </div>
            </div>

            {/* Ticket Info and Conversation */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Conversation */}
                <div className="lg:col-span-2">
                    {/* Ticket Subject */}
                    <Card className="shadow-md mb-6">
                        <CardContent className="p-4 md:p-6">
                            <div className="flex flex-col md:flex-row justify-between md:items-center gap-y-2">
                                <h2 className="md:text-2xl text-xl font-bold text-gray-800">{ticket.subject}</h2>
                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(ticket.status)}`}>
                                    {ticket.status}
                                </span>
                            </div>

                            <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span className="text-gray-500 block">Created:</span>
                                    <span className="font-medium">{formatDateTime(ticket.created_at)}</span>
                                </div>
                                <div>
                                    <span className="text-gray-500 block">Customer:</span>
                                    <span className="font-medium">{ticket.name}</span>
                                </div>
                                <div>
                                    <span className="text-gray-500 block">Email:</span>
                                    <span className="font-medium">{ticket.email}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Original Message */}
                    <div className="space-y-6 mb-6">
                        <div className="flex gap-4">
                            <div className="flex-shrink-0">
                                <div className="w-8 h-8 md:w-10 md:h-10 rounded-full bg-teal-400 flex items-center justify-center text-white font-semibold">
                                    {ticket.name ? ticket.name.charAt(0).toUpperCase() : 'U'}
                                </div>
                            </div>
                            <div className="flex-grow">
                                <div className="flex flex-col md:flex-row md:items-center justify-between mb-2">
                                    <div>
                                        <span className="font-medium text-gray-900">{ticket.name}</span>
                                        <span className="text-gray-500 text-sm ml-2">({formatDate(ticket.created_at)})</span>
                                    </div>
                                </div>
                                <div className="bg-blue-50 border-l-4 border-blue-400 rounded-xl p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                                    <div dangerouslySetInnerHTML={{ __html: ticket.description }} />
                                    
                                    {/* Attachments */}
                                    {ticket.attachments && Array.isArray(ticket.attachments) && ticket.attachments.length > 0 && (
                                        <div className="mt-4 bg-white/50 rounded-lg p-3 border border-blue-100">
                                            <b className="flex items-center gap-2 mb-2">
                                                <Paperclip className="h-4 w-4" />
                                                Attachments:
                                            </b>
                                            {ticket.attachments.map((attachment, index) => (
                                                <div key={index} className="flex items-center justify-between">
                                                    <span>{attachment.name}</span>
                                                    <button 
                                                        onClick={async () => {
                                                            const url = `/storage/${attachment.path}`;
                                                            const response = await fetch(url);
                                                            const blob = await response.blob();
                                                            const link = document.createElement('a');
                                                            link.href = URL.createObjectURL(blob);
                                                            link.download = attachment.name;
                                                            link.click();
                                                            URL.revokeObjectURL(link.href);
                                                        }}
                                                        className="text-blue-600 hover:text-blue-800"
                                                    >
                                                        <Download className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Conversations */}
                        {ticket.conversions && ticket.conversions.map((conversion) => (
                            <div key={conversion.id} className="flex gap-4">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 md:w-10 md:h-10 rounded-full bg-green-400 flex items-center justify-center text-white font-semibold">
                                        {conversion.sender === 'admin' ? 'A' : 'U'}
                                    </div>
                                </div>
                                <div className="flex-grow">
                                    <div className="flex flex-col md:flex-row md:items-center justify-between mb-2">
                                        <div>
                                            <span className="font-medium text-gray-900">
                                                {conversion.sender === 'admin' 
                                                    ? (conversion.replyBy?.name || 'Admin') 
                                                    : ticket.name
                                                }
                                            </span>
                                            <span className="text-gray-500 text-sm ml-2">({formatDate(conversion.created_at)})</span>
                                        </div>
                                    </div>
                                    <div className={`rounded-xl p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-md ${
                                        conversion.sender === 'admin' ? 'bg-green-50 border-l-4 border-green-400' : 'bg-blue-50 border-l-4 border-blue-400'
                                    }`}>
                                        <div dangerouslySetInnerHTML={{ __html: conversion.description || 'No description' }} />
                                        
                                        {/* Conversion Attachments */}
                                        {conversion.attachments && Array.isArray(conversion.attachments) && conversion.attachments.length > 0 && (
                                            <div className="mt-4 bg-white/50 rounded-lg p-3 border border-blue-100">
                                                <b className="flex items-center gap-2 mb-2">
                                                    <Paperclip className="h-4 w-4" />
                                                    Attachments:
                                                </b>
                                                {conversion.attachments.map((attachment, index) => (
                                                    <div key={index} className="flex items-center justify-between">
                                                        <span>{attachment.name}</span>
                                                        <button 
                                                            onClick={async () => {
                                                                const url = `/storage/${attachment.path}`;
                                                                const response = await fetch(url);
                                                                const blob = await response.blob();
                                                                const link = document.createElement('a');
                                                                link.href = URL.createObjectURL(blob);
                                                                link.download = attachment.name;
                                                                link.click();
                                                                URL.revokeObjectURL(link.href);
                                                            }}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <Download className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Reply Form */}
                    {ticket.status !== 'Closed' ? (
                        <Card className="shadow-md overflow-hidden">


                            <CardContent className="p-4">
                                <form onSubmit={handleSubmit}>
                                    <RichTextEditor
                                        content={data.reply_description}
                                        onChange={(value) => setData('reply_description', value)}
                                        placeholder="Write your reply here..."
                                        className="mb-4"
                                    />
                                    
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {t('Attachments')}
                                        </label>
                                        <input
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            onChange={(e) => {
                                                setSelectedFiles(e.target.files);
                                                if (e.target.files) {
                                                    const previews: string[] = [];
                                                    for (let i = 0; i < e.target.files.length; i++) {
                                                        const file = e.target.files[i];
                                                        const reader = new FileReader();
                                                        reader.onload = (event) => {
                                                            previews.push(event.target?.result as string);
                                                            if (previews.length === e.target.files!.length) {
                                                                setImagePreviews(previews);
                                                            }
                                                        };
                                                        reader.readAsDataURL(file);
                                                    }
                                                } else {
                                                    setImagePreviews([]);
                                                }
                                            }}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                        />
                                        {imagePreviews.length > 0 && (
                                            <div className="mt-3 grid grid-cols-3 gap-2">
                                                {imagePreviews.map((preview, index) => (
                                                    <img
                                                        key={index}
                                                        src={preview}
                                                        alt={`Preview ${index + 1}`}
                                                        className="w-full h-20 object-cover rounded border"
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex justify-end">
                                        <Button 
                                            type="submit" 
                                            disabled={processing || !data.reply_description.trim()}
                                            className="bg-teal-600 hover:bg-teal-700"
                                        >
                                            <Send className="h-4 w-4 mr-2" />
                                            {processing ? t('Sending...') : t('Send Reply')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="p-4 text-center text-gray-600">
                                {t('Ticket is closed and cannot send reply.')}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Ticket Sidebar */}
                <div className="space-y-6">
                    <Card className="shadow-md overflow-hidden">
                        <div className="bg-teal-600 text-white py-3 px-4">
                            <h3 className="font-medium flex items-center">
                                <Ticket className="h-4 w-4 mr-2" />
                                {t('Ticket Information')}
                            </h3>
                        </div>
                        <CardContent className="p-4 space-y-4">
                            {[
                                { label: t('Status'), value: ticket.status },
                                { label: t('Ticket ID'), value: `#${ticket.ticket_id}` },
                                { label: t('Created'), value: formatDateTime(ticket.created_at) },
                                { label: t('Customer'), value: ticket.name },
                                { label: t('Email'), value: ticket.email }
                            ].map((item, index) => (
                                <div key={index} className="flex justify-between text-sm">
                                    <span className="text-gray-500">{item.label}:</span>
                                    <span className="font-medium text-gray-900">{item.value}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </SupportTicketLayout>
    );
}