import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface TaskStage {
    id: number;
    name: string;
    color: string;
    created_at: string;
}

export interface TaskStageFormData {
    name: string;
    color: string;
}

export interface CreateTaskStageProps extends CreateProps {}

export interface EditTaskStageProps extends EditProps<TaskStage> {}

export type PaginatedTaskStages = PaginatedData<TaskStage>;
export type TaskStageModalState = ModalState<TaskStage>;

export interface TaskStagesIndexProps {
    taskStages: PaginatedTaskStages;
    auth: AuthContext;
    [key: string]: unknown;
}