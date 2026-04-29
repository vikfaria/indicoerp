import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Interview {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface InterviewFeedback {
    id: number;
    technical_rating: number;
    communication_rating: number;
    cultural_fit_rating: number;
    overall_rating: number;
    strengths?: string;
    weaknesses?: string;
    comments?: string;
    recommendation: boolean;
    interview_id?: number;
    interview?: Interview;
    interviewer_id?: number;
    interviewer?: User;
    created_at: string;
}

export interface CreateInterviewFeedbackFormData {
    technical_rating: string;
    communication_rating: string;
    cultural_fit_rating: string;
    overall_rating: string;
    strengths: string;
    weaknesses: string;
    comments: string;
    recommendation: boolean;
    interview_id: string;
    interviewer_id: string;
}

export interface EditInterviewFeedbackFormData {
    technical_rating: string;
    communication_rating: string;
    cultural_fit_rating: string;
    overall_rating: string;
    strengths: string;
    weaknesses: string;
    comments: string;
    recommendation: boolean;
    interview_id: string;
    interviewer_id: string;
}

export interface InterviewFeedbackFilters {
    strengths: string;
    weaknesses: string;
    comments: string;
    interview_id: string;
    interviewer_id: string;
    recommendation: string;
}

export type PaginatedInterviewFeedbacks = PaginatedData<InterviewFeedback>;
export type InterviewFeedbackModalState = ModalState<InterviewFeedback>;

export interface InterviewFeedbacksIndexProps {
    interviewfeedbacks: PaginatedInterviewFeedbacks;
    auth: AuthContext;
    interviews: any[];
    users: any[];
    [key: string]: unknown;
}

export interface CreateInterviewFeedbackProps {
    onSuccess: () => void;
}

export interface EditInterviewFeedbackProps {
    interviewfeedback: InterviewFeedback;
    onSuccess: () => void;
}

export interface InterviewFeedbackShowProps {
    interviewfeedback: InterviewFeedback;
    [key: string]: unknown;
}