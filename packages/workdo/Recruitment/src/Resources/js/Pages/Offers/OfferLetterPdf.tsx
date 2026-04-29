import { useRef, useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import html2pdf from 'html2pdf.js';
import { getImagePath } from '@/utils/helpers';

interface Candidate {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Job {
    id: number;
    title: string;
    job_type?: string;
    location?: string;
}

interface Department {
    id: number;
    department_name: string;
}

interface ApprovedBy {
    id: number;
    name: string;
}

interface Offer {
    id: number;
    candidate: Candidate;
    job: Job;
    department?: Department;
    approvedBy?: ApprovedBy;
    position: string;
    salary?: number;
    bonus?: number;
    equity?: string;
    benefits?: string;
    start_date?: string;
    expiration_date?: string;
    offer_date?: string;
    status: string;
    approval_status: string;
}

interface CompanySettings {
    company_name?: string;
    logo_light?: string;
    logo_dark?: string;
    favicon?: string;
}

interface OfferLetterPdfProps {
    offer: Offer;
    companyName: string;
    companySettings: CompanySettings;
    templateContent: string;
}

export default function OfferLetterPdf() {
    const { t } = useTranslation();
    const { offer, companyName, companySettings, templateContent } = usePage<OfferLetterPdfProps>().props;
    const printRef = useRef<HTMLDivElement>(null);
    const [isDownloading, setIsDownloading] = useState(false);

    const downloadPDF = () => {
        if (!printRef.current) return;

        setIsDownloading(true);
        const candidateName = offer.candidate.first_name + ' ' + offer.candidate.last_name;
        const filename = `offer-letter-${candidateName.replace(/\s+/g, '-').toLowerCase()}.pdf`;

        const element = printRef.current;
        const opt = {
            margin: 0.5,
            filename: filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            setIsDownloading(false);
        });
    };

    useEffect(() => {
        // Auto download on component mount
        setTimeout(() => {
            downloadPDF();
        }, 1000);
    }, []);

    return (
        <div className="min-h-screen bg-gray-100 py-8">
            <Head title={t('Offer Letter')} />

            <div className="max-w-4xl mx-auto px-4">
                <div className="text-center mb-6">
                    <Button
                        onClick={downloadPDF}
                        disabled={isDownloading}
                        className="bg-blue-600 hover:bg-blue-700"
                    >
                        <Download className="w-4 h-4 mr-2" />
                        {isDownloading ? t('Downloading...') : t('Download PDF')}
                    </Button>
                </div>

                <div ref={printRef} className="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div className="p-8">
                        {/* Company Header */}
                        <div className="flex items-center mb-8">
                            {companySettings.logo_dark && (
                                <img
                                    src={getImagePath(companySettings.logo_dark)}
                                    alt={companyName}
                                    className="h-12 mr-4"
                                />
                            )}
                        </div>

                        {/* Offer Letter Content */}
                        <div
                            className="prose max-w-none"
                            dangerouslySetInnerHTML={{ __html: templateContent }}
                        />

                        {/* Footer */}
                        <div className="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                            <p>{t('Generated on')} {new Date().toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}