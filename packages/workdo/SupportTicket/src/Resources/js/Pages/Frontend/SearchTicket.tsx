import { useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { 
    Search,
    Ticket,
    Mail
} from 'lucide-react';

interface SearchFormData {
    ticket_id: string;
    email: string;
}

interface SearchTicketProps {
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
    brandSettings?: {
        logo_dark?: string;
        favicon?: string;
        titleText?: string;
        footerText?: string;
        privacyEnabled?: boolean;
        termsEnabled?: boolean;
    };
    titleSections?: {
        search_ticket?: {
            title: string;
            description: string;
        };
    };
    slug: string;
}

export default function SearchTicket({ settings, brandSettings, titleSections, slug }: SearchTicketProps) {
    const { t } = useTranslation();
    const pageTitle = titleSections?.search_ticket?.title || t('Find Your Support Ticket');
    const pageDescription = titleSections?.search_ticket?.description || t('Track the status of your existing support tickets');

    const { data, setData, post, processing, errors } = useForm({
        ticket_id: '',
        email: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.search.post',[slug]));
    };

    return (
        <SupportTicketLayout title={pageTitle} settings={settings} brandSettings={brandSettings}>
            {/* Page Title */}
            <div className="text-center lg:mb-8 mb-6">
                <h2 className="lg:text-3xl md:text-2xl text-xl font-bold text-gray-800">{pageTitle}</h2>
                <p className="text-gray-500 mt-2">{pageDescription}</p>
            </div>

            <div className="max-w-4xl mx-auto">
                <Card className="shadow-md">
                    <CardContent className="md:p-8 p-4">
                        <div className="p-4 md:p-6 bg-teal-600 rounded-xl overflow-hidden relative shadow-md">
                            <div className="flex flex-col-reverse md:flex-row items-center gap-6 md:gap-8">
                                <div className="w-full md:w-1/3">
                                    <img 
                                        src={getImagePath('packages/workdo/SupportTicket/src/Resources/assets/images/search-person.svg')} 
                                        alt="Search illustration" 
                                        className="w-full h-auto"
                                    />
                                </div>
                                <div className="w-full md:w-2/3 text-white">
                                    <h2 className="lg:text-3xl md:text-2xl text-xl font-bold mb-3 md:mb-4">{t('Find Your Ticket')}</h2>
                                    <p className="mb-4">{t('Enter your ticket ID and email to check the status of your support request')}</p>
                                    
                                    {/* Search Form */}
                                    <form onSubmit={handleSubmit} className="space-y-4">
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Ticket className="h-5 w-5 text-white/70" />
                                            </div>
                                            <Input
                                                type="text"
                                                value={data.ticket_id}
                                                onChange={(e) => setData('ticket_id', e.target.value)}
                                                placeholder={t('Enter Ticket ID (e.g., TKT-123456)')}
                                                className="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/30 text-white placeholder-white/70 rounded-lg focus:outline-none focus:ring-2 focus:ring-white"
                                                required
                                            />
                                            {errors.ticket_id && <p className="text-red-200 text-sm mt-1">{errors.ticket_id}</p>}
                                        </div>
                                        
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Mail className="h-5 w-5 text-white/70" />
                                            </div>
                                            <Input
                                                type="email"
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value)}
                                                placeholder={t('Email address used for the ticket')}
                                                className="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/30 text-white placeholder-white/70 rounded-lg focus:outline-none focus:ring-2 focus:ring-white"
                                                required
                                            />
                                            {errors.email && <p className="text-red-200 text-sm mt-1">{errors.email}</p>}
                                        </div>
                                        
                                        <Button 
                                            type="submit" 
                                            disabled={processing}
                                            className="bg-white text-teal-600 hover:bg-gray-100 w-full md:w-auto transition duration-300 shadow-md hover:shadow-lg"
                                        >
                                            <Search className="h-4 w-4 mr-2" />
                                            {processing ? t('Searching...') : t('Search Ticket')}
                                        </Button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SupportTicketLayout>
    );
}