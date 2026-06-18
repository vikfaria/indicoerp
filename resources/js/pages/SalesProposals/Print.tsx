import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { saveElementAsPdf } from '@/utils/pdf';
import {
    formatDate,
    getCompanyTaxLabel,
    resolveDocumentIssuer,
    resolveSalesDocumentCounterparty,
} from '@/utils/helpers';
import {
    buildCommercialDocumentPdfOptions,
    buildPartyCityLine,
    buildPartyCountryLine,
    buildStructuredDocumentNumber,
    COMMERCIAL_DOCUMENT_CONTAINER_CLASS,
    CommercialDocumentTemplate,
    formatDocumentMoney,
    formatDocumentQuantity,
    moneyToPortugueseWords,
} from '@/components/documents/commercial-document-template';

interface Address {
    name?: string;
    address_line_1?: string;
    address_line_2?: string;
    city?: string;
    state?: string;
    zip_code?: string;
    country?: string;
}

interface SalesProposal {
    id: number;
    proposal_number: string;
    proposal_date: string;
    due_date: string;
    document_series?: string;
    document_sequence?: number;
    customer?: { id: number; name: string; email?: string };
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    total_amount: number;
    status: string;
    payment_terms?: string;
    notes?: string;
    warehouse?: { id: number; name: string };
    customer_details?: {
        company_name?: string;
        tax_number?: string;
        billing_address?: Address;
    };
    items?: Array<{
        id: number;
        quantity: number;
        unit_price: number;
        discount_percentage?: number;
        discount_amount?: number;
        tax_percentage?: number;
        tax_amount?: number;
        total_amount: number;
        product?: {
            id: number;
            name: string;
            sku?: string;
            description?: string;
            unit?: string;
        };
        taxes?: Array<{
            id: number;
            tax_name: string;
            tax_rate: number;
        }>;
    }>;
}

interface PrintProps {
    proposal: SalesProposal;
    [key: string]: any;
}

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

type SalesProposalItem = NonNullable<SalesProposal['items']>[number];
type SalesProposalItemTax = NonNullable<SalesProposalItem['taxes']>[number];

const taxLabelForItem = (item: SalesProposalItem): string => {
    if (item.taxes && item.taxes.length > 0) {
        return item.taxes.map((tax: SalesProposalItemTax) => `${tax.tax_name} ${tax.tax_rate}%`).join(', ');
    }

    return toNumber(item.tax_percentage) > 0 ? `IVA ${item.tax_percentage}%` : '0%';
};

export default function Print() {
    const { t } = useTranslation();
    const page = usePage<PrintProps>();
    const { proposal } = page.props;
    const [isDownloading, setIsDownloading] = useState(false);
    const settings = (page.props as any).companyAllSetting || {};
    const issuer = resolveDocumentIssuer(proposal as Record<string, any>, page.props);
    const customer = resolveSalesDocumentCounterparty(proposal as Record<string, any>);
    const companyTaxLabel = issuer.tax_label || getCompanyTaxLabel(page.props);
    const counterpartyTaxLabel = customer.tax_label || companyTaxLabel;
    const documentNumber = buildStructuredDocumentNumber({
        prefix: 'ORC',
        series: proposal.document_series,
        sequence: proposal.document_sequence,
        number: proposal.proposal_number,
        date: proposal.proposal_date,
    });

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector(`.${COMMERCIAL_DOCUMENT_CONTAINER_CLASS}`);
        if (printContent) {
            try {
                await saveElementAsPdf(
                    printContent as HTMLElement,
                    buildCommercialDocumentPdfOptions(`orcamento-${documentNumber}.pdf`),
                );
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const lines = (proposal.items || []).map((item) => {
        const quantity = toNumber(item.quantity);
        const unitPrice = toNumber(item.unit_price);
        const discount = toNumber(item.discount_amount);
        const netUnitPrice = quantity > 0 ? Math.max(((unitPrice * quantity) - discount) / quantity, 0) : unitPrice;

        return {
            reference: item.product?.sku || String(item.id),
            description: (
                <>
                    <div>{item.product?.name || '-'}</div>
                    {item.product?.description && <div className="text-[10px] font-normal text-slate-500">{item.product.description}</div>}
                </>
            ),
            unit: item.product?.unit || 'UN',
            quantity: formatDocumentQuantity(quantity),
            unitPrice: formatDocumentMoney(unitPrice, settings),
            discount: toNumber(item.discount_percentage) > 0
                ? `${item.discount_percentage}%`
                : discount > 0 ? formatDocumentMoney(discount, settings) : '-',
            netPrice: formatDocumentMoney(netUnitPrice, settings),
            tax: taxLabelForItem(item),
            taxAmount: formatDocumentMoney(toNumber(item.tax_amount), settings),
            total: formatDocumentMoney(item.total_amount, settings),
        };
    });

    const billing = customer.billing_address || proposal.customer_details?.billing_address || {};
    const authUser = (page.props as any).auth?.user;

    return (
        <div className="min-h-screen bg-slate-100 py-6 print:bg-white print:py-0">
            <Head title={`Orçamento #${documentNumber}`} />

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

            <CommercialDocumentTemplate
                title="ORÇAMENTO"
                subtitle="Cotação comercial"
                documentLabel="Orçamento"
                documentNumber={documentNumber}
                copyLabel="ORIGINAL"
                issuer={{
                    title: 'Emitente',
                    name: issuer.company_name || 'Empresa',
                    logoPath: settings.logo_dark || settings.logo_light || null,
                    address: issuer.company_address,
                    cityLine: buildPartyCityLine(issuer),
                    countryLine: buildPartyCountryLine(issuer),
                    taxLabel: companyTaxLabel,
                    taxNumber: issuer.tax_number,
                    phone: issuer.company_telephone,
                    email: issuer.company_email,
                    website: settings.company_website || settings.website,
                    registration: issuer.registration_number,
                }}
                recipient={{
                    title: 'Para',
                    name: customer.company_name || customer.name || proposal.customer?.name || 'Cliente',
                    address: billing.address_line_1,
                    cityLine: [billing.city, billing.state, billing.zip_code].filter(Boolean).join(', '),
                    countryLine: billing.country,
                    taxLabel: counterpartyTaxLabel,
                    taxNumber: customer.tax_number,
                    phone: customer.phone,
                    email: customer.email || proposal.customer?.email,
                }}
                statusPills={[
                    { label: 'Documento não fiscal', tone: 'warning' },
                    { label: proposal.status || 'Rascunho', tone: proposal.status === 'accepted' ? 'success' : 'neutral' },
                ]}
                meta={[
                    { label: 'Data', value: formatDate(proposal.proposal_date, page.props) },
                    { label: 'Validade', value: formatDate(proposal.due_date, page.props) },
                    { label: 'Vendedor', value: authUser?.name || '-' },
                    { label: 'Pagamento', value: proposal.payment_terms || 'A combinar' },
                    { label: 'Moeda', value: 'Metical' },
                    { label: 'Armazém', value: proposal.warehouse?.name || '-' },
                    { label: 'Série', value: proposal.document_series || '-' },
                    { label: 'Sequência', value: proposal.document_sequence || '-' },
                ]}
                lines={lines}
                totals={[
                    { label: 'Subtotal', value: formatDocumentMoney(proposal.subtotal, settings) },
                    { label: 'Descontos', value: formatDocumentMoney(proposal.discount_amount, settings) },
                    { label: 'Valor tributável', value: formatDocumentMoney(Math.max(toNumber(proposal.subtotal) - toNumber(proposal.discount_amount), 0), settings) },
                    { label: 'IVA', value: formatDocumentMoney(proposal.tax_amount, settings) },
                    { label: 'Total com IVA', value: formatDocumentMoney(proposal.total_amount, settings), emphasis: true },
                    { label: 'Total por extenso', value: moneyToPortugueseWords(proposal.total_amount) },
                ]}
                observations={proposal.notes || [
                    'Validade da proposta conforme data indicada.',
                    'Documento sujeito à confirmação de stock.',
                    'Preços sujeitos a alteração após o prazo de validade.',
                ].join('\n')}
                legalNotice="Documento não fiscal. Não serve de factura."
                bankDetails={[
                    { label: 'Banco', value: settings.company_bank_name || settings.bank_name },
                    { label: 'Conta', value: settings.company_bank_account || settings.bank_account },
                    { label: 'NIB', value: settings.company_bank_nib || settings.bank_nib },
                    { label: 'Titular', value: settings.company_bank_holder || issuer.company_name },
                ]}
                validationCode={`${documentNumber}-${proposal.id}`}
                issuedBy={authUser?.name || '-'}
                printedBy={authUser?.name || '-'}
                printedAt={new Date().toLocaleString('pt-MZ')}
            />
        </div>
    );
}
