import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Department {
    id: number;
    department_name: string;
    branch_id?: number;
    branch?: Branch;
    created_at: string;
}

export interface DepartmentFormData {
    department_name: string;
    branch_id: string;
}

export interface CreateDepartmentProps extends CreateProps {
    branches: any[];
}

export interface EditDepartmentProps extends EditProps<Department> {
    branches: any[];
}

export type PaginatedDepartments = PaginatedData<Department>;
export type DepartmentModalState = ModalState<Department>;

export interface DepartmentsIndexProps {
    departments: PaginatedDepartments;
    auth: AuthContext;
    branches: any[];
    [key: string]: unknown;
}