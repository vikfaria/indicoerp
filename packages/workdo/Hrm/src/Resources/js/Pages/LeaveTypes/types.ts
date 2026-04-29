import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface LeaveType {
    id: number;
    name: string;
    description?: string;
    max_days_per_year: number;
    is_paid: boolean;
    color: any;
    created_at: string;
}

export interface CreateLeaveTypeFormData {
    name: string;
    description: string;
    max_days_per_year: string;
    is_paid: boolean;
    color: any;
}

export interface EditLeaveTypeFormData {
    name: string;
    description: string;
    max_days_per_year: string;
    is_paid: boolean;
    color: any;
}

export interface LeaveTypeFilters {
    name: string;
    is_paid: string;
}

export type PaginatedLeaveTypes = PaginatedData<LeaveType>;
export type LeaveTypeModalState = ModalState<LeaveType>;

export interface LeaveTypesIndexProps {
    leavetypes: PaginatedLeaveTypes;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateLeaveTypeProps {
    onSuccess: () => void;
}

export interface EditLeaveTypeProps {
    leavetype: LeaveType;
    onSuccess: () => void;
}

export interface LeaveTypeShowProps {
    leavetype: LeaveType;
    [key: string]: unknown;
}