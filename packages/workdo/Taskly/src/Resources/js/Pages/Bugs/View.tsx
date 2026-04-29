import { useState, useEffect } from 'react';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Trash2, User } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';
import { formatDate, getImagePath } from '@/utils/helpers';

interface ViewBugProps {
    bug: { id: number };
}

export default function View({ bug }: ViewBugProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [bugData, setBugData] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    // Check permissions
    const canManageComments = auth.user?.permissions?.includes('manage-project-bug-comments');
    const canCreateComments = auth.user?.permissions?.includes('create-project-bug-comments');
    const canDeleteComments = auth.user?.permissions?.includes('delete-project-bug-comments');

    useEffect(() => {
        const fetchBugData = async () => {
            try {
                const response = await axios.get(route('project.bugs.show', bug.id));
                setBugData(response.data.bug);
            } catch (error) {
                toast.error(t('Failed to load bug data'));
            } finally {
                setLoading(false);
            }
        };

        fetchBugData();
    }, [bug.id]);

    if (loading) {
        return (
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{t('Bug Details')}</DialogTitle>
                </DialogHeader>
                <div className="flex items-center justify-center py-8">
                    <p className="text-sm text-gray-500">{t('Loading bug details...')}</p>
                </div>
            </DialogContent>
        );
    }

    if (!bugData) return null;

    const assignedUsers = bugData.assignedUsers || [];

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Bug Details')}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <h3 className="text-lg font-semibold text-gray-900">{bugData.title}</h3>
                    </div>
                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            bugData.priority === 'Low' ? 'bg-green-100 text-green-800' :
                            bugData.priority === 'Medium' ? 'bg-yellow-100 text-yellow-800' :
                            bugData.priority === 'High' ? 'bg-red-100 text-red-800' :
                            'bg-red-100 text-red-800'
                        }`}>
                            {t(bugData.priority)}
                    </span>

                </div>

                {bugData.description && (
                    <div>
                        <h4 className="text-sm font-medium text-gray-700 mb-2">{t('Description')}</h4>
                        <p className="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">{bugData.description}</p>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-6">
                    <div>
                        <h4 className="text-sm font-medium text-gray-700 mb-2">{t('Project')}</h4>
                        <p className="text-sm text-gray-900">{bugData.project?.name || '-'}</p>
                    </div>

                    <div>
                        <h4 className="text-sm font-medium text-gray-700 mb-2">{t('Status')}</h4>
                        {bugData.stage?.name ? (
                            <span className="px-2 py-1 rounded-full text-sm" style={{ backgroundColor: `${bugData.stage?.color || '#e5e7eb'}30`, color: '#374151' }}>
                                {t(bugData.stage.name)}
                            </span>
                        ) : (
                            <span className="text-sm text-gray-900">-</span>
                        )}
                    </div>
                </div>

                <div>
                    <h4 className="text-sm font-medium text-gray-700 mb-3">{t('Assigned To')}</h4>
                    {assignedUsers.length > 0 ? (
                        <div className="flex flex-wrap gap-2">
                            {assignedUsers.map((user: any, index: number) => (
                                <div key={index} className="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-md">
                                    <div className="h-8 w-8 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                        <img
                                            src={user.avatar ? getImagePath(user.avatar) : getImagePath('avatar.png')}
                                            alt={user.name}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                    <span className="text-sm text-gray-900">{user.name}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">{t('No users assigned')}</p>
                    )}
                </div>

                {/* Comments Section */}
                {(canManageComments || canCreateComments) && (
                    <Tabs defaultValue="comments" className="w-full">
                        <TabsList className="grid w-full grid-cols-1">
                            <TabsTrigger value="comments">{t('Comments')}</TabsTrigger>
                        </TabsList>

                        <TabsContent value="comments" className="space-y-4">
                            <CommentsTab bugId={bugData.id} canManageComments={canManageComments} canCreateComments={canCreateComments} canDeleteComments={canDeleteComments} />
                        </TabsContent>
                    </Tabs>
                )}
            </div>
        </DialogContent>
    );
}

function CommentsTab({ bugId, canManageComments, canCreateComments, canDeleteComments }: { bugId: number; canManageComments: boolean; canCreateComments: boolean; canDeleteComments: boolean }) {
    const { t } = useTranslation();
    const [comment, setComment] = useState('');
    const [comments, setComments] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [loadingComments, setLoadingComments] = useState(true);
    const [deleteState, setDeleteState] = useState({ isOpen: false, commentId: null, message: '' });

    const openDeleteDialog = (commentId: number) => {
        setDeleteState({ isOpen: true, commentId, message: t('Are you sure you want to delete this comment?') });
    };

    const closeDeleteDialog = () => {
        setDeleteState({ isOpen: false, commentId: null, message: '' });
    };

    const confirmDelete = async () => {
        if (!deleteState.commentId) return;
        try {
            const response = await axios.delete(route('project.bugs.comments.destroy', deleteState.commentId));
            toast.success(response.data.message);
            fetchComments();
            closeDeleteDialog();
        } catch (error: any) {
            toast.error(error.response?.status === 403 ? t('Permission denied') : t('Failed to delete comment'));
        }
    };

    const fetchComments = async () => {
        try {
            const response = await axios.get(route('project.bugs.comments.index', bugId));
            setComments(response.data.comments);
        } catch (error: any) {
            toast.error(error.response?.status === 403 ? t('Permission denied') : t('Failed to load comments'));
        } finally {
            setLoadingComments(false);
        }
    };

    useEffect(() => {
        fetchComments();
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!comment.trim()) return;
        setLoading(true);
        try {
            const response = await axios.post(route('project.bugs.comments.store', bugId), { comment });
            setComment('');
            if (response.data.message) {
                toast.success(t(response.data.message));
            }
            fetchComments();
        } catch (error: any) {
            toast.error(error.response?.status === 403 ? t('Permission denied') : t('Failed to add comment'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            {(canManageComments || canCreateComments) && (
                <form onSubmit={handleSubmit} className="space-y-3">
                    <div>
                        <Label htmlFor="comment">{t('Add Comment')}</Label>
                        <Textarea
                            id="comment"
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            placeholder={t('Enter your comment...')}
                            rows={3}
                        />
                    </div>
                    <Button type="submit" disabled={loading || !comment.trim()}>
                        {loading ? t('Adding...') : t('Add Comment')}
                    </Button>
                </form>
            )}

            {loadingComments ? (
                <div className="text-center py-4">
                    <p className="text-sm text-gray-500">{t('Loading comments...')}</p>
                </div>
            ) : comments.length > 0 ? (
                <div className="space-y-3">
                    {comments.map((comment) => (
                        <div key={comment.id} className="bg-gray-50 p-3 rounded-md">
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="h-8 w-8 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                        {comment.user.avatar ? (
                                            <img
                                                src={getImagePath(comment.user.avatar)}
                                                alt={comment.user.name}
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <User className="h-3 w-3 text-gray-400" />
                                        )}
                                    </div>
                                    <span className="text-sm font-medium text-gray-900">{comment.user.name}</span>
                                    <span className="text-xs text-gray-500">
                                        {formatDate(comment.created_at)}
                                    </span>
                                </div>
                                {auth.user?.permissions?.includes('delete-project-bug-comments') && (
                                    <TooltipProvider>
                                        <Tooltip delayDuration={0}>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openDeleteDialog(comment.id)}
                                                    className="h-6 w-6 p-0 text-red-600 hover:text-red-700 mt-1"
                                                >
                                                    <Trash2 className="h-3 w-3" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('Delete')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                )}
                            </div>
                            <p className="text-sm text-gray-700">{comment.comment}</p>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="text-center py-4">
                    <p className="text-sm text-gray-500">{t('No comments yet')}</p>
                </div>
            )}

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Comment')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </div>
    );
}
