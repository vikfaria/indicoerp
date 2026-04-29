import React, { useState, useEffect } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import InputError from "@/components/ui/input-error";
import axios from 'axios';
import { useTranslation } from 'react-i18next';

export const createTasklyProjectTaskField = (data: any, setData: any, errors: any, mode: string = 'create') => {
    const { t } = useTranslation();
    const [projects, setProjects] = useState([]);
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [tasksLoading, setTasksLoading] = useState(false);

    const fetchProjects = async () => {
        try {
            const response = await axios.get(route('api.taskly.projects.index'));
            setProjects(response.data);
        } catch (error) {
            console.error('Error fetching projects:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchTasks = async (projectId: string) => {
        if (!projectId) {
            setTasks([]);
            return;
        }
        
        setTasksLoading(true);
        try {
            const response = await axios.get(route('api.taskly.projects.tasks', projectId));
            setTasks(response.data);
        } catch (error) {
            console.error('Error fetching tasks:', error);
            setTasks([]);
        } finally {
            setTasksLoading(false);
        }
    };

    useEffect(() => {
        fetchProjects();
    }, []);

    useEffect(() => {
        if (data.project_id) {
            fetchTasks(data.project_id);
        } else {
            setTasks([]);
            setData('task_id', '');
        }
    }, [data.project_id]);

    const handleProjectSelect = (projectId: string) => {
        setData('project_id', projectId);
        setData('task_id', '');
    };

    const handleTaskSelect = (taskId: string) => {
        setData('task_id', taskId);
    };

    const projectFieldId = mode === 'edit' ? 'edit_project_id' : 'project_id';
    const taskFieldId = mode === 'edit' ? 'edit_task_id' : 'task_id';

    return [
        {
            id: 'taskly-project',
            order: 2,
            component: (
                <div key={`project-${data.project_id}-${projects.length}`}>
                    <Label htmlFor={projectFieldId}>{t('Project')}</Label>
                    <Select value={data.project_id ? data.project_id.toString() : ''} onValueChange={handleProjectSelect}>
                        <SelectTrigger>
                            <SelectValue placeholder={loading ? t('Loading...') : t('Select a project')} />
                        </SelectTrigger>
                        <SelectContent>
                            {projects.map((project) => (
                                <SelectItem key={project.id} value={project.id.toString()}>
                                    <span className="font-medium">{project.name}</span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.project_id} />
                </div>
            )
        },
        {
            id: 'taskly-task',
            order: 3,
            component: (
                <div key={`task-${data.task_id}-${tasks.length}`}>
                    <Label htmlFor={taskFieldId}>{t('Task')} <span className="text-gray-500 text-sm">({t('Optional')})</span></Label>
                    <Select 
                        value={data.task_id ? data.task_id.toString() : ''} 
                        onValueChange={handleTaskSelect}
                        disabled={!data.project_id || tasksLoading}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={
                                !data.project_id ? t('Select a project first') :
                                tasksLoading ? t('Loading tasks...') :
                                tasks.length === 0 ? t('No tasks available') :
                                t('Select a task')
                            } />
                        </SelectTrigger>
                        <SelectContent>
                            {tasks.map((task) => (
                                <SelectItem key={task.id} value={task.id.toString()}>
                                    <span className="font-medium">{task.title}</span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.task_id} />
                </div>
            )
        }
    ];
};