import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { StickyNote, Edit, Trash2, ChevronLeft, ChevronRight } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchInput } from '@/components/ui/search-input';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { formatDateTime, getImagePath } from '@/utils/helpers';

interface NotesTabProps {
    contract: any;
    setDeleteConfig: (config: any) => void;
}

export default function NotesTab({ contract, setDeleteConfig }: NotesTabProps) {
    const { t } = useTranslation();
    const pageProps = usePage<any>().props;
    const { auth } = pageProps;
    const [noteDialogOpen, setNoteDialogOpen] = useState(false);
    const [editNoteId, setEditNoteId] = useState<number | null>(null);
    const [noteText, setNoteText] = useState('');
    const [createNoteText, setCreateNoteText] = useState('');
    const [notePage, setNotePage] = useState(1);
    const [notePerPage, setNotePerPage] = useState(10);
    const [noteSearch, setNoteSearch] = useState('');
    const [noteSearchInput, setNoteSearchInput] = useState('');

    const handleNoteSubmit = () => {
        if (editNoteId) {
            router.put(route('contract-notes.update', editNoteId), { note: noteText }, {
                onSuccess: () => {
                    setNoteDialogOpen(false);
                    setNoteText('');
                    setEditNoteId(null);
                    router.reload();
                }
            });
        } else {
            router.post(route('contract-notes.store', contract.id), { note: noteText }, {
                onSuccess: () => {
                    setNoteDialogOpen(false);
                    setNoteText('');
                    router.reload();
                }
            });
        }
    };

    const handleCreateNoteSubmit = () => {
        router.post(route('contract-notes.store', contract.id), { note: createNoteText }, {
            onSuccess: () => {
                setCreateNoteText('');
                router.reload();
            }
        });
    };

    const openEditNote = (note: any) => {
        setEditNoteId(note.id);
        setNoteText(note.note);
        setNoteDialogOpen(true);
    };

    return (
        <>
            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>{t('Notes')}</CardTitle>
                    <div className="flex items-center gap-3">
                        <SearchInput
                            value={noteSearchInput}
                            onChange={(value) => setNoteSearchInput(value)}
                            onSearch={() => {
                                setNoteSearch(noteSearchInput);
                                setNotePage(1);
                            }}
                            onClear={() => {
                                setNoteSearch('');
                                setNoteSearchInput('');
                                setNotePage(1);
                            }}
                            placeholder={t('Search notes...')}
                            className="w-64"
                        />
                        <Select value={notePerPage.toString()} onValueChange={(value) => {
                            setNotePerPage(Number(value));
                            setNotePage(1);
                        }}>
                            <SelectTrigger className="w-[120px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="5">{t('5 per page')}</SelectItem>
                                <SelectItem value="10">{t('10 per page')}</SelectItem>
                                <SelectItem value="20">{t('20 per page')}</SelectItem>
                                <SelectItem value="50">{t('50 per page')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent className="p-4">
                    {auth.user?.permissions?.includes('create-contract-notes') && (
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div className="flex items-start gap-3">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage src={auth.user?.avatar ? getImagePath(auth.user.avatar, pageProps) : auth.user?.profile_photo_url} alt={auth.user?.name} />
                                    <AvatarFallback className="text-xs bg-green-100 text-green-700">
                                        {auth.user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1">
                                    <p className="text-sm font-medium mb-2 text-green-900">{auth.user?.name}</p>
                                    <Textarea
                                        value={createNoteText}
                                        onChange={(e) => setCreateNoteText(e.target.value)}
                                        placeholder={t('Write your note...')}
                                        rows={4}
                                        className="resize-none mb-3 bg-white border-green-200 focus:border-green-300"
                                    />
                                    <div className="flex justify-end">
                                        <Button onClick={handleCreateNoteSubmit} disabled={!createNoteText.trim()} size="sm">
                                            {t('Send')}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {(() => {
                        const filteredNotes = contract.notes ? contract.notes.filter((note: any) =>
                            note.note.toLowerCase().includes(noteSearch.toLowerCase()) ||
                            note.user?.name.toLowerCase().includes(noteSearch.toLowerCase())
                        ) : [];

                        const paginatedNotes = {
                            data: filteredNotes.slice((notePage - 1) * notePerPage, notePage * notePerPage),
                            total: filteredNotes.length,
                            last_page: Math.ceil(filteredNotes.length / notePerPage)
                        };

                        return paginatedNotes.data.length > 0 ? (
                            <>
                                <div className="space-y-4">
                                    {paginatedNotes.data.map((note: any) => (
                                        <div key={note.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow group">
                                            <div className="flex items-start gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={note.user?.avatar ? getImagePath(note.user.avatar, pageProps) : note.user?.profile_photo_url} alt={note.user?.name} />
                                                    <AvatarFallback className="text-xs bg-green-100 text-green-700">
                                                        {note.user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <span className="text-sm font-medium">{note.user?.name}</span>
                                                        <span className="text-xs text-gray-500">{formatDateTime(note.created_at)}</span>
                                                        {(note.user_id === auth.user?.id || note.created_by === auth.user?.id) && (
                                                            <TooltipProvider>
                                                                <div className="flex gap-1">
                                                                    {auth.user?.permissions?.includes('edit-contract-notes') && (
                                                                        <Tooltip >
                                                                            <TooltipTrigger asChild>
                                                                                <Button variant="ghost" size="sm" onClick={() => openEditNote(note)} className="h-6 w-6 p-0 text-blue-600 hover:text-blue-700">
                                                                                    <Edit className="h-3 w-3" />
                                                                                </Button>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>{t('Edit')}</p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    )}
                                                                    {auth.user?.permissions?.includes('delete-contract-notes') && (
                                                                        <Tooltip >
                                                                            <TooltipTrigger asChild>
                                                                                <Button variant="ghost" size="sm" onClick={() => setDeleteConfig({
                                                                                    type: 'note',
                                                                                    id: note.id,
                                                                                    route: 'contract-notes.destroy',
                                                                                    message: t('Are you sure you want to delete this note?')
                                                                                })} className="h-6 w-6 p-0 text-destructive hover:text-destructive">
                                                                                    <Trash2 className="h-3 w-3" />
                                                                                </Button>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>{t('Delete')}</p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    )}
                                                                </div>
                                                            </TooltipProvider>
                                                        )}
                                                    </div>
                                                    <div className="mt-2 bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                        <p className="text-sm text-gray-700">{note.note}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {filteredNotes.length > notePerPage && (
                                    <div className="px-4 py-3 border-t bg-gray-50/30 mt-4">
                                        <div className="flex items-center justify-between">
                                            <div className="text-sm text-muted-foreground">
                                                {t('Showing')} <span className="font-medium text-foreground">{((notePage - 1) * notePerPage) + 1}</span> {t('to')} <span className="font-medium text-foreground">{Math.min(notePage * notePerPage, filteredNotes.length)}</span> {t('of')} <span className="font-medium text-foreground">{filteredNotes.length}</span> {t('results')}
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setNotePage(p => Math.max(1, p - 1))}
                                                    disabled={notePage === 1}
                                                    className="h-8 px-3"
                                                >
                                                    <ChevronLeft className="h-4 w-4" />
                                                    {t('Previous')}
                                                </Button>
                                                <div className="flex items-center space-x-1">
                                                    {Array.from({ length: Math.min(5, paginatedNotes.last_page) }, (_, i) => {
                                                        let pageNum;
                                                        if (paginatedNotes.last_page <= 5) {
                                                            pageNum = i + 1;
                                                        } else if (notePage <= 3) {
                                                            pageNum = i + 1;
                                                        } else if (notePage >= paginatedNotes.last_page - 2) {
                                                            pageNum = paginatedNotes.last_page - 4 + i;
                                                        } else {
                                                            pageNum = notePage - 2 + i;
                                                        }

                                                        return (
                                                            <Button
                                                                key={pageNum}
                                                                variant={notePage === pageNum ? "default" : "outline"}
                                                                size="sm"
                                                                onClick={() => setNotePage(pageNum)}
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
                                                    onClick={() => setNotePage(p => Math.min(paginatedNotes.last_page, p + 1))}
                                                    disabled={notePage === paginatedNotes.last_page}
                                                    className="h-8 px-3"
                                                >
                                                    {t('Next')}
                                                    <ChevronRight className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <StickyNote className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500 text-sm">{noteSearch ? t('No notes found') : t('No notes yet')}</p>
                                <p className="text-gray-400 text-xs mt-1">{noteSearch ? t('Try adjusting your search') : t('Be the first to add a note')}</p>
                            </div>
                        );
                    })()}
                </CardContent>
            </Card>

            {editNoteId && (
                <Dialog open={noteDialogOpen} onOpenChange={setNoteDialogOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader className="pb-4">
                            <DialogTitle className="flex items-center gap-2">
                                <StickyNote className="h-4 w-4" />
                                {t('Edit Note')}
                            </DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="flex gap-3">
                                <Avatar className="h-8 w-8 flex-shrink-0">
                                    <AvatarImage src={auth.user?.avatar ? getImagePath(auth.user.avatar, pageProps) : auth.user?.profile_photo_url} alt={auth.user?.name} />
                                    <AvatarFallback className="text-xs bg-green-100 text-green-700">
                                        {auth.user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900 mb-2">{auth.user?.name}</p>
                                    <Textarea
                                        value={noteText}
                                        onChange={(e) => setNoteText(e.target.value)}
                                        placeholder={t('Write your note...')}
                                        rows={4}
                                        className="resize-none border-gray-200 focus:border-green-300 focus:ring-green-200"
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter className="mt-6">
                            <Button variant="outline" onClick={() => {
                                setNoteDialogOpen(false);
                                setNoteText('');
                                setEditNoteId(null);
                            }}>
                                {t('Cancel')}
                            </Button>
                            <Button onClick={handleNoteSubmit} disabled={!noteText.trim()}>
                                {t('Update')}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}