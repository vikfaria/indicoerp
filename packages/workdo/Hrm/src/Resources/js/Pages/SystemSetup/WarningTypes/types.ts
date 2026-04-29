import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface WarningType {
    id: number;
    warning_type_name: string;
    created_at: string;
}

export interface WarningTypeFormData {
    warning_type_name: string;
}

export interface CreateWarningTypeProps extends CreateProps {
}

export interface EditWarningTypeProps extends EditProps<WarningType> {
}

export type PaginatedWarningTypes = PaginatedData<WarningType>;
export type WarningTypeModalState = ModalState<WarningType>;

export interface WarningTypesIndexProps {
    warningtypes: PaginatedWarningTypes;
    auth: AuthContext;
    [key: string]: unknown;
}