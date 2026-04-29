import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface CustomQuestion {
    id: number;
    question: string;
    type: boolean;
    options?: string;
    is_required?: boolean;
    is_active?: boolean;
    sort_order?: number;
    created_at: string;
}

export interface CreateCustomQuestionFormData {
    question: string;
    type: boolean;
    options: string;
    is_required: boolean;
    is_active: boolean;
    sort_order: string;
}

export interface EditCustomQuestionFormData {
    question: string;
    type: boolean;
    options: string;
    is_required: boolean;
    is_active: boolean;
    sort_order: string;
}

export interface CustomQuestionFilters {
    question: string;
    type: string;
    is_active: string;
    is_required: string;
}

export type PaginatedCustomQuestions = PaginatedData<CustomQuestion>;
export type CustomQuestionModalState = ModalState<CustomQuestion>;

export interface CustomQuestionsIndexProps {
    customquestions: PaginatedCustomQuestions;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateCustomQuestionProps {
    onSuccess: () => void;
}

export interface EditCustomQuestionProps {
    customquestion: CustomQuestion;
    onSuccess: () => void;
}

export interface CustomQuestionShowProps {
    customquestion: CustomQuestion;
    [key: string]: unknown;
}