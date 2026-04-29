import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Employee {
    id: number;
    employee_id: string;
    user?: {
        id: number;
        name: string;
    };
    rate_per_hour?: number;
}

export interface Shift {
    id: number;
    shift_name: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Attendance {
    id: number;
    employee_id: number;
    shift_id: number;
    date: string;
    clock_in: string;
    clock_out?: string;
    break_hour?: number;
    total_hour?: number;
    overtime_hours?: number;
    overtime_amount?: number;
    status: 'present' | 'half_day' | 'absent';
    notes?: string;
    user?: User;
    employee?: Employee;
    shift?: Shift;
    created_at: string;
}

export interface CreateAttendanceFormData {
    employee_id: string;
    date: string;
    clock_in: string;
    clock_out: string;
    break_hour: string;
    notes: string;
}

export interface EditAttendanceFormData {
    employee_id: string;
    date: string;
    clock_in: string;
    clock_out: string;
    break_hour: string;
    notes: string;
}

export interface AttendanceFilters {
    search: string;
    status: string;
    employee_id: string;
    date_from: string;
    date_to: string;
}

export type PaginatedAttendances = PaginatedData<Attendance>;
export type AttendanceModalState = ModalState<Attendance>;

export interface AttendancesIndexProps {
    attendances: PaginatedAttendances;
    auth: AuthContext;
    employees: any[];
    shifts: any[];
    [key: string]: unknown;
}

export interface CreateAttendanceProps {
    onSuccess: () => void;
}

export interface EditAttendanceProps {
    attendance: Attendance;
    onSuccess: () => void;
}

export interface AttendanceShowProps {
    attendance: Attendance;
    [key: string]: unknown;
}