import React, { useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface XenditPaymentProps {
    xendit_session: {
        id: string;
        invoice_url: string;
        external_id: string;
        amount: number;
    };
    xendit_key: string;
}

const XenditPayment: React.FC<XenditPaymentProps> = ({ xendit_session, xendit_key }) => {
    const { t } = useTranslation();
    
    useEffect(() => {
        if (xendit_session && xendit_session.invoice_url) {
            // Small delay to ensure the page loads before redirect
            const timer = setTimeout(() => {
                window.location.href = xendit_session.invoice_url;
            }, 1000);
            
            return () => clearTimeout(timer);
        }
    }, [xendit_session]);

    return (
        <>
            <Head title={t('Xendit Payment')} />
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">{t('Redirecting to Xendit...')}</p>
                </div>
            </div>
        </>
    );
};

export default XenditPayment;