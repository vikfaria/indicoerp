import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Calculator } from 'lucide-react';
import { Payroll } from './types';

interface ViewProps {
    payroll: Payroll;
}

export default function View({ payroll }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Calculator className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Payroll Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{payroll.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-4">
                <div className="text-center py-8">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Hello World</h3>
                    <p className="text-gray-500">View functionality is ready for customization</p>
                </div>
            </div>
        </DialogContent>
    );
}