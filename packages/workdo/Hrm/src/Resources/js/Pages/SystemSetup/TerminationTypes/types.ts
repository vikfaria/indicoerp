import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface TerminationType {
    id: number;
    termination_type: string;
    created_at: string;
}

export interface TerminationTypeFormData {
    termination_type: string;
}

export interface CreateTerminationTypeProps extends CreateProps {
}

export interface EditTerminationTypeProps extends EditProps<TerminationType> {
}

export type PaginatedTerminationTypes = PaginatedData<TerminationType>;
export type TerminationTypeModalState = ModalState<TerminationType>;

export interface TerminationTypesIndexProps {
    terminationtypes: PaginatedTerminationTypes;
    auth: AuthContext;
    [key: string]: unknown;
}