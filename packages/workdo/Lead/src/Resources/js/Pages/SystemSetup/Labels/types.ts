import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Label {
    id: number;
    name: string;
    color: any;
    pipeline_id?: number;
    pipeline?: Pipeline;
    created_at: string;
}

export interface LabelFormData {
    name: string;
    color: any;
    pipeline_id: string;
}

export interface CreateLabelProps extends CreateProps {
    pipelines: any[];
    defaultPipelineId?: number;
}

export interface EditLabelProps extends EditProps<Label> {
    pipelines: any[];
}

export type PaginatedLabels = PaginatedData<Label>;
export type LabelModalState = ModalState<Label>;

export interface LabelsIndexProps {
    labels: PaginatedLabels;
    auth: AuthContext;
    pipelines: any[];
    [key: string]: unknown;
}