import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreditCard, Lock, Shield, AlertCircle, CheckCircle, Receipt, Tag, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';

interface User {
    id: string;
    name: string;
    email: string;
}

interface AuthorizeNetCheckoutProps {
    payment_data: any;
    user: User;
    errors?: Record<string, string>;
}

const AuthorizeNetCheckout: React.FC<AuthorizeNetCheckoutProps> = ({ payment_data, user, errors }) => {
    const { t } = useTranslation();
    const [showCvv, setShowCvv] = useState(false);
    const [alert, setAlert] = useState<{ type: 'success' | 'error' | 'info', message: string } | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    // Loading animation on page load
    useEffect(() => {
        const timer = setTimeout(() => setIsLoading(false), 1500);
        return () => clearTimeout(timer);
    }, []);

    const { data, setData, post, processing } = useForm({
        // Credit Card Information
        card_number: '',
        expiry_month: '',
        expiry_year: '',
        cvv: '',

        // Billing Information
        first_name: user?.name?.split(' ')[0] || '',
        last_name: user?.name?.split(' ').slice(1).join(' ') || '',
        email: user?.email || '',
        company: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        country: 'US',
        phone: ''
    });

    const validateCardNumber = (cardNumber: string) => {
        // Remove spaces and check if it's 13-19 digits (most cards are 16)
        const digits = cardNumber.replace(/\D/g, '');
        return digits.length >= 13 && digits.length <= 19;
    };

    const validateCvv = (cvv: string) => {
        // CVV should be 3-4 digits
        return cvv.length >= 3 && cvv.length <= 4 && /^\d+$/.test(cvv);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Client-side validation
        if (!validateCardNumber(data.card_number)) {
            toast.error(t('The card number is invalid (13-19 digits required).'));
            return;
        }

        if (!validateCvv(data.cvv)) {
            toast.error(t('The CVV is invalid (3-4 digits required).'));
            return;
        }

        if (!data.expiry_month || !data.expiry_year) {
            toast.error(t('The expiry month and year are required.'));
            return;
        }

        // Format expiration date as YYYY-MM
        const expirationDate = `${data.expiry_year}-${data.expiry_month}`;

        // Prepare complete payment data
        const completePaymentData = {
            ...(payment_data || {}),
            card_number: data.card_number,
            expiration_date: expirationDate,
            cvv: data.cvv,
            reference_id: payment_data?.order_id,
            invoice_number: payment_data?.order_id,
            billing: {
                first_name: data.first_name,
                last_name: data.last_name,
                email: data.email,
                company: data.company,
                address: data.address,
                city: data.city,
                state: data.state,
                zip: data.zip,
                country: data.country,
                phone: data.phone
            }
        };

        // Submit immediately with loading state
        setIsProcessing(true);
        setAlert({ type: 'info', message: t('The payment is being processed...') });

        router.post(route('authorizenet.process.payment'), completePaymentData, {
            preserveScroll: true,
            onStart: () => {
                setIsLoading(true);
            },
            onSuccess: (page) => {
                setIsProcessing(false);
                setIsLoading(false);
                setAlert({ type: 'success', message: t('The payment has been completed successfully.') });
            },
            onError: (errors) => {
                setIsProcessing(false);
                setIsLoading(false);
                const errorMessage = errors.error || t('The payment has failed. Please try again.');
                setAlert({ type: 'error', message: errorMessage });
            }
        });
    };

    const formatCardNumber = (value: string) => {
        const v = value.replace(/\D/g, '');
        const limited = v.slice(0, 16);
        const formatted = limited.replace(/(\d{4})(?=\d)/g, '$1 ');
        return formatted;
    };

    const handleCardNumberChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        const digitsOnly = value.replace(/\D/g, '');
        setData('card_number', digitsOnly);
    };

    const handleCvvChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value.replace(/\D/g, ''); // Only allow digits
        const limited = value.slice(0, 4); // Limit to 4 digits
        setData('cvv', limited);
    };

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 20 }, (_, i) => currentYear + i);

    const months = [
        { value: '01', label: '01 - January' },
        { value: '02', label: '02 - February' },
        { value: '03', label: '03 - March' },
        { value: '04', label: '04 - April' },
        { value: '05', label: '05 - May' },
        { value: '06', label: '06 - June' },
        { value: '07', label: '07 - July' },
        { value: '08', label: '08 - August' },
        { value: '09', label: '09 - September' },
        { value: '10', label: '10 - October' },
        { value: '11', label: '11 - November' },
        { value: '12', label: '12 - December' }
    ];
    return (
        <>
            <Head title={t('AuthorizeNet Checkout')} />

            {/* Loading Animation */}
            {isLoading && (
                <div className="fixed inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center">
                    <div className="text-center">
                        <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-green-600 mx-auto mb-4"></div>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">{t('Loading Checkout')}</h2>
                        <p className="text-gray-600 dark:text-gray-400">{t('Please wait while we prepare your secure payment.')}</p>
                    </div>
                </div>
            )}

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
                <div className="max-w-4xl mx-auto px-4">
                    <div className="text-center mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.history.back()}
                                className="flex items-center"
                            >
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                {t('Back')}
                            </Button>
                            <div className="flex items-center">
                                <Shield className="h-8 w-8 text-green-600 mr-2" />
                                <h1 className="text-3xl font-bold text-gray-900">{t('Secure Checkout')}</h1>
                            </div>
                            <div className="w-20"></div> {/* Spacer for centering */}
                        </div>
                        <p className="text-gray-600">
                            {t('Complete your payment securely with AuthorizeNet.')}
                        </p>
                        <p className="text-sm text-red-900">
                            {t('Please do not refresh the page.')}
                        </p>
                    </div>

                    {/* Alert Messages - Only show during processing */}
                    {alert && isProcessing && (
                        <div className={`mb-6 p-4 rounded-lg border ${alert.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                            alert.type === 'error' ? 'bg-red-50 border-red-200 text-red-800' :
                                'bg-blue-50 border-blue-200 text-blue-800'
                            }`}>
                            <div className="flex items-center">
                                {alert.type === 'success' && <CheckCircle className="h-5 w-5 mr-2" />}
                                {alert.type === 'error' && <AlertCircle className="h-5 w-5 mr-2" />}
                                {alert.type === 'info' && <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2"></div>}
                                <span className="font-medium">{alert.message}</span>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Payment Form */}
                        <div className="lg:col-span-2">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Payment Errors */}
                                {errors?.payment && (
                                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div className="flex items-center text-red-800">
                                            <AlertCircle className="h-4 w-4 mr-2" />
                                            <span className="text-sm font-medium">{errors.payment}</span>
                                        </div>
                                    </div>
                                )}

                                {/* Credit Card Information */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <CreditCard className="h-5 w-5 mr-2" />
                                            {t('Card Information')}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div>
                                            <Label htmlFor="card_number">{t('Card Number')}</Label>
                                            <Input
                                                id="card_number"
                                                type="text"
                                                value={formatCardNumber(data.card_number)}
                                                onChange={handleCardNumberChange}
                                                placeholder={t('Enter Card Number')}
                                                maxLength={19} // 16 digits + 3 spaces
                                                required
                                                className={`${errors?.card_number ? 'border-red-500' :
                                                    data.card_number && validateCardNumber(data.card_number) ? 'border-green-500' :
                                                        data.card_number && !validateCardNumber(data.card_number) ? 'border-red-500' : ''
                                                    }`}
                                            />
                                            {errors?.card_number && (
                                                <p className="text-red-500 text-sm mt-1">{errors.card_number}</p>
                                            )}
                                            <p className="text-xs text-gray-500 mt-1">{t('Enter the 16-digit card number.')}</p>
                                        </div>

                                        <div className="grid grid-cols-3 gap-4">
                                            <div>
                                                <Label htmlFor="expiry_month" required>{t('Month')}</Label>
                                                <Select value={data.expiry_month} onValueChange={(value: string) => setData('expiry_month', value)}>
                                                    <SelectTrigger className={errors?.expiry_month ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder={t('Month')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {months.map((month) => (
                                                            <SelectItem key={month.value} value={month.value}>
                                                                {month.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors?.expiry_month && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.expiry_month}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="expiry_year" required>{t('Year')}</Label>
                                                <Select value={data.expiry_year} onValueChange={(value: string) => setData('expiry_year', value)}>
                                                    <SelectTrigger className={errors?.expiry_year ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder={t('Year')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {years.map((year) => (
                                                            <SelectItem key={year} value={year.toString()}>
                                                                {year}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors?.expiry_year && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.expiry_year}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="cvv">{t('CVV')}</Label>
                                                <Input
                                                    id="cvv"
                                                    type={showCvv ? 'text' : 'password'}
                                                    value={data.cvv}
                                                    onChange={handleCvvChange}
                                                    placeholder={t('Enter CVV')}
                                                    maxLength={4}
                                                    required
                                                    className={`${errors?.cvv ? 'border-red-500' :
                                                        data.cvv && validateCvv(data.cvv) ? 'border-green-500' :
                                                            data.cvv && !validateCvv(data.cvv) ? 'border-red-500' : ''
                                                        }`}
                                                />
                                                {errors?.cvv && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.cvv}</p>
                                                )}
                                                <p className="text-xs text-gray-500 mt-1">{t('Enter 3-4 digits.')}</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Billing Information */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>{t('Billing Information')}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="first_name">{t('First Name')}</Label>
                                                <Input
                                                    id="first_name"
                                                    type="text"
                                                    value={data.first_name}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('first_name', e.target.value)}
                                                    placeholder={t('Enter First Name')}
                                                    required
                                                    className={errors?.first_name ? 'border-red-500' : ''}
                                                />
                                                {errors?.first_name && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.first_name}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="last_name">{t('Last Name')}</Label>
                                                <Input
                                                    id="last_name"
                                                    type="text"
                                                    value={data.last_name}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('last_name', e.target.value)}
                                                    placeholder={t('Enter Last Name')}
                                                    required
                                                    className={errors?.last_name ? 'border-red-500' : ''}
                                                />
                                                {errors?.last_name && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.last_name}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div>
                                            <Label htmlFor="email">{t('Email')}</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                value={data.email}
                                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                                                placeholder={t('Enter Email')}
                                                required
                                                className={errors?.email ? 'border-red-500' : ''}
                                            />
                                            {errors?.email && (
                                                <p className="text-red-500 text-sm mt-1">{errors.email}</p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="company">{t('Company')}</Label>
                                            <Input
                                                id="company"
                                                type="text"
                                                value={data.company}
                                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('company', e.target.value)}
                                                placeholder={t('Enter Company Name')}
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="address">{t('Address')}</Label>
                                            <Input
                                                id="address"
                                                type="text"
                                                value={data.address}
                                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('address', e.target.value)}
                                                placeholder={t('Enter Address')}
                                                required
                                                className={errors?.address ? 'border-red-500' : ''}
                                            />
                                            {errors?.address && (
                                                <p className="text-red-500 text-sm mt-1">{errors.address}</p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="city">{t('City')}</Label>
                                                <Input
                                                    id="city"
                                                    type="text"
                                                    value={data.city}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('city', e.target.value)}
                                                    placeholder={t('Enter City')}
                                                    required
                                                    className={errors?.city ? 'border-red-500' : ''}
                                                />
                                                {errors?.city && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.city}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="state">{t('State')}</Label>
                                                <Input
                                                    id="state"
                                                    type="text"
                                                    value={data.state}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('state', e.target.value)}
                                                    placeholder={t('Enter State')}
                                                    required
                                                    className={errors?.state ? 'border-red-500' : ''}
                                                />
                                                {errors?.state && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.state}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="zip">{t('ZIP Code')}</Label>
                                                <Input
                                                    id="zip"
                                                    type="text"
                                                    value={data.zip}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('zip', e.target.value)}
                                                    placeholder={t('Enter ZIP Code')}
                                                    required
                                                    className={errors?.zip ? 'border-red-500' : ''}
                                                />
                                                {errors?.zip && (
                                                    <p className="text-red-500 text-sm mt-1">{errors.zip}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="phone">{t('Phone')}</Label>
                                                <Input
                                                    id="phone"
                                                    type="tel"
                                                    value={data.phone}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('phone', e.target.value)}
                                                    placeholder={t('Enter Phone Number')}
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Button
                                    type="submit"
                                    className="w-full bg-green-600 hover:bg-green-700 text-white py-3 text-lg font-semibold disabled:opacity-50"
                                    disabled={isProcessing || processing}
                                >
                                    {(isProcessing || processing) ? (
                                        <>
                                            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                                            {t('The payment is being processed...')}
                                        </>
                                    ) : (
                                        <>
                                            <Lock className="h-5 w-5 mr-2" />
                                            {t('Complete Payment')}
                                        </>
                                    )}
                                </Button>
                            </form>
                        </div>

                        {/* Order Summary */}
                        <div className="lg:col-span-1">
                            <Card className="sticky top-8">
                                <CardHeader>
                                    <CardTitle className="flex items-center text-lg">
                                        <Receipt className="h-5 w-5 mr-2" />
                                        {t('Order Summary')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {/* Plan/Service/Product */}
                                    {payment_data?.plan_name && (
                                        <div>
                                            <div className="text-sm text-gray-500">{t('Plan')}</div>
                                            <div className="font-medium">{payment_data?.plan_name}</div>
                                        </div>
                                    )}

                                    {payment_data?.service_name && (
                                        <div>
                                            <div className="text-sm text-gray-500">{t('Service')}</div>
                                            <div className="font-medium">{payment_data?.service_name}</div>
                                        </div>
                                    )}

                                    {payment_data?.product_name && (
                                        <div>
                                            <div className="text-sm text-gray-500">{t('Product')}</div>
                                            <div className="font-medium">{payment_data?.product_name}</div>
                                        </div>
                                    )}

                                    {/* Description */}
                                    {payment_data?.description && (
                                        <div>
                                            <div className="text-sm text-gray-500">{t('Description')}</div>
                                            <div className="text-sm text-gray-700">{payment_data?.description}</div>
                                        </div>
                                    )}

                                    {/* Order ID */}
                                    <div>
                                        <div className="text-sm text-gray-500">{t('Order ID')}</div>
                                        <div className="font-mono text-sm">{payment_data?.order_id}</div>
                                    </div>

                                    {/* Pricing */}
                                    {payment_data?.subtotal && payment_data?.subtotal !== payment_data?.amount && (
                                        <div className="flex justify-between text-sm">
                                            <span>{t('Subtotal')}</span>
                                            <span>{payment_data?.currency} {payment_data?.subtotal.toFixed(2)}</span>
                                        </div>
                                    )}

                                    {payment_data?.discount && payment_data?.discount > 0 && (
                                        <div className="flex justify-between text-sm text-green-600">
                                            <span>{t('Discount')}</span>
                                            <span>-{payment_data?.currency} {payment_data?.discount.toFixed(2)}</span>
                                        </div>
                                    )}

                                    {payment_data?.tax && payment_data?.tax > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span>{t('Tax')}</span>
                                            <span>{payment_data?.currency} {payment_data?.tax.toFixed(2)}</span>
                                        </div>
                                    )}

                                    <hr className="my-3" />

                                    <div className="flex justify-between font-bold text-lg">
                                        <span>{t('Total')}</span>
                                        <span>{payment_data?.currency} {Number(payment_data?.amount || 0).toFixed(2)}</span>
                                    </div>

                                    <div className="bg-blue-50 p-3 rounded text-center">
                                        <div className="flex items-center justify-center text-blue-700 text-sm">
                                            <Shield className="h-4 w-4 mr-1" />
                                            {t('Secure Payment')}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default AuthorizeNetCheckout;