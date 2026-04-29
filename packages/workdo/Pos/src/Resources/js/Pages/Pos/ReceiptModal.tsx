import React, { useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Printer, Download, CheckCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { formatCurrency, formatDate, resolveCompanyTaxLabel, resolveCompanyTaxNumber } from '@/utils/helpers';
import { printReceipt } from './PrintReceipt';
import { downloadReceiptPDF } from './DownloadReceipt';




interface ReceiptModalProps {
    isOpen: boolean;
    onClose: () => void;
    completedSale: any;
    globalSettings: any;
}

export default function ReceiptModal({ isOpen, onClose, completedSale, globalSettings }: ReceiptModalProps) {
    const { t } = useTranslation();
    const receiptRef = useRef<HTMLDivElement>(null);
    const companyTaxLabel = resolveCompanyTaxLabel(globalSettings);
    const companyTaxNumber = resolveCompanyTaxNumber(globalSettings);

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
                            <p className="text-green-600 text-sm mt-1">{t('Receipt Number')}: {completedSale.pos_number}</p>
                        </div>

                        {/* Thermal Receipt Preview */}
                        <div ref={receiptRef} className="mx-auto w-72 bg-white border border-gray-200 shadow-lg font-mono text-xs leading-tight print-area print-receipt">
                            <div className="p-3">
                                {/* Header */}
                                <div className="text-center mb-3">
                                    <div className="font-bold text-base tracking-wider mb-1">{globalSettings?.company_name || 'COMPANY NAME'}</div>
                                    <div className="text-xs leading-relaxed">
                                        <div>{globalSettings?.company_address || 'Company Address'}</div>
                                        <div>{globalSettings?.company_city || 'City'}, {globalSettings?.company_state || 'State'}</div>
                                        <div>{globalSettings?.company_country || 'Country'} - {globalSettings?.company_zipcode || 'Zipcode'}</div>
                                        {companyTaxNumber && <div>{companyTaxLabel}: {companyTaxNumber}</div>}
                                    </div>
                                </div>

                                {/* Separator */}
                                <div className="text-center my-2">
                                    <div className="border-t-2 border-dashed border-gray-400"></div>
                                </div>

                                {/* Receipt Info */}
                                <div className="mb-3">
                                    <div className="flex justify-between py-0.5">
                                        <span className="font-medium">{t('Receipt')}:</span>
                                        <span className="font-bold">{completedSale.pos_number}</span>
                                    </div>
                                    <div className="flex justify-between py-0.5">
                                        <span className="font-medium">{t('Date')}:</span>
                                        <span>{formatDate(new Date())}</span>
                                    </div>
                                    <div className="flex justify-between py-0.5">
                                        <span className="font-medium">{t('Customer')}:</span>
                                        <span className="truncate ml-2 font-medium">{completedSale.customer?.name || t('Walk-in')}</span>
                                    </div>
                                </div>

                                {/* Separator */}
                                <div className="text-center my-2">
                                    <div className="border-t-2 border-dashed border-gray-400"></div>
                                </div>

                                {/* Items */}
                                <div className="mb-3">
                                    {completedSale.items.map((item: any) => {
                                        const itemSubtotal = item.price * item.quantity;
                                        let itemTaxAmount = 0;
                                        let taxDisplay = '';
                                        if (item.taxes && item.taxes.length > 0) {
                                            const taxNames = item.taxes.map((tax: any) => {
                                                itemTaxAmount += (itemSubtotal * tax.rate) / 100;
                                                return `${tax.name} (${tax.rate}%)`;
                                            });
                                            taxDisplay = taxNames.join(', ');
                                        } else {
                                            taxDisplay = '-';
                                        }
                                        return (
                                            <div key={item.id} className="mb-3 pb-2 border-b border-dotted border-gray-300">
                                                <div className="font-bold text-sm mb-1 truncate">{item.name}</div>
                                                <div className="space-y-0.5 text-xs">
                                                    <div className="flex justify-between">
                                                        <span>{t('Qty')}:</span>
                                                        <span className="font-medium">{item.quantity}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>{t('Price')}:</span>
                                                        <span className="font-medium">{formatCurrency(item.price)}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>{t('Tax')}:</span>
                                                        <span className="font-medium">{taxDisplay}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>{t('Tax Amount')}:</span>
                                                        <span className="font-medium">{formatCurrency(itemTaxAmount)}</span>
                                                    </div>
                                                    <div className="flex justify-between font-bold border-t border-dotted pt-1">
                                                        <span>{t('Sub Total')}:</span>
                                                        <span>{formatCurrency(itemSubtotal)}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Separator */}
                                <div className="text-center my-2">
                                    <div className="border-t-2 border-dashed border-gray-400"></div>
                                </div>

                                {/* Totals */}
                                <div className="mb-3">
                                    <div className="flex justify-between py-1 text-sm">
                                        <span className="font-medium">{t('Discount')}:</span>
                                        <span className="font-bold">-{formatCurrency(completedSale.discount)}</span>
                                    </div>
                                    <div className="flex justify-between py-2 text-base font-bold border-t-2 border-double border-gray-600">
                                        <span>{t('Total')}:</span>
                                        <span className="text-lg">{formatCurrency(completedSale.total)}</span>
                                    </div>
                                </div>

                                {/* Separator */}
                                <div className="text-center my-2">
                                    <div className="border-t-2 border-dashed border-gray-400"></div>
                                </div>

                                {/* Footer */}
                                <div className="text-center">
                                    <div className="text-xs font-medium">{t('★ Thank you for your business! ★')}</div>
                                    <div className="text-xs mt-1 opacity-75">{new Date().toLocaleTimeString()}</div>
                                </div>
                            </div>
                        </div>

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
