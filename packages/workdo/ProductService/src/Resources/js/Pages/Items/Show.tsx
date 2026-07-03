import { Head, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { usePageButtons } from '@/hooks/usePageButtons';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Package, Image } from "lucide-react";
import { formatCurrency, formatDate, getImagePath } from "@/utils/helpers";
import { ImageSlider } from "@/components/ui/image-slider";
import { Item, StockSummary, WarehouseStockSummary } from './types';

interface ShowItemPageProps {
    item: Item & {
        description?: string;
        sale_price?: number;
        purchase_price?: number;
        quantity?: number;
        total_quantity?: number;
        type: string;
        image?: string;
        images?: string[] | string;
        gallery?: string[];
        additional_images?: string[];
        warehouse_stocks?: WarehouseStockSummary[];
        stock_summary?: StockSummary;
        category?: {
            id: number;
            name: string;
        };
        unit_relation?: {
            id: number;
            unit_name: string;
        };
        taxes?: Array<{
            id: number;
            tax_name: string;
            rate: number;
        }>;
        created_at: string;
    };
}

const formatStockQuantity = (value?: number | null) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat('pt-MZ', {
        minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const getStockBadgeVariant = (status?: string) => {
    switch (status) {
        case 'empty':
            return 'destructive' as const;
        case 'distributed':
            return 'secondary' as const;
        default:
            return 'default' as const;
    }
};

export default function Show() {
    const { t } = useTranslation();
    const { item } = usePage<ShowItemPageProps>().props;
    const videoHubButtons = usePageButtons('itemShowButtons', { item });
    let imageUrl = getImagePath(item.image);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Items'), url: route('product-service.items.index')},
                {label: t('Item Details')}
            ]}
            pageTitle={t('Item Details')}
            backUrl={route('product-service.items.index')}
        >
            <Head title={t('Item Details')} />

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            {item.name}
                        </div>
                        {videoHubButtons && videoHubButtons.length > 0 && (
                            <div className="flex items-center gap-2">
                                {videoHubButtons.map((button, index) => (
                                    <div key={button.id || index}>{button.component}</div>
                                ))}
                            </div>
                        )}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2 space-y-6">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Basic Information')}</h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">

                                    {item.sku && (
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <label className="text-lg font-semibold text-gray-800 mb-3">{t('SKU')}</label>
                                            <p className="text-gray-700 leading-relaxed">{item.sku}</p>
                                        </div>
                                    )}
                                     <div className="bg-gray-50 p-4 rounded-lg">
                                        <label className="text-lg font-semibold text-gray-800 mb-3">{t('Category')}</label>
                                        <p className="text-gray-700 leading-relaxed">{item.category?.name || '-'}</p>
                                    </div>
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <label className="text-lg font-semibold text-gray-800 mb-3">{t('Type')}</label>
                                        <p className="text-gray-700 leading-relaxed">{item.type || '-'}</p>
                                    </div>

                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Pricing & Inventory')}</h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {item.sale_price && (
                                        <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                                            <label className="text-sm font-medium text-green-700">{t('Sale Price')}</label>
                                            <p className="text-xl font-bold text-green-800 mt-1">{formatCurrency(item.sale_price)}</p>
                                        </div>
                                    )}
                                    {item.purchase_price && (
                                        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                            <label className="text-sm font-medium text-blue-700">{t('Purchase Price')}</label>
                                        <p className="text-xl font-bold text-blue-800 mt-1">{formatCurrency(item.purchase_price)}</p>
                                        </div>
                                    )}
                                    <div className="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                        <label className="text-sm font-medium text-orange-700">{t('Total Quantity')}</label>
                                        <p className="text-xl font-bold text-orange-800 mt-1">{formatStockQuantity(item.total_quantity)}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Warehouse Stock')}</h3>
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div className="bg-slate-50 p-4 rounded-lg border">
                                        <label className="text-sm font-medium text-slate-700">{t('Stock status')}</label>
                                        <div className="mt-2">
                                            <Badge variant={getStockBadgeVariant(item.stock_summary?.status ?? (item.total_quantity && item.total_quantity > 0 ? 'available' : 'empty'))}>
                                                {item.stock_summary?.status_label ?? (item.total_quantity && item.total_quantity > 0 ? t('Available') : t('Out of stock'))}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="bg-slate-50 p-4 rounded-lg border">
                                        <label className="text-sm font-medium text-slate-700">{t('Warehouses with stock')}</label>
                                        <p className="text-xl font-bold text-slate-900 mt-1">
                                            {item.stock_summary?.active_warehouse_count ?? item.active_warehouse_count ?? 0}/{item.stock_summary?.warehouse_count ?? item.warehouse_stock_count ?? 0}
                                        </p>
                                    </div>
                                    <div className="bg-slate-50 p-4 rounded-lg border">
                                        <label className="text-sm font-medium text-slate-700">{t('Last updated')}</label>
                                        <p className="text-sm font-semibold text-slate-900 mt-2">
                                            {item.stock_summary?.last_updated_at ? formatDate(item.stock_summary.last_updated_at) : '-'}
                                        </p>
                                    </div>
                                    <div className="bg-slate-50 p-4 rounded-lg border">
                                        <label className="text-sm font-medium text-slate-700">{t('Total Quantity')}</label>
                                        <p className="text-xl font-bold text-slate-900 mt-1">{formatStockQuantity(item.total_quantity)}</p>
                                    </div>
                                </div>

                                <div className="mt-4 overflow-hidden rounded-lg border bg-white">
                                    {item.warehouse_stocks && item.warehouse_stocks.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">{t('Warehouse')}</th>
                                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">{t('Quantity')}</th>
                                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">{t('Share')}</th>
                                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">{t('Status')}</th>
                                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">{t('Updated')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100 bg-white">
                                                    {item.warehouse_stocks.map((stock, index) => (
                                                        <tr key={`${stock.warehouse_id}-${index}`}>
                                                            <td className="px-4 py-3">
                                                                <div className="font-medium text-gray-900">{stock.warehouse_name}</div>
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-semibold text-gray-900">
                                                                {formatStockQuantity(stock.quantity)}
                                                            </td>
                                                            <td className="px-4 py-3 text-right text-sm text-gray-600">
                                                                {Number(stock.share_percent || 0).toFixed(1)}%
                                                            </td>
                                                            <td className="px-4 py-3 text-right">
                                                                <Badge variant={getStockBadgeVariant(stock.status)}>
                                                                    {stock.status_label}
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-3 text-right text-sm text-gray-600">
                                                                {stock.updated_at ? formatDate(stock.updated_at) : '-'}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="p-6 text-center text-gray-500">
                                            {t('No warehouse stock registered yet.')}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Additional Details')}</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <label className="text-lg font-semibold text-gray-800 mb-3">{t('Unit')}</label>
                                        <p className="text-gray-700 leading-relaxed">{item.unit_relation?.unit_name || '-'}</p>
                                    </div>
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <label className="text-lg font-semibold text-gray-800 mb-3">{t('Taxes')}</label>
                                        <div className="mt-2">
                                            {item.taxes && item.taxes.length > 0 ? (
                                                <div className="flex flex-wrap gap-2">
                                                    {item.taxes.map((tax) => (
                                                        <Badge key={tax.id} variant="outline" className="text-sm">
                                                            {tax.tax_name} ({tax.rate}%)
                                                        </Badge>
                                                    ))}
                                                </div>
                                            ) : (
                                                <p className="text-lg text-gray-900">-</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                {item.description && (
                                    <div className="bg-gray-50 p-4 rounded-lg mt-4">
                                        <label className="text-lg font-semibold text-gray-800 mb-3">{t('Description')}</label>
                                        <p className="text-gray-700 leading-relaxed">{item.description}</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="lg:col-span-1 space-y-6">
                            {/* Main Image */}
                            <div className="bg-white border rounded-lg p-6 shadow-sm">
                                <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Product Image')}</h3>
                                {item.image ? (
                                    <img
                                        src={imageUrl}
                                        alt={item.name}
                                        className="w-full h-48 object-cover rounded-lg border shadow-md cursor-pointer"
                                        onClick={() => window.open(imageUrl, '_blank')}
                                    />
                                ) : (
                                    <div className="w-full h-48 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                        <div className="text-center">
                                            <Image className="h-16 w-16 text-gray-400 mx-auto mb-2" />
                                            <p className="text-gray-500 text-sm">{t('No Image Available')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Additional Images */}
                            {(() => {
                                const additionalImages = item.images ?
                                    (typeof item.images === 'string' ? JSON.parse(item.images) : item.images).filter(Boolean) : [];
                                const fullPathImages = additionalImages.map(img => getImagePath(img));

                                return additionalImages.length > 0 && (
                                    <div className="bg-white border rounded-lg p-6 shadow-sm">
                                        <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Additional Images')}</h3>
                                        <ImageSlider
                                            images={fullPathImages}
                                            className="w-full"
                                            aspectRatio="square"
                                            showZoom={true}
                                            showDownload={true}
                                            autoPlay={additionalImages.length > 1}
                                            autoPlayInterval={5000}
                                            onImageClick={(index) => {
                                                window.open(fullPathImages[index], '_blank');
                                            }}
                                        />
                                    </div>
                                );
                            })()}
                        </div>
                    </div>


                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
