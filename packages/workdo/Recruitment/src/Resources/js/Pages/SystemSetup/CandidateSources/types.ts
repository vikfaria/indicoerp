import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface CandidateSources {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    created_at: string;
}

export interface CandidateSourcesFormData {
    name: string;
    description: string;
    is_active: boolean;
}

export interface CreateCandidateSourcesProps extends CreateProps {
}

export interface EditCandidateSourcesProps extends EditProps<CandidateSources> {
}

export type PaginatedCandidateSources = PaginatedData<CandidateSources>;
export type CandidateSourcesModalState = ModalState<CandidateSources>;

export interface CandidateSourcesIndexProps {
    candidatesources: PaginatedCandidateSources;
    auth: AuthContext;
    [key: string]: unknown;
}