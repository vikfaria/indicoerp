import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface LoanType {
    id: number;
    name: string;
    description?: string;
    created_at: string;
}

export interface LoanTypeFormData {
    name: string;
    description: string;
}

export interface CreateLoanTypeProps extends CreateProps {
}

export interface EditLoanTypeProps extends EditProps<LoanType> {
}

export type PaginatedLoanTypes = PaginatedData<LoanType>;
export type LoanTypeModalState = ModalState<LoanType>;

export interface LoanTypesIndexProps {
    loantypes: PaginatedLoanTypes;
    auth: AuthContext;
    [key: string]: unknown;
}