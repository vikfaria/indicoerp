import { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RichTextEditor } from "@/components/ui/rich-text-editor";
import { Card, CardContent } from "@/components/ui/card";

import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { getImagePath, formatTime } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

import { 
    Info,
    Send,
    CheckCircle,
    AlertCircle,
    Clock,
    MessageSquare,
    FileText,
    Users,
    X,
    Phone,
    BookOpen,
    Video,
    Lightbulb,
    Code,
    MessageCircle,
    HelpCircle,
    Upload
} from 'lucide-react';

interface Category {
    id: number;
    name: string;
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

interface CreateTicketProps {
    categories: Category[];
    allFields?: Field[];
    customFields?: Field[];
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
    brandSettings?: {
        logo_dark?: string;
        favicon?: string;
        titleText?: string;
        footerText?: string;
    };
    titleSections?: {
        create_ticket?: {
            title: string;
            description: string;
        };
    };
    quickLinks?: Array<{
        title: string;
        icon: string;
        link: string;
    }>;
    supportInformation?: {
        response_time: string;
        opening_hours: string;
        closing_hours: string;
        phone_support: string;
        business_hours: Array<{
            day: string;
            is_open: boolean;
        }>;
    };
    slug: string;
}

export default function CreateTicket({ categories, allFields, customFields, settings, brandSettings, titleSections, quickLinks, supportInformation, slug }: CreateTicketProps) {
     const { t } = useTranslation();
    const { flash } = usePage().props as any;
    const [showSuccess, setShowSuccess] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        category: '',
        subject: '',
        status: 'In Progress',
        description: '',
        account_type: 'custom',
        fields: {} as Record<string, any>
    });

    useEffect(() => {
        if (flash?.success) {
            // Extract link from HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = flash.success;
            const linkElement = tempDiv.querySelector('a');
            const ticketLink = linkElement?.getAttribute('href');
            const textContent = tempDiv.textContent || tempDiv.innerText || '';
            
            if (ticketLink) {
                toast.success(textContent, {
                    action: {
                        label: 'View Ticket',
                        onClick: () => window.open(ticketLink, '_blank')
                    },
                    duration: 10000
                });
            } else {
                toast.success(textContent);
            }
            reset();
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files.length > 0) {
            const files = Array.from(e.target.files);
            setSelectedFiles(files);
            setData('attachments', files);
        } else {
            setSelectedFiles([]);
            // Remove attachments from form data if no files
            const newData = { ...data };
            delete newData.attachments;
            setData(newData as any);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.create.store',[slug]), {
            onSuccess: (response) => {
                reset();
                setSelectedFiles([]);
            },
            onError: (errors) => {
                toast.error('Failed to create ticket. Please try again.');
            }
        });
    };

    const pageTitle = titleSections?.create_ticket?.title || 'Create Support Ticket';
    const pageDescription = titleSections?.create_ticket?.description || 'Submit your support request and get help from our team';

    return (
        <SupportTicketLayout title={pageTitle} settings={settings} brandSettings={brandSettings}>
            
            <div className="flex flex-col lg:flex-row gap-8">
                <div className="lg:w-1/4" style={{
                    animation: 'fadeIn 0.5s ease-out'
                }}>
                    <Card className="mb-6 shadow-lg">
                        <div className="bg-teal-600 py-3 px-4 rounded-t-xl">
                            <h3 className="text-white font-medium text-lg md:text-xl">{t('Support Information')}</h3>
                        </div>
                        <CardContent className="p-4">
                            <div className="mb-4 text-center rounded-lg overflow-hidden">
                                <img 
                                    src={getImagePath('packages/workdo/SupportTicket/src/Resources/assets/images/svg/support-illustration.svg')} 
                                    alt="Support" 
                                    className="mx-auto w-full h-full" 
                                />
                            </div>
                            <div className="space-y-4">
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Info className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Response Time')}</h4>
                                        <p className="text-sm text-gray-600">{supportInformation?.response_time || 'We typically respond within 24 hours on business days.'}</p>
                                    </div>
                                </div>
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Clock className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Support Hours')}</h4>
                                        <p className="text-sm text-gray-600">
                                            {supportInformation?.business_hours ? (
                                                supportInformation.business_hours
                                                    .filter(h => h.is_open)
                                                    .map(h => h.day)
                                                    .join(', ') || 'Closed'
                                            ) : 'Monday - Friday'}: {supportInformation?.opening_hours ? formatTime(supportInformation.opening_hours) : '9AM'} - {supportInformation?.closing_hours ? formatTime(supportInformation.closing_hours) : '6PM'}
                                        </p>
                                    </div>
                                </div>
                                <div className="bg-teal-50 p-3 rounded-lg flex items-start">
                                    <Phone className="text-teal-600 mt-1 mr-3 h-5 w-5" />
                                    <div>
                                        <h4 className="font-medium text-gray-800">{t('Phone Support')}</h4>
                                        <p className="text-sm text-gray-600">{supportInformation?.phone_support || '+1 (555) 123-4567'}</p>
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
                            {quickLinks && quickLinks.length > 0 ? (
                                quickLinks.map((link, index) => {
                                    const iconMap: Record<string, any> = {
                                        BookOpen,
                                        Video,
                                        Lightbulb,
                                        Code,
                                        MessageCircle,
                                        FileText,
                                        HelpCircle,
                                        Users,
                                        Info,
                                        Clock,
                                        Phone
                                    };
                                    const IconComponent = iconMap[link.icon] || BookOpen;
                                    
                                    return (
                                        <a 
                                            key={index}
                                            href={link.link} 
                                            className={`block p-4 hover:bg-teal-50 transition duration-200 text-gray-700 ${
                                                index < quickLinks.length - 1 ? 'border-b border-gray-100' : ''
                                            }`}
                                            target={link.link.startsWith('http') ? '_blank' : '_self'}
                                            rel={link.link.startsWith('http') ? 'noopener noreferrer' : undefined}
                                        >
                                            <IconComponent className="inline mr-2 text-teal-600 h-4 w-4" /> {link.title}
                                        </a>
                                    );
                                })
                            ) : (
                                <div className="p-4 text-center text-gray-500">
                                   {t(' No quick links available')}
                                </div>
                            )}
                        </div>
                    </Card>
                </div>

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
                            <h2 className="text-white text-xl md:text-2xl lg:text-3xl font-semibold text-center">{pageTitle}</h2>
                            {pageDescription && (
                                <p className="text-teal-100 text-center mt-2 text-sm md:text-base">{pageDescription}</p>
                            )}
                        </div>
                        <CardContent className="p-4 md:p-8">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Dynamic Fields ordered by 'order' field */}
                                <div className="grid grid-cols-12 gap-4">
                                    {allFields && allFields.length > 0 ? allFields.sort((a, b) => a.order - b.order).map((field) => {
                                        const colSpan = field.width === '12' ? 'col-span-12' : 
                                                      field.width === '6' ? 'col-span-6' : 
                                                      field.width === '4' ? 'col-span-4' : 
                                                      field.width === '3' ? 'col-span-3' : 'col-span-12';
                                        
                                        // Handle default fields by custom_id
                                        if (field.custom_id == 1) { // Name field
                                            return (
                                                <div key={field.id} className={colSpan}>
                                                    <Label htmlFor="name" className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                    <Input
                                                        id="name"
                                                        value={data.name}
                                                        onChange={(e) => setData('name', e.target.value)}
                                                        placeholder={t(field.placeholder)}
                                                        className="w-full"
                                                        required={field.is_required}
                                                    />
                                                    {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                                </div>
                                            );
                                        }
                                        
                                        if (field.custom_id == 2) { // Email field
                                            return (
                                                <div key={field.id} className={colSpan}>
                                                    <Label htmlFor="email" className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                    <Input
                                                        id="email"
                                                        type="email"
                                                        value={data.email}
                                                        onChange={(e) => setData('email', e.target.value)}
                                                        placeholder={t(field.placeholder)}
                                                        className="w-full"
                                                        required={field.is_required}
                                                    />
                                                    {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                                </div>
                                            );
                                        }
                                        
                                        if (field.custom_id == 3) { // Category field
                                            return (
                                                <div key={field.id} className={colSpan}>
                                                    <Label htmlFor="category" className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                    <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder={t(field.placeholder)} />
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
                                            );
                                        }
                                        
                                        if (field.custom_id == 4) { // Subject field
                                            return (
                                                <>
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor="subject" className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <Input
                                                            id="subject"
                                                            value={data.subject}
                                                            onChange={(e) => setData('subject', e.target.value)}
                                                            placeholder={t(field.placeholder)}
                                                            className="w-full"
                                                            required={field.is_required}
                                                        />
                                                        {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                                                    </div>
                                                    {/* Status field - show after subject */}
                                                    <div className="col-span-6">
                                                        <Label htmlFor="status" className="block text-gray-700 font-medium mb-2" required>{t('Status')}</Label>
                                                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select Status" />
                                                            </SelectTrigger>
                                                            <SelectContent>
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
                                                <div key={field.id} className={colSpan}>
                                                    <Label htmlFor="description" className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                    <RichTextEditor
                                                        value={data.description}
                                                        onChange={(value) => setData('description', value)}
                                                        placeholder={t(field.placeholder)}
                                                    />
                                                    {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                                                </div>
                                            );
                                        }
                                        
                                        if (field.custom_id == 6) { // Attachments field
                                            return (
                                                <div key={field.id} className={colSpan}>
                                                    <Label className="block text-gray-700 font-medium mb-2">
                                                        {t(field.name)} <span className="text-sm text-gray-500">({t(field.placeholder)})</span>
                                                    </Label>
                                                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-teal-400 transition-all duration-300">
                                                        <div className="space-y-1 text-center">
                                                            <img src={getImagePath('packages/workdo/SupportTicket/src/Resources/assets/images/svg/file-upload-illustration.svg')} alt="File Upload" className="h-28 mx-auto" style={{
                                                                animation: 'float 6s ease-in-out infinite'
                                                            }} />
                                                            <div className="flex text-sm text-gray-600">
                                                                <label htmlFor="file-upload" className="relative cursor-pointer rounded-md font-medium text-teal-600 hover:text-teal-500">
                                                                    <span>Choose Files</span>
                                                                    <input
                                                                        id="file-upload"
                                                                        name="file-upload"
                                                                        type="file"
                                                                        className="sr-only"
                                                                        multiple
                                                                        accept="image/png,image/jpeg,image/gif,application/pdf"
                                                                        onChange={handleFileChange}
                                                                    />
                                                                </label>
                                                                <p className="pl-1">{t('or drag and drop')}</p>
                                                            </div>
                                                            <p className="text-xs text-gray-500">PNG, JPG, GIF, PDF up to 10MB</p>
                                                            {selectedFiles.length > 0 && (
                                                                <div className="text-sm text-gray-500 mt-2">
                                                                    {selectedFiles.length} file(s) selected
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    {errors.attachments && <p className="text-red-500 text-sm mt-1">{errors.attachments}</p>}
                                                </div>
                                            );
                                        }
                                        
                                        // Handle custom fields (custom_id > 6)
                                        if (field.custom_id > 6) {
                                            if (field.type === 'text') {
                                                return (
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor={`field-${field.id}`} className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <Input
                                                            id={`field-${field.id}`}
                                                            value={data.fields[field.id] || ''}
                                                            onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                                                            placeholder={t(field.placeholder)}
                                                            required={field.is_required}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                );
                                            }
                                            
                                            if (field.type === 'email') {
                                                return (
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor={`field-${field.id}`} className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <Input
                                                            id={`field-${field.id}`}
                                                            type="email"
                                                            value={data.fields[field.id] || ''}
                                                            onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                                                            placeholder={t(field.placeholder)}
                                                            required={field.is_required}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                );
                                            }
                                            
                                            if (field.type === 'number') {
                                                return (
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor={`field-${field.id}`} className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <Input
                                                            id={`field-${field.id}`}
                                                            type="number"
                                                            value={data.fields[field.id] || ''}
                                                            onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                                                            placeholder={t(field.placeholder)}
                                                            required={field.is_required}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                );
                                            }
                                            
                                            if (field.type === 'date') {
                                                return (
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor={`field-${field.id}`} className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <Input
                                                            id={`field-${field.id}`}
                                                            type="date"
                                                            value={data.fields[field.id] || ''}
                                                            onChange={(e) => setData('fields', {...data.fields, [field.id]: e.target.value})}
                                                            placeholder={t(field.placeholder)}
                                                            required={field.is_required}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                );
                                            }
                                            
                                            if (field.type === 'textarea') {
                                                return (
                                                    <div key={field.id} className={colSpan}>
                                                        <Label htmlFor={`field-${field.id}`} className="block text-gray-700 font-medium mb-2" required={field.is_required}>{t(field.name)}</Label>
                                                        <RichTextEditor
                                                            value={data.fields[field.id] || ''}
                                                            onChange={(value) => setData('fields', {...data.fields, [field.id]: value})}
                                                            placeholder={t(field.placeholder)}
                                                        />
                                                    </div>
                                                );
                                            }
                                        }
                                        
                                        return null;
                                    }) : (
                                        // Fallback fields if no allFields
                                        <>
                                            <div className="col-span-6">
                                                <Label htmlFor="name" className="block text-gray-700 font-medium mb-2">{t('Name')}</Label>
                                                <Input
                                                    id="name"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    placeholder="Enter Your Name"
                                                    className="w-full"
                                                    required
                                                />
                                                {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                            </div>
                                            <div className="col-span-6">
                                                <Label htmlFor="email" className="block text-gray-700 font-medium mb-2">{t('Email')}</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    placeholder="Enter Your Email"
                                                    className="w-full"
                                                    required
                                                />
                                                {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                            </div>
                                            <div className="col-span-6">
                                                <Label htmlFor="category" className="block text-gray-700 font-medium mb-2">{t('Category')}</Label>
                                                <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select Category" />
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
                                            <div className="col-span-6">
                                                <Label htmlFor="subject" className="block text-gray-700 font-medium mb-2">{t('Subject')}</Label>
                                                <Input
                                                    id="subject"
                                                    value={data.subject}
                                                    onChange={(e) => setData('subject', e.target.value)}
                                                    placeholder="Enter Subject"
                                                    className="w-full"
                                                    required
                                                />
                                                {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                                            </div>
                                            <div className="col-span-12">
                                                <Label htmlFor="description" className="block text-gray-700 font-medium mb-2">{t('Description')}</Label>
                                                <RichTextEditor
                                                    value={data.description}
                                                    onChange={(value) => setData('description', value)}
                                                    placeholder="Please describe your issue in detail..."
                                                />
                                                {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                                            </div>
                                        </>
                                    )}
                                </div>



                                <div className="text-center">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-teal-600 hover:bg-teal-700 text-white font-medium py-3 px-8 rounded-full transition-all duration-300 shadow-md hover:shadow-lg"
                                    >
                                        <Send className="h-4 w-4 mr-2" />
                                        {processing ? 'Creating...' : 'Create Ticket'}
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