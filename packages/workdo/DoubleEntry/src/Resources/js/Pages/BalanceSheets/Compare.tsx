import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDate } from '@/utils/helpers';

interface CompareProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    balanceSheetId: number;
    otherBalanceSheets: any[];
}

export default function Compare({ open, onOpenChange, balanceSheetId, otherBalanceSheets }: CompareProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        current_period_id: balanceSheetId,
        previous_period_id: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.previous_period_id) {
            return;
        }
        post(route('double-entry.balance-sheets.compare'), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
            onError: (errors) => {
                console.error('Comparison error:', errors);
            }
        });
    };

    const handleClose = () => {
        reset();
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('Compare Balance Sheets')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="previous_period">{t('Compare with')}</Label>
                        <Select value={data.previous_period_id} onValueChange={(value) => setData('previous_period_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select balance sheet to compare')} />
                            </SelectTrigger>
                            <SelectContent>
                                {otherBalanceSheets?.map((bs: any) => (
                                    <SelectItem key={bs.id} value={bs.id.toString()}>
                                        {formatDate(bs.balance_sheet_date)} - {bs.financial_year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.previous_period_id && <p className="text-red-500 text-sm">{errors.previous_period_id}</p>}
                    </div>
                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing || !data.previous_period_id}>
                            {processing ? t('Comparing...') : t('Compare')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
