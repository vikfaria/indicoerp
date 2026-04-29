import React, { useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Download, Printer } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Contract } from './types';
import { formatCurrency, formatDate } from '@/utils/helpers';
import html2pdf from 'html2pdf.js';

interface ContractPreviewProps {
    contract: Contract & {
        signatures?: Array<{
            id: number;
            user: { name: string };
            signature_type: string;
            signature_data: string;
            signed_at: string;
            is_finalized: boolean;
        }>;
    };
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Preview() {
    const { t } = useTranslation();
    const { contract, auth } = usePage<ContractPreviewProps>().props;
    const printRef = useRef<HTMLDivElement>(null);
    const [isDownloading, setIsDownloading] = useState(false);

    const handleDownload = () => {
        if (!printRef.current) return;

        setIsDownloading(true);
        const element = printRef.current;
        const opt = {
            margin: 0.5,
            filename: `contract-${contract.contract_number || contract.id}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            setIsDownloading(false);
        });
    };

    const handlePrint = () => {
        window.print();
    };

    const getStatusColor = (status: any) => {
        const statusValue = status?.toString();
        switch (statusValue) {
            case 'active':
            case '1':
                return 'bg-green-100 text-green-800';
            case 'draft':
            case '0':
                return 'bg-yellow-100 text-yellow-800';
            case 'archived':
            case '2':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-yellow-100 text-yellow-800';
        }
    };

    const getStatusText = (status: any) => {
        const statusValue = status?.toString();
        switch (statusValue) {
            case 'active':
            case '1':
                return t('Active');
            case 'draft':
            case '0':
                return t('Draft');
            case 'archived':
            case '2':
                return t('Archived');
            default:
                return t('Draft');
        }
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Contract'), url: route('contract.index') },
                { label: contract.contract_number || `Contract ${contract.id}`, url: route('contract.show', contract.id) },
                { label: t('Preview') }
            ]}
            pageTitle={t('Contract Preview')}
            backUrl={route('contract.show', contract.id)}
            pageActions={
                <TooltipProvider>
                    <div className="flex gap-2 print:hidden">
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button variant="outline" size="sm" onClick={handlePrint}>
                                    <Printer className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Print')}</p>
                            </TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleDownload}
                                    disabled={isDownloading}
                                >
                                    <Download className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{isDownloading ? t('Generating PDF...') : t('Download PDF')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </TooltipProvider>
            }
        >
            <Head title={`${t('Contract Preview')} - ${contract.subject}`} />

            <div className="space-y-6">
                <Card ref={printRef} className="max-w-4xl mx-auto bg-white">
                    <CardHeader className="text-center border-b print:border-b-2">
                        <CardTitle className="text-3xl font-bold">{contract.subject}</CardTitle>
                        <p className="text-lg text-muted-foreground">Contract #{contract.contract_number || 'Not Generated'}</p>
                    </CardHeader>

                    <CardContent className="p-8 space-y-8">
                        <div className="grid grid-cols-2 gap-8">
                            <div>
                                <h3 className="text-lg font-semibold mb-4">{t('Contract Information')}</h3>
                                <div className="space-y-3">
                                    <div>
                                        <span className="font-medium">{t('Subject')}:</span>
                                        <p className="mt-1">{contract.subject}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium">{t('Contract Number')}:</span>
                                        <p className="mt-1">{contract.contract_number || 'Not Generated'}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium">{t('Contract Type')}:</span>
                                        <p className="mt-1">{contract.contract_type?.name || '-'}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium">{t('Status')}:</span>
                                        <p className="mt-1">{getStatusText(contract.status)}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-semibold mb-4">{t('Contract Details')}</h3>
                                <div className="space-y-3">
                                    {contract.user && (
                                        <div>
                                            <span className="font-medium">{t('Assigned To')}:</span>
                                            <p className="mt-1">{contract.user.name}</p>
                                        </div>
                                    )}
                                    {contract.value && (
                                        <div>
                                            <span className="font-medium">{t('Contract Value')}:</span>
                                            <p className="mt-1">{formatCurrency(contract.value)}</p>
                                        </div>
                                    )}
                                    {contract.start_date && (
                                        <div>
                                            <span className="font-medium">{t('Start Date')}:</span>
                                            <p className="mt-1">{formatDate(contract.start_date)}</p>
                                        </div>
                                    )}
                                    {contract.end_date && (
                                        <div>
                                            <span className="font-medium">{t('End Date')}:</span>
                                            <p className="mt-1">{formatDate(contract.end_date)}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {contract.description && (
                            <div>
                                <h3 className="text-lg font-semibold mb-4">{t('Description')}</h3>
                                <div className="prose max-w-none">
                                    <div dangerouslySetInnerHTML={{ __html: contract.description }} />
                                </div>
                            </div>
                        )}

                        {contract.signatures && contract.signatures.length > 0 && (
                            <div className="border-t pt-8 mt-8">
                                <h3 className="text-lg font-semibold mb-4">{t('Signatures')}</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {contract.signatures.map((signature, index) => (
                                        <div key={signature.id} className="border rounded-lg p-4">
                                            <h4 className="font-semibold mb-3">{signature.user.name}</h4>
                                            <div className="mb-3">
                                                <img
                                                    src={signature.signature_data}
                                                    alt={`${signature.user.name} signature`}
                                                    className="max-h-16 max-w-full object-contain border-b-2 border-gray-400"
                                                />
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {t('Signed on')} {formatDate(signature.signed_at)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="text-center text-sm text-muted-foreground border-t pt-4">
                            <p>{t('Generated on')} {new Date().toLocaleDateString()}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <style jsx>{`
                @media print {
                    .print\\:hidden {
                        display: none !important;
                    }
                    .print\\:border-b-2 {
                        border-bottom-width: 2px !important;
                    }
                    body * {
                        visibility: hidden;
                    }
                    .max-w-4xl, .max-w-4xl * {
                        visibility: visible;
                    }
                    .max-w-4xl {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100% !important;
                        max-width: none !important;
                        margin: 0 !important;
                    }
                }
            `}</style>
        </AuthenticatedLayout>
    );
}