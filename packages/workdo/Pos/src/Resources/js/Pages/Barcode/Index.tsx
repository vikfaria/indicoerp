import { useState, useEffect, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, QrCode, Package, Search } from 'lucide-react';
import { formatCurrency } from '@/utils/helpers';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { DataTable } from "@/components/ui/data-table";
import NoRecordsFound from '@/components/no-records-found';
import JsBarcode from 'jsbarcode';

interface Warehouse {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    price: number;
}

interface IndexProps {
    warehouses: Warehouse[];
}

export default function Index() {
    const { t } = useTranslation();
    const { warehouses } = usePage<IndexProps>().props;

    const [selectedWarehouse, setSelectedWarehouse] = useState('');
    const [products, setProducts] = useState<Product[]>([]);
    const [selectedProducts, setSelectedProducts] = useState<number[]>([]);
    const barcodeField = 'sku';
    const [productCopies, setProductCopies] = useState<{ [key: number]: number }>({});
    const [searchTerm, setSearchTerm] = useState('');
    const [barcodeDataUrls, setBarcodeDataUrls] = useState<{ [key: number]: string }>({});

    useEffect(() => {
        if (warehouses.length > 0 && !selectedWarehouse) {
            setSelectedWarehouse(warehouses[0].id.toString());
        }
    }, [warehouses]);

    useEffect(() => {
        if (selectedWarehouse) {
            fetch(`${route('pos.products')}?warehouse_id=${selectedWarehouse}`)
                .then(response => response.json())
                .then(data => {
                    setProducts(data);
                    setTimeout(() => generateBarcodes(data), 100);
                })
                .catch(error => console.error('Error:', error));
        }
    }, [selectedWarehouse]);

    const generateBarcodes = useCallback((productList: Product[]) => {
        const newBarcodeUrls: { [key: number]: string } = {};

        productList.forEach(product => {
            try {
                if (product.sku) {
                    const canvas = document.createElement('canvas');
                    canvas.width = 400;
                    canvas.height = 150;
                    JsBarcode(canvas, product.sku, {
                        format: "CODE128",
                        width: 4,
                        height: 80,
                        displayValue: false,
                        margin: 10,
                        background: "#ffffff",
                        lineColor: "#000000"
                    });
                    newBarcodeUrls[product.id] = canvas.toDataURL('image/png', 1.0);
                }
            } catch (error) {
                console.error('Barcode generation failed:', error);
            }
        });

        setBarcodeDataUrls(newBarcodeUrls);
    }, []);

    useEffect(() => {
        if (products.length > 0) {
            generateBarcodes(products);
        }
    }, [products, generateBarcodes]);

    const handleProductSelect = (productId: number, checked: boolean) => {
        if (checked) {
            setSelectedProducts([...selectedProducts, productId]);
            setProductCopies({ ...productCopies, [productId]: 1 });
        } else {
            setSelectedProducts(selectedProducts.filter(id => id !== productId));
            const newCopies = { ...productCopies };
            delete newCopies[productId];
            setProductCopies(newCopies);
        }
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            const productIds = filteredProducts.map(p => p.id);
            setSelectedProducts(productIds);
            const newCopies: { [key: number]: number } = {};
            productIds.forEach(id => newCopies[id] = 1);
            setProductCopies(newCopies);
        } else {
            setSelectedProducts([]);
            setProductCopies({});
        }
    };



    const handleDownloadBarcodes = () => {
        if (selectedProducts.length === 0) return;

        const selectedProductsData = products.filter(p => selectedProducts.includes(p.id));
        const params = new URLSearchParams({
            products: JSON.stringify(selectedProductsData),
            copies: JSON.stringify(productCopies),
            field: barcodeField
        });

        const printUrl = route('pos.barcode.print', 'bulk') + '?' + params.toString() + '&download=pdf';
        window.open(printUrl, '_blank');
    };

    const filteredProducts = products.filter(product =>
        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.sku.toLowerCase().includes(searchTerm.toLowerCase())
    );

    useEffect(() => {
        if (filteredProducts.length > 0) {
            generateBarcodes(filteredProducts);
        }
    }, [filteredProducts, generateBarcodes]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('POS'), url: route('pos.index') },
                { label: t('Product Barcode') }
            ]}
            pageTitle={t('Manage Product Barcode')}
            pageActions={null}
        >
            <Head title={t('Product Barcode')} />

            <div className="space-y-6">
                {/* Header Section */}
                <Card className="bg-gradient-to-r from-gray-50 to-gray-100 border-gray-200">
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-3 text-xl">
                            <div className="p-2 bg-gray-100 rounded-lg">
                                <QrCode className="h-6 w-6 text-gray-600" />
                            </div>
                            {t('Product Barcode Generator')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <Label className="text-sm font-medium text-gray-700 mb-2 block">{t('Warehouse')}</Label>
                                <Select value={selectedWarehouse} onValueChange={setSelectedWarehouse}>
                                    <SelectTrigger className="bg-white border-gray-300">
                                        <SelectValue placeholder={t('Select Warehouse')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {warehouses.map(warehouse => (
                                            <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                                {warehouse.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-gray-700 mb-2 block">{t('Search Products')}</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder={t('Search by name or SKU...')}
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-10 bg-white border-gray-300"
                                    />
                                </div>
                            </div>
                            <div>
                                {selectedProducts.length > 0 && (
                                    <Button
                                        onClick={handleDownloadBarcodes}
                                        variant="outline"
                                        size="sm"
                                        className="bg-white border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400"
                                    >
                                        <Download className="h-4 w-4 mr-2" />
                                        {t('Download PDF')} ({selectedProducts.length})
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Products Section */}
                <Card>
                    <CardHeader className="border-b bg-gray-50">
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <Package className="h-5 w-5 text-gray-600" />
                                {t('Available Products')}
                                <Badge variant="secondary" className="ml-2">{filteredProducts.length}</Badge>
                            </CardTitle>
                            {selectedProducts.length > 0 && (
                                <Badge variant="default" className="bg-blue-600">
                                    {selectedProducts.length} {t('Selected')}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">

                        {selectedWarehouse ? (
                            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                                <div className="min-w-[800px]">
                                    {filteredProducts.length > 0 ? (
                                        <DataTable
                                            data={filteredProducts}
                                            columns={[
                                                {
                                                    key: 'select',
                                                    header: (
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedProducts.length === filteredProducts.length && filteredProducts.length > 0}
                                                            onChange={(e) => handleSelectAll(e.target.checked)}
                                                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                        />
                                                    ),
                                                    render: (_: any, product: Product) => (
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedProducts.includes(product.id)}
                                                            onChange={() => handleProductSelect(product.id, !selectedProducts.includes(product.id))}
                                                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                        />
                                                    )
                                                },
                                                {
                                                    key: 'name',
                                                    header: t('Product Name')
                                                },
                                                {
                                                    key: 'sku',
                                                    header: t('SKU')
                                                },
                                                {
                                                    key: 'price',
                                                    header: t('Price'),
                                                    render: (value: number) => (
                                                        <span className="font-semibold text-green-600">
                                                            {formatCurrency(value || 0)}
                                                        </span>
                                                    )
                                                },
                                                {
                                                    key: 'barcode',
                                                    header: t('Barcode'),
                                                    render: (_: any, product: Product) => (
                                                        barcodeDataUrls[product.id] ? (
                                                            <img
                                                                src={barcodeDataUrls[product.id]}
                                                                alt="Barcode"
                                                                className="h-20 w-48 object-contain"
                                                                style={{ imageRendering: 'crisp-edges' }}
                                                            />
                                                        ) : (
                                                            <div className="h-16 w-32 flex items-center justify-center bg-gray-100 text-gray-500 text-xs">
                                                                {t('Generating...')}
                                                            </div>
                                                        )
                                                    )
                                                },
                                                {
                                                    key: 'copies',
                                                    header: t('Copies'),
                                                    render: (_: any, product: Product) => (
                                                        <div className="flex justify-center">
                                                            {selectedProducts.includes(product.id) ? (
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    max="50"
                                                                    value={productCopies[product.id] || 1}
                                                                    onChange={(e) => {
                                                                        setProductCopies({ ...productCopies, [product.id]: Number(e.target.value) || 1 });
                                                                    }}
                                                                    className="w-16 h-8 text-center"
                                                                />
                                                            ) : (
                                                                <span className="text-gray-400">-</span>
                                                            )}
                                                        </div>
                                                    )
                                                }
                                            ]}
                                            className="rounded-none"
                                            emptyState={
                                                <NoRecordsFound
                                                    icon={Package}
                                                    title={t('No products found')}
                                                    description={t('Try adjusting your search terms or add products to this warehouse.')}
                                                    hasFilters={!!searchTerm}
                                                    onClearFilters={() => setSearchTerm('')}
                                                    className="h-auto py-8"
                                                />
                                            }
                                        />
                                    ) : (
                                        <NoRecordsFound
                                            icon={Package}
                                            title={searchTerm ? t('No products found') : t('No products available')}
                                            description={searchTerm ? t('Try adjusting your search terms') : t('Add products to this warehouse to generate barcodes')}
                                            hasFilters={!!searchTerm}
                                            onClearFilters={() => setSearchTerm('')}
                                            className="h-auto py-8"
                                        />
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <div className="bg-blue-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6">
                                    <QrCode className="h-10 w-10 text-blue-600" />
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-2">{t('Select Warehouse')}</h3>
                                <p className="text-gray-500 max-w-md mx-auto">
                                    {t('Choose a warehouse from the dropdown above to view available products and generate barcodes')}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}