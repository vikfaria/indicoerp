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
import EditTask from './Edit';
import ViewTask from './View';
import { formatDate } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';

interface Project {
    id: number;
    name: string;
}

interface TasksByStatus {
    [key: string]: KanbanTask[];
}

interface KanbanProps {
    project: Project;
    stages: Array<{ id: number; name: string; color: string; order: number; }>;
    tasks: TasksByStatus;
    milestones: Array<{ id: number; title: string; }>;
    teamMembers: Array<{ id: number; name: string; }>;
    taskStages: Array<{ id: number; name: string; color: string; }>;
    auth: { user?: { permissions?: string[] } };
}

type ModalMode = 'add' | 'edit' | 'view';

interface ModalState {
    isOpen: boolean;
    mode: ModalMode | '';
    data: KanbanTask | null;
    preSelectedStage?: number;
}

export default function Kanban() {
    const { t } = useTranslation();
    const { project, stages, tasks, milestones, teamMembers, taskStages, auth } = usePage<KanbanProps>().props;

    const [modalState, setModalState] = useState<ModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [deleteState, setDeleteState] = useState({ isOpen: false, taskId: null as number | null });

    const openDeleteDialog = (taskId: number) => {
        setDeleteState({ isOpen: true, taskId });
    };

    const closeDeleteDialog = () => {
        setDeleteState({ isOpen: false, taskId: null });
    };
    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Project Task', settingKey: 'GoogleDrive Task' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Task', settingKey: 'OneDrive Task' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Project Task', settingKey: 'Dropbox Project Task' });

    const confirmDelete = async () => {
        if (deleteState.taskId) {
            try {
                await axios.delete(route('project.tasks.destroy', deleteState.taskId));
                refreshTasks();
                closeDeleteDialog();
                toast.success(t('The task has been deleted successfully.'));
            } catch (error) {
                toast.error(t('Failed to delete task'));
            }
        }
    };

    const handleMove = async (taskId: number, fromStatus: string, toStatus: string) => {
        const stageId = stages.find(stage => stage.name.toLowerCase().replace(/\s+/g, '-') === toStatus)?.id;
        if (stageId) {
            try {
                const response = await axios.patch(route('project.tasks.move', taskId), { stage_id: stageId });
                refreshTasks();
                if (response.data.message) {
                    toast.success(t(response.data.message));
                }
            } catch (error) {
                console.error('Failed to move task:', error);
            }
        }
    };

    const openModal = (mode: ModalMode, data: KanbanTask | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const [currentTasks, setCurrentTasks] = useState(tasks);

    const refreshTasks = useCallback(async () => {
        try {
            const response = await axios.get(route('project.tasks.api', project.id));
            setCurrentTasks(response.data.tasks || tasks);
        } catch (error) {
            setCurrentTasks(tasks);
        }
    }, [project.id, tasks]);

    const handleTaskCreated = () => {
        setModalState({ isOpen: false, mode: '', data: null });
        router.reload();
    };


    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    useEffect(() => {
        setCurrentTasks(tasks);
    }, [tasks]);

    // Debug stage mapping
    const generatedColumns = stages.map(stage => ({
        id: stage.name.toLowerCase().replace(/\s+/g, '-'),
        title: stage.name,
        color: stage.color
    }));

    const TaskCard = ({ task }: { task: KanbanTask }) => {
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
                {/* Header with title and actions */}
                <div className="flex items-start justify-between mb-2">
                    <h4 className="font-medium text-sm text-gray-900 leading-tight pr-2 flex-1">{task.title}</h4>
                    {(auth.user?.permissions?.includes('view-project-task') ||
                      auth.user?.permissions?.includes('edit-project-task') ||
                      auth.user?.permissions?.includes('delete-project-task')) && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 shrink-0">
                                    <MoreVertical className="h-3 w-3" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {auth.user?.permissions?.includes('view-project-task') && (
                                <DropdownMenuItem onClick={() => openModal('view', { id: task.id })}>
                                    <Eye className="h-3 w-3 mr-2" />
                                    {t('View')}
                                </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('edit-project-task') && (
                                    <DropdownMenuItem onClick={() => openModal('edit', { id: task.id })}>
                                        <Edit className="h-3 w-3 mr-2" />
                                        {t('Edit')}
                                    </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('delete-project-task') && (
                                    <DropdownMenuItem onClick={() => openDeleteDialog(task.id)} className="text-red-600 hover:!text-red-600 focus:text-red-600">
                                        <Trash2 className="h-3 w-3 mr-2" />
                                        {t('Delete')}
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>

                {/* Priority badge */}
                {task.priority && (
                    <div className="mb-2">
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            task.priority === 'Low' ? 'bg-green-100 text-green-800' :
                            task.priority === 'Medium' ? 'bg-yellow-100 text-yellow-800' :
                            task.priority === 'High' ? 'bg-red-100 text-red-800' :
                            'bg-red-100 text-red-800'
                        }`}>
                            {t(task.priority)}
                        </span>
                    </div>
                )}
                {/* Milestone */}
                {task.milestone && (
                    <p className="text-xs text-gray-600 mb-3">{task.milestone}</p>
                )}

                {/* Footer with assignees and due date */}
                <div className="flex items-center justify-between mt-3 pt-2 border-t border-gray-100">
                    {/* Assigned users */}
                    <div className="flex items-center">
                        {task.assigned_users && task.assigned_users.length > 0 ? (
                            <div className="flex -space-x-1">
                                {task.assigned_users.slice(0, 3).map((user: any, index: number) => (
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
                                {task.assigned_users.length > 3 && (
                                    <div className="h-6 w-6 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center">
                                        <span className="text-xs text-gray-600">+{task.assigned_users.length - 3}</span>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <span className="text-xs text-gray-400">{t('Unassigned')}</span>
                        )}
                    </div>

                    {/* Duration */}
                    {task.due_date && (
                        <div className="flex items-center space-x-1 text-xs text-gray-500">
                            <Calendar className="h-3 w-3" />
                            <span>
                                {task.due_date.includes(' - ') ? (
                                    (() => {
                                        const [startDate, endDate] = task.due_date.split(' - ');
                                        return `${formatDate(startDate.trim())} - ${formatDate(endDate.trim())}`;
                                    })()
                                ) : (
                                    formatDate(task.due_date)
                                )}
                            </span>
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
                { label: t('Task Kanban') }
            ]}
            pageTitle={`${project.name} - ${t('Task Kanban')}`}
            backUrl={route('project.show', project.id)}
            pageActions={
                <div className="flex gap-2">
                    {googleDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {oneDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {dropboxBtn.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-project-task') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm"
                                        onClick={() => router.get(route('project.tasks.index', { project_id: project.id }))}
                                    >
                                        <List className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('List View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('create-project-task') && (
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
            <Head title={t('Task Kanban')} />

            <KanbanBoard
                tasks={currentTasks}
                columns={stages.map(stage => ({
                    id: stage.name.toLowerCase().replace(/\s+/g, '-'),
                    title: stage.name,
                    color: stage.color
                }))}
                onMove={handleMove}
                taskCard={TaskCard}
                kanbanActions={(columnId: string) => {
                    const stage = stages.find(s => s.name.toLowerCase().replace(/\s+/g, '-') === columnId);
                    return auth.user?.permissions?.includes('create-project-task') ? (
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
                        onSuccess={handleTaskCreated}
                        project={project}
                        milestones={milestones}
                        teamMembers={teamMembers}
                        taskStages={taskStages}
                        preSelectedStageId={modalState.preSelectedStage}
                    />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditTask
                        onSuccess={() => {
                            closeModal();
                            refreshTasks();
                        }}
                        task={{ id: modalState.data.id }}
                        project={project}
                        milestones={milestones}
                        teamMembers={teamMembers}
                        taskStages={taskStages}
                    />
                )}
                {modalState.mode === 'view' && modalState.data && (
                    <ViewTask
                        task={{ id: modalState.data.id }}
                        project={project}
                        milestones={milestones}
                        teamMembers={teamMembers}
                        taskStages={taskStages}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Task')}
                message={t('Are you sure you want to delete this task?')}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
