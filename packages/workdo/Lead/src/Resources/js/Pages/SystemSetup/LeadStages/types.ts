import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface LeadStage {
    id: number;
    name: string;
    order: number;
    pipeline_id?: number;
    pipeline?: Pipeline;
    created_at: string;
}

export interface LeadStageFormData {
    name: string;
    order: string;
    pipeline_id: string;
}

export interface CreateLeadStageProps extends CreateProps {
    pipelines: any[];
    defaultPipelineId?: number;
}

export interface EditLeadStageProps extends EditProps<LeadStage> {
    pipelines: any[];
}

export type PaginatedLeadStages = PaginatedData<LeadStage>;
export type LeadStageModalState = ModalState<LeadStage>;

export interface LeadStagesIndexProps {
    leadstages: PaginatedLeadStages;
    auth: AuthContext;
    pipelines: any[];
    [key: string]: unknown;
}