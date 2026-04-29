import { useState, useEffect } from 'react';
import { Building2, Calculator, Users, CreditCard, UserCheck, FolderOpen, ChevronLeft, ChevronRight } from 'lucide-react';

interface FeaturesProps {
    settings?: any;
}

const FEATURES_VARIANTS = {
    features1: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center max-w-3xl mx-auto',
        grid: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8',
        layout: 'grid'
    },
    features2: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        grid: 'space-y-8',
        layout: 'list'
    },
    features3: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        grid: 'grid grid-cols-1 md:grid-cols-2 gap-8',
        layout: 'cards'
    },
    features4: {
        section: 'bg-gray-900 py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6',
        subtitle: 'text-lg text-gray-300 mb-16',
        grid: 'grid grid-cols-1 lg:grid-cols-2 gap-12 items-center',
        layout: 'split'
    },
    features5: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-full px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        grid: 'relative overflow-hidden',
        layout: 'carousel'
    }
};

export default function Features({ settings }: FeaturesProps) {
    const sectionData = settings?.config_sections?.sections?.features || {};
    const variant = sectionData.variant || 'features1';
    const config = FEATURES_VARIANTS[variant as keyof typeof FEATURES_VARIANTS] || FEATURES_VARIANTS.features1;
    
    const title = sectionData.title || 'Powerful Features';
    const subtitle = sectionData.subtitle || 'Everything your business needs in one integrated platform';
    const colors = settings?.config_sections?.colors || { primary: '#10b77f', secondary: '#059669', accent: '#f59e0b' };
    const [currentSlide, setCurrentSlide] = useState(0);
    
    const defaultFeatures = [
        { title: 'ERP Management', description: 'Complete enterprise resource planning', icon: 'Building2' },
        { title: 'Accounting', description: 'Advanced financial management', icon: 'Calculator' },
        { title: 'CRM System', description: 'Customer relationship management', icon: 'Users' }
    ];
    
    const features = sectionData.features?.length > 0 ? sectionData.features : defaultFeatures;
    const duplicatedFeatures = [...features, ...features]; // Duplicate for infinite scroll
    
    // Auto slide for carousel
    useEffect(() => {
        if (config.layout === 'carousel' && features.length > 1) {
            const interval = setInterval(() => {
                setCurrentSlide(prev => {
                    const next = prev + 1;
                    if (next >= features.length) {
                        setTimeout(() => setCurrentSlide(0), 500);
                        return next;
                    }
                    return next;
                });
            }, 4000);
            return () => clearInterval(interval);
        }
    }, [config.layout, features.length]);

    const getIcon = (iconName: string) => {
        const icons = { Building2, Calculator, Users, CreditCard, UserCheck, FolderOpen };
        return icons[iconName as keyof typeof icons] || Building2;
    };

    const renderFeature = (feature: any, index: number) => {
        const IconComponent = getIcon(feature.icon);

        if (config.layout === 'grid') {
            return (
                <div key={index} className="text-center p-6 hover:bg-gray-50 rounded-lg transition-colors">
                    <div className="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center" style={{ backgroundColor: colors.primary }}>
                        <IconComponent className="h-8 w-8 text-white" />
                    </div>
                    <h3 className="text-xl font-semibold text-gray-900 mb-3">{feature.title}</h3>
                    <p className="text-gray-600">{feature.description}</p>
                </div>
            );
        }

        if (config.layout === 'list') {
            return (
                <div key={index} className="flex items-start space-x-6 p-6 bg-white rounded-lg shadow-sm">
                    <div className="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" style={{ backgroundColor: colors.primary }}>
                        <IconComponent className="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h3 className="text-xl font-semibold text-gray-900 mb-2">{feature.title}</h3>
                        <p className="text-gray-600">{feature.description}</p>
                    </div>
                </div>
            );
        }

        if (config.layout === 'cards') {
            return (
                <div key={index} className="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div className="w-14 h-14 mb-6 rounded-lg flex items-center justify-center" style={{ backgroundColor: colors.primary }}>
                        <IconComponent className="h-7 w-7 text-white" />
                    </div>
                    <h3 className="text-2xl font-semibold text-gray-900 mb-4">{feature.title}</h3>
                    <p className="text-gray-600 text-lg">{feature.description}</p>
                </div>
            );
        }

        if (config.layout === 'split') {
            return (
                <div key={index} className="flex items-center space-x-4 p-4 bg-gray-800 rounded-lg">
                    <div className="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" style={{ backgroundColor: colors.primary }}>
                        <IconComponent className="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold text-white mb-1">{feature.title}</h3>
                        <p className="text-gray-300 text-sm">{feature.description}</p>
                    </div>
                </div>
            );
        }

        if (config.layout === 'carousel') {
            return (
                <div key={index} className="flex-shrink-0 w-80 mr-6 group">
                    <div className="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-gray-200 transform hover:-translate-y-2 h-full">
                        <div className="w-16 h-16 mb-6 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300" style={{ backgroundColor: `${colors.primary}15` }}>
                            <IconComponent className="h-8 w-8" style={{ color: colors.primary }} />
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-4 group-hover:text-gray-700 transition-colors">{feature.title}</h3>
                        <p className="text-gray-600 leading-relaxed mb-6">{feature.description}</p>
                        <div className="w-12 h-1 rounded-full transition-all duration-300" style={{ backgroundColor: colors.primary }}></div>
                    </div>
                </div>
            );
        }

        return null;
    };

    if (config.layout === 'split') {
        return (
            <section className={config.section}>
                <div className={config.container}>
                    <div className={config.grid}>
                        <div>
                            <h2 className={config.title}>{title}</h2>
                            <p className={config.subtitle}>{subtitle}</p>
                        </div>
                        <div className="space-y-4">
                            {features.map((feature: any, index: number) => renderFeature(feature, index))}
                        </div>
                    </div>
                </div>
            </section>
        );
    }

    if (config.layout === 'carousel') {
        const nextSlide = () => {
            setCurrentSlide(prev => {
                const next = prev + 1;
                if (next >= features.length) {
                    setTimeout(() => setCurrentSlide(0), 500);
                    return next;
                }
                return next;
            });
        };
        
        const prevSlide = () => {
            setCurrentSlide(prev => {
                if (prev === 0) {
                    setCurrentSlide(features.length);
                    setTimeout(() => setCurrentSlide(features.length - 1), 50);
                    return features.length - 1;
                }
                return prev - 1;
            });
        };
        
        return (
            <section className={config.section}>
                <div className={config.container}>
                    <h2 className={config.title}>{title}</h2>
                    <p className={config.subtitle}>{subtitle}</p>
                    <div className="relative">
                        <div className={config.grid}>
                            <div 
                                className={`flex transition-transform duration-500 ease-in-out ${currentSlide >= features.length ? 'transition-none' : ''}`}
                                style={{ transform: `translateX(-${currentSlide * 344}px)` }}
                            >
                                {duplicatedFeatures.map((feature: any, index: number) => renderFeature(feature, index))}
                            </div>
                        </div>
                        <button
                            onClick={prevSlide}
                            className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-3 rounded-full shadow-xl transition-all hover:scale-110 z-10"
                        >
                            <ChevronLeft className="w-6 h-6" />
                        </button>
                        <button
                            onClick={nextSlide}
                            className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-3 rounded-full shadow-xl transition-all hover:scale-110 z-10"
                        >
                            <ChevronRight className="w-6 h-6" />
                        </button>
                        <div className="flex justify-center mt-8 space-x-2">
                            {features.map((_, index) => (
                                <button
                                    key={index}
                                    onClick={() => setCurrentSlide(index)}
                                    className={`w-3 h-3 rounded-full transition-all duration-300 ${
                                        currentSlide === index ? 'scale-125' : 'hover:scale-110'
                                    }`}
                                    style={{ backgroundColor: currentSlide === index ? colors.primary : '#d1d5db' }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </section>
        );
    }
    
    return (
        <section className={config.section}>
            <div className={config.container}>
                <h2 className={config.title}>{title}</h2>
                <p className={config.subtitle}>{subtitle}</p>
                <div className={config.grid}>
                    {features.map((feature: any, index: number) => renderFeature(feature, index))}
                </div>
            </div>
        </section>
    );
}