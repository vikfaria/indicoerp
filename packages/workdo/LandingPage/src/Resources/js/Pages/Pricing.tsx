import { Head, router, usePage } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import { getAdminSetting, getImagePath, formatAdminCurrency } from '@/utils/helpers';
import { useState } from 'react';
import CookieConsent from "@/components/cookie-consent";
import { useTranslation } from 'react-i18next';

interface Plan {
    id: number;
    name: string;
    description?: string;
    package_price_monthly: number;
    package_price_yearly: number;
    number_of_users: number;
    storage_limit: number;
    modules: string[];
    free_plan: boolean;
    trial: boolean;
    trial_days: number;
    orders_count?: number;
}

interface Module {
    module: string;
    alias: string;
    image?: string;
    monthly_price?: number;
    yearly_price?: number;
}

interface PricingProps {
    plans?: Plan[];
    activeModules?: Module[];
    settings?: any;
    filters?: {
        search?: string;
        category?: string;
        price?: string;
        price_type?: string;
        sort?: string;
    };
}

export default function Pricing(props: PricingProps) {
    const { t } = useTranslation();
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const { adminAllSetting, auth } = usePage().props as any;
    const plans = props.plans || [];
    const activeModules = props.activeModules || [];
    const settings = { ...props.settings, is_authenticated: (auth?.user?.id !== undefined && auth?.user?.id !== null) };
    const filters = props.filters || {};
    const colors = settings?.config_sections?.colors || { primary: '#10b77f', secondary: '#059669', accent: '#f59e0b' };
    const pricingSettings = settings?.config_sections?.sections?.pricing || {};
    
    const [priceType, setPriceType] = useState(pricingSettings.default_price_type || 'monthly');

    // Find the plan with the highest order count for "Most Popular" badge
    const mostPopularPlanId = plans.length > 0 
        ? plans.reduce((prev, current) => 
            (current.orders_count || 0) > (prev.orders_count || 0) ? current : prev
          ).id
        : null;

    return (
        <>
            <Head title="Pricing" >
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>
            
            <Header settings={settings} />
            
            <main className="min-h-screen bg-gray-50 py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                            {pricingSettings.title || t('Subscription Setting')}
                        </h1>
                        <p className="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
                            {pricingSettings.subtitle || t('Choose the perfect subscription plan for your business needs')}
                        </p>
                    </div>

                    {/* Monthly/Yearly Toggle */}
                    {pricingSettings.show_monthly_yearly_toggle === true && (
                        <div className="flex items-center justify-center space-x-6 mb-8">
                            <div className="bg-gray-100 p-1 rounded-lg">
                                <div className="flex items-center">
                                    <button
                                        onClick={() => setPriceType('monthly')}
                                        className={`px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 ${
                                            priceType === 'monthly'
                                                ? 'bg-white text-gray-900 shadow-sm'
                                                : 'text-gray-600 hover:text-gray-900'
                                        }`}
                                    >
                                        {t("Monthly")}
                                    </button>
                                    <button
                                        onClick={() => setPriceType('yearly')}
                                        className={`px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 ${
                                            priceType === 'yearly'
                                                ? 'bg-white text-gray-900 shadow-sm'
                                                : 'text-gray-600 hover:text-gray-900'
                                        }`}
                                    >
                                        {t("Yearly")}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Pre-Package Subscription Layout */}
                    {pricingSettings.show_pre_package === true && plans.length > 0 ? (
                        <div className="space-y-6 overflow-x-auto pt-6">
                            {/* Plans Header Cards */}
                            <div className="grid gap-6" style={{ gridTemplateColumns: `300px repeat(${plans.length}, 280px)`, minWidth: `${300 + (plans.length * 280) + ((plans.length - 1) * 24)}px` }}>
                                {/* Features Header */}
                                <div className="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-6 border border-slate-200 sticky left-0 z-20">
                                    <div className="flex items-center justify-center space-x-3">
                                        <h3 className="text-xl font-bold text-gray-900">{t("Features")}</h3>
                                    </div>
                                </div>

                                {/* Plan Header Cards */}
                                {plans.map((plan) => (
                                    <div 
                                        key={plan.id} 
                                        className={`relative rounded-2xl p-6 border-2 bg-white ${
                                            plan.id === mostPopularPlanId && plans.length > 1
                                                ? ''
                                                : 'border-gray-200'
                                        }`}
                                        style={plan.id === mostPopularPlanId && plans.length > 1 ? {
                                            borderColor: colors.primary,
                                            boxShadow: `0 0 0 2px ${colors.primary}20`
                                        } : {}}
                                    >
                                        {plan.id === mostPopularPlanId && plans.length > 1 && (
                                            <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                                <div 
                                                    className="text-white px-4 py-2 text-sm font-bold shadow-lg rounded-md whitespace-nowrap"
                                                    style={{ backgroundColor: colors.primary }}
                                                >
                                                    ⭐ {t("Most Popular")}
                                                </div>
                                            </div>
                                        )}
                                        
                                        <div className="text-center space-y-4">
                                            <div>
                                                <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                                                <p className="text-sm text-gray-600">{plan.description}</p>
                                            </div>
                                            
                                            <div className="space-y-2">
                                                {plan.free_plan ? (
                                                    <div>
                                                        <div className="text-5xl font-black mb-1" style={{ color: colors.primary }}>
                                                            {t("Free")}
                                                        </div>
                                                        <div className="font-semibold" style={{ color: colors.primary }}>
                                                            {t("Forever")}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <div className="flex items-baseline justify-center space-x-1 mb-2">
                                                            <span className="text-5xl font-black text-gray-900">
                                                                {priceType === 'monthly' ? formatAdminCurrency(plan.package_price_monthly) : formatAdminCurrency(plan.package_price_yearly)}
                                                            </span>
                                                            <span className="text-xl text-gray-500 font-semibold">
                                                                /{priceType === 'monthly' ? 'mo' : 'yr'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                            
                                            <div className="space-y-3 py-4">
                                                <div className="flex items-center space-x-2">
                                                    <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: colors.primary }}></div>
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {plan.number_of_users === -1 ? 'Unlimited users' : `${plan.number_of_users} users`}
                                                    </span>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: colors.primary }}></div>
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {Math.round(plan.storage_limit / (1024 * 1024))}{t("GB storage")}
                                                    </span>
                                                </div>
                                                {plan.trial && (
                                                    <div className="flex items-center space-x-2">
                                                        <div className="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></div>
                                                        <span className="text-sm font-medium text-green-600">
                                                            {plan.trial_days}{t("d trial")}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Features Comparison Cards */}
                            <div className="space-y-4">
                                <div className="grid gap-6" style={{ gridTemplateColumns: `300px repeat(${plans.length}, 280px)`, minWidth: `${300 + (plans.length * 280) + ((plans.length - 1) * 24)}px` }}>
                                    {/* All Modules Card */}
                                    <div className="bg-white rounded-2xl p-6 border border-gray-200 sticky left-0 z-20">
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-center py-2 h-10 border-b border-gray-200 mb-3">
                                                <span className="text-gray-900 font-semibold text-sm">
                                                    {t("Features")}
                                                </span>
                                            </div>
                                            {activeModules.map((module) => (
                                                <div key={module.module} className="flex items-center justify-center py-2 h-10">
                                                    <span className="text-gray-700 capitalize text-sm">
                                                        {module.alias}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    
                                    {/* Plan Feature Cards */}
                                    {plans.map((plan) => {
                                        const enabledAddOns = activeModules.filter(module => plan.modules?.includes(module.module));
                                        const totalAddOns = activeModules.length;
                                        
                                        return (
                                        <div key={plan.id} className="bg-white rounded-2xl p-6 border border-gray-200">
                                            <div className="space-y-3">
                                                <div className="flex items-center justify-center py-2 h-10 border-b border-gray-200 mb-3">
                                                    <span className="text-gray-900 font-semibold text-sm">
                                                        {enabledAddOns.length}/{totalAddOns} {t("Enabled")}
                                                    </span>
                                                </div>
                                                {activeModules.map((module) => (
                                                    <div key={module.module} className="flex items-center justify-center py-2 h-10">
                                                        {plan.modules?.includes(module.module) ? (
                                                            <div className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                                                <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        ) : (
                                                            <div className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                                                <svg className="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                                <div className="pt-4 border-t space-y-2">
                                                    <button 
                                                        className="w-full py-2 px-4 rounded-md text-white font-medium transition-colors"
                                                        style={{ backgroundColor: colors.primary }}
                                                        onClick={() => {
                                                            if (settings?.is_authenticated) {
                                                                router.visit(route('dashboard'));
                                                            } else {
                                                                router.visit(route('register'));
                                                            }
                                                        }}
                                                    >
                                                        {settings?.is_authenticated ? t('Go to Dashboard') : t('Get Started')}
                                                    </button>
                                                    {plan.trial && !settings?.is_authenticated && (
                                                        <button 
                                                            className="w-full py-2 px-4 rounded-md border font-medium transition-colors"
                                                            style={{ 
                                                                borderColor: colors.primary, 
                                                                color: colors.primary,
                                                                backgroundColor: 'transparent'
                                                            }}
                                                            onMouseEnter={(e) => {
                                                                e.currentTarget.style.backgroundColor = colors.primary;
                                                                e.currentTarget.style.color = 'white';
                                                            }}
                                                            onMouseLeave={(e) => {
                                                                e.currentTarget.style.backgroundColor = 'transparent';
                                                                e.currentTarget.style.color = colors.primary;
                                                            }}
                                                            onClick={() => router.visit(route('register'))}
                                                        >
                                                            {t("Start Trial")} ({plan.trial_days}d)
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg className="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">{t("No Plans Available")}</h3>
                            <p className="text-gray-600">{pricingSettings.empty_message || t('Check back later for new pricing plans.')}</p>
                        </div>
                    )}
                </div>
            </main>
            
            <Footer settings={settings} />

            <CookieConsent settings={adminAllSetting || {}} />
        </>
    );
}