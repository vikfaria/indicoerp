import { useState, useEffect } from 'react';
import { Package, Star, Download } from 'lucide-react';
import AddonCard from '../components/AddonCard';
import { router } from '@inertiajs/react';

interface ModulesProps {
    settings?: any;
    packages?: Array<{
        name: string;
        slug: string;
        description: string;
        price: string;
        yearly_price?: string;
        image?: string;
    }>;
    title?: string;
    subtitle?: string;
}

const MODULES_VARIANTS = {
    modules1: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'grid',
        defaultCard: 'card1'
    },
    modules2: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'list',
        defaultCard: 'card4'
    },
    modules3: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'cards',
        defaultCard: 'card2'
    },
    modules4: {
        section: 'bg-gray-900 py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6 text-center',
        subtitle: 'text-lg text-gray-300 mb-16 text-center',
        layout: 'slider',
        defaultCard: 'card3'
    },
    modules5: {
        section: 'bg-white py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'masonry',
        defaultCard: 'card5'
    }
};

export default function Modules({ settings, packages, title: propTitle, subtitle: propSubtitle }: ModulesProps) {
    const sectionData = settings?.config_sections?.sections?.modules || {};
    const variant = sectionData.variant || 'modules1';
    const config = MODULES_VARIANTS[variant as keyof typeof MODULES_VARIANTS] || MODULES_VARIANTS.modules1;
    
    const title = propTitle || sectionData.title || 'Premium Packages';
    const subtitle = propSubtitle || sectionData.subtitle || 'Discover powerful extensions for your business';
    const colors = { primary: 'var(--color-primary)', secondary: 'var(--color-secondary)', accent: 'var(--color-accent)' };
    
    const getSectionData = (key: string) => {
        return settings?.config_sections?.sections?.[key] || {};
    };
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);
    
    // Auto slide functionality
    useEffect(() => {
        if (!isAutoPlaying || !packages?.length) return;
        
        const interval = setInterval(() => {
            setCurrentSlide(prev => prev < packages.length - 1 ? prev + 1 : 0);
        }, 4000);
        
        return () => clearInterval(interval);
    }, [isAutoPlaying, packages?.length]);
    
    const defaultModules = [
        { name: 'Advanced CRM', description: 'Complete customer management', price: '$49', rating: '4.9', downloads: '2.5k' },
        { name: 'HR Management', description: 'Employee management system', price: '$39', rating: '4.8', downloads: '1.8k' },
        { name: 'Project Pro', description: 'Advanced project planning', price: '$59', rating: '4.9', downloads: '3.2k' }
    ];
    
    // Use packages prop if available, otherwise use settings data, otherwise use defaults
    const modules = packages?.length > 0 
        ? packages.map(pkg => ({ name: pkg.name, description: pkg.description, price: pkg.price, rating: '4.9', downloads: '1k+' }))
        : sectionData.modules?.length > 0 
        ? sectionData.modules 
        : defaultModules;

    const renderGrid = () => (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            {packages?.map((addon) => (
                <AddonCard
                    key={addon.id}
                    addon={addon}
                    colors={colors}
                    variant={getSectionData('modules').card_variant || config.defaultCard}
                    onViewDetails={() => {
                        router.visit(route('marketplace', { slug: addon.package_name }));
                    }}
                />
            ))}
        </div>
    );

    const renderList = () => {
        const cardVariant = getSectionData('modules').card_variant || config.defaultCard;
        const needsGrid = ['card1', 'card2', 'card3', 'card5'].includes(cardVariant);
        
        return (
            <div className={needsGrid ? "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8" + (cardVariant === 'card1' ? " pt-8" : "") : cardVariant === 'card4' ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" : "max-w-4xl mx-auto space-y-3"}>
                {packages?.map((addon) => (
                    <AddonCard
                        key={addon.id}
                        addon={addon}
                        colors={colors}
                        variant={cardVariant as any}
                        onViewDetails={() => {
                            router.visit(route('marketplace', { slug: addon.package_name }));
                        }}
                    />
                ))}
            </div>
        );
    };

    const renderCards = () => {
        const cardVariant = getSectionData('modules').card_variant || config.defaultCard;
        const isHorizontal = cardVariant === 'card4';
        
        return (
            <div className={isHorizontal ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" : "grid grid-cols-1 md:grid-cols-2 gap-8" + (cardVariant === 'card1' ? " pt-8" : "")}>
                {packages?.map((addon) => (
                    <AddonCard
                        key={addon.id}
                        addon={addon}
                        colors={colors}
                        variant={cardVariant as any}
                        onViewDetails={() => {
                            router.visit(route('marketplace', { slug: addon.package_name }));
                        }}
                    />
                ))}
            </div>
        );
    };

    const renderSlider = () => {
        const currentPackage = packages?.[currentSlide];
        const cardVariant = getSectionData('modules').card_variant || config.defaultCard;
        
        if (!currentPackage) return null;
        
        return (
            <div className="relative max-w-5xl mx-auto">
                <div className="bg-gradient-to-br from-gray-900 to-gray-800 p-10 rounded-2xl shadow-2xl">
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center space-x-2 bg-white/10 rounded-full px-4 py-2 mb-4">
                            <Package className="h-4 w-4 text-white" />
                            <span className="text-white text-sm font-medium">{currentSlide + 1} of {packages.length}</span>
                        </div>
                        <h3 className="text-3xl font-bold text-white mb-3">{currentPackage.name}</h3>
                        <p className="text-gray-300 text-lg max-w-2xl mx-auto">{currentPackage.description}</p>
                    </div>
                    
                    <div className="flex justify-center mb-8">
                        <div className="w-full max-w-sm">
                            <AddonCard
                                addon={currentPackage}
                                colors={colors}
                                variant={cardVariant as any}
                                onViewDetails={() => {
                                    router.visit(route('marketplace', { slug: currentPackage.package_name }));
                                }}
                            />
                        </div>
                    </div>
                    
                    <div className="flex items-center justify-center space-x-4">
                        <button
                            onClick={() => {
                                setCurrentSlide(currentSlide > 0 ? currentSlide - 1 : packages.length - 1);
                                setIsAutoPlaying(false);
                            }}
                            className="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                        >
                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        
                        <div className="flex space-x-2">
                            {packages.map((_: any, index: number) => (
                                <button
                                    key={index}
                                    onClick={() => {
                                        setCurrentSlide(index);
                                        setIsAutoPlaying(false);
                                    }}
                                    className={`transition-all duration-300 rounded-full ${
                                        currentSlide === index 
                                            ? 'w-8 h-3 bg-white' 
                                            : 'w-3 h-3 bg-white/40 hover:bg-white/60'
                                    }`}
                                />
                            ))}
                        </div>
                        
                        <button
                            onClick={() => {
                                setCurrentSlide(currentSlide < packages.length - 1 ? currentSlide + 1 : 0);
                                setIsAutoPlaying(false);
                            }}
                            className="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                        >
                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    const renderMasonry = () => {
        const cardVariant = getSectionData('modules').card_variant || config.defaultCard;
        const isHorizontal = cardVariant === 'card4';
        
        return (
            <div className={isHorizontal ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" : "columns-1 md:columns-2 lg:columns-3 gap-6 space-y-6" + (cardVariant === 'card1' ? " pt-8" : "")}>
                {packages?.map((addon, index) => (
                    <div key={addon.id} className={isHorizontal ? "" : "break-inside-avoid"}>
                        <AddonCard
                            addon={addon}
                            colors={colors}
                            variant={cardVariant as any}
                            onViewDetails={() => {
                                router.visit(route('marketplace', { slug: addon.package_name }));
                            }}
                        />
                    </div>
                ))}
            </div>
        );
    };

    const renderContent = () => {
        switch (config.layout) {
            case 'list':
                return renderList();
            case 'cards':
                return renderCards();
            case 'slider':
                return renderSlider();
            case 'masonry':
                return renderMasonry();
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