import { useState, useCallback, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Calendar, Edit, Trash2, MoreVertical, Users, List, Eye, User } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';
import KanbanBoard, { KanbanTask, KanbanColumn } from '@/components/kanban-board';
import Create from './Create';
import EditBug from './Edit';
import ViewBug from './View';
import { usePageButtons } from '@/hooks/usePageButtons';

interface Project {
    id: number;
    name: string;
}

interface BugsByStatus {
    [key: string]: KanbanTask[];
}

interface BugKanbanProps {
    project: Project;
    stages: Array<{ id: number; name: string; color: string; order: number; }>;
    bugs: BugsByStatus;
    teamMembers: Array<{ id: number; name: string; }>;
    bugStages: Array<{ id: number; name: string; color: string; }>;
    auth: { user?: { permissions?: string[] } };
}

type ModalMode = 'add' | 'edit' | 'view';

interface ModalState {
    isOpen: boolean;
    mode: ModalMode | '';
    data: KanbanTask | null;
    preSelectedStage?: number;
}

export default function BugKanban() {
    const { t } = useTranslation();
    const { project, stages, bugs, teamMembers, bugStages, auth } = usePage<BugKanbanProps>().props;

    const pageButtons = usePageButtons('googleDriveBtn', { module: 'Project Bug', settingKey: 'GoogleDrive Bug' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Bug', settingKey: 'OneDrive Bug' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Project Bug', settingKey: 'Dropbox Project Bug' });

    const [modalState, setModalState] = useState<ModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [deleteState, setDeleteState] = useState({ isOpen: false, bugId: null as number | null });

    const openDeleteDialog = (bugId: number) => {
        setDeleteState({ isOpen: true, bugId });
    };

    const closeDeleteDialog = () => {
        setDeleteState({ isOpen: false, bugId: null });
    };

    const confirmDelete = async () => {
        if (deleteState.bugId) {
            try {
                const response = await axios.delete(route('project.bugs.destroy', deleteState.bugId));
                refreshBugs();
                closeDeleteDialog();
                toast.success(response.data.message);
            } catch (error) {
                toast.error(t('Failed to delete bug'));
            }
        }
    };

    const handleMove = async (bugId: number, fromStatus: string, toStatus: string) => {
        const stageId = stages.find(stage => stage.name.toLowerCase().replace(/\s+/g, '-') === toStatus)?.id;
        if (stageId) {
            try {
                const response = await axios.patch(route('project.bugs.move', bugId), { stage_id: stageId });
                refreshBugs();
                toast.success(t('The bug has been moved successfully.'));
            } catch (error) {
                toast.error(t('Failed to move bug'));
            }
        }
    };

    const openModal = (mode: ModalMode, data: KanbanTask | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const [currentBugs, setCurrentBugs] = useState(bugs);

    const refreshBugs = useCallback(async () => {
        try {
            const response = await axios.get(route('project.bugs.api', project.id));
            setCurrentBugs(response.data.bugs);
        } catch (error) {
            console.error('Failed to refresh bugs:', error);
        }
    }, [project.id]);

    const handleBugCreated = () => {
        setModalState({ isOpen: false, mode: '', data: null });
        refreshBugs();
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    useEffect(() => {
        refreshBugs();
    }, [refreshBugs]);



    const BugCard = ({ task }: { task: KanbanTask }) => {
        const handleDragStart = (e: React.DragEvent) => {
            e.dataTransfer.setData('application/json', JSON.stringify({ taskId: task.id }));
            e.dataTransfer.effectAllowed = 'move';
        };

        return (
            <div
                className="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2 hover:shadow-md transition-all cursor-move select-none group"
                draggable={true}
                onDragStart={handleDragStart}
            >
                <div className="flex items-start justify-between mb-2">
                    <h4 className="font-medium text-sm text-gray-900 leading-tight pr-2">{task.title}</h4>
                    <div className="flex items-center gap-1">
                        {task.priority && (
                            <Badge className={`text-xs shrink-0 pointer-events-none ${
                                task.priority === 'Low' ? 'bg-green-100 text-green-800' :
                                task.priority === 'Medium' ? 'bg-yellow-100 text-yellow-800' :
                                task.priority === 'High' ? 'bg-orange-100 text-orange-800' :
                                'bg-red-100 text-red-800'
                            }`}>
                                {task.priority}
                            </Badge>
                        )}
                        {(auth.user?.permissions?.includes('view-project-bug') ||
                          auth.user?.permissions?.includes('edit-project-bug') ||
                          auth.user?.permissions?.includes('delete-project-bug')) && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100">
                                    <MoreVertical className="h-3 w-3" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {auth.user?.permissions?.includes('view-project-bug') && (
                                <DropdownMenuItem onClick={() => {
                                    openModal('view', { id: task.id });
                                }}>
                                    <Eye className="h-3 w-3 mr-2" />
                                    {t('View')}
                                </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('edit-project-bug') && (
                                    <DropdownMenuItem onClick={() => {
                                        openModal('edit', { id: task.id });
                                    }}>
                                        <Edit className="h-3 w-3 mr-2" />
                                        {t('Edit')}
                                    </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('delete-project-bug') && (
                                    <DropdownMenuItem onClick={() => openDeleteDialog(task.id)} className="text-red-600">
                                        <Trash2 className="h-3 w-3 mr-2" />
                                        {t('Delete')}
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                        )}
                    </div>
                </div>

                {task.description && (
                    <p className="text-xs text-gray-600 mb-3 line-clamp-2">{task.description}</p>
                )}

                <div className="flex items-center justify-between mt-3">
                    {task.assigned_users && task.assigned_users.length > 0 ? (
                        <div className="flex -space-x-1">
                            {task.assigned_users.slice(0, 2).map((user: any, index: number) => (
                                <TooltipProvider key={index}>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger>
                                            <div className="h-8 w-8 rounded-full border-2 border-white overflow-hidden bg-gray-100 flex items-center justify-center">
                                                {user.avatar ? (
                                                    <img
                                                        src={getImagePath(user.avatar)}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <User className="h-3 w-3 text-gray-400" />
                                                )}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{user.name}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            ))}
                            {task.assigned_users.length > 2 && (
                                <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center">
                                    <span className="text-xs text-gray-600">+{task.assigned_users.length - 2}</span>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="h-6 w-6" />
                    )}

                    {task.due_date && (
                        <div className="flex items-center space-x-1 text-xs px-2 py-1 rounded bg-gray-100 text-gray-600">
                            <Calendar className="h-3 w-3" />
                            <span>{task.due_date}</span>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Project'), url: route('project.index') },
                { label: project.name, url: route('project.show', project.id) },
                { label: t('Bug') }
            ]}
            pageTitle={`${project.name} - ${t('Bug')}`}
            backUrl={route('project.show', project.id)}
            pageActions={
                <div className="flex gap-2">
                    {pageButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                     {oneDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {dropboxBtn.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm"
                                        onClick={() => router.get(route('project.bugs.index', { project_id: project.id }))}
                                    >
                                        <List className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('List View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('create-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Bug Kanban')} />

            <KanbanBoard
                tasks={currentBugs}
                columns={stages.map(stage => ({
                    id: stage.name.toLowerCase().replace(/\s+/g, '-'),
                    title: stage.name,
                    color: stage.color
                }))}
                onMove={handleMove}
                taskCard={BugCard}
                kanbanActions={(columnId: string) => {
                    const stage = stages.find(s => s.name.toLowerCase().replace(/\s+/g, '-') === columnId);
                    return auth.user?.permissions?.includes('create-project-bug') ? (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-6 w-6 p-0 hover:bg-white/50"
                                    onClick={() => {
                                        setModalState({
                                            isOpen: true,
                                            mode: 'add',
                                            data: null,
                                            preSelectedStage: stage?.id
                                        });
                                    }}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Create')}</p>
                            </TooltipContent>
                        </Tooltip>
                    ) : null;
                }}
            />
            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create
                        onSuccess={handleBugCreated}
                        project={project}
                        teamMembers={teamMembers}
                        bugStages={bugStages}
                        preSelectedStageId={modalState.preSelectedStage}
                    />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditBug
                        onSuccess={() => {
                            closeModal();
                            refreshBugs();
                        }}
                        bug={{ id: modalState.data.id }}
                        project={project}
                        teamMembers={teamMembers}
                        bugStages={bugStages}
                    />
                )}
                {modalState.mode === 'view' && modalState.data && (
                    <ViewBug
                        bug={{ id: modalState.data.id }}
                        project={project}
                        teamMembers={teamMembers}
                        bugStages={bugStages}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Bug')}
                message={t('Are you sure you want to delete this bug?')}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
