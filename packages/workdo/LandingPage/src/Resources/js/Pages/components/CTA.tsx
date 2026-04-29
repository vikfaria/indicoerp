import { ArrowRight } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

interface CTAProps {
    settings?: any;
}

const CTA_VARIANTS = {
    cta1: {
        section: 'py-20',
        container: 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6',
        subtitle: 'text-xl text-white/90 mb-8',
        buttons: 'flex flex-col sm:flex-row gap-4 justify-center',
        layout: 'centered'
    },
    cta2: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6',
        subtitle: 'text-xl text-gray-600 mb-8',
        buttons: 'flex flex-col sm:flex-row gap-4',
        layout: 'split'
    },
    cta3: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6',
        subtitle: 'text-lg text-gray-600 mb-8',
        buttons: 'flex flex-col sm:flex-row gap-4 justify-center',
        layout: 'card'
    },
    cta4: {
        section: 'py-20',
        container: 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center',
        title: 'text-4xl md:text-5xl font-bold text-white mb-6',
        subtitle: 'text-xl text-white/90 mb-10',
        buttons: 'flex flex-col sm:flex-row gap-6 justify-center',
        layout: 'gradient'
    },
    cta5: {
        section: 'bg-gradient-to-b from-gray-50 to-white py-20 border-t border-gray-100',
        container: 'max-w-4xl mx-auto px-6 sm:px-8 lg:px-12 text-center',
        title: 'text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 mb-6',
        subtitle: 'text-base md:text-lg text-gray-600 mb-8',
        buttons: 'flex flex-col sm:flex-row gap-4 justify-center items-center',
        layout: 'minimal'
    }
};

export default function CTA({ settings }: CTAProps) {
    const { t } = useTranslation();
    const sectionData = settings?.config_sections?.sections?.cta || {};
    const variant = sectionData.variant || 'cta1';
    const config = CTA_VARIANTS[variant as keyof typeof CTA_VARIANTS] || CTA_VARIANTS.cta1;
    
    const title = sectionData.title || 'Ready to Transform Your Business?';
    const subtitle = sectionData.subtitle || 'Join thousands of businesses already using ERPGo SaaS to streamline their operations.';
    const primaryButton = sectionData.primary_button || 'Start Free Trial';
    const secondaryButton = sectionData.secondary_button || 'Contact Sales';
    const colors = settings?.config_sections?.colors || { primary: '#10b77f', secondary: '#059669', accent: '#f59e0b' };

    const getBackgroundStyle = () => {
        if (config.layout === 'centered') {
            return { backgroundColor: colors.primary };
        }
        if (config.layout === 'gradient') {
            return { 
                background: `linear-gradient(135deg, ${colors.primary} 0%, ${colors.secondary} 50%, ${colors.accent} 100%)`,
                backgroundAttachment: 'fixed'
            };
        }
        return {};
    };

    const renderButtons = () => {
        const primaryLink = sectionData.primary_button_link || '#';
        const secondaryLink = sectionData.secondary_button_link || '#';
        
        return (
            <div className={config.buttons}>
                <a 
                    href={primaryLink}
                    className={`inline-flex items-center justify-center text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 hover:shadow-lg ${
                        config.layout === 'minimal' 
                            ? 'text-base px-8 py-3.5 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5' 
                            : config.layout === 'card'
                                ? 'text-base px-10 py-4 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1'
                                : 'text-lg'
                    } ${config.layout === 'split' ? 'shadow-lg hover:shadow-xl' : 'bg-white/20 hover:bg-white/30 backdrop-blur-sm'}`}
                    style={{ 
                        backgroundColor: config.layout === 'split' || config.layout === 'minimal' || config.layout === 'card' ? colors.primary : undefined,
                        boxShadow: config.layout === 'minimal' || config.layout === 'card' ? `0 4px 14px 0 ${colors.primary}40` : undefined
                    }}
                >
                    {primaryButton}
                    <ArrowRight className={`ml-2 ${config.layout === 'minimal' || config.layout === 'card' ? 'h-5 w-5' : 'h-5 w-5'}`} />
                </a>
                <a 
                    href={secondaryLink}
                    className={`inline-flex items-center justify-center border-2 px-8 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 ${
                        config.layout === 'minimal' 
                            ? 'text-base px-8 py-3.5 border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400 rounded-lg shadow-sm hover:shadow-md transform hover:-translate-y-0.5' 
                            : config.layout === 'card'
                                ? 'text-base px-10 py-4 border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400 rounded-xl shadow-sm hover:shadow-lg transform hover:-translate-y-1'
                                : config.layout === 'split'
                                    ? 'border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-400 shadow-md hover:shadow-lg'
                                    : 'border-white text-white hover:bg-white hover:text-gray-900 backdrop-blur-sm'
                    }`}
                >
                    {secondaryButton}
                </a>
            </div>
        );
    };

    if (config.layout === 'split') {
        return (
            <section className={config.section}>
                <div className={config.container}>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h2 className={config.title}>{title}</h2>
                            <p className={config.subtitle}>{subtitle}</p>
                            {renderButtons()}
                        </div>
                        <div className="relative overflow-hidden rounded-xl shadow-2xl">
                            {sectionData.image ? (
                                <img 
                                    src={sectionData.image.startsWith('http') ? sectionData.image : getImagePath(sectionData.image)}
                                    alt="CTA Image"
                                    className="w-full h-80 object-cover"
                                />
                            ) : (
                                <div className="bg-gradient-to-br from-gray-100 to-gray-200 h-80 flex items-center justify-center">
                                    <div className="text-center">
                                        <div className="w-16 h-16 bg-gray-300 rounded-full mx-auto mb-4 flex items-center justify-center">
                                            <svg className="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <p className="text-gray-500 font-medium">{t('Upload CTA Image')}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        );
    }

    if (config.layout === 'card') {
        return (
            <section className={config.section}>
                <div className={config.container}>
                    <div className="bg-white p-8 md:p-12 lg:p-16 rounded-2xl shadow-xl text-center border border-gray-200/50 relative overflow-hidden">
                        <div className="absolute inset-0 bg-gradient-to-br from-gray-50/80 via-white to-gray-50/80"></div>
                        <div className="relative z-10 space-y-8">
                            <div className="space-y-6">
                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4" style={{
                                    backgroundColor: `${colors.primary}15`,
                                    border: `2px solid ${colors.primary}25`
                                }}>
                                    <svg className="w-8 h-8" style={{ color: colors.primary }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 mb-4 leading-tight">{title}</h2>
                                    <p className="text-base md:text-lg text-gray-600 max-w-xl mx-auto leading-relaxed">{subtitle}</p>
                                </div>
                            </div>
                            <div className="pt-4">
                                {renderButtons()}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        );
    }

    if (config.layout === 'minimal') {
        return (
            <section className={config.section}>
                <div className={config.container}>
                    <div className="w-16 h-1 mx-auto mb-6 rounded-full" style={{ backgroundColor: colors.primary }}></div>
                    <h2 className={`${config.title} leading-tight tracking-tight`}>{title}</h2>
                    <p className={`${config.subtitle} max-w-2xl mx-auto leading-relaxed`}>{subtitle}</p>
                    {renderButtons()}
                </div>
            </section>
        );
    }

    return (
        <section className={config.section} style={getBackgroundStyle()}>
            {config.layout === 'gradient' && (
                <div className="absolute inset-0 bg-black/20"></div>
            )}
            <div className={`${config.container} relative z-10`}>
                <h2 className={config.title}>{title}</h2>
                <p className={config.subtitle}>{subtitle}</p>
                {renderButtons()}
            </div>
        </section>
    );
}