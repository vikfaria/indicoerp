import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface JobType {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    created_at: string;
}

export interface JobTypeFormData {
    name: string;
    description: string;
    is_active: boolean;
}

export interface CreateJobTypeProps extends CreateProps {
}

export interface EditJobTypeProps extends EditProps<JobType> {
}

export type PaginatedJobTypes = PaginatedData<JobType>;
export type JobTypeModalState = ModalState<JobType>;

export interface JobTypesIndexProps {
    jobtypes: PaginatedJobTypes;
    auth: AuthContext;
    [key: string]: unknown;
}