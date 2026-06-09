import { saveElementAsPdf } from '@/utils/pdf';
import { formatCurrency, formatDate, resolveCompanyTaxLabel, resolveCompanyTaxNumber } from '@/utils/helpers';

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

export const downloadReceiptPDF = async (completedSale: any, globalSettings: any) => {
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
                <div class="info-row"><span class="label">Talão:</span><span class="value">${completedSale.pos_number}</span></div>
                <div class="info-row"><span class="label">Data:</span><span class="value">${formatDate(new Date())}</span></div>
                <div class="info-row"><span class="label">Hora:</span><span class="value">${new Date().toLocaleTimeString()}</span></div>
                <div class="info-row"><span class="label">Terminal/Série:</span><span class="value">${completedSale.document_series || '-'}</span></div>
                <div class="info-row"><span class="label">Cliente:</span><span class="value">${completedSale.customer?.name || 'Cliente ocasional'}</span></div>
            </div>

            <div class="separator"></div>

            <div class="items-section">
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
                <div class="total-row"><span class="label">Desconto:</span><span>-${formatCurrency(completedSale.discount)}</span></div>
                <div class="total-row"><span class="label">Recebido:</span><span>${formatCurrency(receivedAmount)}</span></div>
                <div class="total-row"><span class="label">Forma de pagamento:</span><span>${paymentMethodLabel(completedSale.payment_method)}</span></div>
                <div class="total-row"><span class="label">Troco:</span><span>${formatCurrency(changeAmount)}</span></div>
                <div class="final-total"><span>TOTAL:</span><span>${formatCurrency(totalAmount)}</span></div>
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
        
        <style>
            .receipt { max-width: 400px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; }
            .center { text-align: center; }
            .header { text-align: center; margin-bottom: 20px; }
            .company-name { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
            .company-info { font-size: 12px; line-height: 1.4; }
            .separator { border-top: 1px dashed #000; margin: 15px 0; }
            .info-row, .line-row, .total-row { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
            .label { font-weight: bold; white-space: nowrap; }
            .value { text-align: right; flex: 1; }
            .item { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dotted #ccc; }
            .item-name { font-weight: bold; margin-bottom: 8px; }
            .item-meta { font-size: 12px; }
            .totals { margin-top: 4px; }
            .final-total { display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; }
            .badge { display: inline-block; margin-top: 6px; padding: 2px 6px; border: 1px solid #000; border-radius: 999px; font-size: 10px; font-weight: bold; }
        </style>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = receiptHTML;
    document.body.appendChild(tempDiv);
    
    const opt = {
        margin: 0.1,
        filename: `receipt-${completedSale.pos_number}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: [80, 297], orientation: 'portrait' }
    };
    
    try {
        await saveElementAsPdf(tempDiv, opt);
    } catch (error) {
        console.error('PDF generation failed:', error);
    } finally {
        document.body.removeChild(tempDiv);
    }
};
