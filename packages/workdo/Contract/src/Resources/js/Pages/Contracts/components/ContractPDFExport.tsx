import { Button } from "@/components/ui/button";
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Download } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { formatCurrency, formatDate, formatDateTime } from '@/utils/helpers';
import { Contract } from '../types';

interface ContractPDFExportProps {
    contract: Contract & {
        signatures?: Array<{
            id: number;
            user: { name: string };
            signature_type: string;
            signature_data: string;
            signed_at: string;
        }>;
    };
    variant?: "outline" | "default";
    size?: "sm" | "default";
}

export default function ContractPDFExport({ contract, variant = "outline", size = "sm" }: ContractPDFExportProps) {
    const { t } = useTranslation();

    const handleDownloadPDF = () => {
        const element = document.createElement('div');
        element.innerHTML = `
            <div style="padding: 40px; font-family: Arial, sans-serif;">
                <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 24px; margin-bottom: 32px;">
                    <h1 style="font-size: 24px; font-weight: bold; color: #111827; margin-bottom: 8px;">${contract.subject}</h1>
                    <p style="color: #6b7280;">${contract.contract_number}</p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t('Contract Details')}</h3>
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t('Client')}</label>
                            <p style="font-weight: 500; color: #111827;">${contract.user?.name || t('Not Assigned')}</p>
                        </div>
                        <div>
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t('Contract Value')}</label>
                            <p style="font-size: 18px; font-weight: 600; color: #111827;">${contract.value ? formatCurrency(contract.value) : t('Not Set')}</p>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t('Timeline')}</h3>
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t('Start Date')}</label>
                            <p style="font-weight: 500; color: #111827;">${contract.start_date ? formatDate(contract.start_date) : t('Not Set')}</p>
                        </div>
                        <div>
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t('End Date')}</label>
                            <p style="font-weight: 500; color: #111827;">${contract.end_date ? formatDate(contract.end_date) : t('Not Set')}</p>
                        </div>
                    </div>
                </div>
                ${contract.description ? `
                    <div style="margin-bottom: 32px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t('Terms and Conditions')}</h3>
                        <div style="border-left: 4px solid #d1d5db; padding-left: 16px;">
                            <p style="color: #374151; line-height: 1.6; white-space: pre-wrap;">${contract.description}</p>
                        </div>
                    </div>
                ` : ''}
                <div style="border-top: 1px solid #e5e7eb; padding-top: 32px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 24px;">${t('Signatures')}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px;">
                        ${contract.signatures && contract.signatures.length > 0 ? 
                            contract.signatures.map(signature => `
                                <div style="text-align: center;">
                                    <div style="border-bottom: 2px solid #9ca3af; padding-bottom: 8px; margin-bottom: 12px; height: 64px; display: flex; align-items: end; justify-content: center;">
                                        <img src="${signature.signature_data}" alt="Signature" style="max-height: 48px; max-width: 100%; object-fit: contain;" />
                                    </div>
                                    <p style="font-weight: 500; color: #111827;">${signature.user.name}</p>
                                    <p style="font-size: 14px; color: #6b7280;">${formatDateTime(signature.signed_at)}</p>
                                </div>
                            `).join('') : 
                            `<div style="text-align: center;">
                                <div style="border-bottom: 2px solid #d1d5db; padding-bottom: 8px; margin-bottom: 12px; height: 64px;"></div>
                                <p style="font-weight: 500; color: #111827;">${t('Client Signature')}</p>
                                <p style="font-size: 14px; color: #6b7280;">${t('Date')}: _______________</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="border-bottom: 2px solid #d1d5db; padding-bottom: 8px; margin-bottom: 12px; height: 64px;"></div>
                                <p style="font-weight: 500; color: #111827;">${t('Company Representative')}</p>
                                <p style="font-size: 14px; color: #6b7280;">${t('Date')}: _______________</p>
                            </div>`
                        }
                    </div>
                </div>
                <div style="text-align: center; font-size: 12px; color: #9ca3af; padding-top: 32px; margin-top: 32px; border-top: 1px solid #e5e7eb;">
                    <p>${t('Generated on')} ${formatDateTime(contract.created_at)} • ${contract.contract_number}</p>
                </div>
            </div>
        `;
        
        const opt = {
            margin: 0.5,
            filename: `contract-${contract.contract_number}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        import('html2pdf.js').then(html2pdf => {
            html2pdf.default().set(opt).from(element).save();
        });
    };

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant={variant}
                    size={size}
                    onClick={handleDownloadPDF}
                >
                    <Download className="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{t('Download PDF')}</p>
            </TooltipContent>
        </Tooltip>
    );
}