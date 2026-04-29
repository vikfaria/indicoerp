import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { 
    Database,
    Calendar,
    Clock,
    Eye,
    List,
    FileCode,
    Server,
    Shield,
    Info,
    Lightbulb,
    AlertTriangle,
    CheckCircle,
    Copy,
    Check,
    ThumbsUp,
    ThumbsDown,
    Ticket,
    Link as LinkIcon,
    Tags,
    ChevronRight,
    ArrowLeft
} from 'lucide-react';

interface Article {
    id: number;
    title: string;
    description: string;
    category?: {
        id: number;
        title: string;
    };
    created_at: string;
    updated_at: string;
}

interface RelatedArticle {
    id: number;
    title: string;
    description: string;
    created_at: string;
}

interface KnowledgeArticleProps {
    article: Article;
    relatedArticles: RelatedArticle[];
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
        customPages?: Array<{
            slug: string;
            name: string;
        }>;
    };
    slug: string;
}

export default function KnowledgeArticle({ article, relatedArticles, settings, brandSettings, slug }: KnowledgeArticleProps) {
    const { t } = useTranslation();
    const [copiedCode, setCopiedCode] = useState<string | null>(null);

    const copyToClipboard = (text: string, id: string) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopiedCode(id);
            setTimeout(() => setCopiedCode(null), 2000);
        });
    };

    // Generate table of contents from article description HTML
    const generateTocItems = (description: string) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(description, 'text/html');
        const headings = doc.querySelectorAll('h1, h2, h3, h4, h5, h6');
        
        return Array.from(headings).map((heading, index) => ({
            id: `heading-${index}`,
            title: heading.textContent || `Section ${index + 1}`
        }));
    };

    const tocItems = generateTocItems(article.description);

    return (
        <SupportTicketLayout title={article.title} settings={settings} brandSettings={brandSettings}>
            {/* Breadcrumbs */}
            <div className="text-sm text-gray-500 mb-4">
                <Link href={route('support-ticket.index',[slug])} className="hover:text-teal-600">{t('Home')}</Link>
                <span className="mx-2">/</span>
                <Link href={route('support-ticket.knowledge',[slug])} className="hover:text-teal-600">{t('Knowledge Base')}</Link>
                {article.category && (
                    <>
                        <span className="mx-2">/</span>
                        <span className="hover:text-teal-600">{article.category.title}</span>
                    </>
                )}
                <span className="mx-2">/</span>
                <span className="text-gray-700">{article.title}</span>
            </div>

            {/* Article Header */}
            <Card className="bg-teal-600 overflow-hidden shadow-lg mb-8">
                <CardContent className="py-6 px-4 md:p-8 flex flex-col md:flex-row items-center">
                    <div className="w-full md:w-3/5 text-white mb-8 md:mb-0 md:pr-8">
                        <div className="mb-4">
                            <span className="inline-block bg-white/20 text-white text-sm px-4 py-1 rounded-full backdrop-blur-sm mb-3 md:mb-4">
                                <Database className="inline h-4 w-4 mr-1" />
                                {article.category?.title || t('Knowledge Base')}
                            </span>
                        </div>
                        <h2 className="text-2xl md:text-3xl lg:text-4xl font-bold mb-3 md:mb-4 text-white">{article.title}</h2>
                        <p className="text-white mb-4 md:mb-6">
                            {t('Comprehensive guide to help you understand and implement the solution')}
                        </p>
                        <div className="flex flex-wrap items-center gap-4 lg:gap-6 text-sm">
                            <div className="flex items-center">
                                <Calendar className="h-4 w-4 mr-2" />
                                <span>{t('Last updated')}: {new Date(article.updated_at).toLocaleDateString()}</span>
                            </div>
                            <div className="flex items-center">
                                <Clock className="h-4 w-4 mr-2" />
                                <span>{t('5 min read')}</span>
                            </div>
                            <div className="flex items-center">
                                <Eye className="h-4 w-4 mr-2" />
                                <span>{t('1,245 views')}</span>
                            </div>
                        </div>
                    </div>
                    <div className="w-full md:w-2/5 flex justify-center">
                      
                            <img src={getImagePath('packages/workdo/SupportTicket/src/Resources/assets/images/svg/database-illustration.svg')}
                            alt="Knowledge Base Illustration" 
                            className="w-full max-w-sm"
                            style={{ animation: 'float 3s ease-in-out infinite' }}
                        />
                    </div>
                </CardContent>
            </Card>

            {/* Main Content Area */}
            <div className="flex flex-col lg:flex-row gap-8">
                {/* Left Sidebar (Table of Contents) */}
                {tocItems.length > 0 && (
                    <div className="w-full lg:w-1/4">
                        <Card className="shadow-md sticky top-4">
                            <CardContent className="p-4 md:p-5">
                                <h3 className="text-lg font-semibold mb-4 flex items-center">
                                    <List className="h-5 w-5 text-teal-600 mr-2" />
                                    {t('Table of Contents')}
                                </h3>
                                <div className="space-y-1">
                                    {tocItems.map((item) => (
                                        <a 
                                            key={item.id}
                                            href={`#${item.id}`} 
                                            className="block text-gray-700 text-sm rounded p-2 hover:bg-teal-50 hover:text-teal-600 hover:border-l-2 hover:border-teal-600 hover:pl-3 transition-all duration-300"
                                        >
                                            {item.title}
                                        </a>
                                    ))}
                                </div>
                                
                                {/* Quick Resources Box */}
                                <div className="mt-8 pt-6 border-t border-gray-200">
                                    <h3 className="text-lg font-semibold mb-4 flex items-center">
                                        <LinkIcon className="h-5 w-5 text-teal-600 mr-2" />
                                        {t('Quick Resources')}
                                    </h3>
                                    <div className="space-y-3">
                                        <Link href={route('support-ticket.knowledge',[slug])} className="flex items-center text-gray-700 hover:text-teal-600 text-sm">
                                            <FileCode className="h-4 w-4 mr-2 text-teal-600" />
                                            <span>{t('Browse All Articles')}</span>
                                        </Link>
                                        <Link href={route('support-ticket.create',[slug])} className="flex items-center text-gray-700 hover:text-teal-600 text-sm">
                                            <Server className="h-4 w-4 mr-2 text-teal-600" />
                                            <span>{t('Create Support Ticket')}</span>
                                        </Link>
                                        {settings.faq_is_on === 'on' && (
                                            <Link href={route('support-ticket.faq',[slug])} className="flex items-center text-gray-700 hover:text-teal-600 text-sm">
                                                <Shield className="h-4 w-4 mr-2 text-teal-600" />
                                                <span>{t('FAQ Section')}</span>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Main Article Content */}
                <div className={`w-full ${tocItems.length > 0 ? 'lg:w-2/4' : 'lg:w-2/3'}`}>
                    <Card className="shadow-md">
                        <CardContent className="p-4 lg:p-8 md:p-6 max-h-[80vh] overflow-y-auto">
                            {/* Introduction Section */}
                            <div className="mb-8">
                                <div className="bg-teal-50 p-4 rounded-xl">
                                    <h4 className="font-medium text-gray-800 mb-2 flex items-center">
                                        <Info className="h-5 w-5 text-teal-600 mr-2" />
                                        {t('Article Information')}
                                    </h4>
                                    <p className="text-gray-700 text-sm">
                                        {t('This article provides detailed information to help you understand the topic. Please read through the content carefully.')}
                                    </p>
                                </div>
                            </div>

                            {/* Article Content */}
                            <div className="mb-10">
                                <div 
                                    className="prose prose-gray max-w-none prose-headings:text-gray-800 prose-p:text-gray-700 prose-li:text-gray-700 prose-strong:text-gray-800"
                                    dangerouslySetInnerHTML={{ __html: article.description }}
                                />
                            </div>


                        </CardContent>
                    </Card>
                </div>

                {/* Right Sidebar */}
                {tocItems.length > 0 && (
                    <div className="w-full lg:w-1/4">
                        {/* Related Articles */}
                        <Card className="shadow-md mb-6">
                            <CardContent className="p-5">

                                <h3 className="text-lg font-semibold mb-4 flex items-center">
                                    <LinkIcon className="h-5 w-5 text-teal-600 mr-2" />
                                    {t('Related Articles')}
                                </h3>
                                <div className="space-y-4">
                                    {relatedArticles && relatedArticles.length > 0 ? (
                                        relatedArticles.map((relatedArticle) => (
                                            <Link key={relatedArticle.id} href={route('support-ticket.knowledge.article',[slug,relatedArticle.id])} className="block rounded-lg p-4 border border-gray-200 hover:border-teal-600 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                                                <h4 className="font-medium text-gray-800 mb-1 hover:text-teal-600 transition-colors duration-300">{relatedArticle.title}</h4>
                                                <p className="text-gray-500 text-sm line-clamp-2">{relatedArticle.description.replace(/<[^>]*>/g, '').substring(0, 100)}...</p>
                                                <p className="text-xs text-gray-400 mt-2">{new Date(relatedArticle.created_at).toLocaleDateString()}</p>
                                            </Link>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">{t('No related articles found.')}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Navigation */}
                        <Card className="shadow-md">
                            <CardContent className="p-5">
                                <h3 className="text-lg font-semibold mb-4 flex items-center">
                                    <LinkIcon className="h-5 w-5 text-teal-600 mr-2" />
                                    {t('Navigation')}
                                </h3>
                                <div className="space-y-4">
                                    <Link href={route('support-ticket.knowledge',[slug])} className="block rounded-lg p-4 border border-gray-200 hover:border-teal-600 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                                        <h4 className="font-medium text-gray-800 mb-1 hover:text-teal-600 transition-colors duration-300">← {t('Back to Knowledge Base')}</h4>
                                        <p className="text-gray-500 text-sm">{t('Browse all available articles')}</p>
                                    </Link>
                                    {article.category && (
                                        <div className="rounded-lg p-4 border border-gray-200">
                                            <h4 className="font-medium text-gray-800 mb-1">{t('Category')}</h4>
                                            <p className="text-teal-600 text-sm">{article.category.title}</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
                {/* Right Sidebar - Show when no TOC */}
                {tocItems.length === 0 && (
                    <div className="w-full lg:w-1/3">
                        {/* Related Articles */}
                        <Card className="shadow-md mb-6">
                            <CardContent className="p-5">
                                <h3 className="text-lg font-semibold mb-4 flex items-center">
                                    <LinkIcon className="h-5 w-5 text-teal-600 mr-2" />
                                    {t('Related Articles')}
                                </h3>
                                <div className="space-y-4">
                                    {relatedArticles && relatedArticles.length > 0 ? (
                                        relatedArticles.map((relatedArticle) => (
                                            <Link key={relatedArticle.id} href={route('support-ticket.knowledge.article', [slug, relatedArticle.id])} className="block rounded-lg p-4 border border-gray-200 hover:border-teal-600 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                                                <h4 className="font-medium text-gray-800 mb-1 hover:text-teal-600 transition-colors duration-300">{relatedArticle.title}</h4>
                                                <p className="text-gray-500 text-sm line-clamp-2">{relatedArticle.description.replace(/<[^>]*>/g, '').substring(0, 100)}...</p>
                                                <p className="text-xs text-gray-400 mt-2">{new Date(relatedArticle.created_at).toLocaleDateString()}</p>
                                            </Link>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">{t('No related articles found.')}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Support Section */}
                        <Card className="shadow-md">
                            <CardContent className="p-5">
                                <h3 className="text-lg font-semibold mb-4">{t('Need Help?')}</h3>
                                <div className="bg-teal-50 rounded-xl p-4">
                                    <h4 className="font-bold text-gray-800 mb-3">{t('Still have questions?')}</h4>
                                    <p className="text-gray-700 text-sm mb-4">
                                        {t('If you couldn\'t find the information you were looking for, our support team is here to help.')}
                                    </p>
                                    <Button className="bg-teal-600 hover:bg-teal-700 w-full" asChild>
                                        <Link href={route('support-ticket.index',[slug])}>
                                            <Ticket className="h-4 w-4 mr-2" />
                                            {t('Create a Support Ticket')}
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </SupportTicketLayout>
    );
}