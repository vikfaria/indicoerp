import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface ComplaintType {
    id: number;
    complaint_type: string;
    created_at: string;
}

export interface ComplaintTypeFormData {
    complaint_type: string;
}

export interface CreateComplaintTypeProps extends CreateProps {
}

export interface EditComplaintTypeProps extends EditProps<ComplaintType> {
}

export type PaginatedComplaintTypes = PaginatedData<ComplaintType>;
export type ComplaintTypeModalState = ModalState<ComplaintType>;

export interface ComplaintTypesIndexProps {
    complainttypes: PaginatedComplaintTypes;
    auth: AuthContext;
    [key: string]: unknown;
}