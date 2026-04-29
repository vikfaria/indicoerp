import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface LeaveType {
    id: number;
    name: string;
}

export interface Employee {
    id: number;
    name?: string;
    user?: User;
}

export interface LeaveApplication {
    id: number;
    start_date: string;
    end_date: string;
    total_days: number;
    reason: string;
    attachment?: string;
    status: string;
    approver_comment?: string;
    approved_at?: any;
    employee_id?: number;
    employee?: User;
    leave_type_id?: number;
    leave_type?: LeaveType;
    approved_by?: number;
    created_at: string;
}

export interface CreateLeaveApplicationFormData {
    employee_id: string;
    leave_type_id: string;
    start_date: string;
    end_date: string;
    reason: string;
    attachment: string;
}

export interface EditLeaveApplicationFormData {
    employee_id: string;
    leave_type_id: string;
    start_date: string;
    end_date: string;
    reason: string;
    attachment: string;
}

export interface LeaveApplicationFilters {
    reason: string;
    status: string;
    employee_id: string;
    start_date: string;
    end_date: string;
}

export type PaginatedLeaveApplications = PaginatedData<LeaveApplication>;
export type LeaveApplicationModalState = ModalState<LeaveApplication>;

export interface LeaveApplicationsIndexProps {
    leaveapplications: PaginatedLeaveApplications;
    auth: AuthContext;
    users: any[];
    leavetypes: any[];
    employees: Employee[];
    [key: string]: unknown;
}

export interface CreateLeaveApplicationProps {
    onSuccess: () => void;
}

export interface EditLeaveApplicationProps {
    leaveapplication: LeaveApplication;
    onSuccess: () => void;
}

export interface LeaveApplicationShowProps {
    leaveapplication: LeaveApplication;
    [key: string]: unknown;
}