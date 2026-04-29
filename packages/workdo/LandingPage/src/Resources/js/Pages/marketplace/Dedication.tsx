import { useState, useEffect } from 'react';
import { CheckCircle, ChevronDown } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { getImagePath } from '@/utils/helpers';

interface DedicationSubSection {
    title: string;
    description: string;
    keyPoints: string[];
    screenshot: string;
}

interface DedicationProps {
    settings?: any;
    title?: string;
    description?: string;
    subSections?: Array<{
        title: string;
        description: string;
        keyPoints: string[];
        screenshot: string;
    }>;
}

const DEDICATION_VARIANTS = {
    dedication1: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 max-w-3xl mx-auto text-center',
        layout: 'alternating'
    },
    dedication2: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'cards'
    },
    dedication3: {
        section: 'bg-white py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'tabs'
    },
    dedication4: {
        section: 'bg-gray-900 py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6 text-center',
        subtitle: 'text-lg text-gray-300 mb-16 text-center',
        layout: 'timeline'
    },
    dedication5: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'carousel'
    }
};

export default function Dedication({ settings, title: propTitle, description: propDescription, subSections: propSubSections }: DedicationProps) {
    const { t } = useTranslation();
    const sectionData = settings?.config_sections?.sections?.dedication || {};
    const variant = sectionData.variant || 'dedication1';
    const config = DEDICATION_VARIANTS[variant as keyof typeof DEDICATION_VARIANTS] || DEDICATION_VARIANTS.dedication1;
    
    const title = propTitle || sectionData.title || 'Dedicated to Excellence';
    const description = propDescription || sectionData.description || 'Our premium packages are crafted with attention to detail, ensuring seamless integration and powerful functionality for your business needs.';
    const colors = { 
        primary: settings?.config_sections?.colors?.primary || 'var(--color-primary)' || '#10b77f', 
        secondary: settings?.config_sections?.colors?.secondary || 'var(--color-secondary)' || '#059669', 
        accent: settings?.config_sections?.colors?.accent || 'var(--color-accent)' || '#f59e0b' 
    };
 
    
    const subSections = propSubSections?.length > 0 ? propSubSections : sectionData.subSections?.length > 0 ? sectionData.subSections : [];
    
    const [activeTab, setActiveTab] = useState(0);
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);
    
    // Auto slide functionality for carousel
    useEffect(() => {
        if (!isAutoPlaying || !subSections?.length || config?.layout !== 'carousel') return;
        
        const interval = setInterval(() => {
            setCurrentSlide(prev => {
                if (!subSections?.length) return 0;
                return prev < subSections.length - 1 ? prev + 1 : 0;
            });
        }, 5000);
        
        return () => clearInterval(interval);
    }, [isAutoPlaying, subSections, config?.layout]);

    const renderAlternating = () => (
        <div className="space-y-20">
            {subSections.map((section: any, index: number) => {
                const isEven = index % 2 === 0;
                return (
                    <div key={index} className={`grid grid-cols-1 lg:grid-cols-2 gap-12 items-center ${!isEven ? 'lg:grid-flow-col-dense' : ''}`}>
                        <div className={isEven ? 'lg:order-1' : 'lg:order-2'}>
                            <h3 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4">{section.title}</h3>
                            <p className="text-gray-600 mb-6 leading-relaxed">{section.description}</p>
                            <div className="space-y-3">
                                {section.keyPoints?.map((point: string, pointIndex: number) => (
                                    <div key={pointIndex} className="flex items-center">
                                        <CheckCircle className="h-5 w-5 text-green-600 mr-3 flex-shrink-0" />
                                        <span className="text-gray-700">{point}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className={isEven ? 'lg:order-2' : 'lg:order-1'}>
                            <div className="relative">
                                <img src={getImagePath(section.screenshot)} alt={section.title} className="w-full rounded-lg shadow-2xl" />
                                <div className="absolute inset-0 from-black/20 to-transparent rounded-lg"></div>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );

    const renderCards = () => (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {subSections.map((section: any, index: number) => (
                <div key={index} className="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div className="h-48 mb-6 rounded-lg overflow-hidden">
                        <img src={getImagePath(section.screenshot)} alt={section.title} className="w-full h-full object-cover" />
                    </div>
                    <h3 className="text-xl font-bold text-gray-900 mb-3">{section.title}</h3>
                    <p className="text-gray-600 mb-4">{section.description}</p>
                    <div className="space-y-2">
                        {section.keyPoints?.slice(0, 3).map((point: string, pointIndex: number) => (
                            <div key={pointIndex} className="flex items-center">
                                <CheckCircle className="h-4 w-4 text-green-600 mr-2 flex-shrink-0" />
                                <span className="text-gray-700 text-sm">{point}</span>
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );

    const renderTabs = () => (
        <div>
            <div className="flex flex-wrap justify-center mb-12 border-b">
                {subSections.map((section: any, index: number) => (
                    <button
                        key={index}
                        onClick={() => setActiveTab(index)}
                        className={`px-6 py-3 font-medium text-sm border-b-2 transition-colors ${
                            activeTab === index
                                ? 'text-white rounded-t-lg'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        }`}
                        style={activeTab === index ? { backgroundColor: colors.primary, borderColor: colors.primary } : {}}
                    >
                        {section.title}
                    </button>
                ))}
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 className="text-2xl font-bold text-gray-900 mb-4">{subSections[activeTab]?.title}</h3>
                    <p className="text-gray-600 mb-6">{subSections[activeTab]?.description}</p>
                    <div className="space-y-3">
                        {subSections[activeTab]?.keyPoints?.map((point: string, pointIndex: number) => (
                            <div key={pointIndex} className="flex items-center">
                                <CheckCircle className="h-5 w-5 text-green-600 mr-3 flex-shrink-0" />
                                <span className="text-gray-700">{point}</span>
                            </div>
                        ))}
                    </div>
                </div>
                <div>
                    <img src={getImagePath(subSections[activeTab]?.screenshot)} alt={subSections[activeTab]?.title} className="w-full rounded-lg shadow-xl" />
                </div>
            </div>
        </div>
    );

    const renderTimeline = () => (
        <div className="relative max-w-4xl mx-auto">
            <div className="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-600"></div>
            <div className="space-y-16">
                {subSections.map((section: any, index: number) => (
                    <div key={index} className="relative flex items-start space-x-8">
                        <div className="flex-shrink-0 w-16 h-16 rounded-full flex items-center justify-center shadow-lg" style={{ backgroundColor: colors.primary }}>
                            <span className="text-white font-bold text-lg">{index + 1}</span>
                        </div>
                        <div className="flex-1 bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-xl shadow-2xl">
                            <h3 className="text-2xl font-bold text-white mb-4">{section.title}</h3>
                            <p className="text-gray-300 mb-6 leading-relaxed">{section.description}</p>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                                <div className="space-y-3">
                                    {section.keyPoints?.map((point: string, pointIndex: number) => (
                                        <div key={pointIndex} className="flex items-center">
                                            <CheckCircle className="h-5 w-5 text-green-400 mr-3 flex-shrink-0" />
                                            <span className="text-gray-300">{point}</span>
                                        </div>
                                    ))}
                                </div>
                                <div className="relative group">
                                    <div className="h-48 rounded-lg overflow-hidden shadow-xl">
                                        <img src={getImagePath(section.screenshot)} alt={section.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    </div>
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-lg"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );

    const renderCarousel = () => {
        const currentSection = subSections?.[currentSlide];
        if (!currentSection) return null;
        
        return (
            <div className="relative">
                <div className="overflow-hidden rounded-xl">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center p-8 bg-gray-50 rounded-xl">
                        <div>
                            <h3 className="text-2xl font-bold text-gray-900 mb-4">{currentSection.title}</h3>
                            <p className="text-gray-600 mb-6">{currentSection.description}</p>
                        <div className="space-y-3 mb-6">
                            {currentSection.keyPoints?.map((point: string, pointIndex: number) => (
                                <div key={pointIndex} className="flex items-center">
                                    <CheckCircle className="h-5 w-5 text-green-600 mr-3 flex-shrink-0" />
                                    <span className="text-gray-700">{point}</span>
                                </div>
                            ))}
                        </div>
                        <div className="flex items-center space-x-4">
                            <button
                                onClick={() => {
                                    if (subSections?.length) {
                                        setCurrentSlide(currentSlide > 0 ? currentSlide - 1 : subSections.length - 1);
                                        setIsAutoPlaying(false);
                                    }
                                }}
                                className="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition-colors"
                            >
                                <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <div className="flex space-x-2">
                                {subSections.map((_: any, index: number) => (
                                    <button
                                        key={index}
                                        onClick={() => {
                                            setCurrentSlide(index);
                                            setIsAutoPlaying(false);
                                        }}
                                        className={`transition-all duration-300 rounded-full ${
                                            currentSlide === index 
                                                ? 'w-8 h-3' 
                                                : 'w-3 h-3 bg-gray-300 hover:bg-gray-400'
                                        }`}
                                        style={currentSlide === index ? { backgroundColor: colors.primary } : {}}
                                    />
                                ))}
                            </div>
                            
                            <button
                                onClick={() => {
                                    if (subSections?.length) {
                                        setCurrentSlide(currentSlide < subSections.length - 1 ? currentSlide + 1 : 0);
                                        setIsAutoPlaying(false);
                                    }
                                }}
                                className="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition-colors"
                            >
                                <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <img src={getImagePath(currentSection.screenshot)} alt={currentSection.title} className="w-full rounded-lg shadow-xl" />
                    </div>
                </div>
            </div>
        </div>
        );
    };

    const renderContent = () => {
        if (!subSections?.length) {
            return (
                <div className="text-center py-12">
                    <p className="text-gray-500">{t('No content available')}</p>
                </div>
            );
        }
        
        switch (config?.layout) {
            case 'cards':
                return renderCards();
            case 'tabs':
                return renderTabs();
            case 'timeline':
                return renderTimeline();
            case 'carousel':
                return renderCarousel();
            default:
                return renderAlternating();
        }
    };

    return (
        <section className={config.section}>
            <div className={config.container}>
                <div className="text-center mb-16">
                    <h2 className={config.title}>{title}</h2>
                    <p className={config.subtitle}>{description}</p>
                </div>
                {renderContent()}
            </div>
        </section>
    );
}