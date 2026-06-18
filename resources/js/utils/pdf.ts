let html2PdfLoader: Promise<any> | null = null;

const getHtml2Pdf = async () => {
    if (!html2PdfLoader) {
        html2PdfLoader = import('html2pdf.js').then((module) => module.default);
    }

    return html2PdfLoader;
};

const A4_WIDTH_MM = 210;
const A4_HEIGHT_MM = 297;
const MM_TO_PX = 96 / 25.4;

const computeFitToPageScale = (element: HTMLElement, options: Record<string, any>): number => {
    const rect = element.getBoundingClientRect();
    const width = Math.max(rect.width, element.scrollWidth, element.offsetWidth, 1);
    const height = Math.max(rect.height, element.scrollHeight, element.offsetHeight, 1);
    const marginMm = Number(options.fitToPageMarginMm ?? 0) || 0;
    const availableWidthPx = Math.max((A4_WIDTH_MM - (marginMm * 2)) * MM_TO_PX, 1);
    const availableHeightPx = Math.max((A4_HEIGHT_MM - (marginMm * 2)) * MM_TO_PX, 1);
    const minScale = Number(options.fitToPageMinScale ?? 0.45) || 0.45;
    const maxScale = Number(options.fitToPageMaxScale ?? 1) || 1;

    return Math.min(maxScale, Math.max(minScale, Math.min(availableWidthPx / width, availableHeightPx / height)));
};

export const saveElementAsPdf = async (element: HTMLElement, options: Record<string, any>) => {
    const html2pdf = await getHtml2Pdf();
    const { fitToPage, fitToPageMarginMm, fitToPageMinScale, fitToPageMaxScale, ...pdfOptions } = options ?? {};

    if (!fitToPage) {
        return html2pdf().set(pdfOptions).from(element).save();
    }

    const originalTransform = element.style.transform;
    const originalTransformOrigin = element.style.transformOrigin;
    const originalWillChange = element.style.willChange;
    const scale = computeFitToPageScale(element, {
        fitToPageMarginMm,
        fitToPageMinScale,
        fitToPageMaxScale,
    });

    try {
        element.style.transformOrigin = 'top center';
        element.style.transform = scale < 1 ? `scale(${scale})` : originalTransform;
        element.style.willChange = 'transform';

        return await html2pdf().set({
            ...pdfOptions,
            margin: 0,
            pagebreak: {
                mode: ['avoid-all', 'css', 'legacy'],
            },
            html2canvas: {
                scale: Math.max(Number(pdfOptions?.html2canvas?.scale ?? 2), 2),
                useCORS: true,
                backgroundColor: '#ffffff',
                ...(pdfOptions?.html2canvas || {}),
            },
        }).from(element).save();
    } finally {
        element.style.transform = originalTransform;
        element.style.transformOrigin = originalTransformOrigin;
        element.style.willChange = originalWillChange;
    }
};
