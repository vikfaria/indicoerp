import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DatePicker } from "@/components/ui/date-picker";
import InputError from "@/components/ui/input-error";

import { CreateTransferProps, CreateTransferFormData, TransfersIndexProps } from './types';

export default function Create({ onSuccess }: CreateTransferProps) {
    const { warehouses, products, warehouseStocks } = usePage<TransfersIndexProps>().props;
    
    const { data, setData, post, processing, errors } = useForm<CreateTransferFormData>({
        from_warehouse: '',
        to_warehouse: '',
        product_id: '',
        quantity: '',
        date: new Date().toISOString().split('T')[0],
        carrier_name: '',
        vehicle_plate: '',
        driver_name: '',
    });

    // Filter warehouses for "to" dropdown (exclude selected "from" warehouse)
    const availableToWarehouses = warehouses.filter(w => w.id.toString() !== data.from_warehouse);
    
    // Filter products based on selected warehouse and show available quantity
    const availableProducts = warehouseStocks
        ?.filter(stock => stock.warehouse_id.toString() === data.from_warehouse && Number(stock.quantity) > 0)
        ?.map(stock => ({
            ...stock.product,
            available_quantity: stock.quantity
        })) || [];

    const handleFromWarehouseChange = (value: string) => {
        setData({
            ...data,
            from_warehouse: value,
            to_warehouse: data.to_warehouse === value ? '' : data.to_warehouse,
            product_id: '',
            quantity: ''
        });
    };

    const selectedProduct = availableProducts.find(p => p.id.toString() === data.product_id);
    const maxQuantity = selectedProduct?.available_quantity || 0;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('transfers.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Criar transferência</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="from_warehouse">Armazém de origem</Label>
                    <Select value={data.from_warehouse} onValueChange={handleFromWarehouseChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Selecionar armazém" />
                        </SelectTrigger>
                        <SelectContent>
                            {warehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.from_warehouse} />
                </div>

                <div>
                    <Label htmlFor="to_warehouse">Armazém de destino</Label>
                    <Select value={data.to_warehouse} onValueChange={(value) => setData('to_warehouse', value)} disabled={!data.from_warehouse}>
                        <SelectTrigger>
                            <SelectValue placeholder={data.from_warehouse ? 'Selecionar armazém' : 'Selecione primeiro o armazém de origem'} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableToWarehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_warehouse} />
                </div>

                <div>
                    <Label htmlFor="product_id">Produto</Label>
                    <Select value={data.product_id} onValueChange={(value) => setData('product_id', value)} disabled={!data.from_warehouse}>
                        <SelectTrigger>
                            <SelectValue placeholder={data.from_warehouse ? 'Selecionar produto' : 'Selecione primeiro o armazém de origem'} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableProducts.map((product) => (
                                <SelectItem key={product.id} value={product.id.toString()}>
                                    {product.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.product_id} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="quantity">Quantidade</Label>
                        <Input
                            id="quantity"
                            type="number"
                            step="1"
                            min="1"
                            max={maxQuantity}
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            placeholder={selectedProduct ? `Disponível: ${maxQuantity}` : 'Selecione primeiro o produto'}
                            disabled={!data.product_id}
                            required
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div>
                        <Label>Data</Label>
                        <DatePicker
                            value={data.date}
                            onChange={(value) => setData('date', value)}
                            placeholder="Selecionar data da transferência"
                        />
                        <InputError message={errors.date} />
                    </div>
                </div>

                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4">
                    <div className="mb-3">
                        <div className="text-sm font-semibold text-slate-900">Dados de transporte</div>
                        <div className="mt-1 text-xs text-slate-500">
                            Preencha apenas se pretender emitir uma Guia de Transporte completa.
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="carrier_name">Transportador</Label>
                            <Input
                                id="carrier_name"
                                value={data.carrier_name}
                                onChange={(e) => setData('carrier_name', e.target.value)}
                                placeholder="Ex: Swift Logistics"
                            />
                            <InputError message={errors.carrier_name} />
                        </div>
                        <div>
                            <Label htmlFor="vehicle_plate">Matrícula</Label>
                            <Input
                                id="vehicle_plate"
                                value={data.vehicle_plate}
                                onChange={(e) => setData('vehicle_plate', e.target.value)}
                                placeholder="Ex: ABC-123-MP"
                            />
                            <InputError message={errors.vehicle_plate} />
                        </div>
                        <div>
                            <Label htmlFor="driver_name">Motorista</Label>
                            <Input
                                id="driver_name"
                                value={data.driver_name}
                                onChange={(e) => setData('driver_name', e.target.value)}
                                placeholder="Ex: João Manjate"
                            />
                            <InputError message={errors.driver_name} />
                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        Cancelar
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'A criar...' : 'Criar'}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
