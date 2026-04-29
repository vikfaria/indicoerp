import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, formatTime, resolveCompanyTaxLabel, resolveCompanyTaxNumber } from '@/utils/helpers';

export const downloadReceiptPDF = async (completedSale: any, globalSettings: any) => {
    const companyTaxLabel = resolveCompanyTaxLabel(globalSettings);
    const companyTaxNumber = resolveCompanyTaxNumber(globalSettings);
    const receiptHTML = `
        <div class="receipt">
            <div class="header">
                <div class="company-name">${globalSettings?.company_name || 'COMPANY NAME'}</div>
                <div class="company-info">
                    ${globalSettings?.company_address || 'Company Address'}<br>
                    ${globalSettings?.company_city || 'City'}, ${globalSettings?.company_state || 'State'}<br>
                    ${globalSettings?.company_country || 'Country'} - ${globalSettings?.company_zipcode || 'Zipcode'}
                    ${companyTaxNumber ? `<br>${companyTaxLabel}: ${companyTaxNumber}` : ''}
                </div>
            </div>
            
            <div class="separator"></div>
            
            <div class="receipt-info">
                <div class="info-row">
                    <span>Receipt No:</span>
                    <span>${completedSale.pos_number}</span>
                </div>
                <div class="info-row">
                    <span>Date:</span>
                    <span>${formatDate(new Date())}</span>
                </div>
                <div class="info-row">
                    <span>Time:</span>
                    <span>${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="info-row">
                    <span>Customer:</span>
                    <span>${completedSale.customer?.name || 'Walk-in Customer'}</span>
                </div>
            </div>
            
            <div class="separator"></div>
            
            <div class="items-section">
                ${completedSale.items.map((item: any) => {
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
                        taxDisplay = 'No Tax';
                    }
                    return `
                        <div class="item">
                            <div class="item-name">${item.name}</div>
                            <div class="item-details">
                                <div class="total-row">
                                    <span>Qty: ${item.quantity}</span>
                                    <span>Price: ${formatCurrency(item.price)}</span>
                                </div>
                                <div class="total-row">
                                    <span>Tax: ${taxDisplay}</span>
                                    <span>Tax Amount: ${formatCurrency(itemTaxAmount)}</span>
                                </div>
                                <div class="total-row" style="font-weight: bold;">
                                    <span>Subtotal:</span>
                                    <span>${formatCurrency(itemSubtotal + itemTaxAmount)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
            
            <div class="separator"></div>
            
            <div class="totals">
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-${formatCurrency(completedSale.discount)}</span>
                </div>
                <div class="final-total">
                    <span>TOTAL:</span>
                    <span>${formatCurrency(completedSale.total)}</span>
                </div>
            </div>
            
            <div class="separator"></div>
            
            <div class="footer">
                <div style="font-weight: bold;">*** THANK YOU ***</div>
                <div>Visit Again!</div>
            </div>
        </div>
        
        <style>
            .receipt { max-width: 400px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 20px; }
            .company-name { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
            .company-info { font-size: 12px; line-height: 1.4; }
            .separator { border-top: 1px dashed #000; margin: 15px 0; }
            .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .item { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dotted #ccc; }
            .item-name { font-weight: bold; margin-bottom: 8px; }
            .item-details { font-size: 12px; }
            .total-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .final-total { display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; }
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
        await html2pdf().set(opt).from(tempDiv).save();
    } catch (error) {
        console.error('PDF generation failed:', error);
    } finally {
        document.body.removeChild(tempDiv);
    }
};
