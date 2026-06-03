export interface Complaint {
    id: number;
    employee_id?: number;
    against_employee_id?: number;
    complaint_type_id?: number;
    subject: string;
    description: string;
    complaint_date: string;
    status: string;
    is_confidential?: boolean;
    is_harassment_report?: boolean;
    confidential_channel?: string;
    confidentiality_level?: string;
    confidential_access_user_ids?: number[];
    document?: string;
    resolved_by?: number;
    handling_owner_id?: number;
    resolution_date?: string;
    investigation_started_at?: string;
    investigation_closed_at?: string;
    is_cancelled?: boolean;
    cancelled_at?: string;
    cancelled_by?: number;
    cancellation_reason?: string;
    creator_id: number;
    created_by: number;
    employee?: {
        id: number;
        name: string;
    };
    againstEmployee?: {
        id: number;
        name: string;
    };
    against_employee?: {
        id: number;
        name: string;
    };
    complaintType?: {
        id: number;
        complaint_type: string;
    };
    complaint_type?: {
        id: number;
        complaint_type: string;
    };
    resolvedBy?: {
        id: number;
        name: string;
    };
    resolved_by?: {
        id: number;
        name: string;
    };
    handlingOwner?: {
        id: number;
        name: string;
    };
    handling_owner?: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface ComplaintsIndexProps {
    complaints: {
        data: Complaint[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: any[];
        meta: any;
    };
    employees: Array<{
        id: number;
        name: string;
    }>;
    complaintTypes: Array<{
        id: number;
        complaint_type: string;
    }>;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export interface ComplaintFilters {
    subject: string;
    employee_id: string;
    complaint_type_id: string;
    status: string;
}

export interface ComplaintModalState {
    isOpen: boolean;
    mode: string;
    data: Complaint | null;
}

export interface CreateComplaintProps {
    onSuccess: () => void;
}

export interface EditComplaintProps {
    complaint: Complaint;
    onSuccess: () => void;
}

export interface CreateComplaintFormData {
    employee_id: string;
    against_employee_id: string;
    complaint_type_id: string;
    subject: string;
    description: string;
    complaint_date: string;
    is_confidential: boolean;
    is_harassment_report: boolean;
    confidential_channel: string;
    confidentiality_level: string;
    confidential_access_user_ids: string[];
    handling_owner_id: string;
    investigation_started_at: string;
    investigation_closed_at: string;
    document: string;
}

export interface EditComplaintFormData {
    employee_id: string;
    against_employee_id: string;
    complaint_type_id: string;
    subject: string;
    description: string;
    complaint_date: string;
    is_confidential: boolean;
    is_harassment_report: boolean;
    confidential_channel: string;
    confidentiality_level: string;
    confidential_access_user_ids: string[];
    handling_owner_id: string;
    investigation_started_at: string;
    investigation_closed_at: string;
    document: string;
}

export interface ComplaintStatusProps {
    complaint: Complaint;
    onSuccess: () => void;
}
