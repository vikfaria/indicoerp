import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Pipeline {
    id: number;
    name: string;
    created_at: string;
}

export interface PipelineFormData {
    name: string;
}

export interface CreatePipelineProps extends CreateProps {
}

export interface EditPipelineProps extends EditProps<Pipeline> {
}

export type PaginatedPipelines = PaginatedData<Pipeline>;
export type PipelineModalState = ModalState<Pipeline>;

export interface PipelinesIndexProps {
    pipelines: PaginatedPipelines;
    auth: AuthContext;
    [key: string]: unknown;
}