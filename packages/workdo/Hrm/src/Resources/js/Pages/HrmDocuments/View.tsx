import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Document } from './types';
import { formatDate } from '@/utils/helpers';
import { usePage } from '@inertiajs/react';

interface ViewProps {
    document: Document;
}

export default function View({ document }: ViewProps) {
    const { t } = useTranslation();
    const { documentcategories, users } = usePage<any>().props;
    
    const documentCategory = documentcategories?.find((item: any) => item.id.toString() === document.document_category_id?.toString());

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <FileText className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Document Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="space-y-6 p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Title')}</label>
                            <p className="text-base font-medium">{document.title || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Document Category')}</label>
                            <p className="text-base">{documentCategory?.document_type || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                            <div className="mt-1">
                                <Badge className={`${
                                    document.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    document.status === 'approve' ? 'bg-green-100 text-green-800' :
                                    document.status === 'reject' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {t(document.status === 'pending' ? 'Pending' : document.status === 'approve' ? 'Approved' : document.status === 'reject' ? 'Rejected' : document.status)}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Effective Date')}</label>
                            <p className="text-base">{document.effective_date ? formatDate(document.effective_date) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Uploaded By')}</label>
                            <p className="text-base">{document.uploaded_by?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Approved By')}</label>
                            <p className="text-base">{document.approved_by?.name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                {document.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Description')}</label>
                        <div className="mt-2 p-4 bg-gray-50 rounded-lg">
                            <p className="text-base whitespace-pre-wrap">{document.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}