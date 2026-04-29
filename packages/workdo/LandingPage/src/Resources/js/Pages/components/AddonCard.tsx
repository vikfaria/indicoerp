import { Button } from '@/components/ui/button';
import { getImagePath, formatAdminCurrency } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

interface AddonCardProps {
    addon: {
        id: number;
        name: string;
        description?: string;
        image?: string;
        monthly_price?: number;
        yearly_price?: number;
        package_name: string;
    };
    colors: {
        primary: string;
        secondary: string;
        accent: string;
    };
    priceType?: 'monthly' | 'yearly';
    variant?: 'card1' | 'card2' | 'card3' | 'card4' | 'card5';
    onViewDetails?: () => void;
}

const CARD_CONFIGS = {
    card1: {
        container: "relative bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow duration-300 pt-10 pb-6 px-6 mt-8",
        imageContainer: "absolute -top-8 left-1/2 transform -translate-x-1/2",
        imageWrapper: "w-16 h-16 bg-white rounded-full border-2 border-gray-200 overflow-hidden shadow-sm",
        content: "text-center",
        title: "text-sm font-semibold text-gray-900 mb-3 line-clamp-2",
        priceContainer: "mb-4",
        priceText: "text-lg font-bold text-gray-900",
        priceLabel: "text-gray-500 text-sm",
        button: "w-full text-white font-medium py-2 px-4 rounded-md text-sm",
        fallbackClass: "w-10 h-10 object-contain mx-auto mt-3"
    },
    card2: {
        container: "bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden",
        imageContainer: "h-32 flex items-center justify-center",
        imageWrapper: "w-16 h-16 rounded-lg overflow-hidden shadow-sm",
        content: "p-4",
        title: "text-lg font-bold text-gray-900 mb-2 line-clamp-2",
        priceContainer: "mb-3",
        priceText: "text-xl font-bold text-gray-900",
        priceLabel: "text-gray-500 text-sm",
        button: "w-full text-white font-semibold py-2.5 px-4 rounded-lg text-sm",
        fallbackClass: "w-10 h-10 object-contain mx-auto mt-3",
        useDynamicGradient: true
    },
    card3: {
        container: "group bg-gradient-to-br from-white to-gray-50 rounded-2xl border-2 border-gray-100 hover:border-gray-200 transition-all duration-300 p-6",
        imageContainer: "flex justify-center mb-4",
        imageWrapper: "w-24 h-24 bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-lg group-hover:scale-105 transition-transform duration-300",
        content: "text-center",
        title: "text-base font-bold text-gray-900 mb-3 line-clamp-2",
        priceContainer: "mb-4",
        priceText: "text-2xl font-bold text-gray-900",
        priceLabel: "text-gray-500 text-sm",
        button: "w-full text-white font-bold py-3 px-4 rounded-xl text-sm shadow-lg hover:shadow-xl transition-shadow",
        fallbackClass: "w-12 h-12 object-contain mx-auto mt-6"
    },
    card4: {
        container: "bg-white rounded-lg border-l-4 shadow-sm hover:shadow-md transition-shadow duration-300 p-5",
        imageContainer: "flex items-start gap-4",
        imageWrapper: "w-14 h-14 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0",
        content: "flex-1",
        title: "text-sm font-semibold text-gray-900 mb-2 line-clamp-2",
        priceContainer: "mb-3",
        priceText: "text-lg font-bold text-gray-900",
        priceLabel: "text-gray-500 text-xs",
        button: "w-full text-white font-medium py-2 px-3 rounded-md text-xs",
        fallbackClass: "w-8 h-8 object-contain mx-auto mt-3",
        isHorizontal: true
    },
    card5: {
        container: "bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1",
        imageContainer: "h-40 flex items-center justify-center",
        imageWrapper: "w-20 h-20 bg-white rounded-full overflow-hidden shadow-2xl",
        content: "p-6 text-center",
        title: "text-lg font-bold text-gray-900 mb-3 line-clamp-2",
        priceContainer: "mb-5",
        priceText: "text-2xl font-bold text-gray-900",
        priceLabel: "text-gray-500 text-sm",
        button: "w-full text-white font-bold py-3 px-6 rounded-full text-sm shadow-lg",
        fallbackClass: "w-12 h-12 object-contain mx-auto mt-4",
        useDynamicGradient: true
    }
};

export default function AddonCard({ addon, colors, priceType = 'monthly', variant = 'card1', onViewDetails }: AddonCardProps) {
    const { t } = useTranslation();
    const displayPrice = priceType === 'yearly' ? addon.yearly_price : addon.monthly_price;
    const priceLabel = priceType === 'yearly' ? '/year' : '/month';
    const config = CARD_CONFIGS[variant];

    const handleImageError = (e: React.SyntheticEvent<HTMLImageElement>) => {
        const target = e.currentTarget;
        target.src = getImagePath(`/packages/workdo/${addon.module}/favicon.png`);
        target.className = config.fallbackClass;
    };

    const renderImage = () => (
        <img
            src={getImagePath(addon.image ?? `/packages/workdo/${addon.module}/favicon.png`)}
            alt={addon.name}
            className="w-full h-full object-cover"
            onError={handleImageError}
        />
    );

    const renderPrice = () => {
        const hasMonthlyPrice = addon.monthly_price && addon.monthly_price > 0;
        const hasYearlyPrice = addon.yearly_price && addon.yearly_price > 0;
        
        if (displayPrice) {
            return (
                <div className={config.priceContainer}>
                    <div>
                        <span className={config.priceText}>{formatAdminCurrency(displayPrice)}</span>
                        <span className={config.priceLabel}>{priceLabel}</span>
                    </div>
                </div>
            );
        }
        
        if (hasMonthlyPrice || hasYearlyPrice) {
            return (
                <div className={config.priceContainer}>
                    {hasMonthlyPrice && hasYearlyPrice ? (
                        <div>
                            <div>
                                <span className={config.priceText}>{formatAdminCurrency(addon.monthly_price)}</span>
                                <span className={config.priceLabel}>/{t('month')}</span>
                            </div>
                            <div className="text-xs text-gray-500 mt-1">
                                {formatAdminCurrency(addon.yearly_price)}/{t('year')}
                            </div>
                        </div>
                    ) : (
                        <div>
                            <span className={config.priceText}>
                                {hasMonthlyPrice ? formatAdminCurrency(addon.monthly_price) : formatAdminCurrency(addon.yearly_price)}
                            </span>
                            <span className={config.priceLabel}>
                                {hasMonthlyPrice ? t('/month') : t('/year')}
                            </span>
                        </div>
                    )}
                </div>
            );
        }
        
        return (
            <div className={config.priceContainer}>
                <span className={variant === 'card4' ? 'text-sm font-bold text-green-600' : 'text-lg font-bold text-green-600'}>
                    {t('Free')}
                </span>
            </div>
        );
    };

    const renderButton = () => (
        <Button 
            onClick={onViewDetails}
            className={config.button}
            style={{ backgroundColor: colors.primary }}
        >
            {t('View Details')}
        </Button>
    );

    const getImageContainerStyle = () => {
        if (config.useDynamicGradient) {
            if (variant === 'card2') {
                return { background: `linear-gradient(135deg, ${colors.primary}20, ${colors.secondary}20)` };
            } else if (variant === 'card5') {
                return { background: `linear-gradient(135deg, ${colors.primary}, ${colors.secondary}, ${colors.accent || colors.primary})` };
            }
        }
        return {};
    };
    
    const containerStyle = variant === 'card4' ? { borderLeftColor: colors.primary } : {};

    if (config.isHorizontal) {
        return (
            <div className={config.container} style={containerStyle}>
                <div className={config.imageContainer} style={getImageContainerStyle()}>
                    <div className={config.imageWrapper}>
                        {renderImage()}
                    </div>
                    <div className={config.content}>
                        <h3 className={config.title}>{addon.name}</h3>
                        {renderPrice()}
                        {renderButton()}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={config.container} style={containerStyle}>
            <div className={config.imageContainer} style={getImageContainerStyle()}>
                <div className={config.imageWrapper}>
                    {renderImage()}
                </div>
            </div>
            <div className={config.content}>
                <h3 className={config.title}>{addon.name}</h3>
                {renderPrice()}
                {renderButton()}
            </div>
        </div>
    );
}