import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { useState } from 'react';


interface AllowanceType {
    id: number;
    name: string;
}

interface Allowance {
    id: number;
    allowance_type_id: number;
    type: string;
    amount: number;
}

interface EditAllowanceProps {
    allowance: Allowance;
    allowanceTypes: AllowanceType[];
    onSuccess: () => void;
}

export default function Edit({ allowance, allowanceTypes, onSuccess }: EditAllowanceProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm({
        allowance_type_id: allowance.allowance_type_id.toString(),
        type: allowance.type,
        amount: allowance.amount.toString()
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.allowances.update', allowance.id), {
            only: ['errors', 'flash', 'allowances'],
            onSuccess: () => {
                onSuccess();
            },
            onError: () => {
                // Keep modal open on validation errors
            }
        });
    };

    const allowanceTypeOptions = allowanceTypes?.map(type => ({
        value: type.id.toString(),
        label: type.name
    })) || [];

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Allowance')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="edit_allowance_type_id" required>{t('Allowance Type')}</Label>
                    <Select value={data.allowance_type_id} onValueChange={(value) => setData('allowance_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select allowance type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {allowanceTypeOptions.map(option => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.allowance_type_id} />
                </div>

                <div>
                    <Label htmlFor="edit_type" required>{t('Type')}</Label>
                    <Select value={data.type} onValueChange={(value) => setData('type', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select type')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="fixed">{t('Fixed Amount')}</SelectItem>
                            <SelectItem value="percentage">{t('Percentage')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                <div>
                    <Label htmlFor="edit_amount" required>{t('Amount')}</Label>
                    <Input
                        id="edit_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.amount}
                        onChange={(e) => setData('amount', e.target.value)}
                        placeholder={data.type === 'percentage' ? t('Enter percentage') : t('Enter amount')}
                        required
                    />
                    <InputError message={errors.amount} />
                </div>

                <div className="flex justify-end gap-2">
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