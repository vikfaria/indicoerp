import { saveElementAsPdf } from '@/utils/pdf';
import {
    POS_RECEIPT_WIDTH_MM,
    renderPosReceiptMarkup,
    resolvePosReceiptFileName,
} from './PosReceiptTemplate';

const PX_TO_MM = 25.4 / 96;

const nextFrame = () => new Promise<void>((resolve) => {
    requestAnimationFrame(() => resolve());
});

export const buildReceiptPdfOptions = (element: HTMLElement, sale: any) => {
    const measuredHeight = Math.ceil((element.scrollHeight || element.offsetHeight || 0) * PX_TO_MM) + 4;
    const pageHeight = Math.max(90, measuredHeight);

    return {
        margin: 0,
        filename: resolvePosReceiptFileName(sale),
        image: { type: 'jpeg' as const, quality: 0.98 },
        html2canvas: {
            scale: 3,
            useCORS: true,
            backgroundColor: '#ffffff',
            scrollX: 0,
            scrollY: 0,
            windowWidth: element.scrollWidth || element.offsetWidth,
        },
        jsPDF: {
            unit: 'mm',
            format: [POS_RECEIPT_WIDTH_MM, pageHeight],
            orientation: 'portrait' as const,
        },
        pagebreak: { mode: ['css', 'legacy'] },
    };
};
export const downloadReceiptElementPDF = async (element: HTMLElement, sale: any) => {
    await saveElementAsPdf(element, buildReceiptPdfOptions(element, sale));
};

export const downloadReceiptPDF = async (completedSale: any, globalSettings: any) => {
    const container = document.createElement('div');
    container.style.position = 'fixed';
    container.style.left = '-10000px';
    container.style.top = '0';
    container.style.width = `${POS_RECEIPT_WIDTH_MM}mm`;
    container.style.background = '#ffffff';
    container.innerHTML = renderPosReceiptMarkup(completedSale, globalSettings);

    document.body.appendChild(container);

    try {
        await nextFrame();
        const receipt = container.querySelector('[data-pos-receipt]') as HTMLElement | null;
        await downloadReceiptElementPDF(receipt || container, completedSale);
    } catch (error) {
        console.error('PDF generation failed:', error);
    } finally {
        document.body.removeChild(container);
    }
};
