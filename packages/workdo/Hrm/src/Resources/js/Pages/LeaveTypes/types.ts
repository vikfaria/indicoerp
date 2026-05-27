import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface LeaveType {
    id: number;
    name: string;
    legal_code?: string | null;
    description?: string;
    max_days_per_year: number;
    is_paid: boolean;
    requires_supporting_document?: boolean;
    must_be_consecutive?: boolean;
    fixed_duration_days?: number | null;
    min_advance_notice_days?: number | null;
    pre_event_start_window_days?: number | null;
    post_event_start_offset_days?: number | null;
    allow_cash_out?: boolean;
    min_effective_rest_days?: number | null;
    color: any;
    created_at: string;
}

export interface CreateLeaveTypeFormData {
    name: string;
    legal_code: string;
    description: string;
    max_days_per_year: string;
    is_paid: boolean;
    requires_supporting_document: boolean;
    must_be_consecutive: boolean;
    fixed_duration_days: string;
    min_advance_notice_days: string;
    pre_event_start_window_days: string;
    post_event_start_offset_days: string;
    allow_cash_out: boolean;
    min_effective_rest_days: string;
    color: any;
}

export interface EditLeaveTypeFormData {
    name: string;
    legal_code: string;
    description: string;
    max_days_per_year: string;
    is_paid: boolean;
    requires_supporting_document: boolean;
    must_be_consecutive: boolean;
    fixed_duration_days: string;
    min_advance_notice_days: string;
    pre_event_start_window_days: string;
    post_event_start_offset_days: string;
    allow_cash_out: boolean;
    min_effective_rest_days: string;
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
