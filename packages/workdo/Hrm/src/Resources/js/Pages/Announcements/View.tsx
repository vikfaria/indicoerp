import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Megaphone, FileText, Calendar, AlertCircle, CheckCircle, Building2, Tag } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Announcement } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    announcement: Announcement;
}

export default function View({ announcement }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Megaphone className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Announcement Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Title')}
                            </label>
                            <p className="mt-1 font-medium">{announcement.title || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Tag className="h-4 w-4" />
                                {t('Category')}
                            </label>
                            <p className="mt-1 font-medium">{announcement.announcement_category?.announcement_category || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <AlertCircle className="h-4 w-4" />
                                {t('Priority')}
                            </label>
                            <div className="mt-1">
                                {announcement.priority ? (
                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                        announcement.priority === 'low' ? 'bg-slate-100 text-slate-800' :
                                        announcement.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                                        announcement.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                        'bg-red-100 text-red-800'
                                    }`}>
                                        {t(announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1))}
                                    </span>
                                ) : '-'}
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <div className="mt-1">
                                {announcement.status ? (
                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                        announcement.status === 'active' ? 'bg-green-100 text-green-800' :
                                        announcement.status === 'inactive' ? 'bg-red-100 text-red-800' :
                                        'bg-blue-100 text-blue-800'
                                    }`}>
                                        {t(announcement.status.charAt(0).toUpperCase() + announcement.status.slice(1))}
                                    </span>
                                ) : '-'}
                            </div>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Start Date')}
                            </label>
                            <p className="mt-1 font-medium">{announcement.start_date ? formatDate(announcement.start_date) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('End Date')}
                            </label>
                            <p className={`mt-1 font-medium ${announcement.end_date && new Date(announcement.end_date) < new Date() ? 'text-red-600' : ''}`}>
                                {announcement.end_date ? formatDate(announcement.end_date) : '-'}
                            </p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Building2 className="h-4 w-4" />
                                {t('Departments')}
                            </label>
                            <div className="mt-1">
                                {announcement.departments && announcement.departments.length > 0 ? (
                                    <div className="flex flex-wrap gap-1">
                                        {announcement.departments.map((dept: any) => (
                                            <Badge key={dept.id} variant="outline" className="text-xs">
                                                {dept.department_name || dept.name}
                                            </Badge>
                                        ))}
                                    </div>
                                ) : '-'}
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Approved By')}
                            </label>
                            <p className="mt-1 font-medium">{announcement.approved_by?.name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                {announcement.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{announcement.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}