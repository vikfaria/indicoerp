import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface Branch {
    id: number;
    name: string;
}

export interface Department {
    id: number;
    name: string;
}

export interface Designation {
    id: number;
    name: string;
}

export interface EmployeeTransfer {
    id: number;
    transfer_date?: string;
    effective_date: string;
    reason?: string;
    status: string;
    document?: string;
    employee_id?: number;
    employee?: User;
    from_branch_id?: number;
    from_branch?: Branch;
    from_department_id?: number;
    from_department?: Department;
    from_designation_id?: number;
    from_designation?: Designation;
    to_branch_id?: number;
    to_branch?: Branch;
    to_department_id?: number;
    to_department?: Department;
    to_designation_id?: number;
    to_designation?: Designation;
    approved_by?: number;
    created_at: string;
}

export interface CreateEmployeeTransferFormData {
    transfer_date: string;
    effective_date: string;
    reason: string;
    status: string;
    document: string;
    employee_id: string;
    from_branch_id: string;
    from_department_id: string;
    from_designation_id: string;
    to_branch_id: string;
    to_department_id: string;
    to_designation_id: string;
    approved_by: string;
}

export interface EditEmployeeTransferFormData {
    transfer_date: string;
    effective_date: string;
    reason: string;
    status: string;
    document: string;
    employee_id: string;
    from_branch_id: string;
    from_department_id: string;
    from_designation_id: string;
    to_branch_id: string;
    to_department_id: string;
    to_designation_id: string;
    approved_by: string;
}

export interface EmployeeTransferFilters {
    reason: string;
    status: string;
}

export type PaginatedEmployeeTransfers = PaginatedData<EmployeeTransfer>;
export type EmployeeTransferModalState = ModalState<EmployeeTransfer>;

export interface EmployeeTransfersIndexProps {
    employeetransfers: PaginatedEmployeeTransfers;
    auth: AuthContext;
    users: any[];
    branches: any[];
    departments: any[];
    designations: any[];
    [key: string]: unknown;
}

export interface CreateEmployeeTransferProps {
    onSuccess: () => void;
}

export interface EditEmployeeTransferProps {
    employeetransfer: EmployeeTransfer;
    onSuccess: () => void;
}

export interface EmployeeTransferShowProps {
    employeetransfer: EmployeeTransfer;
    [key: string]: unknown;
}