import React from 'react';
import { usePage } from '@inertiajs/react';

export default function FrontendFooter() {
    const { props } = usePage();
    const settings = props.settings as any;
    
    return (
        <footer className="bg-gray-900 text-white py-4">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <p className="text-gray-400 text-sm">
                        {settings?.footer?.footer_text || `© ${new Date().getFullYear()} WorkDo. All rights reserved.`}
                    </p>
                </div>
            </div>
        </footer>
    );
}