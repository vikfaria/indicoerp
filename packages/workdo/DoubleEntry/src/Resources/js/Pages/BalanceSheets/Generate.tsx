import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DatePicker } from '@/components/ui/date-picker';

interface GenerateProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function Generate({ open, onOpenChange }: GenerateProps) {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const currentDate = new Date().toISOString().split('T')[0];

    const { data, setData, post, processing, errors, reset } = useForm({
        balance_sheet_date: currentDate,
        financial_year: currentYear.toString(),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('double-entry.balance-sheets.store'), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
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
                    <DialogTitle>{t('Generate Balance Sheet')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="balance_sheet_date">
                            {t('Balance Sheet Date')}
                        </Label>
                        <DatePicker
                            id="balance_sheet_date"
                            value={data.balance_sheet_date}
                            onChange={(value) => setData('balance_sheet_date', value)}
                            className={errors.balance_sheet_date ? 'border-red-500' : ''}
                            required
                        />
                        {errors.balance_sheet_date && (
                            <p className="text-sm text-red-600">{errors.balance_sheet_date}</p>
                        )}
                        <p className="text-xs text-gray-500">
                            {t('Select the date for which you want to generate the balance sheet')}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="financial_year">
                            {t('Financial Year')}
                        </Label>
                        <Input
                            id="financial_year"
                            type="text"
                            value={data.financial_year}
                            onChange={(e) => setData('financial_year', e.target.value)}
                            className={errors.financial_year ? 'border-red-500' : ''}
                            placeholder={t('e.g., 2024')}
                            maxLength={4}
                            required
                        />
                        {errors.financial_year && (
                            <p className="text-sm text-red-600">{errors.financial_year}</p>
                        )}
                        <p className="text-xs text-gray-500">
                            {t('Enter the financial year (e.g., 2024)')}
                        </p>
                    </div>

                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 className="font-medium text-blue-900 mb-2">{t('How it works')}</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• {t('System will calculate balances for all accounts up to the selected date')}</li>
                            <li>• {t('Accounts will be automatically categorized into Assets, Liabilities, and Equity')}</li>
                            <li>• {t('Balance sheet will be validated to ensure Assets = Liabilities + Equity')}</li>
                            <li>• {t('You can review and finalize the balance sheet after generation')}</li>
                        </ul>
                    </div>

                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? t('Generating...') : t('Generate')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
