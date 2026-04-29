import React, { useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface FlutterwavePaymentProps {
    data: {
        public_key: string;
        price: number;
        currency: string;
        redirect_url: string;
        cancel_url: string;
        email: string;
        name: string;
        title?: string;
        logo?: string;
        meta?: any;
    };
}

declare global {
    interface Window {
        FlutterwaveCheckout: any;
    }
}

const FlutterwavePayment: React.FC<FlutterwavePaymentProps> = ({ data }) => {
    const { t } = useTranslation();

    useEffect(() => {
        if (data) {
            const script = document.createElement('script');
            script.src = 'https://checkout.flutterwave.com/v3.js';
            script.onload = () => {
                const tx_ref_id = new Date().toISOString().replace(/[-:.]/g, '') + '-' +
                    Math.floor((Math.random() * 1000000000)) + '-fltwp-' +
                    new Date().toISOString().split('T')[0];

                window.FlutterwaveCheckout({
                    public_key: data.public_key,
                    tx_ref: tx_ref_id,
                    amount: data.price,
                    currency: data.currency,
                    redirect_url: data.redirect_url,
                    customer: {
                        email: data.email,
                        name: data.name,
                    },
                    meta: {
                        meta: data.meta ?? '',
                    },
                    customizations: {
                        title: data.title ?? null,
                        logo: data.logo ?? null,
                    },
                    callback: function (data: any) {
                        if (data.status === 'successful') {
                            window.location.href = data.redirect_url + '?status=successful&transaction_id=' + data.transaction_id;
                        } else {
                            window.location.href = data.cancel_url;
                        }
                    },
                    onclose: function () {
                        window.location.href = data.cancel_url;
                    }
                });
            };
            document.head.appendChild(script);
        }
    }, [data]);

    return (
        <>
            <Head title={t('Flutterwave Payment')} />
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">{t('Redirecting to Flutterwave...')}</p>
                </div>
            </div>
        </>
    );
};

export default FlutterwavePayment;