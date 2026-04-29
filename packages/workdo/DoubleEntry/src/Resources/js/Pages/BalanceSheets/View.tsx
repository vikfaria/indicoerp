import React, { useState } from 'react';
import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle, FileText, Printer, Plus, GitCompare, LayoutGrid, Columns, Trash2, Calendar } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { BalanceSheetViewProps } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';
import Note from './Note';
import Compare from './Compare';
import Generate from './Generate';
import YearEndClose from './YearEndClose';

export default function View() {
    const { t } = useTranslation();
    const { balanceSheet, groupedItems, allBalanceSheets, otherBalanceSheets, auth } = usePage<BalanceSheetViewProps>().props;
    const [showNoteModal, setShowNoteModal] = useState(false);
    const [showCompareModal, setShowCompareModal] = useState(false);
    const [showGenerateModal, setShowGenerateModal] = useState(false);
    const [showYearEndModal, setShowYearEndModal] = useState(false);
    const [viewType, setViewType] = useState<'vertical' | 'horizontal'>('horizontal');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'double-entry.balance-sheets.delete-note',
        defaultMessage: t('Are you sure you want to delete this note?')
    });






    const handleFinalize = () => {
        router.post(route('double-entry.balance-sheets.finalize', balanceSheet.id), {}, {
            preserveState: true,
        });
    };





    const handleDeleteNote = (noteId: number) => {
        openDeleteDialog([balanceSheet.id, noteId]);
    };

    const renderSection = (sectionType: string, sectionTitle: string) => {
        const sectionItems = groupedItems[sectionType];
        if (!sectionItems) return null;

        let sectionTotal = 0;
        const sectionColors = {
            assets: 'bg-emerald-50 border-emerald-200 text-emerald-800',
            liabilities: 'bg-rose-50 border-rose-200 text-rose-800',
            equity: 'bg-blue-50 border-blue-200 text-blue-800'
        };

        return (
            <div className="mb-8">
                <h3 className="text-xl font-bold text-gray-800 mb-6">
                    {sectionTitle}
                </h3>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[60%]">{t('Account')}</TableHead>
                            <TableHead className="w-[20%] text-center">{t('Code')}</TableHead>
                            <TableHead className="w-[20%] text-right">{t('Amount')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {Object.entries(sectionItems).map(([subSection, items]) => {
                            const subSectionTotal = items.reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0);
                            sectionTotal += subSectionTotal;

                            return (
                                <React.Fragment key={subSection}>
                                    <TableRow key={`${subSection}-header`}>
                                        <TableCell colSpan={3} className="font-semibold text-gray-700 capitalize">
                                            {subSection.replace('_', ' ')}
                                        </TableCell>
                                    </TableRow>
                                    {items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium text-green-600">
                                                {item.account?.account_name}
                                            </TableCell>
                                            <TableCell className="text-center text-green-600">
                                                {item.account?.account_code}
                                            </TableCell>
                                            <TableCell className="text-right font-semibold text-green-600 tabular-nums">
                                                {formatCurrency(item.amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow key={`${subSection}-total`} className="border-b-2">
                                        <TableCell className="font-semibold">
                                            {t('Total')} {subSection.replace('_', ' ')}
                                        </TableCell>
                                        <TableCell></TableCell>
                                        <TableCell className="text-right font-bold tabular-nums">
                                            {formatCurrency(subSectionTotal)}
                                        </TableCell>
                                    </TableRow>
                                </React.Fragment>
                            );
                        })}
                        <TableRow className="border-t-2 border-gray-400">
                            <TableCell className="font-bold text-lg">
                                {t('TOTAL')} {sectionTitle.toUpperCase()}
                            </TableCell>
                            <TableCell></TableCell>
                            <TableCell className="text-right font-bold text-lg tabular-nums">
                                {formatCurrency(sectionTotal)}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Double Entry')},
                {label: t('Balance Sheets'), url: route('double-entry.balance-sheets.list')},
                {label: `${t('Balance Sheet')} - ${formatDate(balanceSheet.balance_sheet_date)}`}
            ]}
            pageTitle={`${t('Balance Sheet')} - ${formatDate(balanceSheet.balance_sheet_date)}`}
            pageActions={
                <div className="flex items-center gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-balance-sheets') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="outline" size="sm" onClick={() => router.get(route('double-entry.balance-sheets.list'))}>
                                        <FileText className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('All Balance Sheets')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                          {auth.user?.permissions?.includes('view-balance-sheet-comparisons') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="outline" size="sm" onClick={() => router.get(route('double-entry.balance-sheets.comparisons'))}>
                                        <GitCompare className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View Comparisons')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('year-end-close') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="outline" size="sm" onClick={() => setShowYearEndModal(true)}>
                                        <Calendar className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Year-End Close')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        <div className="flex items-center border rounded-lg">
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant={viewType === 'vertical' ? 'default' : 'ghost'}
                                        size="sm"
                                        onClick={() => setViewType('vertical')}
                                        className="rounded-r-none"
                                    >
                                        <LayoutGrid className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Vertical View')}</p>
                                </TooltipContent>
                            </Tooltip>
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant={viewType === 'horizontal' ? 'default' : 'ghost'}
                                        size="sm"
                                        onClick={() => setViewType('horizontal')}
                                        className="rounded-l-none"
                                    >
                                        <Columns className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Horizontal View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                        {auth.user?.permissions?.includes('create-balance-sheets') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => setShowGenerateModal(true)}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Generate Balance Sheet')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={`${t('Balance Sheet')} - ${formatDate(balanceSheet.balance_sheet_date)}`} />

            <div className="max-w-7xl mx-auto space-y-6">
                <Note
                    open={showNoteModal}
                    onOpenChange={setShowNoteModal}
                    balanceSheetId={balanceSheet.id}
                />

                <Compare
                    open={showCompareModal}
                    onOpenChange={setShowCompareModal}
                    balanceSheetId={balanceSheet.id}
                    otherBalanceSheets={otherBalanceSheets}
                />

                <Generate
                    open={showGenerateModal}
                    onOpenChange={setShowGenerateModal}
                />

                <YearEndClose
                    open={showYearEndModal}
                    onOpenChange={setShowYearEndModal}
                />
                {/* Header Card */}
                <Card className="shadow-lg border-0 bg-gradient-to-r from-white to-gray-50">
                    <CardHeader className="pb-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-50 rounded-lg border flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <CardTitle className="text-xl">
                                        {t('Balance Sheet')}
                                    </CardTitle>
                                    <p className="text-sm text-gray-600">
                                        {t('As of')} {formatDate(balanceSheet.balance_sheet_date)} | {t('Financial Year')}: {balanceSheet.financial_year}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {allBalanceSheets && allBalanceSheets.length > 0 && (
                                    <Select
                                        value={balanceSheet.id.toString()}
                                        onValueChange={(value) => router.visit(route('double-entry.balance-sheets.show', value))}
                                    >
                                        <SelectTrigger className="w-[200px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {allBalanceSheets.map((sheet) => (
                                                <SelectItem key={sheet.id} value={sheet.id.toString()}>
                                                    {formatDate(sheet.balance_sheet_date)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                                {auth.user?.permissions?.includes('create-balance-sheet-notes') && (
                                    <Button variant="outline" size="sm" onClick={() => setShowNoteModal(true)}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t('Add Note')}
                                    </Button>
                                )}
                                {auth.user?.permissions?.includes('create-balance-sheet-comparisons') && (
                                    <Button variant="outline" size="sm" onClick={() => setShowCompareModal(true)}>
                                        <GitCompare className="h-4 w-4 mr-2" />
                                        {t('Compare')}
                                    </Button>
                                )}
                                {auth.user?.permissions?.includes('print-balance-sheets') && (
                                    <Button variant="outline" size="sm" onClick={() => {
                                        const printUrl = route('double-entry.balance-sheets.print', balanceSheet.id) + '?download=pdf';
                                        window.open(printUrl, '_blank');
                                    }}>
                                        <Printer className="h-4 w-4 mr-2" />
                                        {t('Download PDF')}
                                    </Button>
                                )}
                                {auth.user?.permissions?.includes('finalize-balance-sheets') &&
                                 balanceSheet.status === 'draft' &&
                                 balanceSheet.is_balanced && (
                                    <Button size="sm" onClick={handleFinalize}>
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        {t('Finalize')}
                                    </Button>
                                )}
                                <div className="flex items-center gap-2 ml-2">
                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                        balanceSheet.is_balanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {t(balanceSheet.is_balanced ? 'Balanced' : 'Unbalanced')}
                                    </span>
                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                        balanceSheet.status === 'finalized' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {t(balanceSheet.status === 'finalized' ? 'Finalized' : 'Draft')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center p-6 bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl border border-emerald-200 shadow-sm hover:shadow-md transition-shadow">
                                <h4 className="font-semibold text-emerald-700 mb-2">{t('Total Assets')}</h4>
                                <p className="text-3xl font-bold text-emerald-900 tabular-nums">
                                    {formatCurrency(balanceSheet.total_assets)}
                                </p>
                            </div>
                            <div className="text-center p-6 bg-gradient-to-br from-rose-50 to-rose-100 rounded-xl border border-rose-200 shadow-sm hover:shadow-md transition-shadow">
                                <h4 className="font-semibold text-rose-700 mb-2">{t('Total Liabilities')}</h4>
                                <p className="text-3xl font-bold text-rose-900 tabular-nums">
                                    {formatCurrency(balanceSheet.total_liabilities)}
                                </p>
                            </div>
                            <div className="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 shadow-sm hover:shadow-md transition-shadow">
                                <h4 className="font-semibold text-blue-700 mb-2">{t('Total Equity')}</h4>
                                <p className="text-3xl font-bold text-blue-900 tabular-nums">
                                    {formatCurrency(balanceSheet.total_equity)}
                                </p>
                            </div>
                        </div>

                        {!balanceSheet.is_balanced && (
                            <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p className="text-red-800 font-medium">
                                    ⚠️ {t('Warning: This balance sheet is not balanced!')}
                                </p>
                                <p className="text-red-700 text-sm mt-1">
                                    {t('Assets should equal Liabilities + Equity. Please review the accounts and transactions.')}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Balance Sheet Content */}
                <Card className="shadow-lg border-0">
                    <CardContent className="p-8">
                        <div className="mb-8">
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">
                                {t('Balance Sheet of')} {formatDate(balanceSheet.balance_sheet_date)}
                            </h2>
                        </div>

                        {viewType === 'vertical' ? (
                            <>
                                {renderSection('assets', t('ASSETS'))}
                                {renderSection('liabilities', t('LIABILITIES'))}
                                {renderSection('equity', t('EQUITY'))}


                            </>
                        ) : (
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                {/* Left Side - Liabilities & Equity */}
                                <div>
                                    <h3 className="text-xl font-bold text-gray-800 mb-6">{t('Liabilities & Equity')}</h3>

                                    {/* Equity */}
                                    {groupedItems.equity && (
                                        <div className="mb-6">
                                            <h4 className="font-semibold text-gray-700 mb-3">{t('Equity')}</h4>
                                            {Object.entries(groupedItems.equity).map(([subSection, items]) => (
                                                <div key={subSection} className="mb-4">
                                                    {items.map((item) => (
                                                        <div key={item.id} className="flex justify-between py-1 text-sm">
                                                            <span className="text-green-600">{item.account?.account_name}</span>
                                                            <span className="text-green-600 tabular-nums">{formatCurrency(item.amount)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            ))}
                                            <div className="flex justify-between py-2 font-semibold border-b">
                                                <span>{t('Total for Equity')}</span>
                                                <span className="tabular-nums">{formatCurrency(balanceSheet.total_equity)}</span>
                                            </div>
                                        </div>
                                    )}

                                    {/* Liabilities */}
                                    {groupedItems.liabilities && (
                                        <div className="mb-6">
                                            <h4 className="font-semibold text-gray-700 mb-3">{t('Liabilities')}</h4>
                                            {Object.entries(groupedItems.liabilities).map(([subSection, items]) => {
                                                const subTotal = items.reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0);
                                                return (
                                                    <div key={subSection} className="mb-4">
                                                        <h5 className="font-medium text-gray-600 mb-2 capitalize">{subSection.replace('_', ' ')}</h5>
                                                        {items.map((item) => (
                                                            <div key={item.id} className="flex justify-between items-center py-1 text-sm ml-4">
                                                                <div className="flex justify-between w-full">
                                                                    <span className="text-green-600">{item.account?.account_name}</span>
                                                                    <div className="flex gap-8">
                                                                        <span className="text-gray-600">{item.account?.account_code}</span>
                                                                        <span className="text-green-600 tabular-nums">{formatCurrency(item.amount)}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                        <div className="flex justify-between py-2 font-medium border-b ml-4">
                                                            <span>Total {subSection.replace('_', ' ')}</span>
                                                            <span className="tabular-nums">{formatCurrency(subTotal)}</span>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                            <div className="flex justify-between py-2 font-semibold border-b">
                                                <span>{t('Total for Liabilities')}</span>
                                                <span className="tabular-nums">{formatCurrency(balanceSheet.total_liabilities)}</span>
                                            </div>
                                        </div>
                                    )}


                                </div>

                                {/* Right Side - Assets */}
                                <div>
                                    <h3 className="text-xl font-bold text-gray-800 mb-6">{t('Assets')}</h3>

                                    {groupedItems.assets && (
                                        <div>
                                            <h4 className="font-semibold text-gray-700 mb-3">{t('Assets')}</h4>
                                            {Object.entries(groupedItems.assets).map(([subSection, items]) => {
                                                const subTotal = items.reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0);
                                                return (
                                                    <div key={subSection} className="mb-6">
                                                        <h5 className="font-medium text-gray-600 mb-2 capitalize">{subSection.replace('_', ' ')}</h5>
                                                        {items.map((item) => (
                                                            <div key={item.id} className="flex justify-between items-center py-1 text-sm ml-4">
                                                                <div className="flex justify-between w-full">
                                                                    <span className="text-green-600">{item.account?.account_name}</span>
                                                                    <div className="flex gap-8">
                                                                        <span className="text-gray-600">{item.account?.account_code}</span>
                                                                        <span className="text-green-600 tabular-nums">{formatCurrency(item.amount)}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                        <div className="flex justify-between py-2 font-medium border-b ml-4">
                                                            <span>Total {subSection.replace('_', ' ')}</span>
                                                            <span className="tabular-nums">{formatCurrency(subTotal)}</span>
                                                        </div>
                                                    </div>
                                                );
                                            })}

                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Balance Totals - Always Show */}
                        <div className="mt-8 pt-6 border-t-2 border-gray-400">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                <div className="flex justify-between py-3 font-bold text-lg">
                                    <span>{t('Total for Liabilities & Equity')}</span>
                                    <span className="tabular-nums">
                                        {formatCurrency(
                                            (groupedItems.liabilities ?
                                                Object.values(groupedItems.liabilities).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0) +
                                            (groupedItems.equity ?
                                                Object.values(groupedItems.equity).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0)
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between py-3 font-bold text-lg">
                                    <span>{t('Total for Assets')}</span>
                                    <span className="tabular-nums">
                                        {formatCurrency(
                                            groupedItems.assets ?
                                                Object.values(groupedItems.assets).flat().reduce((sum, item) => sum + parseFloat(item.amount.toString()), 0) : 0
                                        )}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Notes Section */}
                        {balanceSheet.notes && balanceSheet.notes.length > 0 && (
                            <div className="mt-8 pt-6 border-t">
                                <h3 className="text-lg font-semibold mb-4">{t('Notes to Balance Sheet')}</h3>
                                <div className="space-y-4">
                                    {balanceSheet.notes.map((note: any) => (
                                        <div key={note.id} className="p-4 bg-gray-50 rounded-lg">
                                            <div className="flex justify-between items-start">
                                                <div className="flex-1">
                                                    <h4 className="font-medium">
                                                        {t('Note')} {note.note_number}: {note.note_title}
                                                    </h4>
                                                    <p className="text-gray-700 mt-2">{note.note_content}</p>
                                                </div>
                                                {auth.user?.permissions?.includes('delete-balance-sheet-notes') && (
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeleteNote(note.id)}
                                                                className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{t('Delete Note')}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Note')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />
            </div>
        </AuthenticatedLayout>
    );
}
