import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MediaPicker from '@/components/MediaPicker';
import { getImagePath, downloadFile } from '@/utils/helpers';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Image, File, FileText, Video, Music, Download, Eye, Trash2 } from 'lucide-react';
import { Lead } from '../types';

interface FilesProps {
    lead: Lead;
}

export default function Files({ lead }: FilesProps) {
    const { t } = useTranslation();
    const [files, setFiles] = useState<string[]>([]);

    const handleFilesChange = (value: string | string[]) => {
        setFiles(Array.isArray(value) ? value : [value].filter(Boolean));
    };

    const handleSave = () => {
        router.post(route('lead.leads.store-file', lead.id), {
            additional_images: files
        }, {
            onSuccess: () => {
                setFiles([]);
            }
        });
    };

    const getFileIcon = (fileName: string) => {
        const ext = fileName.split('.').pop()?.toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext || '')) {
            return <Image className="h-5 w-5 text-blue-500" />;
        } else if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(ext || '')) {
            return <Video className="h-5 w-5 text-purple-500" />;
        } else if (['mp3', 'wav', 'flac', 'aac', 'ogg'].includes(ext || '')) {
            return <Music className="h-5 w-5 text-green-500" />;
        } else if (['txt', 'doc', 'docx', 'pdf', 'rtf'].includes(ext || '')) {
            return <FileText className="h-5 w-5 text-red-500" />;
        } else {
            return <File className="h-5 w-5 text-gray-500" />;
        }
    };

    const isImage = (fileName: string) => {
        const ext = fileName.split('.').pop()?.toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext || '');
    };

    return (
        <div className="bg-white border rounded-lg p-6 shadow-sm h-full flex flex-col">
            <h4 className="text-lg font-semibold text-gray-800 mb-4">{t('Files')}</h4>
            <div className="mb-4">
                <MediaPicker
                    value={files}
                    onChange={handleFilesChange}
                    multiple={true}
                    placeholder={t('Select files')}
                    showPreview={false}
                    label=""
                />
                {files.length > 0 && (
                    <div className="flex justify-end mt-2">
                        <Button onClick={handleSave}>
                            {t('Save Files')}
                        </Button>
                    </div>
                )}
            </div>
            {lead.files && lead.files.length > 0 ? (
                <div className="space-y-2 flex-1 overflow-y-auto max-h-96 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                    {lead.files.map((file) => {
                        let imageUrl = getImagePath(file.file_path);
                        return (
                            <div key={file.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors group">
                                <div className="flex-shrink-0">
                                    {isImage(file.file_path) ? (
                                        <img
                                            src={imageUrl}
                                            alt={file.file_name || 'File'}
                                            className="w-10 h-10 object-cover rounded border"
                                        />
                                    ) : (
                                        <div className="w-10 h-10 bg-white rounded border flex items-center justify-center">
                                            {getFileIcon(file.file_path)}
                                        </div>
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-900 truncate" title={file.file_name || file.file_path}>
                                        {file.file_name || file.file_path.split('/').pop()}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {file.file_path.split('.').pop()?.toUpperCase()} file
                                    </p>
                                </div>
                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <TooltipProvider>
                                        <Tooltip delayDuration={0}>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => window.open(imageUrl, '_blank')}
                                                    className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('View')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider>
                                        <Tooltip delayDuration={0}>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => downloadFile(imageUrl)}
                                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('Download')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider>
                                        <Tooltip delayDuration={0}>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => router.delete(route('lead.leads.delete-file', file.id))}
                                                    className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('Delete')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <div className="text-center py-8 text-gray-500 flex-1 flex flex-col items-center justify-center">
                    <File className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p className="text-sm">{t('No files uploaded yet')}</p>
                </div>
            )}
        </div>
    );
}
