import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";

interface PerformanceIndicator {
    id: number;
    name: string;
    description: string;
    measurement_unit: string;
    target_value: string;
    status: string;
    category_id: number;
}

interface IndicatorCategory {
    id: number;
    name: string;
}

interface EditProps {
    indicator: PerformanceIndicator;
    onSuccess: () => void;
    categories: IndicatorCategory[];
}

interface FormData {
    category_id: string;
    name: string;
    description: string;
    measurement_unit: string;
    target_value: string;
    status: string;
}

export default function Edit({ indicator, onSuccess, categories }: EditProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<FormData>({
        category_id: indicator.category_id?.toString() || '',
        name: indicator.name,
        description: indicator.description || '',
        measurement_unit: indicator.measurement_unit,
        target_value: indicator.target_value || '',
        status: indicator.status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('performance.indicators.update', indicator.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Performance Indicator')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="name" required>{t('Name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('Enter indicator name')}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    
                    <div>
                        <Label htmlFor="category_id" required>{t('Category')}</Label>
                        <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select category')} />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={category.id.toString()}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category_id} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter indicator description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="measurement_unit" required>{t('Measurement Unit')}</Label>
                        <Input
                            id="measurement_unit"
                            value={data.measurement_unit}
                            onChange={(e) => setData('measurement_unit', e.target.value)}
                            placeholder={t('e.g., %, hours, count')}
                            required
                        />
                        <InputError message={errors.measurement_unit} />
                    </div>
                    
                    <div>
                        <Label htmlFor="target_value">{t('Target Value')}</Label>
                        <Input
                            id="target_value"
                            value={data.target_value}
                            onChange={(e) => setData('target_value', e.target.value)}
                            placeholder={t('e.g., 4/5, 4.5/5, <24, 90%')}
                        />
                        <InputError message={errors.target_value} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">{t('Active')}</SelectItem>
                            <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                
                <div className="flex justify-end gap-2 pt-4">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}