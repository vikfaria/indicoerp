import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

import { 
    Lightbulb,
    Search,
    Laptop,
    Download,
    Users,
    FileText,
    ChevronRight,
    Ticket,
    HelpCircle
} from 'lucide-react';

interface KnowledgeItem {
    id: number;
    title: string;
    description: string;
    category?: {
        id: number;
        name: string;
    };
    created_at: string;
}

interface Category {
    id: number;
    title: string;
}

interface KnowledgeProps {
    knowledgeItems: KnowledgeItem[];
    categories: Category[];
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
        knowledge_base?: {
            title: string;
            description: string;
        };
    };
    ctaSections?: {
        knowledge_base?: {
            title: string;
            description: string;
        };
    };
     slug: string;
}

export default function Knowledge({ knowledgeItems, categories, settings, brandSettings, titleSections, ctaSections, slug }: KnowledgeProps) {
    const { t } = useTranslation();
    const [searchTerm, setSearchTerm] = useState('');
    
    const pageTitle = titleSections?.knowledge_base?.title || 'Knowledge Base';
    const pageDescription = titleSections?.knowledge_base?.description || 'Find answers to common questions and issues';
    const bottomTitle = ctaSections?.knowledge_base?.title || 'Can\'t find what you\'re looking for?';
    const bottomDescription = ctaSections?.knowledge_base?.description || 'Our support team is here to help';

    const filteredItems = knowledgeItems.filter(item =>
        item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.description.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const groupedItems = categories.map(category => ({
        ...category,
        icon: FileText,
        articles: filteredItems.filter(item => item.category?.id === category.id)
    })).filter(category => category.articles.length > 0);

    // Add uncategorized items
    const uncategorizedItems = filteredItems.filter(item => !item.category);
    if (uncategorizedItems.length > 0) {
        groupedItems.push({
            id: 0,
            title: "General",
            icon: FileText,
            articles: uncategorizedItems
        });
    }

    return (
        <SupportTicketLayout title={pageTitle} settings={settings} brandSettings={brandSettings}>
            <div className="rounded-2xl overflow-hidden shadow-xl mb-12 bg-teal-600 relative">
                <div className="relative py-12 px-8">
                    <div className="max-w-3xl mx-auto text-center">
                        <div className="mb-4 inline-block p-2 px-4 bg-white/20 rounded-full text-white text-sm">
                            <Lightbulb className="inline h-4 w-4 mr-2" />
                            {t('Knowledge Center')}
                        </div>
                        <h2 className="text-4xl font-bold mb-4 text-white">{pageTitle}</h2>
                        <p className="text-white mb-6">{pageDescription}</p>
                        
                        <div className="relative mx-auto max-w-2xl">
                            <div className="absolute inset-y-0 left-0 pl-4 flex items-center">
                                <Search className="h-5 w-5 text-teal-600" />
                            </div>
                            <Input
                                type="text"
                                className="bg-white/90 border-0 pl-12 pr-24 py-3 text-gray-700"
                                placeholder={t('What are you looking for?')}
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="mb-12">
                <h2 className="text-3xl font-bold mb-6 text-gray-800">{t('Knowledge Articles')}</h2>
                
                {groupedItems.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {groupedItems.map((category) => (
                            <Card key={category.id} className="shadow-lg overflow-hidden h-96 flex flex-col">
                                <div className="bg-teal-600 py-4 px-5">
                                    <h3 className="text-white font-medium flex items-center text-lg">
                                        <category.icon className="h-5 w-5 mr-2" />
                                        {category.title}
                                    </h3>
                                </div>
                                <CardContent className="p-5 space-y-1 flex-1 overflow-y-auto">
                                    {category.articles.map((article) => (
                                        <div key={article.id} className="group hover:translate-x-1 transition-all duration-300">
                                            <Link href={route('support-ticket.knowledge.article', [slug, article.id])} className="flex items-center p-3 gap-2 rounded-lg hover:bg-teal-50">
                                                <div className="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center group-hover:bg-teal-600">
                                                    <FileText className="h-4 w-4 text-teal-600 group-hover:text-white" />
                                                </div>
                                                <div className="flex-grow">
                                                    <h4 className="text-gray-800 group-hover:text-teal-600">{article.title}</h4>
                                                    <p className="text-xs text-gray-500 mt-1">{new Date(article.created_at).toLocaleDateString()}</p>
                                                </div>
                                                <ChevronRight className="h-4 w-4 text-teal-600 opacity-0 group-hover:opacity-100 transition-all" />
                                            </Link>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="p-8 text-center">
                        <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-xl font-semibold text-gray-600 mb-2">{t('No Knowledge Articles Found')}</h3>
                        <p className="text-gray-500">{t('There are no knowledge articles available at the moment.')}</p>
                    </Card>
                )}
            </div>

            <div className="mt-12 mb-12">
                <Card className="bg-teal-600 text-white overflow-hidden relative">
                    <div className="absolute inset-0 opacity-10">
                        <svg width="100%" height="100%">
                            <defs>
                                <pattern id="smallGrid" width="20" height="20" patternUnits="userSpaceOnUse">
                                    <path d="M 20 0 L 0 0 0 20" fill="none" stroke="white" strokeWidth="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#smallGrid)"/>
                        </svg>
                    </div>
                    
                    <CardContent className="flex flex-col md:flex-row items-center px-4 py-6 md:p-8 lg:p-12 relative">
                        <div className="w-full md:w-2/3 text-white mb-6 md:mb-0 md:pr-12 text-center md:text-left">
                            <h2 className="lg:text-3xl md:text-2xl text-xl font-bold mb-3 md:mb-4">{bottomTitle}</h2>
                            <p className="text-white mb-4 md:mb-6">
                                {bottomDescription || "Can't find the answer you're looking for? Our support team is ready to help you with any issues or concerns you might have."}
                            </p>
                            <div className="flex flex-wrap gap-3 md:gap-4 justify-center md:justify-start">
                                <Button variant="secondary" className="bg-white text-teal-600 hover:bg-gray-100" asChild>
                                    <Link href={route('support-ticket.index',[slug])}>
                                        <Ticket className="h-4 w-4 mr-2" />
                                        {t('Create Support Ticket')}
                                    </Link>
                                </Button>
                                {settings.faq_is_on === 'on' && (
                                    <Button variant="outline" className="border-white/20 bg-white/20 hover:bg-white/30 text-white border-2" asChild>
                                        <Link href={route('support-ticket.faq',[slug])}>
                                            <HelpCircle className="h-4 w-4 mr-2" />
                                            {t('Browse FAQ')}
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                        <div className="w-full md:w-1/3 flex justify-center">
                            <div className="relative w-48 h-48 md:w-56 md:h-56">
                                <img src={getImagePath('packages/workdo/SupportTicket/src/Resources/assets/images/svg/support-illustration.svg')} alt="FAQ" className="w-full h-full object-contain" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SupportTicketLayout>
    );
}