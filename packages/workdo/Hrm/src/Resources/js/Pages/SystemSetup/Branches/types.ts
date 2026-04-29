import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Branch {
    id: number;
    branch_name: string;
    created_at: string;
}

export interface BranchFormData {
    branch_name: string;
}

export interface CreateBranchProps extends CreateProps {
}

export interface EditBranchProps extends EditProps<Branch> {
}

export type PaginatedBranches = PaginatedData<Branch>;
export type BranchModalState = ModalState<Branch>;

export interface BranchesIndexProps {
    branches: PaginatedBranches;
    auth: AuthContext;
    [key: string]: unknown;
}