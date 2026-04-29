import { PaginatedData, ModalState, AuthContext, CreateProps } from '@/types/common';

export interface ProjectTask {
    id: number;
    project_id: number;
    milestone_id?: number;
    title: string;
    priority: 'High' | 'Medium' | 'Low';
    assigned_to?: number;
    duration?: string;
    description?: string;
    project?: {
        id: number;
        name: string;
    };
    milestone?: {
        id: number;
        title: string;
    };
    assignedUser?: {
        id: number;
        name: string;
    };
    created_at: string;
}

export interface CreateProjectTaskFormData {
    project_id: number;
    milestone_id?: number;
    title: string;
    priority: 'High' | 'Medium' | 'Low';
    assigned_to?: number[];
    duration?: string;
    description?: string;
    stage_id?: number;
    sync_to_google_calendar?: boolean;
    sync_to_outlook_calendar?: boolean;
}

export interface ProjectTaskFilters {
    title: string;
    priority: string;
}

export interface Project {
    id: number;
    name: string;
    teamMembers?: Array<{
        id: number;
        name: string;
    }>;
}

export interface Milestone {
    id: number;
    title: string;
}

export interface TaskStage {
    id: number;
    name: string;
    color: string;
    order?: number;
}

export type PaginatedProjectTasks = PaginatedData<ProjectTask>;
export type ProjectTaskModalState = ModalState<ProjectTask> & {
    mode: 'add' | 'edit' | 'view' | '';
};

export interface ProjectTasksIndexProps {
    tasks: PaginatedProjectTasks;
    project?: Project;
    milestones: Milestone[];
    teamMembers: Array<{
        id: number;
        name: string;
    }>;
    taskStages: TaskStage[];
    auth: AuthContext;
    [key: string]: unknown;
}