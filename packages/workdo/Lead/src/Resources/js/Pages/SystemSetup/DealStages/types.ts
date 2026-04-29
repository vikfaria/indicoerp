import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface DealStage {
    id: number;
    name: string;
    order: number;
    pipeline_id?: number;
    pipeline?: Pipeline;
    created_at: string;
}

export interface DealStageFormData {
    name: string;
    order: string;
    pipeline_id: string;
}

export interface CreateDealStageProps extends CreateProps {
    pipelines: any[];
    defaultPipelineId?: number;
}

export interface EditDealStageProps extends EditProps<DealStage> {
    pipelines: any[];
}

export type PaginatedDealStages = PaginatedData<DealStage>;
export type DealStageModalState = ModalState<DealStage>;

export interface DealStagesIndexProps {
    dealstages: PaginatedDealStages;
    auth: AuthContext;
    pipelines: any[];
    [key: string]: unknown;
}