import React from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import SystemSetupSidebar from '../SystemSetupSidebar';

export default function Index() {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {t('Support Categories')}
                </h2>
            }
        >
            <Head title={t('Support Categories')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex">
                            <div className="w-64 p-6 border-r">
                                <SystemSetupSidebar activeItem="support-categories" />
                            </div>
                            <div className="flex-1 p-6">
                                <div className="mb-6">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        {t('Support Categories')}
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-600">
                                        {t('Manage support ticket categories for better organization')}
                                    </p>
                                </div>
                                
                                <div className="text-center py-8">
                                    <p className="text-gray-500">
                                        {t('Support Categories management will be implemented here')}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}