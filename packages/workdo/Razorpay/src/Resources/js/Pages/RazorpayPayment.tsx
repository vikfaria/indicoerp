import { useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

declare global {
    interface Window {
        Razorpay: any;
    }
}

interface RazorpayPaymentProps {
    paymentData: {
        success: boolean;
        key: string;
        amount: number;
        currency: string;
        name: string;
        description: string;
        prefill: {
            name: string;
            email: string;
        };
        theme: {
            color: string;
        };
        callback_url: string;
    };
    planId: number;
    userId: number;
    duration: string;
    userModule: string;
    couponCode: string;
}

function RazorpayPayment({ paymentData, planId, userId, duration, userModule, couponCode }: RazorpayPaymentProps) {
    const { t } = useTranslation();

    useEffect(() => {
        // Load Razorpay SDK
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.onload = () => {
            initializePayment();
        };
        document.body.appendChild(script);
        
        return () => {
            if (document.body.contains(script)) {
                document.body.removeChild(script);
            }
        };
    }, []);
    
    const initializePayment = () => {
        if (paymentData.success && paymentData.key) {
           
            const options = {
                key: paymentData.key,
                amount: paymentData.amount,
                currency: paymentData.currency,
                name: paymentData.name,
                description: paymentData.description,
                prefill: paymentData.prefill,
                theme: paymentData.theme,
                handler: function(response: any) {

                    const verificationData = {
                        razorpay_payment_id: response.razorpay_payment_id,
                        plan_id: planId,
                        user_id: userId,
                        amount: paymentData.amount / 100,
                        duration: duration,
                        user_module: userModule,
                        coupon_code: couponCode || ''
                    };
                    
                    // Redirect to callback URL with GET method like CyberSource
                    const queryString = new URLSearchParams(
                        Object.entries(verificationData).map(([key, value]) => [key, String(value)])
                    ).toString();
                    
                    window.location.href = paymentData.callback_url + '?' + queryString;
                },
                modal: {
                    ondismiss: function() {
                        window.location.href = paymentData.callback_url;
                    }
                }
            };
            
            if (window.Razorpay) {
                const rzp = new window.Razorpay(options);
                rzp.open();
            }
        } 
    };

    return (
        <>
            <Head title={t('Razorpay Payment')} />
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">{t('Redirecting to Razorpay...')}</p>
                </div>
            </div>
        </>
    );
}

export default RazorpayPayment;