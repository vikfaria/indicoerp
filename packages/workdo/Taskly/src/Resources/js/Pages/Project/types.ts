import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Project {
    id: number;
    name: string;
    description?: string;
    budget?: number;
    start_date?: string;
    end_date?: string;
    status: 'Ongoing' | 'Onhold' | 'Finished';
    team_members?: Array<{
        id: number;
        name: string;
    }>;
    created_at: string;
}

export interface ProjectFormData {
    name: string;
    description?: string;
    budget?: number;
    start_date?: string;
    end_date?: string;
    status: 'Ongoing' | 'Onhold' | 'Finished';
    user_ids?: number[];
}

export interface CreateProjectProps extends CreateProps {
    users: Array<{
        id: number;
        name: string;
    }>;
}

export interface EditProjectProps extends EditProps<Project> {
    users: Array<{
        id: number;
        name: string;
    }>;
}

export interface ProjectFilters {
    name: string;
    status: string;
}

export type PaginatedProjects = PaginatedData<Project>;
export type ProjectModalState = ModalState<Project>;

export interface ProjectsIndexProps {
    items: PaginatedProjects;
    users: Array<{
        id: number;
        name: string;
    }>;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface ProjectFormErrors {
    name?: string;
    description?: string;
    budget?: string;
    start_date?: string;
    end_date?: string;
    status?: string;
    user_ids?: string;
}