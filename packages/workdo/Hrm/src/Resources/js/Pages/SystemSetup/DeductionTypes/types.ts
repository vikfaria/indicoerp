import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface DeductionType {
    id: number;
    name: string;
    description?: string;
    created_at: string;
}

export interface DeductionTypeFormData {
    name: string;
    description: string;
}

export interface CreateDeductionTypeProps extends CreateProps {
}

export interface EditDeductionTypeProps extends EditProps<DeductionType> {
}

export type PaginatedDeductionTypes = PaginatedData<DeductionType>;
export type DeductionTypeModalState = ModalState<DeductionType>;

export interface DeductionTypesIndexProps {
    deductiontypes: PaginatedDeductionTypes;
    auth: AuthContext;
    [key: string]: unknown;
}