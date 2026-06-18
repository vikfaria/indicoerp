import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Printer, Download, CheckCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { printReceipt } from './PrintReceipt';
import { downloadReceiptPDF } from './DownloadReceipt';
import { PosReceiptTemplate, resolvePosReceiptNumber } from './PosReceiptTemplate';

interface ReceiptModalProps {
    isOpen: boolean;
    onClose: () => void;
    completedSale: any;
    globalSettings: any;
}

export default function ReceiptModal({ isOpen, onClose, completedSale, globalSettings }: ReceiptModalProps) {
    const { t } = useTranslation();

    const handlePrint = () => {
        printReceipt(completedSale, globalSettings);
    };

    const handleDownload = () => {
        downloadReceiptPDF(completedSale, globalSettings);
    };

    if (!completedSale) return null;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
                <DialogContent className="max-w-md max-h-[90vh] overflow-y-auto backdrop-blur-none">
                    <DialogHeader className="no-print">
                        <DialogTitle className="flex items-center justify-center text-green-600">
                            <CheckCircle className="h-6 w-6 mr-2" />
                            {t('Sale Completed Successfully!')}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        {/* Success Message */}
                        <div className="text-center bg-green-50 p-4 rounded-lg no-print">
                            <p className="text-green-800 font-medium">{t('Your transaction has been processed successfully.')}</p>
                            <p className="text-green-600 text-sm mt-1">{t('Receipt Number')}: {resolvePosReceiptNumber(completedSale)}</p>
                        </div>

                        {/* Thermal Receipt Preview */}
                        <PosReceiptTemplate sale={completedSale} settings={globalSettings} framed />

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-2 no-print">
                        <Button onClick={handleDownload} className="bg-green-500 hover:bg-green-700">
                            <Download className="h-4 w-4 mr-2" />
                            {t('Download PDF')}
                        </Button>
                        <Button onClick={handlePrint} className="bg-blue-500 hover:bg-blue-700">
                            <Printer className="h-4 w-4 mr-2" />
                            {t('Print')}
                        </Button>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('Close')}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
