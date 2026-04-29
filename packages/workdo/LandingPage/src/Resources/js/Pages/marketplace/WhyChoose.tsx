import { useState } from 'react';
import { Shield, Zap, Users, Headphones, Award, RefreshCw, Building2, Calculator, CreditCard, UserCheck, FolderOpen, ChevronDown } from 'lucide-react';

interface WhyChooseProps {
    settings?: any;
    title?: string;
    subtitle?: string;
    benefits?: Array<{
        title: string;
        description: string;
        icon: string;
        color: string;
    }>;
}

const WHYCHOOSE_VARIANTS = {
    whychoose1: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'grid'
    },
    whychoose2: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'list'
    },
    whychoose3: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'cards'
    },
    whychoose4: {
        section: 'bg-gray-900 py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6 text-center',
        subtitle: 'text-lg text-gray-300 mb-16 text-center',
        layout: 'timeline'
    },
    whychoose5: {
        section: 'bg-white py-20',
        container: 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'accordion'
    }
};

export default function WhyChoose({ settings, title: propTitle, subtitle: propSubtitle, benefits: propBenefits }: WhyChooseProps) {
    const sectionData = settings?.config_sections?.sections?.why_choose || {};
    const variant = sectionData.variant || 'whychoose1';
    const config = WHYCHOOSE_VARIANTS[variant as keyof typeof WHYCHOOSE_VARIANTS] || WHYCHOOSE_VARIANTS.whychoose1;
    
    const title = propTitle || sectionData.title || 'Why Choose Our Marketplace?';
    const subtitle = propSubtitle || sectionData.subtitle || 'We provide the most comprehensive and reliable marketplace solutions';
    const colors = { primary: 'var(--color-primary)', secondary: 'var(--color-secondary)', accent: 'var(--color-accent)' };
    const [openAccordion, setOpenAccordion] = useState(0);
    
    const defaultBenefits = [
        { title: 'Secure & Reliable', description: 'All packages are thoroughly tested and secure', icon: 'Shield', color: 'blue' },
        { title: 'Fast Performance', description: 'Optimized for speed and efficiency', icon: 'Zap', color: 'yellow' },
        { title: '24/7 Support', description: 'Round-the-clock customer assistance', icon: 'Headphones', color: 'green' }
    ];
    
    const benefits = propBenefits?.length > 0 ? propBenefits : sectionData.benefits?.length > 0 ? sectionData.benefits : defaultBenefits;

    const getIcon = (iconName: string) => {
        const icons = { Shield, Zap, Users, Headphones, Award, RefreshCw, Building2, Calculator, CreditCard, UserCheck, FolderOpen };
        return icons[iconName as keyof typeof icons] || Shield;
    };

    const getColorClass = (color: string) => {
        const colors = {
            blue: 'bg-blue-100 text-blue-600',
            green: 'bg-green-100 text-green-600',
            yellow: 'bg-yellow-100 text-yellow-600',
            purple: 'bg-purple-100 text-purple-600',
            red: 'bg-red-100 text-red-600',
            indigo: 'bg-indigo-100 text-indigo-600',
            pink: 'bg-pink-100 text-pink-600',
            gray: 'bg-gray-100 text-gray-600'
        };
        return colors[color as keyof typeof colors] || colors.blue;
    };

    const renderGrid = () => (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {benefits.map((benefit: any, index: number) => {
                const IconComponent = getIcon(benefit.icon);
                const colorClass = getColorClass(benefit.color);
                return (
                    <div key={index} className="text-center p-6 hover:bg-gray-50 rounded-lg transition-colors">
                        <div className={`w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center ${colorClass}`}>
                            <IconComponent className="h-8 w-8" />
                        </div>
                        <h3 className="text-xl font-semibold text-gray-900 mb-3">{benefit.title}</h3>
                        <p className="text-gray-600">{benefit.description}</p>
                    </div>
                );
            })}
        </div>
    );

    const renderList = () => (
        <div className="space-y-8">
            {benefits.map((benefit: any, index: number) => {
                const IconComponent = getIcon(benefit.icon);
                const colorClass = getColorClass(benefit.color);
                return (
                    <div key={index} className="flex items-start space-x-6 p-6 bg-white rounded-lg shadow-sm">
                        <div className={`flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center ${colorClass}`}>
                            <IconComponent className="h-6 w-6" />
                        </div>
                        <div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">{benefit.title}</h3>
                            <p className="text-gray-600">{benefit.description}</p>
                        </div>
                    </div>
                );
            })}
        </div>
    );

    const renderCards = () => (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {benefits.map((benefit: any, index: number) => {
                const IconComponent = getIcon(benefit.icon);
                const colorClass = getColorClass(benefit.color);
                return (
                    <div key={index} className="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                        <div className={`w-14 h-14 mb-6 rounded-lg flex items-center justify-center ${colorClass}`}>
                            <IconComponent className="h-7 w-7" />
                        </div>
                        <h3 className="text-2xl font-semibold text-gray-900 mb-4">{benefit.title}</h3>
                        <p className="text-gray-600 text-lg">{benefit.description}</p>
                    </div>
                );
            })}
        </div>
    );

    const renderTimeline = () => (
        <div className="relative">
            <div className="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-600"></div>
            <div className="space-y-12">
                {benefits.map((benefit: any, index: number) => {
                    const IconComponent = getIcon(benefit.icon);
                    return (
                        <div key={index} className="relative flex items-start space-x-8">
                            <div className="flex-shrink-0 w-16 h-16 rounded-full flex items-center justify-center" style={{ backgroundColor: colors.primary }}>
                                <IconComponent className="h-8 w-8 text-white" />
                            </div>
                            <div className="flex-1 bg-gray-800 p-6 rounded-lg">
                                <h3 className="text-xl font-semibold text-white mb-3">{benefit.title}</h3>
                                <p className="text-gray-300">{benefit.description}</p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );

    const renderAccordion = () => (
        <div className="space-y-4">
            {benefits.map((benefit: any, index: number) => {
                const IconComponent = getIcon(benefit.icon);
                const colorClass = getColorClass(benefit.color);
                return (
                    <div key={index} className="border border-gray-200 rounded-lg overflow-hidden">
                        <button
                            onClick={() => setOpenAccordion(openAccordion === index ? -1 : index)}
                            className="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <div className="flex items-center space-x-4">
                                <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${colorClass}`}>
                                    <IconComponent className="h-5 w-5" />
                                </div>
                                <span className="font-semibold text-gray-900">{benefit.title}</span>
                            </div>
                            <ChevronDown className={`h-5 w-5 text-gray-500 transition-transform ${openAccordion === index ? 'rotate-180' : ''}`} />
                        </button>
                        {openAccordion === index && (
                            <div className="px-6 pb-4 text-gray-600">
                                {benefit.description}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );

    const renderContent = () => {
        switch (config.layout) {
            case 'list':
                return renderList();
            case 'cards':
                return renderCards();
            case 'timeline':
                return renderTimeline();
            case 'accordion':
                return renderAccordion();
            default:
                return renderGrid();
        }
    };

    return (
        <section className={config.section}>
            <div className={config.container}>
                <h2 className={config.title}>{title}</h2>
                <p className={config.subtitle}>{subtitle}</p>
                {renderContent()}
            </div>
        </section>
    );
}