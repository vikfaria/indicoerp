import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { useTranslation } from 'react-i18next';
import { HelpCircle } from 'lucide-react';
import { CustomQuestion } from './types';

interface ViewProps {
    customquestion: CustomQuestion;
}

export default function View({ customquestion }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <HelpCircle className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Custom Question Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{customquestion.name}</p>
                    </div>
                </div>
            </DialogHeader>

            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-700">{t('Question')}</label>
                        <p className="mt-1 text-sm text-gray-900">{customquestion.question || '-'}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700">{t('Type')}</label>
                        <div className="mt-1">
                            <span className="px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                {(() => {
                                    const options: any = { "0": "Text", "1": "Textarea", "2": "Select", "3": "Radio", "4": "Checkbox", "5": "File" };
                                    const displayValue = options[customquestion.type] || customquestion.type || '-';
                                    const capitalizedValue = displayValue === '-' ? displayValue : displayValue.charAt(0).toUpperCase() + displayValue.slice(1).toLowerCase();
                                    return t(capitalizedValue);
                                })()}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">{t('Sort Order')}</label>
                        <p className="mt-1 text-sm text-gray-900">{customquestion.sort_order || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">{t('Required')}</label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${customquestion.is_required ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                }`}>
                                {customquestion.is_required ? t('Required') : t('Optional')}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700">{t('Status')}</label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${customquestion.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                }`}>
                                {customquestion.is_active ? t('Active') : t('Inactive')}
                            </span>
                        </div>
                    </div>


                </div>

                {(() => {
                    let options = [];
                    if (customquestion.options) {
                        try {
                            options = typeof customquestion.options === 'string'
                                ? JSON.parse(customquestion.options)
                                : customquestion.options;
                        } catch (e) {
                            options = [];
                        }
                    }
                    return options.length > 0 && (
                        <div>
                            <label className="text-sm font-medium text-gray-700">{t('Options')}</label>
                            <div className="mt-2 space-y-2">
                                {options.map((option, index) => (
                                    <div key={index} className="flex items-center gap-2 p-2 bg-gray-50 rounded-md">
                                        <span className="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">{index + 1}</span>
                                        <span className="text-sm text-gray-900">{option}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })()}

            </div>
        </DialogContent>
    );
}