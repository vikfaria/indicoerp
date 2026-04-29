import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface InterviewType {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    created_at: string;
}

export interface InterviewTypeFormData {
    name: string;
    description: string;
    is_active: boolean;
}

export interface CreateInterviewTypeProps extends CreateProps {
}

export interface EditInterviewTypeProps extends EditProps<InterviewType> {
}

export type PaginatedInterviewTypes = PaginatedData<InterviewType>;
export type InterviewTypeModalState = ModalState<InterviewType>;

export interface InterviewTypesIndexProps {
    interviewtypes: PaginatedInterviewTypes;
    auth: AuthContext;
    [key: string]: unknown;
}