import { useState, ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { 
    Menu, 
    X, 
    Ticket, 
    Search, 
    Book, 
    HelpCircle, 
    UserCircle, 
    Headphones
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface SupportTicketLayoutProps {
    children: ReactNode;
    title?: string;
    brandSettings?: {
        logo_dark?: string;
        favicon?: string;
        titleText?: string;
        footerText?: string;
    };
}

export default function Layout({ children, title = "Support Ticket", brandSettings }: SupportTicketLayoutProps) {
    const { t } = useTranslation();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <>
            <Head title={title}>
                <link rel="icon" type="image/png" href={brandSettings?.favicon || '/packages/workdo/SupportTicket/src/Resources/assets/images/favicon.png'} />
            </Head>
            
            <style>{`
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes float {
                    0% { transform: translateY(0px); }
                    50% { transform: translateY(-10px); }
                    100% { transform: translateY(0px); }
                }
            `}</style>
            
            <div className="min-h-screen font-sans relative overflow-x-hidden" style={{
                backgroundColor: '#14b8a614',
                backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%230284c7' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`
            }}>
                <div className="container mx-auto px-4 py-4 max-w-7xl relative z-10">
                    {/* Header */}
                    <header className="mb-10 md:mb-16">
                        <div className="bg-teal-600 text-white py-3 px-4 rounded-xl shadow-lg mb-4">
                            <div className="flex flex-wrap justify-between items-center">
                                {/* Logo */}
                                <h1>
                                    <a href="#" className="flex items-center space-x-3 lg:max-w-none max-w-[120px]">
                                        <img src={brandSettings?.logo_dark || '/packages/workdo/SupportTicket/src/Resources/assets/images/logo.png'} alt="WorkDo Support" className="h-8" />
                                    </a>
                                </h1>

                                {/* Desktop Navigation */}
                                <div className="main-nav hidden md:block">
                                    <nav className="flex flex-wrap justify-center md:gap-3 lg:gap-4">
                                        <a href="#" className="text-white transition font-medium lg:text-lg duration-300 flex items-center hover:text-gray-200">
                                            {t('Create Ticket')}
                                        </a>
                                        <a href="#" className="text-white transition font-medium lg:text-lg duration-300 flex items-center hover:text-gray-200">
                                            {t('Search Tickets')}
                                        </a>
                                        <a href="#" className="text-white transition font-medium lg:text-lg duration-300 flex items-center hover:text-gray-200">
                                            {t('Knowledge Base')}
                                        </a>
                                        <a href="#" className="text-white transition font-medium duration-300 lg:text-lg flex items-center hover:text-gray-200">
                                            {t('FAQ')}
                                        </a>
                                    </nav>
                                </div>

                                {/* Action Buttons */}
                                <div className="hidden md:flex items-center space-x-3">
                                    <Button variant="secondary" size="sm" className="bg-white text-gray-700 hover:bg-gray-100">
                                        <UserCircle className="h-4 w-4 mr-2" />
                                        {t('Sign In')}
                                    </Button>
                                    <Button variant="secondary" size="sm" className="bg-white text-gray-700 hover:bg-gray-100">
                                        <Headphones className="h-4 w-4 mr-2" />
                                        {t('Contact')}
                                    </Button>
                                </div>

                                {/* Mobile Menu Button */}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="md:hidden text-white"
                                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                >
                                    {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                                </Button>
                            </div>
                        </div>

                        {/* Mobile Menu */}
                        {mobileMenuOpen && (
                            <div className="bg-white rounded-xl shadow-lg mb-4 p-4 md:hidden">
                                <nav className="flex flex-col space-y-2">
                                    <a href="#" className="text-teal-600 rounded-lg transition duration-300 flex items-center p-2">
                                        <Ticket className="h-4 w-4 mr-2" /> {t('Create Ticket')}
                                    </a>
                                    <a href="#" className="text-gray-700 rounded-lg hover:text-teal-600 transition duration-300 flex items-center p-2">
                                        <Search className="h-4 w-4 mr-2" /> {t('Search Tickets')}
                                    </a>
                                    <a href="#" className="text-gray-700 rounded-lg hover:text-teal-600 transition duration-300 flex items-center p-2">
                                        <Book className="h-4 w-4 mr-2" /> {t('Knowledge Base')}
                                    </a>
                                    <a href="#" className="text-gray-700 rounded-lg hover:text-teal-600 transition duration-300 flex items-center p-2">
                                        <HelpCircle className="h-4 w-4 mr-2" /> {t('FAQ')}
                                    </a>
                                    <div className="flex flex-wrap justify-center items-center border-t pt-3 gap-3">
                                        <Button size="sm">
                                            <UserCircle className="h-4 w-4 mr-2" /> {t('Sign In')}
                                        </Button>
                                        <Button size="sm">
                                            <Headphones className="h-4 w-4 mr-2" /> {t('Contact')}
                                        </Button>
                                    </div>
                                </nav>
                            </div>
                        )}
                    </header>

                    {/* Main Content */}
                    {children}

                    {/* Help Chat Button */}
                    <div className="fixed right-4 bottom-4 md:right-8 md:bottom-8 z-10">
                        <Button
                            className="bg-teal-600 hover:bg-teal-700 text-white rounded-full w-14 h-14 p-0 shadow-lg hover:scale-110 transition-all duration-300"
                            aria-label="Get Help"
                        >
                            <Headphones className="h-6 w-6" />
                        </Button>
                    </div>

                    {/* Footer */}
                    <footer className="mt-8 md:mt-12 text-center text-gray-600 text-sm">
                        <p>{t(`© ${new Date().getFullYear()} WorkDo Support System. All rights reserved.`)}</p>
                        <div className="mt-2 flex flex-wrap justify-center space-x-4">
                            <a href="#" className="hover:text-teal-600 transition-colors duration-200">{t('Privacy Policy')}</a>
                            <a href="#" className="hover:text-teal-600 transition-colors duration-200">{t('Terms of Service')}</a>
                            <a href="#" className="hover:text-teal-600 transition-colors duration-200">{t('Contact Us')}</a>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}