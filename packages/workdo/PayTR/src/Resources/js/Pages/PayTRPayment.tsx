import { useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface PayTRPaymentProps {
    token: string;
    order_id: string;
    callback_link: string;
}

export default function PayTRPayment({ token, order_id }: PayTRPaymentProps) {
    const { t } = useTranslation();
    const iframeRef = useRef<HTMLIFrameElement>(null);

    useEffect(() => {
        if (token) {
            const iframe = iframeRef.current;
            if (iframe) {
                iframe.src = `https://www.paytr.com/odeme/guvenli/${token}`;
            }
        }
    }, [token]);

    return (
        <>
            <Head title={t('PayTR Payment')} />
            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl w-full space-y-8">
                    <div className="text-center">
                        <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
                            {t('Complete Your Payment')}
                        </h2>
                        <p className="mt-2 text-sm text-gray-600">
                            {t('Please complete your payment using PayTR secure payment gateway')}
                        </p>
                    </div>
                    
                    <div className="bg-white shadow-lg rounded-lg p-6">
                        {token ? (
                            <iframe
                                ref={iframeRef}
                                width="100%"
                                height="600"
                                frameBorder="0"
                                scrolling="no"
                                className="border-0 rounded-lg"
                                title={t('PayTR Payment Gateway')}
                            />
                        ) : (
                            <div className="text-center py-12">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                                <p className="mt-4 text-gray-600">{t('Loading payment gateway...')}</p>
                            </div>
                        )}
                    </div>
                    
                    {order_id && (
                        <div className="text-center">
                            <p className="text-xs text-gray-500">
                                {t('Order ID')}: {order_id}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}