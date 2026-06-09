import { formatCurrency, formatDate, resolveCompanyTaxLabel, resolveCompanyTaxNumber } from '@/utils/helpers';

interface PrintReceiptProps {
    completedSale: any;
    globalSettings: any;
}

const paymentMethodLabel = (method?: string | null): string => {
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

export const printReceipt = (completedSale: any, globalSettings: any) => {
    const companyTaxLabel = resolveCompanyTaxLabel(globalSettings);
    const companyTaxNumber = resolveCompanyTaxNumber(globalSettings);
    const receivedAmount = Number(completedSale?.paid_amount ?? completedSale?.total ?? 0);
    const totalAmount = Number(completedSale?.total ?? 0);
    const changeAmount = Math.max(receivedAmount - totalAmount, 0);
    const taxAmount = (completedSale?.items || []).reduce((sum: number, item: any) => {
        const subtotal = Number(item.price ?? 0) * Number(item.quantity ?? 0);
        if (Array.isArray(item.taxes) && item.taxes.length > 0) {
            return sum + item.taxes.reduce((taxSum: number, tax: any) => taxSum + ((subtotal * Number(tax.rate ?? 0)) / 100), 0);
        }
        return sum + Number(item.tax_amount ?? 0);
    }, 0);
    const hasTax = taxAmount > 0;
    const paymentStatus = receivedAmount >= totalAmount && totalAmount > 0 ? 'Pago' : receivedAmount > 0 ? 'Parcial' : 'Pendente';
    const receiptHTML = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Talão POS - ${completedSale.pos_number}</title>
        <style>
            @page {
                size: 80mm auto;
                margin: 0;
            }
            @media print {
                body { 
                    width: 80mm;
                    margin: 0;
                    padding: 0;
                }
            }
            body { 
                font-family: 'Courier New', monospace; 
                width: 80mm;
                margin: 0; 
                padding: 0;
                font-size: 12px;
                line-height: 1.35;
                color: #000;
                background: #fff;
            }
            .receipt { 
                width: 100%;
                padding: 5mm;
                margin: 0;
                box-sizing: border-box;
            }
            .center {
                text-align: center;
            }
            .company-name {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 4px;
            }
            .company-info {
                font-size: 10px;
                line-height: 1.45;
            }
            .separator {
                border-top: 1px dashed #000;
                margin: 8px 0;
            }
            .receipt-info, .summary {
                text-align: left;
            }
            .info-row, .line-row {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 2px;
            }
            .label {
                white-space: nowrap;
                font-weight: bold;
            }
            .value {
                text-align: right;
                flex: 1;
            }
            .item {
                margin-bottom: 10px;
                border-bottom: 1px dotted #000;
                padding-bottom: 6px;
            }
            .item-name {
                font-weight: bold;
                font-size: 12px;
                margin-bottom: 4px;
            }
            .item-meta {
                font-size: 10px;
            }
            .totals {
                margin-top: 4px;
            }
            .total-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
            }
            .final-total {
                display: flex;
                justify-content: space-between;
                font-weight: bold;
                font-size: 15px;
                border-top: 1px solid #000;
                padding-top: 4px;
                margin-top: 6px;
            }
            .footer {
                text-align: center;
                margin-top: 10px;
                font-size: 10px;
            }
            .badge {
                display: inline-block;
                padding: 2px 6px;
                border: 1px solid #000;
                border-radius: 999px;
                font-size: 10px;
                font-weight: bold;
                margin-top: 4px;
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="center">
                <div class="company-name">${globalSettings?.company_name || 'EMPRESA'}</div>
                <div class="company-info">
                    ${globalSettings?.company_address || 'Morada da empresa'}<br>
                    ${globalSettings?.company_city || 'Cidade'}, ${globalSettings?.company_state || 'Província'}<br>
                    ${globalSettings?.company_country || 'País'} - ${globalSettings?.company_zipcode || 'Código postal'}<br>
                    ${companyTaxNumber ? `${companyTaxLabel}: ${companyTaxNumber}<br>` : ''}
                    ${completedSale.operator_name ? `Operador: ${completedSale.operator_name}<br>` : ''}
                </div>
            </div>

            <div class="separator"></div>

            <div class="receipt-info">
                <div class="info-row">
                    <span class="label">Talão:</span>
                    <span class="value">${completedSale.pos_number}</span>
                </div>
                <div class="info-row">
                    <span class="label">Data:</span>
                    <span class="value">${formatDate(new Date())}</span>
                </div>
                <div class="info-row">
                    <span class="label">Hora:</span>
                    <span class="value">${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Terminal/Série:</span>
                    <span class="value">${completedSale.document_series || '-'}</span>
                </div>
                <div class="info-row">
                    <span class="label">Cliente:</span>
                    <span class="value">${completedSale.customer?.name || 'Cliente ocasional'}</span>
                </div>
            </div>

            <div class="separator"></div>

            <div>
                ${completedSale.items.map((item: any) => {
                    const itemSubtotal = Number(item.price ?? 0) * Number(item.quantity ?? 0);
                    let itemTaxAmount = 0;
                    let taxDisplay = '-';
                    if (item.taxes && item.taxes.length > 0) {
                        const taxNames = item.taxes.map((tax: any) => {
                            itemTaxAmount += (itemSubtotal * Number(tax.rate ?? 0)) / 100;
                            return `${tax.name} (${tax.rate}%)`;
                        });
                        taxDisplay = taxNames.join(', ');
                    } else if (Number(item.tax_amount ?? 0) > 0) {
                        itemTaxAmount = Number(item.tax_amount);
                        taxDisplay = 'IVA incluído';
                    }
                    return `
                        <div class="item">
                            <div class="item-name">${item.name}</div>
                            <div class="item-meta">
                                <div class="line-row"><span class="label">Qtd:</span><span class="value">${item.quantity}</span></div>
                                <div class="line-row"><span class="label">Preço:</span><span class="value">${formatCurrency(item.price)}</span></div>
                                <div class="line-row"><span class="label">IVA:</span><span class="value">${taxDisplay}</span></div>
                                <div class="line-row"><span class="label">IVA valor:</span><span class="value">${formatCurrency(itemTaxAmount)}</span></div>
                                <div class="line-row"><span class="label">Subtotal:</span><span class="value">${formatCurrency(itemSubtotal + itemTaxAmount)}</span></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>

            <div class="separator"></div>

            <div class="totals">
                <div class="total-row">
                    <span class="label">Desconto:</span>
                    <span>-${formatCurrency(completedSale.discount)}</span>
                </div>
                <div class="total-row">
                    <span class="label">Recebido:</span>
                    <span>${formatCurrency(receivedAmount)}</span>
                </div>
                <div class="total-row">
                    <span class="label">Forma de pagamento:</span>
                    <span>${paymentMethodLabel(completedSale.payment_method)}</span>
                </div>
                <div class="total-row">
                    <span class="label">Troco:</span>
                    <span>${formatCurrency(changeAmount)}</span>
                </div>
                <div class="final-total">
                    <span>TOTAL:</span>
                    <span>${formatCurrency(totalAmount)}</span>
                </div>
                <div class="center">
                    <div class="badge">${paymentStatus}</div>
                    ${hasTax ? '<div class="badge" style="margin-left:4px;">IVA incluído</div>' : ''}
                </div>
            </div>

            <div class="separator"></div>

            <div class="footer">
                <div><strong>Obrigado pela sua compra.</strong></div>
                <div>${hasTax ? 'IVA incluído nos preços apresentados.' : 'Sem IVA destacado.'}</div>
            </div>
        </div>
    </body>
    </html>
    `;
    
    const printFrame = document.createElement('iframe');
    printFrame.style.display = 'none';
    document.body.appendChild(printFrame);
    
    const frameDoc = printFrame.contentDocument || printFrame.contentWindow?.document;
    if (frameDoc) {
        frameDoc.write(receiptHTML);
        frameDoc.close();
        
        printFrame.contentWindow?.focus();
        printFrame.contentWindow?.print();
        
        setTimeout(() => {
            document.body.removeChild(printFrame);
        }, 1000);
    }
};
