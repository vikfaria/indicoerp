import React from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Briefcase, Search } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';
import { t } from 'i18next';

interface FrontendHeaderProps {
    currentPage?: string;
}

export default function FrontendHeader({ currentPage }: FrontendHeaderProps) {
    const { props } = usePage();
    const userSlug = props.userSlug as string;
    const settings = props.settings as any;
    
    return (
        <header className="bg-white shadow-sm border-b sticky top-0 z-50">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-16">
                    {/* Logo */}
                    <Link href={userSlug ? route('recruitment.frontend.careers.jobs.index', { userSlug }) : '#'} className="flex items-center">
                        {settings?.header?.logo ? (
                            <img src={getImagePath(settings.header.logo)} alt="Logo" className="h-10 w-auto" />
                        ) : (
                            <div className="flex items-center space-x-2">
                                <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-1 rounded-md font-bold text-lg">
                                    {(settings?.site_title || 'Careers').charAt(0)}
                                </div>
                                <span className="text-xl font-bold text-gray-800">
                                    {settings?.site_title || 'Careers'}
                                </span>
                            </div>
                        )}
                    </Link>

                    {/* Actions */}
                    <div className="flex items-center space-x-4">
                        {currentPage === 'track-form' ? (
                            <Button
                                variant="outline"
                                size="sm"
                                className="hidden sm:flex border-gray-300 text-gray-600 hover:bg-gray-50"
                                onClick={() => router.visit(route('recruitment.frontend.careers.jobs.index', { userSlug }))}
                            >
                                <Briefcase className="h-4 w-4 mr-2" />
                                {t('Browse Jobs')}
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                className="hidden sm:flex border-gray-300 text-gray-600 hover:bg-gray-50"
                                onClick={() => router.visit(route('recruitment.frontend.careers.track.form', { userSlug }))}
                            >
                                <Search className="h-4 w-4 mr-2" />
                                {t('Track Application')}
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}