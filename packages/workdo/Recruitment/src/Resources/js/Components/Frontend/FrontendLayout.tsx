import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import FrontendHeader from './FrontendHeader';
import FrontendFooter from './FrontendFooter';
import { getImagePath } from '@/utils/helpers';

interface FrontendLayoutProps {
    children: React.ReactNode;
    title: string;
    description?: string;
    currentPage?: string;
}

export default function FrontendLayout({ children, title, description = 'Find your dream job with us', currentPage }: FrontendLayoutProps) {
    const { props } = usePage();
    const userSlug = props.userSlug as string;
    const settings = props.settings as any;
    
    return (
        <div className="min-h-screen bg-white flex flex-col">
            <Head title={`${settings?.site_title || 'Careers'} - ${title}`}>
                <meta name="description" content={description} />
                {settings?.favicon && <link rel="icon" href={getImagePath(settings.favicon)} />}
            </Head>

            <FrontendHeader currentPage={currentPage} />

            <main className="flex-1">
                {children}
            </main>

            <FrontendFooter />
        </div>
    );
}