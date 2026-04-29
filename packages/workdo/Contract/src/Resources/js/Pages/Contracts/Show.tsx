import { useState, useEffect } from 'react';
import { Head, usePage, router, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { usePageButtons } from '@/hooks/usePageButtons';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown } from 'lucide-react';
import { FileText, Calendar, User, DollarSign, Edit, Paperclip, MessageSquare, StickyNote, RefreshCw, PenTool, Eye, CheckCircle, Save, X } from 'lucide-react';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Contract, ContractShowProps } from './types';
import { formatCurrency, formatDate, formatDateTime } from '@/utils/helpers';

const getContractStatusColor = (status: any) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'pending':
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        case 'accepted':
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'declined':
            return 'bg-red-100 text-red-800';
        case 'closed':
            return 'bg-slate-100 text-slate-800';
        case 'archived':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
};

const getContractStatusText = (status: any, t: (key: string) => string) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'pending':
            return t('Pending');
        case 'accepted':
            return t('Accepted');
        case 'declined':
            return t('Declined');
        case 'closed':
            return t('Closed');
        case 'draft':
            return t('Draft');
        case 'active':
            return t('Active');
        case 'archived':
            return t('Archived');
        default:
            return t('Pending');
    }
};
import AttachmentsTab from './components/AttachmentsTab';
import CommentsTab from './components/CommentsTab';
import NotesTab from './components/NotesTab';
import RenewalsTab from './components/RenewalsTab';
import SignatureModal from './components/SignatureModal';
import ContractPDFExport from './components/ContractPDFExport';

export default function Show() {
    const { t } = useTranslation();
    const { contract, auth } = usePage<ContractShowProps>().props;


    const [activeTab, setActiveTab] = useState('details');
    const [deleteConfig, setDeleteConfig] = useState<{ type: string, id: number, route: string, message: string } | null>(null);
    const [showSignatureModal, setShowSignatureModal] = useState(false);
    const [isEditingDescription, setIsEditingDescription] = useState(false);

    const { data, setData, put, processing } = useForm({
        subject: contract.subject,
        user_id: contract.user?.id || '',
        value: contract.value || '',
        type_id: contract.contract_type?.id || '',
        start_date: contract.start_date || '',
        end_date: contract.end_date || '',
        description: contract.description || '',
        status: contract.status || 'pending'
    });

    const spreadsheetButtons = usePageButtons('spreadsheetBtn', { module: 'Contract', sub_module: contract.subject });
    const businessProcessMappingButtons = usePageButtons('businessProcessMappingBtn', { module: 'Contract', submodule: 'Contract', id: contract.id });

    const handleDeleteConfirm = () => {
        if (deleteConfig) {
            router.delete(route(deleteConfig.route, deleteConfig.id), {
                onSuccess: () => router.reload()
            });
            setDeleteConfig(null);
        }
    };

    const handleSaveDescription = () => {
        put(route('contract.update', contract.id), {
            onSuccess: () => {
                setIsEditingDescription(false);
                router.reload();
            }
        });
    };

    const handleCancelEdit = () => {
        setData({
            subject: contract.subject,
            user_id: contract.user?.id || '',
            value: contract.value || '',
            type_id: contract.contract_type?.id || '',
            start_date: contract.start_date || '',
            end_date: contract.end_date || '',
            description: contract.description || '',
            status: contract.status || 'pending'
        });
        setIsEditingDescription(false);
    };

    // Permission checks for tabs
    const canViewAttachments = auth.user?.permissions?.includes('manage-any-contract-attachments') || auth.user?.permissions?.includes('manage-own-contract-attachments');
    const canViewComments = auth.user?.permissions?.includes('manage-any-contract-comments') || auth.user?.permissions?.includes('manage-own-contract-comments');
    const canViewNotes = auth.user?.permissions?.includes('manage-any-contract-notes') || auth.user?.permissions?.includes('manage-own-contract-notes');
    const canViewRenewals = auth.user?.permissions?.includes('manage-any-contract-renewals') || auth.user?.permissions?.includes('manage-own-contract-renewals');

    // Calculate visible tabs count for grid layout
    const visibleTabsCount = 1 + (canViewAttachments ? 1 : 0) + (canViewComments ? 1 : 0) + (canViewNotes ? 1 : 0) + (canViewRenewals ? 1 : 0);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Contract'), url: route('contract.index') },
                { label: contract.contract_number }
            ]}
            pageTitle={t('Details Contract')}
            backUrl={route('contract.index')}
            pageActions={
                <TooltipProvider>
                    <div className="flex gap-2">
                        {spreadsheetButtons.map((button) => (
                            <div key={button.id}>
                                {button.component}
                            </div>
                        ))}
                        {businessProcessMappingButtons.map((button) => (
                            <div key={button.id}>
                                {button.component}
                            </div>
                        ))}

                        {auth.user?.permissions?.includes('preview-contracts') && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.get(route('contract.preview', contract.id))}
                                    >
                                        <FileText className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Preview')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}

                        <ContractPDFExport contract={contract} />

                        {!contract.signatures?.some(sig => sig.user.id === auth.user?.id) && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setShowSignatureModal(true)}
                                    >
                                        <PenTool className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Signature Contract')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}


                    </div>
                </TooltipProvider>
            }
        >
            <Head title={`${t('Details Contract')} - ${contract.subject}`} />

            <div className="space-y-6">
                <Card className="border-0 shadow-lg bg-gradient-to-r from-white to-gray-50">
                    <CardHeader className="pb-8">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div className="flex-1 space-y-4">
                                <CardTitle className="flex items-center gap-4 text-2xl">
                                    <div className="p-3 bg-primary/15 rounded-xl shadow-sm">
                                        <FileText className="h-6 w-6 text-primary" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="font-bold text-gray-900">{contract.subject}</span>
                                        <span className="text-sm font-normal text-muted-foreground mt-1">
                                            {contract.contract_number}
                                        </span>
                                    </div>
                                </CardTitle>

                                <div className="flex items-center gap-4">
                                    {auth.user?.permissions?.includes('edit-contracts') ? (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="outline" size="sm" className="flex items-center gap-2 text-sm font-medium hover:bg-gray-50 border-gray-200">
                                                    <div className={`w-2.5 h-2.5 rounded-full ${contract.status === 'pending' ? 'bg-yellow-500' :
                                                        contract.status === 'accepted' ? 'bg-green-500' :
                                                            contract.status === 'declined' ? 'bg-red-500' :
                                                                contract.status === 'closed' ? 'bg-gray-500' :
                                                                    'bg-blue-500'
                                                        }`} />
                                                    {getContractStatusText(contract.status, t)}
                                                    <ChevronDown className="h-3 w-3" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="start" className="w-40 bg-white border shadow-lg">
                                                <DropdownMenuItem
                                                    onClick={() => router.patch(route('contract.update-status', contract.id), { status: 'pending' })}
                                                    className="flex items-center gap-2 cursor-pointer hover:bg-yellow-50 focus:bg-yellow-50"
                                                >
                                                    <div className="w-2 h-2 rounded-full bg-yellow-600" />
                                                    {t('Pending')}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => router.patch(route('contract.update-status', contract.id), { status: 'accepted' })}
                                                    className="flex items-center gap-2 cursor-pointer hover:bg-green-50 focus:bg-green-50"
                                                >
                                                    <div className="w-2 h-2 rounded-full bg-green-600" />
                                                    {t('Accepted')}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => router.patch(route('contract.update-status', contract.id), { status: 'declined' })}
                                                    className="flex items-center gap-2 cursor-pointer hover:bg-red-50 focus:bg-red-50"
                                                >
                                                    <div className="w-2 h-2 rounded-full bg-red-600" />
                                                    {t('Declined')}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => router.patch(route('contract.update-status', contract.id), { status: 'closed' })}
                                                    className="flex items-center gap-2 cursor-pointer hover:bg-gray-50 focus:bg-gray-50"
                                                >
                                                    <div className="w-2 h-2 rounded-full bg-gray-600" />
                                                    {t('Closed')}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    ) : (
                                        <div className="flex items-center gap-2 text-sm font-medium">
                                            <div className={`w-2.5 h-2.5 rounded-full ${getContractStatusColor(contract.status)}`} />
                                            {getContractStatusText(contract.status, t)}
                                        </div>
                                    )}

                                    {contract.user?.name && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <User className="h-4 w-4" />
                                            <span>{contract.user.name}</span>
                                        </div>
                                    )}

                                    {contract.created_at && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            <span>{formatDate(contract.created_at)}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="flex flex-col items-end space-y-3">
                                <div className="text-right">
                                    <p className="text-sm text-muted-foreground mb-1">{t('Contract Value')}</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {contract.value ? formatCurrency(contract.value) : (
                                            <span className="text-lg text-muted-foreground font-normal">{t('Not Set')}</span>
                                        )}
                                    </p>
                                </div>

                                {contract.contract_type?.name && (
                                    <div className="bg-primary/10 px-3 py-1.5 rounded-full">
                                        <span className="text-sm font-medium text-primary">{contract.contract_type.name}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className={`grid w-full grid-cols-${visibleTabsCount}`}>
                        <TabsTrigger value="details" className="flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Details')}
                        </TabsTrigger>
                        {canViewAttachments && (
                            <TabsTrigger value="attachments" className="flex items-center gap-2">
                                <Paperclip className="h-4 w-4" />
                                {t('Attachments')} ({contract.attachments?.length || 0})
                            </TabsTrigger>
                        )}
                        {canViewComments && (
                            <TabsTrigger value="comments" className="flex items-center gap-2">
                                <MessageSquare className="h-4 w-4" />
                                {t('Comments')} ({contract.comments?.length || 0})
                            </TabsTrigger>
                        )}
                        {canViewNotes && (
                            <TabsTrigger value="notes" className="flex items-center gap-2">
                                <StickyNote className="h-4 w-4" />
                                {t('Notes')} ({contract.notes?.length || 0})
                            </TabsTrigger>
                        )}
                        {canViewRenewals && (
                            <TabsTrigger value="renewals" className="flex items-center gap-2">
                                <RefreshCw className="h-4 w-4" />
                                {t('Renewals')} ({contract.renewals?.length || 0})
                            </TabsTrigger>
                        )}
                    </TabsList>

                    <TabsContent value="details">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Contract Information')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <FileText className="h-4 w-4" />
                                            {t('Contract Number')}
                                        </div>
                                        <p className="font-medium">{contract.contract_number || t('Not Generated')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <User className="h-4 w-4" />
                                            {t('Client')}
                                        </div>
                                        <p className="font-medium">{contract.user?.name || t('Not Assigned')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <FileText className="h-4 w-4" />
                                            {t('Contract Type')}
                                        </div>
                                        <p className="font-medium">{contract.contract_type?.name || t('Not Set')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <DollarSign className="h-4 w-4" />
                                            {t('Contract Value')}
                                        </div>
                                        <p className="font-medium text-lg">{contract.value ? formatCurrency(contract.value) : t('Not Set')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            {t('Start Date')}
                                        </div>
                                        <p className="font-medium">{contract.start_date ? formatDate(contract.start_date) : t('Not Set')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            {t('End Date')}
                                        </div>
                                        <p className="font-medium">{contract.end_date ? formatDate(contract.end_date) : t('Not Set')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            {t('Duration')}
                                        </div>
                                        <p className="font-medium">
                                            {contract.start_date && contract.end_date ?
                                                `${Math.ceil((new Date(contract.end_date).getTime() - new Date(contract.start_date).getTime()) / (1000 * 60 * 60 * 24))} ${t('days')}`
                                                : t('Not Calculated')
                                            }
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            {t('Created Date')}
                                        </div>
                                        <p className="font-medium">{contract.created_at ? formatDateTime(contract.created_at) : t('Not Available')}</p>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            {t('Status')}
                                        </div>
                                        <span className={`px-2 py-1 rounded-full text-sm ${getContractStatusColor(contract.status)}`}>
                                            {getContractStatusText(contract.status, t)}
                                        </span>
                                    </div>

                                    <div className="mt-6 pt-6 col-span-full">
                                        <div className="flex items-center justify-between mb-3">
                                            <h3 className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                                <FileText className="h-4 w-4" />
                                                {t('Description')}
                                            </h3>
                                            {auth.user?.permissions?.includes('edit-contracts') && (
                                                <div className="flex gap-2">
                                                    {isEditingDescription ? (
                                                        <>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button size="sm" onClick={handleSaveDescription} disabled={processing}>
                                                                        <Save className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Save')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button size="sm" variant="outline" onClick={handleCancelEdit}>
                                                                        <X className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Cancel')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </>
                                                    ) : (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button size="sm" variant="outline" onClick={() => setIsEditingDescription(true)}>
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                        {isEditingDescription ? (
                                            <RichTextEditor
                                                key={`editor-${contract.id}-${isEditingDescription}`}
                                                content={data.description}
                                                onChange={(value) => setData('description', value)}
                                                placeholder={t('Enter description...')}
                                            />
                                        ) : (
                                            <div className="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                                                {contract.description ? (
                                                    <div className="text-sm text-gray-700 prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: contract.description }} />
                                                ) : (
                                                    <p className="text-sm text-gray-500 italic">{t('No description provided')}</p>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    {contract.signatures && contract.signatures.length > 0 && (
                                        <div className="space-y-2 col-span-full">
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <PenTool className="h-4 w-4" />
                                                {t('Signatures')}
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {contract.signatures.map((signature, index) => (
                                                    <div key={signature.id} className="border rounded-lg p-3 bg-gray-50">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                                <PenTool className="h-5 w-5 text-blue-600" />
                                                            </div>
                                                            <div className="flex-1">
                                                                <p className="font-medium text-sm">{signature.user.name}</p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {t('Signed on')} {formatDateTime(signature.signed_at)}
                                                                </p>
                                                                <div className="mt-1">
                                                                    <img
                                                                        src={signature.signature_data}
                                                                        alt="Signature"
                                                                        className="max-h-12 max-w-full object-contain border-b-2 border-gray-400"
                                                                    />
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {canViewAttachments && (
                        <TabsContent value="attachments">
                            <AttachmentsTab contract={contract} setDeleteConfig={setDeleteConfig} />
                        </TabsContent>
                    )}

                    {canViewComments && (
                        <TabsContent value="comments">
                            <CommentsTab contract={contract} setDeleteConfig={setDeleteConfig} />
                        </TabsContent>
                    )}

                    {canViewNotes && (
                        <TabsContent value="notes">
                            <NotesTab contract={contract} setDeleteConfig={setDeleteConfig} />
                        </TabsContent>
                    )}

                    {canViewRenewals && (
                        <TabsContent value="renewals">
                            <RenewalsTab contract={contract} setDeleteConfig={setDeleteConfig} />
                        </TabsContent>
                    )}
                </Tabs>
            </div>

            <ConfirmationDialog
                open={!!deleteConfig}
                onOpenChange={() => setDeleteConfig(null)}
                title={t(`Delete ${deleteConfig?.type || ''}`)}
                message={deleteConfig?.message || ''}
                confirmText={t('Delete')}
                onConfirm={handleDeleteConfirm}
                variant="destructive"
            />

            <SignatureModal
                open={showSignatureModal}
                onOpenChange={setShowSignatureModal}
                contractId={contract.id}
            />
        </AuthenticatedLayout>
    );
}
