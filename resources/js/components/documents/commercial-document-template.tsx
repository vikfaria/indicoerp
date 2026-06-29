import React, { ReactNode } from 'react';
import { getImagePath } from '@/utils/helpers';

export type CommercialDocumentTone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

export const COMMERCIAL_DOCUMENT_CONTAINER_CLASS = 'commercial-document-pdf-root';

export const buildCommercialDocumentPdfOptions = (filename: string) => ({
    margin: 0,
    filename,
    image: { type: 'jpeg' as const, quality: 0.98 },
    pagebreak: {
        mode: ['css', 'legacy'],
        avoid: ['.commercial-page-break-avoid'],
    },
    html2canvas: {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' as const },
});

export interface CommercialDocumentParty {
    title?: ReactNode;
    name?: ReactNode;
    legalName?: ReactNode;
    logoPath?: string | null;
    address?: ReactNode;
    cityLine?: ReactNode;
    countryLine?: ReactNode;
    taxLabel?: ReactNode;
    taxNumber?: ReactNode;
    phone?: ReactNode;
    email?: ReactNode;
    website?: ReactNode;
    registration?: ReactNode;
    extraLines?: ReactNode[];
}

export interface CommercialDocumentMeta {
    label: ReactNode;
    value?: ReactNode;
}

export interface CommercialDocumentLine {
    reference?: ReactNode;
    description: ReactNode;
    unit?: ReactNode;
    quantity?: ReactNode;
    unitPrice?: ReactNode;
    discount?: ReactNode;
    netPrice?: ReactNode;
    tax?: ReactNode;
    taxAmount?: ReactNode;
    total?: ReactNode;
}

export interface CommercialDocumentTotal {
    label: ReactNode;
    value: ReactNode;
    emphasis?: boolean;
}

export interface CommercialDocumentPill {
    label: ReactNode;
    tone?: CommercialDocumentTone;
}

export interface CommercialDocumentTemplateProps {
    title: ReactNode;
    subtitle?: ReactNode;
    documentNumber: ReactNode;
    documentLabel?: ReactNode;
    copyLabel?: ReactNode;
    issuer: CommercialDocumentParty;
    recipient?: CommercialDocumentParty;
    meta?: CommercialDocumentMeta[];
    lines?: CommercialDocumentLine[];
    totals?: CommercialDocumentTotal[];
    observations?: ReactNode;
    legalNotice?: ReactNode;
    bankDetails?: CommercialDocumentMeta[];
    validationCode?: ReactNode;
    issuedBy?: ReactNode;
    printedBy?: ReactNode;
    printedAt?: ReactNode;
    statusPills?: CommercialDocumentPill[];
    watermark?: ReactNode;
    footerNote?: ReactNode;
    singlePageLineLimit?: number;
    firstPageLineLimit?: number;
    continuationPageLineLimit?: number;
    lastPageLineLimit?: number;
}

const empty = (value: unknown): boolean => value === null || value === undefined || value === '';

const asLine = (...parts: Array<ReactNode | null | undefined | false>): string => (
    parts.filter((part) => !empty(part)).join(', ')
);

const toNumber = (value: unknown): number => Number(value ?? 0) || 0;

const toText = (value: ReactNode): string => {
    if (typeof value === 'string') return value;
    if (typeof value === 'number') return String(value);

    return '';
};

const isExtensoTotalLabel = (label: ReactNode): boolean => toText(label).toLowerCase().includes('total por extenso');

const DEFAULT_SINGLE_PAGE_LINE_LIMIT = 8;
const DEFAULT_FIRST_PAGE_LINE_LIMIT = 14;
const DEFAULT_CONTINUATION_PAGE_LINE_LIMIT = 22;
const DEFAULT_LAST_PAGE_LINE_LIMIT = 10;

const clampLimit = (value: number | undefined, fallback: number): number => {
    const numeric = Number(value);

    return Number.isFinite(numeric) && numeric > 0 ? Math.floor(numeric) : fallback;
};

const paginateCommercialLines = (
    lines: CommercialDocumentLine[],
    {
        singlePageLineLimit,
        firstPageLineLimit,
        continuationPageLineLimit,
        lastPageLineLimit,
    }: {
        singlePageLineLimit?: number;
        firstPageLineLimit?: number;
        continuationPageLineLimit?: number;
        lastPageLineLimit?: number;
    },
): CommercialDocumentLine[][] => {
    const normalizedLines = lines.length > 0 ? lines : [];
    const singleLimit = clampLimit(singlePageLineLimit, DEFAULT_SINGLE_PAGE_LINE_LIMIT);
    const firstLimit = clampLimit(firstPageLineLimit, DEFAULT_FIRST_PAGE_LINE_LIMIT);
    const continuationLimit = clampLimit(continuationPageLineLimit, DEFAULT_CONTINUATION_PAGE_LINE_LIMIT);
    const lastLimit = clampLimit(lastPageLineLimit, DEFAULT_LAST_PAGE_LINE_LIMIT);

    if (normalizedLines.length <= singleLimit) {
        return [normalizedLines];
    }

    const pages: CommercialDocumentLine[][] = [];
    let remaining = [...normalizedLines];

    pages.push(remaining.splice(0, Math.min(firstLimit, remaining.length)));

    if (remaining.length === 0) {
        return pages;
    }

    const middlePageCount = Math.max(0, Math.ceil((remaining.length - lastLimit) / continuationLimit));

    for (let index = 0; index < middlePageCount; index += 1) {
        const pagesAfterThis = middlePageCount - index - 1;
        const linesToLeave = lastLimit + (pagesAfterThis * continuationLimit);
        const take = Math.min(continuationLimit, Math.max(1, remaining.length - linesToLeave));
        pages.push(remaining.splice(0, take));
    }

    if (remaining.length > 0) {
        pages.push(remaining);
    }

    return pages;
};

export const isMozambiqueSettings = (settings?: Record<string, any> | null): boolean => {
    const country = String(settings?.company_country || '').toLowerCase();
    const symbol = String(settings?.currencySymbol || settings?.currency_symbol || '').toUpperCase();
    const currency = String(settings?.currency || settings?.currency_code || '').toUpperCase();

    return country.includes('mozambique') || country.includes('moçambique') || symbol === 'MT' || currency === 'MZN';
};

export const formatDocumentMoney = (
    amount: number | string,
    settings?: Record<string, any> | null,
): string => {
    const mozambique = isMozambiqueSettings(settings);
    const decimalPlaces = parseInt(String(settings?.decimalFormat ?? '2'), 10);
    const decimalSeparator = mozambique ? ',' : String(settings?.decimalSeparator || '.');
    const thousandsSeparator = mozambique ? ' ' : String(settings?.thousandsSeparator || ',');
    const rawSymbol = String(settings?.currencySymbol || settings?.currency_symbol || '');
    const symbol = mozambique && (!rawSymbol || rawSymbol === '$') ? 'MT' : (rawSymbol || '$');
    const position = mozambique ? 'after' : String(settings?.currencySymbolPosition || 'before');
    const space = mozambique || String(settings?.currencySymbolSpace || '') === '1' ? ' ' : '';
    const parts = toNumber(amount).toFixed(Number.isNaN(decimalPlaces) ? 2 : decimalPlaces).split('.');

    if (thousandsSeparator !== 'none') {
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }

    const formatted = parts.join(decimalSeparator);

    return position === 'after' ? `${formatted}${space}${symbol}` : `${symbol}${space}${formatted}`;
};

export const formatDocumentQuantity = (value: number | string): string => {
    const numeric = toNumber(value);

    return new Intl.NumberFormat('pt-MZ', {
        minimumFractionDigits: Number.isInteger(numeric) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(numeric);
};

const smallUnits = [
    'zero', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove',
    'dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezasseis', 'dezassete', 'dezoito', 'dezanove',
];
const tensUnits = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
const hundredsUnits = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

const numberBelowThousandToWords = (value: number): string => {
    if (value === 0) return '';
    if (value === 100) return 'cem';
    if (value < 20) return smallUnits[value];
    if (value < 100) {
        const ten = Math.floor(value / 10);
        const unit = value % 10;
        return unit ? `${tensUnits[ten]} e ${smallUnits[unit]}` : tensUnits[ten];
    }

    const hundred = Math.floor(value / 100);
    const rest = value % 100;
    return rest ? `${hundredsUnits[hundred]} e ${numberBelowThousandToWords(rest)}` : hundredsUnits[hundred];
};

const integerToPortugueseWords = (value: number): string => {
    if (value === 0) return 'zero';

    const groups = [
        { value: 1_000_000_000, singular: 'bilião', plural: 'biliões' },
        { value: 1_000_000, singular: 'milhão', plural: 'milhões' },
        { value: 1_000, singular: 'mil', plural: 'mil' },
    ];
    let remaining = value;
    const words: string[] = [];

    for (const group of groups) {
        const count = Math.floor(remaining / group.value);
        if (count > 0) {
            if (group.value === 1_000 && count === 1) {
                words.push('mil');
            } else {
                words.push(`${integerToPortugueseWords(count)} ${count === 1 ? group.singular : group.plural}`);
            }
            remaining %= group.value;
        }
    }

    if (remaining > 0) {
        words.push(numberBelowThousandToWords(remaining));
    }

    return words.join(remaining > 0 && value > 1000 ? ' e ' : ', ');
};

export const moneyToPortugueseWords = (amount: number | string): string => {
    const numeric = Math.abs(toNumber(amount));
    const meticais = Math.floor(numeric);
    const centavos = Math.round((numeric - meticais) * 100);
    const meticalLabel = meticais === 1 ? 'metical' : 'meticais';
    const centavoLabel = centavos === 1 ? 'centavo' : 'centavos';

    return centavos > 0
        ? `${integerToPortugueseWords(meticais)} ${meticalLabel} e ${integerToPortugueseWords(centavos)} ${centavoLabel}`
        : `${integerToPortugueseWords(meticais)} ${meticalLabel}`;
};

export const buildPartyCityLine = (party?: Record<string, any> | null): string => asLine(
    party?.company_city || party?.city,
    party?.company_state || party?.state,
    party?.company_zipcode || party?.zip_code,
);

export const buildPartyCountryLine = (party?: Record<string, any> | null): string => asLine(
    party?.company_country || party?.country,
);

export const buildStructuredDocumentNumber = ({
    prefix,
    series,
    sequence,
    number,
    date,
}: {
    prefix: string;
    series?: string | null;
    sequence?: number | string | null;
    number?: string | null;
    date?: string | Date | null;
}): string => {
    const rawNumber = String(number || '').trim();

    if (rawNumber && /[A-Z]{2,}\d{4}\/\d+/i.test(rawNumber)) {
        return rawNumber;
    }

    if (series && sequence) {
        const year = date ? new Date(date).getFullYear() : new Date().getFullYear();
        return `${series}${year}/${String(sequence).padStart(7, '0')}`;
    }

    if (rawNumber) {
        return rawNumber.startsWith(prefix) ? rawNumber : `${prefix}-${rawNumber.replace(/^#/, '')}`;
    }

    return `${prefix}-${new Date().getFullYear()}/0000000`;
};

const CompactParty = ({
    party,
    fallbackTitle,
    boxed = false,
}: {
    party: CommercialDocumentParty;
    fallbackTitle: ReactNode;
    boxed?: boolean;
}) => (
    <div className={boxed ? 'text-[10.5px] leading-[1.45] text-slate-950' : 'text-[10.5px] leading-[1.45] text-slate-950'}>
        <div className="mb-1 font-black">{party.title || fallbackTitle}</div>
        {party.name && <div className="text-[13px] font-black uppercase text-slate-950">{party.name}</div>}
        {party.legalName && party.legalName !== party.name && <div className="font-semibold">{party.legalName}</div>}
        {party.address && <div>{party.address}</div>}
        {party.cityLine && <div>{party.cityLine}</div>}
        {party.countryLine && <div>{party.countryLine}</div>}
        {party.phone && <div><span className="font-bold">Tel:</span> {party.phone}</div>}
        {party.email && <div><span className="font-bold">Email:</span> {party.email}</div>}
        {party.website && <div><span className="font-bold">Website:</span> {party.website}</div>}
        {party.registration && <div><span className="font-bold">Registo:</span> {party.registration}</div>}
        {party.taxNumber && <div><span className="font-bold">{party.taxLabel || 'NUIT'}:</span> {party.taxNumber}</div>}
        {party.extraLines?.filter(Boolean).map((line, index) => <div key={index}>{line}</div>)}
    </div>
);

const DocumentMetaGrid = ({ meta = [] }: { meta?: CommercialDocumentMeta[] }) => {
    const filtered = meta.filter((item) => !empty(item.value));

    if (filtered.length === 0) {
        return null;
    }

    return (
        <section className="border-2 border-slate-950">
            <div className="grid grid-cols-2 divide-x divide-y divide-slate-950 text-[10.5px] md:grid-cols-4">
                {filtered.map((item, index) => (
                    <div key={index} className="min-h-[35px] px-2 py-1.5">
                        <div className="font-black text-slate-950">{item.label}:</div>
                        <div className="font-semibold text-slate-950">{item.value}</div>
                    </div>
                ))}
            </div>
        </section>
    );
};

const TotalsSummary = ({ totals = [] }: { totals?: CommercialDocumentTotal[] }) => {
    const summaryRows = totals.filter((row) => !isExtensoTotalLabel(row.label));
    const extensoRow = totals.find((row) => isExtensoTotalLabel(row.label));

    if (summaryRows.length === 0 && !extensoRow) {
        return null;
    }

    return (
        <div className="border-2 border-slate-950 bg-white">
            <div className="border-b-2 border-slate-950 px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-700">
                Resumo financeiro
            </div>

            <div className="divide-y divide-slate-950/10">
                {summaryRows.map((row, index) => (
                    <div
                        key={index}
                        className={`grid grid-cols-[minmax(0,1fr)_auto] items-start gap-3 px-3 ${
                            row.emphasis ? 'bg-slate-50 py-2.5' : 'py-1.5'
                        }`}
                    >
                        <div className={`text-right text-[10.5px] leading-[1.35] ${row.emphasis ? 'font-black' : 'font-bold'}`}>
                            {row.label}:
                        </div>
                        <div className={`tabular-nums ${row.emphasis ? 'text-[13px] font-black' : 'text-[11px] font-bold'}`}>
                            {row.value}
                        </div>
                    </div>
                ))}
            </div>

            {extensoRow && (
                <div className="border-t-2 border-slate-950 px-3 py-2">
                    <div className="text-[9px] font-black uppercase tracking-[0.14em] text-slate-700">
                        {extensoRow.label}
                    </div>
                    <div className="mt-1 text-right text-[10.5px] font-semibold leading-[1.45] text-slate-950">
                        {extensoRow.value}
                    </div>
                </div>
            )}
        </div>
    );
};

export function CommercialDocumentTemplate({
    title,
    subtitle,
    documentNumber,
    documentLabel = 'Documento',
    copyLabel = 'ORIGINAL',
    issuer,
    recipient,
    meta = [],
    lines = [],
    totals = [],
    observations,
    legalNotice,
    bankDetails = [],
    validationCode,
    issuedBy,
    printedBy,
    printedAt,
    statusPills = [],
    watermark,
    footerNote = 'Processado por Índico ERP',
    singlePageLineLimit,
    firstPageLineLimit,
    continuationPageLineLimit,
    lastPageLineLimit,
}: CommercialDocumentTemplateProps) {
    const logoPath = issuer.logoPath || null;
    const pages = paginateCommercialLines(lines, {
        singlePageLineLimit,
        firstPageLineLimit,
        continuationPageLineLimit,
        lastPageLineLimit,
    });
    const totalPages = pages.length;

    const renderLinesTable = (pageLines: CommercialDocumentLine[]) => (
        <table className="commercial-lines-table w-full border-collapse text-[10px]">
            <thead>
                <tr className="border-b-2 border-slate-950 bg-white">
                    <th className="w-[12%] px-1 py-1 text-left font-black">Referência</th>
                    <th className="w-[28%] px-1 py-1 text-left font-black">Designação</th>
                    <th className="w-[6%] px-1 py-1 text-center font-black">Unid.</th>
                    <th className="w-[7%] px-1 py-1 text-right font-black">Quant.</th>
                    <th className="w-[10%] px-1 py-1 text-right font-black">Preço</th>
                    <th className="w-[8%] px-1 py-1 text-right font-black">Desc.</th>
                    <th className="w-[10%] px-1 py-1 text-right font-black">Pr. Líquido</th>
                    <th className="w-[8%] px-1 py-1 text-right font-black">IVA</th>
                    <th className="w-[11%] px-1 py-1 text-right font-black">Total</th>
                </tr>
            </thead>
            <tbody>
                {pageLines.length > 0 ? pageLines.map((line, index) => (
                    <tr key={index} className="commercial-page-break-avoid align-top">
                        <td className="px-1 py-1 font-mono text-[9px] leading-tight text-slate-800">{line.reference || '-'}</td>
                        <td className="px-1 py-1">
                            <div className="text-[9.5px] font-semibold leading-tight text-slate-950">{line.description}</div>
                        </td>
                        <td className="px-1 py-1 text-center text-[9.5px]">{line.unit || 'UN'}</td>
                        <td className="px-1 py-1 text-right tabular-nums">{line.quantity || '-'}</td>
                        <td className="px-1 py-1 text-right tabular-nums">{line.unitPrice || '-'}</td>
                        <td className="px-1 py-1 text-right tabular-nums">{line.discount || '-'}</td>
                        <td className="px-1 py-1 text-right tabular-nums">{line.netPrice || '-'}</td>
                        <td className="px-1 py-1 text-right">
                            <div className="tabular-nums">{line.tax || '0%'}</div>
                            {line.taxAmount && <div className="text-[9px] text-slate-500">{line.taxAmount}</div>}
                        </td>
                        <td className="px-1 py-1 text-right font-bold tabular-nums">{line.total || '-'}</td>
                    </tr>
                )) : (
                    <tr>
                        <td colSpan={9} className="px-3 py-12 text-center text-slate-500">Sem linhas registadas.</td>
                    </tr>
                )}
            </tbody>
        </table>
    );

    const renderFullHeader = () => (
        <>
            <header className="relative grid gap-10 md:grid-cols-[1.04fr_0.96fr]">
                <div>
                    <div className="mb-2 min-h-[46px]">
                        {logoPath ? (
                            <img
                                src={getImagePath(logoPath)}
                                alt={String(issuer.name || 'Logo')}
                                className="max-h-[46px] max-w-[220px] object-contain"
                            />
                        ) : (
                            <div className="text-2xl font-black uppercase leading-none">{issuer.name || 'Empresa'}</div>
                        )}
                    </div>
                    <CompactParty party={issuer} fallbackTitle="Emitente" />
                    <div className="mt-3 border-t-2 border-slate-950" />
                </div>

                <div>
                    {recipient && (
                        <div className="min-h-[34mm] border-2 border-slate-950 px-3 py-2.5">
                            <CompactParty party={recipient} fallbackTitle="Para:" boxed />
                        </div>
                    )}
                </div>
            </header>

            <section className="mt-3 grid items-end gap-3 md:grid-cols-[1fr_auto_1fr]">
                <div className="border-t-2 border-slate-950 pt-1.5">
                    <div className="text-[15px] font-black">{documentLabel}-{title}</div>
                    {subtitle && <div className="mt-0.5 text-[10.5px] font-semibold text-slate-700">{subtitle}</div>}
                </div>
                <div className="px-6 text-center text-[18px] font-black">Nº&nbsp;&nbsp;{documentNumber}</div>
                <div className="text-right text-[11px] font-black uppercase">{copyLabel}</div>
            </section>

            {statusPills.length > 0 && (
                <section className="mt-2 flex flex-wrap gap-x-3 gap-y-1 border border-slate-950 px-2 py-1 text-[9.5px] font-semibold">
                    {statusPills.map((pill, index) => (
                        <span key={index}>
                            <span className="font-black">Estado:</span> {pill.label}
                        </span>
                    ))}
                </section>
            )}

            <div className="mt-5">
                <DocumentMetaGrid meta={meta} />
            </div>
        </>
    );

    const renderContinuationHeader = () => (
        <header className="commercial-page-break-avoid mb-3 grid items-start gap-4 border-b-2 border-slate-950 pb-2 md:grid-cols-[1fr_auto]">
            <div>
                <div className="text-[13px] font-black uppercase leading-tight">{issuer.name || issuer.legalName || 'Empresa'}</div>
                <div className="mt-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-slate-600">Continuação</div>
            </div>
            <div className="text-right">
                <div className="text-[12px] font-black">{documentLabel}-{title}</div>
                <div className="text-[15px] font-black">Nº {documentNumber}</div>
                <div className="text-[9px] font-black uppercase text-slate-700">{copyLabel}</div>
            </div>
        </header>
    );

    const renderFinalSection = () => (
        <section className="commercial-page-break-avoid mt-3 grid gap-4 md:grid-cols-[1fr_330px]">
            <div className="space-y-4">
                <div className="min-h-[58px] border-2 border-slate-950 px-3 py-2 text-[10px] leading-[1.35]">
                    <div className="mb-1 font-black">Observações / Condições</div>
                    <div className="whitespace-pre-line text-slate-800">{observations || 'Sem observações adicionais.'}</div>
                    {legalNotice && <div className="mt-2 border border-slate-950 px-2 py-1 font-semibold text-slate-950">{legalNotice}</div>}
                </div>

                {bankDetails.length > 0 && (
                    <div className="border border-slate-950 px-3 py-2 text-[9.5px]">
                        <div className="mb-1 font-black">Dados bancários</div>
                        <div className="grid grid-cols-2 gap-2">
                            {bankDetails.filter((item) => !empty(item.value)).map((item, index) => (
                                <div key={index}>
                                    <span className="font-bold">{item.label}:</span> {item.value}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <TotalsSummary totals={totals} />
        </section>
    );

    const renderFooter = (pageIndex: number) => (
        <footer className="commercial-page-break-avoid mt-4 border-t-2 border-slate-950 pt-1.5 text-[8.5px] leading-4 text-slate-700">
            <div className="grid gap-2 md:grid-cols-[1fr_auto]">
                <div>
                    <span className="font-bold">{footerNote}</span>
                    {issuedBy && <> | Emitido por: {issuedBy}</>}
                    {printedBy && <> | Impresso por: {printedBy}</>}
                    {printedAt && <> | Impresso em: {printedAt}</>}
                    {validationCode && <> | Código de validação: <span className="font-mono">{validationCode}</span></>}
                </div>
                <div className="font-bold">Página {pageIndex + 1} de {totalPages}</div>
            </div>
        </footer>
    );

    return (
        <div className={`${COMMERCIAL_DOCUMENT_CONTAINER_CLASS} bg-white font-sans text-slate-950`}>
            <style>{`
                @page {
                    size: A4;
                    margin: 0;
                }

                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    background: #f3f4f6;
                }

                .commercial-document-pdf-root,
                .commercial-document-pdf-root * {
                    box-sizing: border-box;
                }

                .commercial-document-pdf-root {
                    width: 210mm;
                    margin: 0 auto;
                    background: #fff;
                }

                .commercial-document-page {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    width: 210mm;
                    height: 296.5mm;
                    min-height: 296.5mm;
                    margin: 0 auto;
                    overflow: hidden;
                    padding: 8mm;
                    background: #fff;
                    box-shadow: none;
                    page-break-after: always;
                    break-after: page;
                }

                .commercial-document-page:last-child {
                    page-break-after: auto;
                    break-after: auto;
                }

                .commercial-lines-frame {
                    flex: 1 1 auto;
                    min-height: 0;
                    overflow: hidden;
                }

                .commercial-lines-table {
                    table-layout: fixed;
                }

                .commercial-lines-table th,
                .commercial-lines-table td {
                    border-right: 1px dotted #475569;
                }

                .commercial-lines-table th:last-child,
                .commercial-lines-table td:last-child {
                    border-right: 0;
                }

                .commercial-page-break-avoid {
                    page-break-inside: avoid;
                    break-inside: avoid;
                }

                @media print {
                    body {
                        background: #fff;
                    }

                    .commercial-document-pdf-root {
                        margin: 0;
                    }

                    .commercial-document-page {
                        margin: 0;
                        box-shadow: none;
                    }
                }
            `}</style>

            {pages.map((pageLines, pageIndex) => {
                const isFirstPage = pageIndex === 0;
                const isLastPage = pageIndex === totalPages - 1;

                return (
                    <section key={pageIndex} className="commercial-document-page">
                        {watermark && (
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center text-8xl font-black uppercase tracking-[0.3em] text-slate-200/35">
                                {watermark}
                            </div>
                        )}

                        <div className="relative flex min-h-0 flex-1 flex-col">
                            {isFirstPage ? renderFullHeader() : renderContinuationHeader()}

                            <section className="commercial-lines-frame mt-4 border-2 border-slate-950">
                                {renderLinesTable(pageLines)}
                            </section>

                            {!isLastPage && (
                                <div className="commercial-page-break-avoid mt-2 text-right text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                    Continua na página seguinte
                                </div>
                            )}

                            {isLastPage && renderFinalSection()}

                            {renderFooter(pageIndex)}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
