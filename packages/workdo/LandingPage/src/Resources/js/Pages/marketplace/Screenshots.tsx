import { useState, useEffect } from 'react';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

interface ScreenshotsProps {
    settings?: any;
    title?: string;
    subtitle?: string;
    screenshots?: string[];
}

const SCREENSHOTS_VARIANTS = {
    screenshots1: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'grid'
    },
    screenshots2: {
        section: 'bg-gray-50 py-20',
        container: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'masonry'
    },
    screenshots3: {
        section: 'bg-white py-20',
        container: 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'slider'
    },
    screenshots4: {
        section: 'bg-gray-900 py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-white mb-6 text-center',
        subtitle: 'text-lg text-gray-300 mb-16 text-center',
        layout: 'lightbox'
    },
    screenshots5: {
        section: 'bg-white py-20',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        title: 'text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center',
        subtitle: 'text-lg text-gray-600 mb-16 text-center',
        layout: 'gallery'
    }
};

export default function Screenshots({ settings, title: propTitle, subtitle: propSubtitle, screenshots: propScreenshots }: ScreenshotsProps) {
    const { t } = useTranslation();
    const sectionData = settings?.config_sections?.sections?.screenshots || {};
    const variant = sectionData.variant || 'screenshots1';
    const config = SCREENSHOTS_VARIANTS[variant as keyof typeof SCREENSHOTS_VARIANTS] || SCREENSHOTS_VARIANTS.screenshots1;
    
    const title = propTitle || sectionData.title || 'Screenshots';
    const subtitle = propSubtitle || sectionData.subtitle || 'Explore our premium packages in action';
    const colors = { 
        primary: settings?.config_sections?.colors?.primary || 'var(--color-primary)' || '#3b82f6'
    };
    const [currentSlide, setCurrentSlide] = useState(0);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const [selectedImage, setSelectedImage] = useState<number | null>(null);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);
    
    const defaultImages = [
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery1.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery2.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery3.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery4.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery5.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery6.jpeg',
        'packages/workdo/LandingPage/src/Resources/assets/img/gallery7.jpeg',
    ];
    
    const images = propScreenshots?.filter((img: string) => img) || sectionData.images?.filter((img: string) => img) || defaultImages;
    
    // Auto slide functionality for slider
    useEffect(() => {
        if (!isAutoPlaying || !images?.length || config?.layout !== 'slider') return;
        
        const interval = setInterval(() => {
            setCurrentSlide(prev => (prev + 1) % images.length);
        }, 4000);
        
        return () => clearInterval(interval);
    }, [isAutoPlaying, images?.length, config?.layout]);
    
    // Add custom CSS for animations
    useEffect(() => {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes scaleIn {
                from { transform: scale(0.9); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .animate-fadeIn {
                animation: fadeIn 0.3s ease-out;
            }
            .animate-scaleIn {
                animation: scaleIn 0.3s ease-out;
            }
        `;
        document.head.appendChild(style);
        return () => document.head.removeChild(style);
    }, []);

    const renderGrid = () => (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {images.map((image: string, index: number) => (
                <div key={index} className="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                    <img
                        src={image.startsWith('http') ? image : getImagePath(image)}
                        alt={`Screenshot ${index + 1}`}
                        className="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                    <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300"></div>
                </div>
            ))}
        </div>
    );

    const renderMasonry = () => {
        return (
            <>
                <div className="columns-1 md:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-4">
                    {images.map((image: string, index: number) => {
                        const heights = ['h-48', 'h-64', 'h-80', 'h-56', 'h-72', 'h-60'];
                        const randomHeight = heights[index % heights.length];
                        
                        return (
                            <div 
                                key={index} 
                                className="break-inside-avoid group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition-all duration-500 cursor-pointer transform hover:-translate-y-1"
                                onClick={() => setSelectedImage(index)}
                            >
                                <img
                                    src={image.startsWith('http') ? image : getImagePath(image)}
                                    alt={`Screenshot ${index + 1}`}
                                    className={`w-full object-cover group-hover:scale-110 transition-transform duration-700 ${randomHeight}`}
                                />
                                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300"></div>
                                <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                    <div className="bg-white/30 backdrop-blur-md rounded-full p-3 transform scale-75 group-hover:scale-100 transition-transform duration-300 shadow-lg">
                                        <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="absolute bottom-0 left-0 right-0 p-4 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                    <div className="bg-white/90 backdrop-blur-sm rounded-lg p-2">
                                        <p className="text-sm font-medium text-gray-800">{t('Screenshot')} {index + 1}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
                
                {selectedImage !== null && (
                    <div 
                        className="fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fadeIn"
                        onClick={() => setSelectedImage(null)}
                    >
                        <div className="relative max-w-5xl max-h-full animate-scaleIn">
                            <img
                                src={images[selectedImage]?.startsWith('http') ? images[selectedImage] : getImagePath(images[selectedImage])}
                                alt={`Screenshot ${selectedImage + 1}`}
                                className="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
                            />
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setSelectedImage((prev) => prev !== null ? (prev - 1 + images.length) % images.length : 0);
                                }}
                                className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-4 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setSelectedImage((prev) => prev !== null ? (prev + 1) % images.length : 0);
                                }}
                                className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-4 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <button
                                onClick={() => setSelectedImage(null)}
                                className="absolute top-4 right-4 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
                                <p className="text-white text-sm font-medium">{selectedImage + 1} {t('of')} {images.length}</p>
                            </div>
                        </div>
                    </div>
                )}
            </>
        );
    };

    const renderSlider = () => (
        <div className="relative max-w-4xl mx-auto">
            <div className="overflow-hidden rounded-xl shadow-2xl">
                <img 
                    src={images[currentSlide]?.startsWith('http') ? images[currentSlide] : getImagePath(images[currentSlide])} 
                    alt={`Screenshot ${currentSlide + 1}`}
                    className="w-full h-[500px] object-cover"
                />
            </div>
            
            <button 
                onClick={() => {
                    setCurrentSlide((prev) => (prev - 1 + images.length) % images.length);
                    setIsAutoPlaying(false);
                }}
                className="absolute left-6 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-3 rounded-full shadow-xl transition-all hover:scale-110"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            
            <button 
                onClick={() => {
                    setCurrentSlide((prev) => (prev + 1) % images.length);
                    setIsAutoPlaying(false);
                }}
                className="absolute right-6 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-3 rounded-full shadow-xl transition-all hover:scale-110"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
            </button>
            
            <div className="flex justify-center mt-8 space-x-3">
                {images.map((_: string, index: number) => (
                    <button
                        key={index}
                        onClick={() => {
                            setCurrentSlide(index);
                            setIsAutoPlaying(false);
                        }}
                        className={`w-4 h-4 rounded-full transition-all duration-300 ${
                            index === currentSlide ? 'scale-125' : 'bg-gray-300 hover:bg-gray-500'
                        }`}
                        style={index === currentSlide ? { backgroundColor: colors.primary } : {}}
                    />
                ))}
            </div>
        </div>
    );

    const renderLightbox = () => {
        // Prevent background scroll when lightbox is open
        useEffect(() => {
            if (lightboxOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'unset';
            }
            
            return () => {
                document.body.style.overflow = 'unset';
            };
        }, [lightboxOpen]);
        
        return (
            <>
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    {images.map((image: string, index: number) => (
                        <div 
                            key={index} 
                            className="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition-all duration-500 cursor-pointer bg-gradient-to-br from-gray-800 to-gray-900 transform hover:-translate-y-2"
                            onClick={() => {
                                setLightboxIndex(index);
                                setLightboxOpen(true);
                            }}
                        >
                            <img
                                src={image.startsWith('http') ? image : getImagePath(image)}
                                alt={`Screenshot ${index + 1}`}
                                className="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-700"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300"></div>
                            <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <div className="bg-white/20 backdrop-blur-md rounded-full p-4 transform scale-75 group-hover:scale-100 transition-transform duration-300 shadow-2xl border border-white/30">
                                    <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="absolute bottom-0 left-0 right-0 p-4 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                <div className="bg-white/90 backdrop-blur-sm rounded-lg p-3 shadow-lg">
                                    <p className="text-sm font-semibold text-gray-800">{t('Screenshot')} {index + 1}</p>
                                    <p className="text-xs text-gray-600 mt-1">{t('Click to view full size')}</p>
                                </div>
                            </div>
                            <div className="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white px-2 py-1 rounded-full text-xs font-medium">
                                {index + 1}
                            </div>
                        </div>
                    ))}
                </div>
                
                {lightboxOpen && (
                    <div className="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fadeIn" onClick={() => setLightboxOpen(false)}>
                        <div className="relative max-w-6xl max-h-full animate-scaleIn">
                            <img
                                src={images[lightboxIndex]?.startsWith('http') ? images[lightboxIndex] : getImagePath(images[lightboxIndex])}
                                alt={`Screenshot ${lightboxIndex + 1}`}
                                className="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
                            />
                            
                            {/* Navigation Buttons */}
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setLightboxIndex((prev) => (prev - 1 + images.length) % images.length);
                                }}
                                className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-4 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20 group"
                            >
                                <svg className="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setLightboxIndex((prev) => (prev + 1) % images.length);
                                }}
                                className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-4 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20 group"
                            >
                                <svg className="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            
                            {/* Close Button */}
                            <button
                                onClick={() => setLightboxOpen(false)}
                                className="absolute top-4 right-4 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110 shadow-xl border border-white/20 group"
                            >
                                <svg className="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            
                            {/* Image Counter */}
                            <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 backdrop-blur-md rounded-full px-6 py-3 border border-white/20">
                                <p className="text-white text-sm font-medium">{lightboxIndex + 1} {t('of')} {images.length}</p>
                            </div>
                            
                            {/* Thumbnail Strip */}
                            <div className="absolute bottom-20 left-1/2 transform -translate-x-1/2 flex space-x-2 bg-black/30 backdrop-blur-md rounded-full p-3 border border-white/20">
                                {images.slice(Math.max(0, lightboxIndex - 2), lightboxIndex + 3).map((image: string, idx: number) => {
                                    const actualIndex = Math.max(0, lightboxIndex - 2) + idx;
                                    return (
                                        <button
                                            key={actualIndex}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setLightboxIndex(actualIndex);
                                            }}
                                            className={`w-12 h-12 rounded-lg overflow-hidden transition-all duration-200 ${
                                                actualIndex === lightboxIndex 
                                                    ? 'ring-2 ring-white scale-110' 
                                                    : 'opacity-60 hover:opacity-100 hover:scale-105'
                                            }`}
                                        >
                                            <img
                                                src={image.startsWith('http') ? image : getImagePath(image)}
                                                alt={`Thumbnail ${actualIndex + 1}`}
                                                className="w-full h-full object-cover"
                                            />
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                )}
            </>
        );
    };

    const renderGallery = () => {
        const [mainImage, setMainImage] = useState(0);
        
        return (
            <div className="space-y-6">
                {/* Main Image Display */}
                <div className="relative group">
                    <div className="aspect-video bg-gray-100 rounded-xl overflow-hidden shadow-2xl">
                        <img
                            src={images[mainImage]?.startsWith('http') ? images[mainImage] : getImagePath(images[mainImage])}
                            alt={`Screenshot ${mainImage + 1}`}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                    </div>
                    <div className="absolute top-4 left-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm">
                        {mainImage + 1} of {images.length}
                    </div>
                </div>
                
                {/* Thumbnail Grid */}
                <div className="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                    {images.map((image: string, index: number) => (
                        <button
                            key={index}
                            onClick={() => setMainImage(index)}
                            className={`aspect-square rounded-lg overflow-hidden transition-all duration-300 ${
                                index === mainImage 
                                    ? 'ring-4 ring-offset-2 scale-105 shadow-lg' 
                                    : 'hover:scale-105 hover:shadow-md opacity-70 hover:opacity-100'
                            }`}
                            style={index === mainImage ? { '--tw-ring-color': colors.primary } as any : {}}
                        >
                            <img
                                src={image.startsWith('http') ? image : getImagePath(image)}
                                alt={`Thumbnail ${index + 1}`}
                                className="w-full h-full object-cover"
                            />
                        </button>
                    ))}
                </div>
            </div>
        );
    };

    const renderContent = () => {
        switch (config.layout) {
            case 'masonry':
                return renderMasonry();
            case 'slider':
                return renderSlider();
            case 'lightbox':
                return renderLightbox();
            case 'gallery':
                return renderGallery();
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