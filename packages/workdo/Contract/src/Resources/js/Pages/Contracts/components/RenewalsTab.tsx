import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { CurrencyInput } from '@/components/ui/currency-input';
import InputError from '@/components/ui/input-error';
import { RefreshCw, Plus, Grid, List, ChevronLeft, ChevronRight, Calendar, DollarSign, User, Eye, Edit, Trash2 } from 'lucide-react';
import { SearchInput } from '@/components/ui/search-input';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatCurrency, formatDate, formatDateTime } from '@/utils/helpers';

const getRenewalStatusColor = (status: any) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'draft':
            return 'bg-gray-100 text-gray-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-blue-100 text-blue-800';
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'expired':
            return 'bg-orange-100 text-orange-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getRenewalStatusText = (status: any, t: (key: string) => string) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'draft':
            return t('Draft');
        case 'pending':
            return t('Pending');
        case 'approved':
            return t('Approved');
        case 'active':
            return t('Active');
        case 'expired':
            return t('Expired');
        case 'cancelled':
            return t('Cancelled');
        default:
            return t('Draft');
    }
};

interface RenewalsTabProps {
    contract: any;
    setDeleteConfig?: (config: any) => void;
}

export default function RenewalsTab({ contract, setDeleteConfig }: RenewalsTabProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;


    const [renewalDialogOpen, setRenewalDialogOpen] = useState(false);
    const [renewalData, setRenewalData] = useState({
        start_date: '',
        end_date: '',
        value: '',
        status: 'pending',
        notes: ''
    });
    const [renewalErrors, setRenewalErrors] = useState<any>({});
    const [renewalView, setRenewalView] = useState<'list' | 'grid'>('list');
    const [renewalPage, setRenewalPage] = useState(parseInt(new URLSearchParams(window.location.search).get('renewal_page') || '1'));
    const [renewalPerPage, setRenewalPerPage] = useState(parseInt(new URLSearchParams(window.location.search).get('renewal_per_page') || '10'));
    const [renewalSearch, setRenewalSearch] = useState(new URLSearchParams(window.location.search).get('renewal_search') || '');
    const [renewalSearchInput, setRenewalSearchInput] = useState(new URLSearchParams(window.location.search).get('renewal_search') || '');
    const [showRenewalId, setShowRenewalId] = useState<number | null>(null);
    const [editRenewalId, setEditRenewalId] = useState<number | null>(null);
    const [showRenewalDialogOpen, setShowRenewalDialogOpen] = useState(false);

    const handleRenewalSubmit = () => {
        setRenewalErrors({});
        const url = editRenewalId
            ? route('contract-renewals.update', editRenewalId)
            : route('contract-renewals.store', contract.id);
        const method = editRenewalId ? 'put' : 'post';

        router[method](url, renewalData, {
            onSuccess: () => {
                setRenewalDialogOpen(false);
                setRenewalData({ start_date: '', end_date: '', value: '', status: 'pending', notes: '' });
                setEditRenewalId(null);
                setRenewalErrors({});
                router.reload();
            },
            onError: (errors) => {
                setRenewalErrors(errors);
            }
        });
    };

    const getSelectedRenewal = () => {
        return contract.renewals?.find((renewal: any) => renewal.id === showRenewalId);
    };

    return (
        <>
            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>{t('Contract Renewals')}</CardTitle>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <SearchInput
                                value={renewalSearchInput}
                                onChange={(value) => setRenewalSearchInput(value)}
                                onSearch={() => {
                                    setRenewalSearch(renewalSearchInput);
                                    setRenewalPage(1);
                                    router.reload({
                                        data: {
                                            renewal_search: renewalSearchInput,
                                            renewal_page: 1,
                                            renewal_per_page: renewalPerPage
                                        }
                                    });
                                }}
                                onClear={() => {
                                    setRenewalSearch('');
                                    setRenewalSearchInput('');
                                    setRenewalPage(1);
                                    router.reload({
                                        data: {
                                            renewal_search: '',
                                            renewal_page: 1,
                                            renewal_per_page: renewalPerPage
                                        }
                                    });
                                }}
                                placeholder={t('Search renewals...')}
                                className="w-64"
                            />
                        </div>
                        <div className="flex border rounded-md">
                            <Button
                                size="sm"
                                variant={renewalView === 'list' ? 'default' : 'ghost'}
                                onClick={() => setRenewalView('list')}
                                className="rounded-r-none px-2 h-9"
                            >
                                <List className="h-3 w-3" />
                            </Button>
                            <Button
                                size="sm"
                                variant={renewalView === 'grid' ? 'default' : 'ghost'}
                                onClick={() => setRenewalView('grid')}
                                className="rounded-l-none px-2 h-9"
                            >
                                <Grid className="h-3 w-3" />
                            </Button>
                        </div>
                        <Select value={renewalPerPage.toString()} onValueChange={(value) => {
                            setRenewalPerPage(Number(value));
                            setRenewalPage(1);
                            router.reload({
                                data: {
                                    renewal_search: renewalSearch,
                                    renewal_page: 1,
                                    renewal_per_page: Number(value)
                                }
                            });
                        }}>
                            <SelectTrigger className="w-[120px] h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="5">5 {t('per page')}</SelectItem>
                                <SelectItem value="10">10 {t('per page')}</SelectItem>
                                <SelectItem value="20">20 {t('per page')}</SelectItem>
                                <SelectItem value="50">50 {t('per page')}</SelectItem>
                            </SelectContent>
                        </Select>
                        {(auth.user?.permissions?.includes('create-contract-renewals')) && (
                            <TooltipProvider>
                                <Tooltip >
                                    <TooltipTrigger asChild>
                                        <Button size="sm" onClick={() => {
                                            setEditRenewalId(null);
                                            setRenewalData({ start_date: '', end_date: '', value: '', status: 'pending', notes: '' });
                                            setRenewalErrors({});
                                            setRenewalDialogOpen(true);
                                        }} className="h-9 w-9 p-0">
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Add Renewal')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        )}
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {(() => {
                        const renewals = contract.renewals || [];
                        const totalRenewals = renewals.length;
                        const lastPage = Math.ceil(totalRenewals / renewalPerPage);

                        return renewals.length > 0 ? (
                            <>
                                {renewalView === 'list' ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>{t('Start Date')}</TableHead>
                                                <TableHead>{t('End Date')}</TableHead>
                                                <TableHead>{t('Value')}</TableHead>
                                                <TableHead>{t('Status')}</TableHead>
                                                <TableHead>{t('Created By')}</TableHead>
                                                <TableHead className="w-24">{t('Actions')}</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {renewals.map((renewal: any) => (
                                                <TableRow key={renewal.id}>
                                                    <TableCell>{formatDate(renewal.start_date)}</TableCell>
                                                    <TableCell>{formatDate(renewal.end_date)}</TableCell>
                                                    <TableCell>{renewal.value ? formatCurrency(renewal.value) : '-'}</TableCell>
                                                    <TableCell>
                                                        <span className={`px-2 py-1 rounded-full text-sm ${getRenewalStatusColor(renewal.status)}`}>
                                                            {getRenewalStatusText(renewal.status, t)}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>{renewal.creator?.name || '-'}</TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-1">
                                                            <TooltipProvider>
                                                                <Tooltip>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" onClick={() => {
                                                                            setShowRenewalId(renewal.id);
                                                                            setShowRenewalDialogOpen(true);
                                                                        }} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View Details')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                            {auth.user?.permissions?.includes('edit-contract-renewals') && (renewal.creator_id === auth.user?.id || renewal.created_by === auth.user?.id) && (
                                                                <TooltipProvider>
                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" onClick={() => {
                                                                                setEditRenewalId(renewal.id);
                                                                                setRenewalData({
                                                                                    start_date: renewal.start_date,
                                                                                    end_date: renewal.end_date,
                                                                                    value: renewal.value,
                                                                                    status: renewal.status,
                                                                                    notes: renewal.notes || ''
                                                                                });
                                                                                setRenewalDialogOpen(true);
                                                                            }} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                                <Edit className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Edit')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                </TooltipProvider>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-contract-renewals') && (renewal.creator_id === auth.user?.id || renewal.created_by === auth.user?.id) && (
                                                                <TooltipProvider>
                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" onClick={() => {
                                                                                if (setDeleteConfig) {
                                                                                    setDeleteConfig({
                                                                                        type: 'renewal',
                                                                                        id: renewal.id,
                                                                                        route: 'contract-renewals.destroy',
                                                                                        message: t('Are you sure you want to delete this renewal?')
                                                                                    });
                                                                                } else {
                                                                                    if (confirm(t('Are you sure you want to delete this renewal?'))) {
                                                                                        router.delete(route('contract-renewals.destroy', renewal.id));
                                                                                    }
                                                                                }
                                                                            }} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                                                <Trash2 className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Delete')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                </TooltipProvider>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 p-4">
                                        {renewals.map((renewal: any) => (
                                            <div key={renewal.id} className="group bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-blue-300 transition-all duration-300 hover:-translate-y-1 cursor-pointer">


                                                {/* Value - Main Focus */}
                                                <div className="text-center mb-4 p-3 bg-gradient-to-r from-primary/5 to-primary/10 rounded-lg">
                                                    <p className="text-xs text-gray-500 mb-1 font-medium">{t('Renewal Value')}</p>
                                                    <p className="font-bold text-xl text-primary">
                                                        {renewal.value ? formatCurrency(renewal.value) : '-'}
                                                    </p>
                                                </div>

                                                {/* Date Range */}
                                                <div className="bg-gray-50 rounded-lg p-3 mb-4">
                                                    <div className="flex items-center justify-between text-xs">
                                                        <div className="text-center">
                                                            <p className="text-gray-500 mb-1">{t('Start')}</p>
                                                            <p className="font-semibold text-gray-900">{formatDate(renewal.start_date)}</p>
                                                        </div>
                                                        <div className="w-px h-8 bg-gray-300"></div>
                                                        <div className="text-center">
                                                            <p className="text-gray-500 mb-1">{t('End')}</p>
                                                            <p className="font-semibold text-gray-900">{formatDate(renewal.end_date)}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Creator */}
                                                <div className="flex items-center gap-2 mb-4 px-2">
                                                    <div className="w-6 h-6 bg-purple-50 rounded-full flex items-center justify-center">
                                                        <User className="h-3 w-3 text-purple-600" />
                                                    </div>
                                                    <span className="text-xs text-gray-600 truncate font-medium">{renewal.creator?.name || '-'}</span>
                                                </div>

                                                {/* Notes Preview */}
                                                {renewal.notes && (
                                                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-4">
                                                        <p className="text-xs text-amber-700 line-clamp-2 leading-relaxed">{renewal.notes}</p>
                                                    </div>
                                                )}

                                                {/* Status and Actions */}
                                                <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                                                    <div className="flex items-center gap-2 text-sm font-medium">
                                                        <div className={`w-2.5 h-2.5 rounded-full ${renewal.status === 'pending' ? 'bg-yellow-500' :
                                                                renewal.status === 'approved' ? 'bg-blue-500' :
                                                                    renewal.status === 'active' ? 'bg-green-500' :
                                                                        renewal.status === 'expired' ? 'bg-gray-500' :
                                                                            renewal.status === 'rejected' ? 'bg-red-500' :
                                                                                'bg-gray-500'
                                                            }`} />
                                                        <span className="text-xs">{t(renewal.status)}</span>
                                                    </div>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            <Tooltip >
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            setShowRenewalId(renewal.id);
                                                                            setShowRenewalDialogOpen(true);
                                                                        }}
                                                                        className="h-6 w-6 p-0 text-blue-600 hover:text-blue-700"
                                                                    >
                                                                        <Eye className="h-3 w-3" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('View Details')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                        {auth.user?.permissions?.includes('edit-contract-renewals') && (renewal.creator_id === auth.user?.id || renewal.created_by === auth.user?.id) && (
                                                            <TooltipProvider>
                                                                <Tooltip >
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                setEditRenewalId(renewal.id);
                                                                                setRenewalData({
                                                                                    start_date: renewal.start_date,
                                                                                    end_date: renewal.end_date,
                                                                                    value: renewal.value,
                                                                                    status: renewal.status,
                                                                                    notes: renewal.notes || ''
                                                                                });
                                                                                setRenewalDialogOpen(true);
                                                                            }}
                                                                            className="h-6 w-6 p-0 text-green-600 hover:text-green-700"
                                                                        >
                                                                            <Edit className="h-3 w-3" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit Renewal')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        )}
                                                        {auth.user?.permissions?.includes('delete-contract-renewals') && (renewal.creator_id === auth.user?.id || renewal.created_by === auth.user?.id) && (
                                                            <TooltipProvider>
                                                                <Tooltip >
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                if (setDeleteConfig) {
                                                                                    setDeleteConfig({
                                                                                        type: 'renewal',
                                                                                        id: renewal.id,
                                                                                        route: 'contract-renewals.destroy',
                                                                                        message: t('Are you sure you want to delete this renewal?')
                                                                                    });
                                                                                } else {
                                                                                    if (confirm(t('Are you sure you want to delete this renewal?'))) {
                                                                                        router.delete(route('contract-renewals.destroy', renewal.id));
                                                                                    }
                                                                                }
                                                                            }}
                                                                            className="h-6 w-6 p-0 text-destructive hover:text-destructive"
                                                                        >
                                                                            <Trash2 className="h-3 w-3" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Delete Renewal')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="px-4 py-3 border-t bg-gray-50/30">
                                    <div className="flex items-center justify-between">
                                        <div className="text-sm text-muted-foreground">
                                            {t('Showing')} <span className="font-medium text-foreground">{((renewalPage - 1) * renewalPerPage) + 1}</span> {t('to')} <span className="font-medium text-foreground">{Math.min(renewalPage * renewalPerPage, totalRenewals)}</span> {t('of')} <span className="font-medium text-foreground">{totalRenewals}</span> {t('results')}
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    const newPage = Math.max(1, renewalPage - 1);
                                                    setRenewalPage(newPage);
                                                    router.reload({
                                                        data: {
                                                            renewal_search: renewalSearch,
                                                            renewal_page: newPage,
                                                            renewal_per_page: renewalPerPage
                                                        }
                                                    });
                                                }}
                                                disabled={renewalPage === 1}
                                                className="h-8 px-3"
                                            >
                                                <ChevronLeft className="h-4 w-4" />
                                                {t('Previous')}
                                            </Button>
                                            <div className="flex items-center space-x-1">
                                                {Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
                                                    let pageNum;
                                                    if (lastPage <= 5) {
                                                        pageNum = i + 1;
                                                    } else if (renewalPage <= 3) {
                                                        pageNum = i + 1;
                                                    } else if (renewalPage >= lastPage - 2) {
                                                        pageNum = lastPage - 4 + i;
                                                    } else {
                                                        pageNum = renewalPage - 2 + i;
                                                    }

                                                    return (
                                                        <Button
                                                            key={pageNum}
                                                            variant={renewalPage === pageNum ? "default" : "outline"}
                                                            size="sm"
                                                            onClick={() => {
                                                                setRenewalPage(pageNum);
                                                                router.reload({
                                                                    data: {
                                                                        renewal_search: renewalSearch,
                                                                        renewal_page: pageNum,
                                                                        renewal_per_page: renewalPerPage
                                                                    }
                                                                });
                                                            }}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            {pageNum}
                                                        </Button>
                                                    );
                                                })}
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    const newPage = Math.min(lastPage, renewalPage + 1);
                                                    setRenewalPage(newPage);
                                                    router.reload({
                                                        data: {
                                                            renewal_search: renewalSearch,
                                                            renewal_page: newPage,
                                                            renewal_per_page: renewalPerPage
                                                        }
                                                    });
                                                }}
                                                disabled={renewalPage === lastPage}
                                                className="h-8 px-3"
                                            >
                                                {t('Next')}
                                                <ChevronRight className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <RefreshCw className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500 text-sm">{renewalSearch ? t('No renewals found') : t('No renewals yet')}</p>
                                <p className="text-gray-400 text-xs mt-1">{renewalSearch ? t('Try adjusting your search') : t('Create your first renewal')}</p>
                            </div>
                        );
                    })()}
                </CardContent>
            </Card>

            <Dialog open={renewalDialogOpen} onOpenChange={setRenewalDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editRenewalId ? t('Edit Contract Renewal') : t('Add Contract Renewal')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 my-3">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label required>{t('Start Date')}</Label>
                                <DatePicker
                                    value={renewalData.start_date}
                                    onChange={(value) => setRenewalData({ ...renewalData, start_date: value })}
                                    placeholder={t('Select Start Date')}
                                    required
                                />
                                <InputError message={renewalErrors.start_date} />
                            </div>
                            <div>
                                <Label required>{t('End Date')}</Label>
                                <DatePicker
                                    value={renewalData.end_date}
                                    onChange={(value) => setRenewalData({ ...renewalData, end_date: value })}
                                    placeholder={t('Select End Date')}
                                    required
                                />
                                <InputError message={renewalErrors.end_date} />
                            </div>
                        </div>
                        <div>
                            <CurrencyInput
                                label={t('Value')}
                                value={renewalData.value}
                                onChange={(value) => setRenewalData({ ...renewalData, value: value })}
                                error={renewalErrors.value}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="status" required>{t('Status')}</Label>
                            <Select value={renewalData.status} onValueChange={(value) => setRenewalData({ ...renewalData, status: value })}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-gray-600" />
                                            {t('Draft')}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-yellow-600" />
                                            {t('Pending')}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="approved">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-blue-600" />
                                            {t('Approved')}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="active">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-green-600" />
                                            {t('Active')}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="expired">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-orange-600" />
                                            {t('Expired')}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="cancelled">
                                        <div className="flex items-center gap-2">
                                            <div className="w-2 h-2 rounded-full bg-red-600" />
                                            {t('Cancelled')}
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={renewalErrors.status} />
                        </div>
                        <div>
                            <Label htmlFor="notes">{t('Notes')}</Label>
                            <Textarea
                                id="notes"
                                value={renewalData.notes}
                                onChange={(e) => setRenewalData({ ...renewalData, notes: e.target.value })}
                                placeholder={t('Enter Notes')}
                                rows={3}
                            />
                            <InputError message={renewalErrors.notes} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => {
                            setRenewalDialogOpen(false);
                            setEditRenewalId(null);
                            setRenewalData({ start_date: '', end_date: '', value: '', status: 'draft', notes: '' });
                            setRenewalErrors({});
                        }}>
                            {t('Cancel')}
                        </Button>
                        <Button onClick={handleRenewalSubmit}>
                            {editRenewalId ? t('Update') : t('Create')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={showRenewalDialogOpen} onOpenChange={setShowRenewalDialogOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <RefreshCw className="h-4 w-4" />
                            {t('Renewal Details')}
                        </DialogTitle>
                    </DialogHeader>
                    {getSelectedRenewal() && (
                        <div className="space-y-4 my-3">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">{t('Start Date')}</Label>
                                    <p className="text-sm mt-1">{formatDate(getSelectedRenewal().start_date)}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">{t('End Date')}</Label>
                                    <p className="text-sm mt-1">{formatDate(getSelectedRenewal().end_date)}</p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">{t('Value')}</Label>
                                    <p className="text-sm mt-1">{getSelectedRenewal().value ? formatCurrency(getSelectedRenewal().value) : '-'}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">{t('Status')}</Label>
                                    <div className="mt-1">
                                        <div className="flex items-center gap-2 text-sm font-medium">
                                            <div className={`w-2.5 h-2.5 rounded-full ${getSelectedRenewal().status === 'pending' ? 'bg-yellow-500' :
                                                    getSelectedRenewal().status === 'approved' ? 'bg-blue-500' :
                                                        getSelectedRenewal().status === 'active' ? 'bg-green-500' :
                                                            getSelectedRenewal().status === 'expired' ? 'bg-gray-500' :
                                                                getSelectedRenewal().status === 'rejected' ? 'bg-red-500' :
                                                                    'bg-gray-500'
                                                }`} />
                                            {t(getSelectedRenewal().status)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-gray-700">{t('Created By')}</Label>
                                <p className="text-sm mt-1">{getSelectedRenewal().creator?.name || '-'}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-gray-700">{t('Created At')}</Label>
                                <p className="text-sm mt-1">{formatDateTime(getSelectedRenewal().created_at)}</p>
                            </div>
                            {getSelectedRenewal().notes && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">{t('Notes')}</Label>
                                    <div className="mt-1 bg-gray-50 rounded-lg p-3">
                                        <p className="text-sm text-gray-700">{getSelectedRenewal().notes}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={() => {
                            setShowRenewalDialogOpen(false);
                            setShowRenewalId(null);
                        }}>
                            {t('Close')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}