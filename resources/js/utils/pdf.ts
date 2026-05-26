let html2PdfLoader: Promise<any> | null = null;

const getHtml2Pdf = async () => {
    if (!html2PdfLoader) {
        html2PdfLoader = import('html2pdf.js').then((module) => module.default);
    }

    return html2PdfLoader;
};

export const saveElementAsPdf = async (element: HTMLElement, options: Record<string, any>) => {
    const html2pdf = await getHtml2Pdf();
    return html2pdf().set(options).from(element).save();
};
