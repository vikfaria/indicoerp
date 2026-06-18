import { renderPosReceiptDocumentHtml } from './PosReceiptTemplate';

export const printReceipt = (completedSale: any, globalSettings: any) => {
    const printFrame = document.createElement('iframe');
    printFrame.style.position = 'fixed';
    printFrame.style.right = '0';
    printFrame.style.bottom = '0';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = '0';

    document.body.appendChild(printFrame);

    const frameDoc = printFrame.contentDocument || printFrame.contentWindow?.document;
    if (!frameDoc) {
        document.body.removeChild(printFrame);
        return;
    }

    frameDoc.open();
    frameDoc.write(renderPosReceiptDocumentHtml(completedSale, globalSettings));
    frameDoc.close();

    const printWindow = printFrame.contentWindow;
    if (!printWindow) {
        document.body.removeChild(printFrame);
        return;
    }

    setTimeout(() => {
        printWindow.focus();
        printWindow.print();

        setTimeout(() => {
            if (document.body.contains(printFrame)) {
                document.body.removeChild(printFrame);
            }
        }, 1000);
    }, 250);
};
