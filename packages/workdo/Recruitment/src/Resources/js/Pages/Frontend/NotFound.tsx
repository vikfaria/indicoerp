import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { AlertTriangle, Home, Search, ArrowLeft } from 'lucide-react';

export default function NotFound() {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;

    return (
        <FrontendLayout title={t('Page Not Found')}>

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <Card className="border shadow-sm">
                    <CardContent className="p-12 text-center">
                        {/* Error Icon */}
                        <div className="mb-8">
                            <div className="mx-auto w-24 h-24 bg-red-100 rounded-full flex items-center justify-center">
                                <AlertTriangle className="h-12 w-12 text-red-600" />
                            </div>
                        </div>

                        {/* Error Message */}
                        <div className="mb-8">
                            <h1 className="text-6xl font-bold text-gray-900 mb-4">404</h1>
                            <h2 className="text-3xl font-bold text-gray-900 mb-4">{t('Page Not Found')}</h2>
                            <p className="text-lg text-gray-600 mb-2">
                                {t('Sorry, the page you are looking for could not be found.')}
                            </p>
                            <p className="text-gray-500">
                                {t('The page may have been moved, deleted, or you may have entered an incorrect URL.')}
                            </p>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <Button
                                className="bg-slate-700 hover:bg-slate-800 text-white px-6 py-3"
                                onClick={() => router.visit(route('recruitment.frontend.careers.jobs.index', { userSlug }))}
                            >
                                <Home className="h-5 w-5 mr-2" />
                                {t('Back to Jobs')}
                            </Button>
                            
                            <Button
                                variant="outline"
                                className="border-slate-300 text-slate-700 hover:bg-slate-50 px-6 py-3"
                                onClick={() => window.history.back()}
                            >
                                <ArrowLeft className="h-5 w-5 mr-2" />
                                {t('Go Back')}
                            </Button>
                        </div>


                    </CardContent>
                </Card>
            </div>
        </FrontendLayout>
    );
}