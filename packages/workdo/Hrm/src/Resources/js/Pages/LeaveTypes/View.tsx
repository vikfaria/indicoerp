import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Calendar, FileText, Palette, DollarSign, Hash } from 'lucide-react';
import { LeaveType } from './types';

interface ViewProps {
    leavetype: LeaveType;
}

export default function View({ leavetype }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Calendar className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Leave Type Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{leavetype.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Name')}
                            </label>
                            <div className="mt-1 flex items-center gap-2">
                                <div 
                                    className="w-3 h-3 rounded-full border border-gray-300" 
                                    style={{ backgroundColor: leavetype.color || '#FF6B6B' }}
                                ></div>
                                <p className="font-medium">{leavetype.name || '-'}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Hash className="h-4 w-4" />
                                {t('Max Days Per Year')}
                            </label>
                            <p className="mt-1 font-medium">{leavetype.max_days_per_year || '-'}</p>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <DollarSign className="h-4 w-4" />
                                {t('Is Paid')}
                            </label>
                            <div className="mt-1">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                    leavetype.is_paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                }`}>
                                    {leavetype.is_paid ? t('Paid') : t('Unpaid')}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Palette className="h-4 w-4" />
                                {t('Color')}
                            </label>
                            <div className="mt-1 flex items-center gap-2">
                                <div 
                                    className="w-6 h-6 rounded border border-gray-300" 
                                    style={{ backgroundColor: leavetype.color || '#FF6B6B' }}
                                ></div>
                                <span className="text-sm text-gray-600">{leavetype.color || '#FF6B6B'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {leavetype.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{leavetype.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}