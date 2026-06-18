import React, { useEffect, useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Download, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { downloadReceiptElementPDF } from '../Pos/DownloadReceipt';
import { printReceipt } from '../Pos/PrintReceipt';
import { PosReceiptTemplate, resolvePosReceiptNumber } from '../Pos/PosReceiptTemplate';

interface PosItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    subtotal?: number;
    tax_amount?: number;
    total_amount?: number;
    product?: {
        id: number;
        name: string;
        sku?: string;
    };
    taxes?: Array<{ id?: number; tax_name?: string; name?: string; rate: number }>;
}
interface PosSale {
    id: number;
    sale_number: string;
    document_series?: string;
    payment_method?: string;
    paid_amount?: number;
    customer?: {
        name: string;
        email?: string;
        tax_number?: string;
        nuit?: string;
    };
    warehouse?: {
        name: string;
    };
    creator?: {
        name?: string;
    };
    subtotal?: number;
    discount_amount?: number;
    tax_amount?: number;
    total?: number;
    total_amount?: number;
    created_at: string;
    pos_date?: string;
    items: PosItem[];
}

interface PrintProps {
    sale: PosSale & Record<string, any>;
}

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { sale } = page.props;
    const receiptRef = useRef<HTMLDivElement>(null);
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = ((page.props as any).companyAllSetting || (page.props as any).adminAllSetting || {});

    const handleDownload = async () => {
        const receipt = receiptRef.current?.querySelector('[data-pos-receipt]') as HTMLElement | null;
        if (!receipt) {
            return;
        }

        setIsDownloading(true);
        try {
            await downloadReceiptElementPDF(receipt, sale);
        } catch (error) {
            console.error('PDF generation failed:', error);
        } finally {
            setIsDownloading(false);
        }
    };

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            handleDownload().then(() => {
                setTimeout(() => window.close(), 700);
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <div className="min-h-screen bg-slate-100 py-6 print:bg-white print:py-0">
            <Head title={`Talão POS #${resolvePosReceiptNumber(sale)}`} />

            {isDownloading && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="rounded-lg bg-white px-5 py-4 shadow-xl">
                        <div className="flex items-center gap-3">
                            <div className="h-5 w-5 animate-spin rounded-full border-b-2 border-emerald-600" />
                            <p className="text-sm font-semibold text-slate-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="mx-auto flex w-fit flex-col items-center gap-4">
                <div className="flex gap-2 print:hidden">
                    <Button type="button" variant="outline" onClick={() => printReceipt(sale, settings)}>
                        <Printer className="mr-2 h-4 w-4" />
                        {t('Print')}
                    </Button>
                    <Button type="button" onClick={handleDownload} disabled={isDownloading}>
                        <Download className="mr-2 h-4 w-4" />
                        {t('Download PDF')}
                    </Button>
                </div>

                <div ref={receiptRef}>
                    <PosReceiptTemplate sale={sale} settings={settings} framed />
                </div>
            </div>
        </div>
    );
}
