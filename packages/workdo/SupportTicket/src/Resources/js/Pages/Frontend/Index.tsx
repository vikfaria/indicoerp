import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RichTextEditor } from "@/components/ui/rich-text-editor";
import { Card, CardContent } from "@/components/ui/card";
import MediaPicker from '@/components/MediaPicker';
import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { useTranslation } from 'react-i18next';

import { 
    Info,
    Clock,
    Phone,
    BookOpen,
    Video,
    Lightbulb,
    Code,
    MessageCircle,
    Send
} from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface IndexProps {
    categories: Category[];
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
}

export default function Index() {
     const { t } = useTranslation();
    const { categories, settings } = usePage<IndexProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        category: '',
        subject: '',
        status: 'open',
        description: '',
        attachments: []
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.store'));
    };

    return (
        <SupportTicketLayout title="Create Support Ticket" settings={settings}>
            <div className="flex flex-col lg:flex-row gap-6">
                {/* Left Sidebar */}
                <div className="lg:w-1/4" style={{
                    animation: 'fadeIn 0.5s ease-out'
                }}>
                    <Card className="mb-6 shadow-lg">
                        <div className="bg-teal-600 py-3 px-4 rounded-t-xl">
                            <h3 className="text-white font-medium text-lg md:text-xl">{t('Support Information')}</h3>
                        </div>
                        <CardContent className="p-4">
                            <div className="mb-4 text-center rounded-lg overflow-hidden">
                                <img src={`${window.location.origin}/packages/workdo/SupportTicket/src/Resources/assets/images/svg/support-illustration.svg`} alt="Support" className="mx-auto w-full h-full" />
                            </div>
                            <div className="space-y-4">
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Info className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Response Time')}</h4>
                                        <p className="text-sm text-gray-600">{t('We typically respond within 24 hours on business days.')}</p>
                                    </div>
                                </div>
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Clock className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Support Hours')}</h4>
                                        <p className="text-sm text-gray-600">{t('Monday - Friday: 9AM - 6PM ET')}</p>
                                    </div>
                                </div>
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Phone className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Phone Support')}</h4>
                                        <p className="text-sm text-gray-600">{t('Premium customers:')} <br />+1 (555) 123-4567</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-lg" style={{
                        animation: 'fadeIn 0.5s ease-out',
                        animationDelay: '0.2s',
                        animationFillMode: 'both'
                    }}>
                        <div className="bg-teal-600 py-3 px-4 rounded-t-xl">
                            <h3 className="text-white font-medium text-lg md:text-xl">{t('Quick Links')}</h3>
                        </div>
                        <div>
                            <a href="#" className="block p-4 hover:bg-teal-50 transition duration-200 text-gray-700 border-b border-gray-100">
                                <BookOpen className="inline mr-2 text-teal-600 h-4 w-4" /> {t('User Guides')}
                            </a>
                            <a href="#" className="block p-4 hover:bg-teal-50 transition duration-200 text-gray-700 border-b border-gray-100">
                                <Video className="inline mr-2 text-teal-600 h-4 w-4" /> {t('Video Tutorials')}
                            </a>
                            <a href="#" className="block p-4 hover:bg-teal-50 transition duration-200 text-gray-700 border-b border-gray-100">
                                <Lightbulb className="inline mr-2 text-teal-600 h-4 w-4" /> {t('Tips & Tricks')}
                            </a>
                            <a href="#" className="block p-4 hover:bg-teal-50 transition duration-200 text-gray-700 border-b border-gray-100">
                                <Code className="inline mr-2 text-teal-600 h-4 w-4" /> {t('API Documentation')}
                            </a>
                            <a href="#" className="block p-4 hover:bg-teal-50 transition duration-200 text-gray-700">
                                <MessageCircle className="inline mr-2 text-teal-600 h-4 w-4" /> {t('Community Forums')}
                            </a>
                        </div>
                    </Card>
                </div>

                {/* Main Form Card */}
                <div className="lg:w-3/4" style={{
                    animation: 'fadeIn 0.5s ease-out',
                    animationDelay: '0.3s',
                    animationFillMode: 'both'
                }}>
                    <Card className="shadow-xl" style={{
                        animation: 'fadeInUp 0.6s ease-out',
                        boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        border: '1px solid rgba(220, 220, 220, 0.5)',
                        position: 'relative',
                        zIndex: 1
                    }}>
                        <div className="bg-teal-600 p-4 md:py-5 md:px-6 rounded-t-xl">
                            <h2 className="text-white text-xl md:text-2xl lg:text-3xl font-semibold text-center">{t('Create Support Ticket')}</h2>
                        </div>

                        <CardContent className="p-4 md:p-8">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Name and Email */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                    <div>
                                        <Label htmlFor="name" className="block text-gray-700 font-medium mb-2">{t('Name')}</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder={t('Enter Your Name')}
                                            className="w-full"
                                            required
                                        />
                                        {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="email" className="block text-gray-700 font-medium mb-2">{t('Email')}</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder={t('Enter Your Email')}
                                            className="w-full"
                                            required
                                        />
                                        {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                    </div>
                                </div>

                                {/* Category and Subject */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                    <div>
                                        <Label htmlFor="category" className="block text-gray-700 font-medium mb-2">{t('Category')}</Label>
                                        <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('Select Category')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories?.map((category) => (
                                                    <SelectItem key={category.id} value={category.id.toString()}>
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.category && <p className="text-red-500 text-sm mt-1">{errors.category}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="subject" className="block text-gray-700 font-medium mb-2">{t('Subject')}</Label>
                                        <Input
                                            id="subject"
                                            value={data.subject}
                                            onChange={(e) => setData('subject', e.target.value)}
                                            placeholder={t('Enter Subject')}
                                            className="w-full"
                                            required
                                        />
                                        {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                                    </div>
                                </div>

                                {/* Status and Attachments */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                    <div>
                                        <Label htmlFor="status" className="block text-gray-700 font-medium mb-2">{t('Status')}</Label>
                                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('Select Status')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="open">{t('Open')}</SelectItem>
                                                <SelectItem value="In Progress">{t('In Progress')}</SelectItem>
                                                <SelectItem value="On Hold">{t('On Hold')}</SelectItem>
                                                <SelectItem value="Closed">{t('Closed')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="block text-gray-700 font-medium mb-2">
                                            {t('Attachments')} <span className="text-sm text-gray-500">({t('Multiple files')})</span>
                                        </Label>
                                        <MediaPicker
                                            value={data.attachments}
                                            onChange={(files) => setData('attachments', files)}
                                            placeholder={t('Select files to attach')}
                                            multiple
                                            className={errors.attachments ? 'border-red-500' : ''}
                                        />
                                        {errors.attachments && <p className="text-red-500 text-sm mt-1">{errors.attachments}</p>}
                                    </div>
                                </div>

                                {/* Description */}
                                <div>
                                    <Label htmlFor="description" className="block text-gray-700 font-medium mb-2">{t('Description')}</Label>
                                    <RichTextEditor
                                        value={data.description}
                                        onChange={(value) => setData('description', value)}
                                        placeholder={t('Please describe your issue in detail...')}
                                    />
                                    {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                                </div>

                                {/* Submit Button */}
                                <div className="text-center">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-teal-600 hover:bg-teal-700 text-white font-medium py-3 px-8 rounded-full transition-all duration-300 shadow-md hover:shadow-lg"
                                    >
                                        <Send className="h-4 w-4 mr-2" />
                                        {processing ? t('Creating...') : t('Create Ticket')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </SupportTicketLayout>
    );
}