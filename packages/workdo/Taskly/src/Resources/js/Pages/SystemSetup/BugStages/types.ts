import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface BugStage {
    id: number;
    name: string;
    color: string;
    created_at: string;
}

export interface BugStageFormData {
    name: string;
    color: string;
}

export interface CreateBugStageProps extends CreateProps {}

export interface EditBugStageProps extends EditProps<BugStage> {}

export type PaginatedBugStages = PaginatedData<BugStage>;
export type BugStageModalState = ModalState<BugStage>;

export interface BugStagesIndexProps {
    bugStages: PaginatedBugStages;
    auth: AuthContext;
    [key: string]: unknown;
}