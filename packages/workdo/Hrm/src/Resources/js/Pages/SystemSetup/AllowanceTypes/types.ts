import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface AllowanceType {
    id: number;
    name: string;
    description?: string;
    created_at: string;
}

export interface AllowanceTypeFormData {
    name: string;
    description: string;
}

export interface CreateAllowanceTypeProps extends CreateProps {
}

export interface EditAllowanceTypeProps extends EditProps<AllowanceType> {
}

export type PaginatedAllowanceTypes = PaginatedData<AllowanceType>;
export type AllowanceTypeModalState = ModalState<AllowanceType>;

export interface AllowanceTypesIndexProps {
    allowancetypes: PaginatedAllowanceTypes;
    auth: AuthContext;
    [key: string]: unknown;
}