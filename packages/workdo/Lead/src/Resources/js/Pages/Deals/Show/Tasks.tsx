import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { CheckSquare, Edit, Trash2, Plus } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate, formatTime } from '@/utils/helpers';
import { Deal } from '../types';
import { useFormFields } from '@/hooks/useFormFields';

interface TasksProps {
    deal: Deal;
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Tasks({ deal, onRegisterAddHandler }: TasksProps) {
    const { t } = useTranslation();

    useEffect(() => {
        onRegisterAddHandler(() => setTaskModalOpen(true));
    }, [onRegisterAddHandler]);
    
    const [taskModalOpen, setTaskModalOpen] = useState(false);
    const [editingTask, setEditingTask] = useState<any>(null);
    const [taskForm, setTaskForm] = useState({
        name: '',
        date: '',
        time: '',
        priority: 'Low',
        status: 'On Going',
        sync_to_google_calendar: false
    });

    const calendarFields = useFormFields('createCalendarSyncField', taskForm, (field, value) => {
        setTaskForm(prev => ({ ...prev, [field]: value }));
    }, {}, 'create', t, 'Lead');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'deal.tasks.destroy',
        defaultMessage: t('Are you sure you want to delete this task?')
    });

    const handleTaskSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingTask) {
            router.put(route('deal.tasks.update', editingTask.id), taskForm, {
                onSuccess: () => {
                    setTaskForm({ name: '', date: '', time: '', priority: 'Low', status: 'On Going' });
                    setTaskModalOpen(false);
                    setEditingTask(null);
                }
            });
        } else {
            router.post(route('deal.tasks.store'), {
                deal_id: deal.id,
                ...taskForm
            }, {
                onSuccess: () => {
                    setTaskForm({ name: '', date: '', time: '', priority: 'Low', status: 'On Going' });
                    setTaskModalOpen(false);
                }
            });
        }
    };

    const handleEditTask = (task: any) => {
        setEditingTask(task);
        setTaskModalOpen(true);
    };

    useEffect(() => {
        if (editingTask) {
            let formattedDate = '';
            if (editingTask.date) {
                const dateStr = editingTask.date.toString();
                if (dateStr.includes('T')) {
                    formattedDate = dateStr.split('T')[0];
                } else {
                    formattedDate = dateStr;
                }
            }
            setTaskForm({
                name: editingTask.name || '',
                date: formattedDate,
                time: editingTask.time || '',
                priority: editingTask.priority || 'Low',
                status: editingTask.status || 'On Going'
            });
        } else {
            setTaskForm({
                name: '',
                date: '',
                time: '',
                priority: 'Low',
                status: 'On Going'
            });
        }
    }, [editingTask]);

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={deal.tasks || []}
                        columns={[
                            {
                                key: 'name',
                                header: t('Name')
                            },
                            {
                                key: 'date',
                                header: t('Date'),
                                render: (value: string, task: any) => {
                                    const taskDate = new Date(value);
                                    const today = new Date();
                                    today.setHours(0, 0, 0, 0);
                                    taskDate.setHours(0, 0, 0, 0);
                                    const isExpired = taskDate < today && task.status !== 'Complete';
                                    
                                    return (
                                        <span className={isExpired ? 'text-red-600 font-medium' : ''}>
                                            {formatDate(value)}
                                        </span>
                                    );
                                }
                            },
                            {
                                key: 'time',
                                header: t('Time'),
                                render: (value: string) => formatTime(value)
                            },
                            {
                                key: 'priority',
                                header: t('Priority'),
                                render: (value: string) => {
                                    const getPriorityClass = (priority: string) => {
                                        switch (priority) {
                                            case 'Low': return 'bg-green-100 text-green-800';
                                            case 'Medium': return 'bg-yellow-100 text-yellow-800';
                                            case 'High': return 'bg-red-100 text-red-800';
                                            default: return 'bg-gray-100 text-gray-800';
                                        }
                                    };
                                    return (
                                        <span className={`px-2 py-1 rounded-full text-sm ${getPriorityClass(value)}`}>
                                            {t(value)}
                                        </span>
                                    );
                                }
                            },
                            {
                                key: 'status',
                                header: t('Status'),
                                render: (value: string) => {
                                    const getStatusClass = (status: string) => {
                                        switch (status) {
                                            case 'On Going': return 'bg-yellow-100 text-yellow-800';
                                            case 'Complete': return 'bg-green-100 text-green-800';
                                            default: return 'bg-gray-100 text-gray-800';
                                        }
                                    };
                                    return (
                                        <span className={`px-2 py-1 rounded-full text-sm ${getStatusClass(value)}`}>
                                            {t(value)}
                                        </span>
                                    );
                                }
                            },
                            {
                                key: 'actions',
                                header: t('Action'),
                                render: (_: any, task: any) => (
                                    <div className="flex gap-1">
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => handleEditTask(task)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Edit')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(task.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Delete')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                )
                            }
                        ]}
                        className="rounded-none"
                        emptyState={
                            <NoRecordsFound
                                icon={CheckSquare}
                                title={t('No Tasks found')}
                                description={t('Get started by creating your first Task.')}
                                onCreateClick={() => setTaskModalOpen(true)}
                                createButtonText={t('Create Task')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={taskModalOpen} onOpenChange={(open) => {
                setTaskModalOpen(open);
                if (!open) {
                    setEditingTask(null);
                    setTaskForm({ name: '', date: '', time: '', priority: 'Low', status: 'On Going' });
                }
            }}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{editingTask ? t('Edit Task') : t('Create Task')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleTaskSubmit} className="space-y-4">
                        <div>
                            <Label required>{t('Name')}</Label>
                            <input
                                type="text"
                                value={taskForm.name}
                                onChange={(e) => setTaskForm({...taskForm, name: e.target.value})}
                                className="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md"
                                placeholder={t('Enter task name')}
                                required
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <Label required>{t('Date')}</Label>
                                <DatePicker
                                    value={taskForm.date}
                                    onChange={(date) => setTaskForm({...taskForm, date: formatDate(date)})}
                                    placeholder={t('Select Date')}
                                />
                            </div>
                            <div>
                                <Label htmlFor="start_time">{t('Time')}</Label>
                                <Input
                                    id="start_time"
                                    required
                                    type="time"
                                    value={taskForm.time}
                                    onChange={(e) => setTaskForm({...taskForm, time: e.target.value})}
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="priority">{t('Priority')}</Label>
                                <Select value={taskForm.priority} onValueChange={(value) => setTaskForm({...taskForm, priority: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select priority')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Low">{t('Low')}</SelectItem>
                                        <SelectItem value="Medium">{t('Medium')}</SelectItem>
                                        <SelectItem value="High">{t('High')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="status">{t('Status')}</Label>
                                <Select value={taskForm.status} onValueChange={(value) => setTaskForm({...taskForm, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="On Going">{t('On Going')}</SelectItem>
                                        <SelectItem value="Complete">{t('Complete')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        
                        {/* Calendar Sync Field - Only show for create form */}
                        {!editingTask && calendarFields.map((field) => (
                            <div key={field.id}>
                                {field.component}
                            </div>
                        ))}
                        
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setTaskModalOpen(false)}>{t('Cancel')}</Button>
                            <Button type="submit">{editingTask ? t('Update') : t('Save')}</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Task')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </>
    );
}