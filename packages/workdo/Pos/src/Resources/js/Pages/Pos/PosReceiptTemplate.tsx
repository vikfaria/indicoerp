import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { formatDate, resolveCompanyTaxLabel, resolveCompanyTaxNumber } from '@/utils/helpers';

export interface PosReceiptTemplateProps {
    sale: any;
    settings?: Record<string, any> | null;
    framed?: boolean;
}

export const POS_RECEIPT_WIDTH_MM = 80;

export const POS_RECEIPT_CSS = `
    .pos-receipt-root {
        width: ${POS_RECEIPT_WIDTH_MM}mm;
        margin: 0 auto;
        background: #fff;
        color: #111827;
        font-family: "Courier New", Courier, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        line-height: 1.35;
        box-sizing: border-box;
    }

    .pos-receipt-root * {
        box-sizing: border-box;
    }

    .pos-receipt-root.pos-receipt-framed {
        border: 1px solid #e5e7eb;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .pos-receipt {
        width: ${POS_RECEIPT_WIDTH_MM}mm;
        min-height: 100%;
        padding: 4mm;
        background: #fff;
    }

    .pos-receipt__center {
        text-align: center;
    }

    .pos-receipt__company {
        margin-bottom: 8px;
        text-align: center;
    }

    .pos-receipt__company-name {
        margin-bottom: 4px;
        font-size: 17px;
        font-weight: 800;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .pos-receipt__company-lines,
    .pos-receipt__muted {
        color: #374151;
        font-size: 10px;
        line-height: 1.4;
    }

    .pos-receipt__divider {
        margin: 8px 0;
        border-top: 1px dashed #6b7280;
    }

    .pos-receipt__row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        margin: 2px 0;
    }

    .pos-receipt__label {
        flex: 0 0 auto;
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }

    .pos-receipt__value {
        min-width: 0;
        flex: 1 1 auto;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .pos-receipt__document-title {
        margin: 5px 0 2px;
        text-align: center;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .pos-receipt__item {
        padding: 6px 0;
        border-bottom: 1px dotted #cbd5e1;
    }

    .pos-receipt__item:last-child {
        border-bottom: 0;
    }

    .pos-receipt__item-name {
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .pos-receipt__total-block {
        margin-top: 4px;
    }

    .pos-receipt__grand-total {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 2px solid #111827;
        font-size: 16px;
        font-weight: 900;
    }

    .pos-receipt__badges {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 8px;
    }

    .pos-receipt__badge {
        display: inline-block;
        padding: 2px 7px;
        border: 1px solid #111827;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
        line-height: 1.2;
    }

    .pos-receipt__footer {
        margin-top: 10px;
        text-align: center;
        font-size: 10px;
        line-height: 1.45;
    }

    @page {
        size: ${POS_RECEIPT_WIDTH_MM}mm auto;
        margin: 0;
    }

    @media print {
        html,
        body {
            width: ${POS_RECEIPT_WIDTH_MM}mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .pos-receipt-root {
            width: ${POS_RECEIPT_WIDTH_MM}mm;
            margin: 0;
            border: 0 !important;
            box-shadow: none !important;
        }

        .pos-receipt {
            width: ${POS_RECEIPT_WIDTH_MM}mm;
            padding: 4mm;
        }
    }
`;

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const lineText = (...parts: Array<string | number | null | undefined>): string => parts
    .map((part) => String(part ?? '').trim())
    .filter(Boolean)
    .join(', ');

const isMozambiqueCompany = (settings?: Record<string, any> | null): boolean => {
    const country = String(settings?.company_country || '').toLowerCase();
    return country.includes('mozambique') || country.includes('moçambique');
};

export const paymentMethodLabel = (method?: string | null): string => {
    const map: Record<string, string> = {
        cash: 'Caixa',
        bank_transfer: 'Transferência bancária',
        card: 'Cartão',
        mobile_money: 'Mobile Money',
        cheque: 'Cheque',
        other: 'Outro',
    };

    return method ? (map[method] || method) : '-';
};

export const formatPosReceiptMoney = (amount: number | string, settings?: Record<string, any> | null): string => {
    const mozambique = isMozambiqueCompany(settings);
    const decimalPlaces = Number.parseInt(String(settings?.decimalFormat ?? '2'), 10);
    const decimals = Number.isFinite(decimalPlaces) ? decimalPlaces : 2;
    const decimalSeparator = mozambique ? ',' : String(settings?.decimalSeparator || '.');
    const thousandsSeparator = mozambique ? ' ' : String(settings?.thousandsSeparator || ',');
    const rawSymbol = String(settings?.currencySymbol || '').trim();
    const symbol = mozambique && (!rawSymbol || rawSymbol === '$') ? 'MT' : (rawSymbol || 'MT');
    const position = mozambique ? 'after' : String(settings?.currencySymbolPosition || 'before');
    const space = String(settings?.currencySymbolSpace ?? '1') !== '0' ? ' ' : '';
    const value = toNumber(amount);
    const parts = value.toFixed(decimals).split('.');

    if (thousandsSeparator && thousandsSeparator !== 'none') {
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }

    const formatted = parts.join(decimalSeparator);

    return position === 'before'
        ? `${symbol}${space}${formatted}`
        : `${formatted}${space}${symbol}`;
};

export const resolvePosReceiptNumber = (sale: any): string => String(
    sale?.pos_number
    || sale?.sale_number
    || sale?.document_number
    || 'POS'
);

export const resolvePosReceiptFileName = (sale: any): string => {
    const number = resolvePosReceiptNumber(sale).replace(/[^a-zA-Z0-9._-]+/g, '-');
    return `receipt-${number}.pdf`;
};

const resolveTaxRateLabel = (tax: any): string => {
    const name = String(tax?.name || tax?.tax_name || 'IVA');
    const rate = toNumber(tax?.rate);
    return rate > 0 ? `${name} (${rate.toFixed(rate % 1 === 0 ? 0 : 2)}%)` : name;
};

export const buildPosReceiptViewModel = (sale: any, settings?: Record<string, any> | null) => {
    const items = Array.isArray(sale?.items) ? sale.items : [];
    const normalizedItems = items.map((item: any) => {
        const quantity = toNumber(item?.quantity);
        const unitPrice = toNumber(item?.price);
        const baseSubtotal = toNumber(item?.subtotal || unitPrice * quantity);
        let taxAmount = toNumber(item?.tax_amount);
        let taxLabel = '-';

        if (Array.isArray(item?.taxes) && item.taxes.length > 0) {
            taxAmount = item.taxes.reduce((sum: number, tax: any) => {
                return sum + (baseSubtotal * toNumber(tax?.rate) / 100);
            }, 0);
            taxLabel = item.taxes.map(resolveTaxRateLabel).join(', ');
        } else if (taxAmount > 0) {
            taxLabel = 'IVA incluído';
        }

        const lineTotal = toNumber(item?.total_amount ?? item?.total ?? baseSubtotal + taxAmount);

        return {
            id: item?.id ?? item?.product_id ?? `${item?.name || item?.product?.name}-${quantity}-${unitPrice}`,
            name: String(item?.name || item?.product?.name || 'Produto'),
            sku: item?.sku || item?.product?.sku || null,
            quantity,
            unitPrice,
            baseSubtotal,
            taxAmount,
            taxLabel,
            lineTotal,
        };
    });

    const subtotal = toNumber(sale?.subtotal) || normalizedItems.reduce((sum: number, item: any) => sum + item.baseSubtotal, 0);
    const taxAmount = toNumber(sale?.tax ?? sale?.tax_amount) || normalizedItems.reduce((sum: number, item: any) => sum + item.taxAmount, 0);
    const discount = toNumber(sale?.discount ?? sale?.discount_amount ?? sale?.payment?.discount);
    const total = toNumber(sale?.total ?? sale?.total_amount ?? sale?.payment?.discount_amount) || Math.max(subtotal + taxAmount - discount, 0);
    const received = toNumber(sale?.paid_amount) || total;
    const change = Math.max(received - total, 0);
    const hasTax = taxAmount > 0 || normalizedItems.some((item: any) => item.taxAmount > 0);
    const createdAt = sale?.created_at ? new Date(sale.created_at) : new Date();
    const dateSource = sale?.pos_date || sale?.created_at || createdAt;
    const companyTaxNumber = resolveCompanyTaxNumber(settings);

    return {
        company: {
            name: settings?.company_name || 'Empresa',
            address: settings?.company_address || null,
            cityState: lineText(settings?.company_city, settings?.company_state),
            countryZip: lineText(settings?.company_country, settings?.company_zipcode),
            phone: settings?.company_telephone || settings?.company_phone || null,
            email: settings?.company_email || null,
            taxLabel: resolveCompanyTaxLabel(settings),
            taxNumber: companyTaxNumber,
        },
        document: {
            type: 'Talão POS',
            number: resolvePosReceiptNumber(sale),
            series: sale?.document_series || '-',
            date: formatDate(dateSource),
            time: createdAt.toLocaleTimeString('pt-MZ', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
            printedAt: new Date().toLocaleString('pt-MZ'),
            operator: sale?.operator_name || sale?.creator?.name || '-',
        },
        customer: {
            name: sale?.customer?.name || 'Consumidor Final',
            taxNumber: sale?.customer?.tax_number || sale?.customer?.nuit || null,
        },
        payment: {
            method: paymentMethodLabel(sale?.payment_method),
            status: received >= total && total > 0 ? 'Pago' : received > 0 ? 'Parcial' : 'Pendente',
            received,
            change,
        },
        totals: {
            subtotal,
            discount,
            taxableBase: Math.max(subtotal - discount, 0),
            taxAmount,
            total,
        },
        items: normalizedItems,
        hasTax,
    };
};

export function PosReceiptTemplate({ sale, settings, framed = false }: PosReceiptTemplateProps) {
    const receipt = buildPosReceiptViewModel(sale, settings);

    return (
        <div className={`pos-receipt-root${framed ? ' pos-receipt-framed' : ''}`} data-pos-receipt>
            <style>{POS_RECEIPT_CSS}</style>
            <article className="pos-receipt">
                <header className="pos-receipt__company">
                    <div className="pos-receipt__company-name">{receipt.company.name}</div>
                    <div className="pos-receipt__company-lines">
                        {receipt.company.address && <div>{receipt.company.address}</div>}
                        {receipt.company.cityState && <div>{receipt.company.cityState}</div>}
                        {receipt.company.countryZip && <div>{receipt.company.countryZip}</div>}
                        {receipt.company.taxNumber && <div>{receipt.company.taxLabel}: {receipt.company.taxNumber}</div>}
                        {receipt.company.phone && <div>Tel: {receipt.company.phone}</div>}
                        {receipt.company.email && <div>{receipt.company.email}</div>}
                        <div>Operador: {receipt.document.operator}</div>
                    </div>
                </header>

                <div className="pos-receipt__divider" />
                <div className="pos-receipt__document-title">{receipt.document.type}</div>

                <section>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Talão:</span>
                        <span className="pos-receipt__value">{receipt.document.number}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Data:</span>
                        <span className="pos-receipt__value">{receipt.document.date}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Hora:</span>
                        <span className="pos-receipt__value">{receipt.document.time}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Terminal/Série:</span>
                        <span className="pos-receipt__value">{receipt.document.series}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Cliente:</span>
                        <span className="pos-receipt__value">{receipt.customer.name}</span>
                    </div>
                    {receipt.customer.taxNumber && (
                        <div className="pos-receipt__row">
                            <span className="pos-receipt__label">NUIT Cliente:</span>
                            <span className="pos-receipt__value">{receipt.customer.taxNumber}</span>
                        </div>
                    )}
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Pagamento:</span>
                        <span className="pos-receipt__value">{receipt.payment.method}</span>
                    </div>
                </section>

                <div className="pos-receipt__divider" />

                <section>
                    {receipt.items.map((item: any) => (
                        <div key={item.id} className="pos-receipt__item">
                            <div className="pos-receipt__item-name">{item.name}</div>
                            {item.sku && <div className="pos-receipt__muted">SKU: {item.sku}</div>}
                            <div className="pos-receipt__row">
                                <span className="pos-receipt__label">Qtd:</span>
                                <span className="pos-receipt__value">{item.quantity} x {formatPosReceiptMoney(item.unitPrice, settings)}</span>
                            </div>
                            <div className="pos-receipt__row">
                                <span className="pos-receipt__label">IVA:</span>
                                <span className="pos-receipt__value">{item.taxLabel}</span>
                            </div>
                            <div className="pos-receipt__row">
                                <span className="pos-receipt__label">Valor IVA:</span>
                                <span className="pos-receipt__value">{formatPosReceiptMoney(item.taxAmount, settings)}</span>
                            </div>
                            <div className="pos-receipt__row">
                                <span className="pos-receipt__label">Subtotal:</span>
                                <span className="pos-receipt__value">{formatPosReceiptMoney(item.lineTotal, settings)}</span>
                            </div>
                        </div>
                    ))}
                </section>

                <div className="pos-receipt__divider" />

                <section className="pos-receipt__total-block">
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Subtotal:</span>
                        <span className="pos-receipt__value">{formatPosReceiptMoney(receipt.totals.subtotal, settings)}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Desconto:</span>
                        <span className="pos-receipt__value">
                            {receipt.totals.discount > 0 ? '-' : ''}{formatPosReceiptMoney(receipt.totals.discount, settings)}
                        </span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Valor tributável:</span>
                        <span className="pos-receipt__value">{formatPosReceiptMoney(receipt.totals.taxableBase, settings)}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">IVA:</span>
                        <span className="pos-receipt__value">{formatPosReceiptMoney(receipt.totals.taxAmount, settings)}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Recebido:</span>
                        <span className="pos-receipt__value">{formatPosReceiptMoney(receipt.payment.received, settings)}</span>
                    </div>
                    <div className="pos-receipt__row">
                        <span className="pos-receipt__label">Troco:</span>
                        <span className="pos-receipt__value">{formatPosReceiptMoney(receipt.payment.change, settings)}</span>
                    </div>
                    <div className="pos-receipt__grand-total">
                        <span>Total:</span>
                        <span>{formatPosReceiptMoney(receipt.totals.total, settings)}</span>
                    </div>
                    <div className="pos-receipt__badges">
                        <span className="pos-receipt__badge">{receipt.payment.status}</span>
                        {receipt.hasTax && <span className="pos-receipt__badge">IVA incluído</span>}
                    </div>
                </section>

                <div className="pos-receipt__divider" />

                <footer className="pos-receipt__footer">
                    <div><strong>Obrigado pela sua compra.</strong></div>
                    <div>{receipt.hasTax ? 'IVA incluído nos preços apresentados.' : 'Sem IVA destacado.'}</div>
                    <div>Processado por Índico ERP</div>
                    <div>Impresso em: {receipt.document.printedAt}</div>
                </footer>
            </article>
        </div>
    );
}

export const renderPosReceiptMarkup = (
    sale: any,
    settings?: Record<string, any> | null,
    options?: { framed?: boolean },
): string => renderToStaticMarkup(
    <PosReceiptTemplate sale={sale} settings={settings} framed={options?.framed ?? false} />
);

export const renderPosReceiptDocumentHtml = (
    sale: any,
    settings?: Record<string, any> | null,
): string => `<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Talão POS - ${resolvePosReceiptNumber(sale)}</title>
    <style>
        html,
        body {
            width: ${POS_RECEIPT_WIDTH_MM}mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }
    </style>
</head>
<body>${renderPosReceiptMarkup(sale, settings)}</body>
</html>`;
