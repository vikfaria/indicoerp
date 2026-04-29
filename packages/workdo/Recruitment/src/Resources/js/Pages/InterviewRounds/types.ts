import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface JobPosting {
    id: number;
    name: string;
}

export interface InterviewRound {
    id: number;
    name: string;
    sequence_number: number;
    description?: string;
    status: boolean;
    job_id?: number;
    jobPosting?: JobPosting;
    created_at: string;
}

export interface CreateInterviewRoundFormData {
    name: string;
    sequence_number: string;
    description: string;
    status: boolean;
    job_id: string;
}

export interface EditInterviewRoundFormData {
    name: string;
    sequence_number: string;
    description: string;
    status: boolean;
    job_id: string;
}

export interface InterviewRoundFilters {
    name: string;
    description: string;
    job_id: string;
    status: string;
}

export type PaginatedInterviewRounds = PaginatedData<InterviewRound>;
export type InterviewRoundModalState = ModalState<InterviewRound>;

export interface InterviewRoundsIndexProps {
    interviewrounds: PaginatedInterviewRounds;
    auth: AuthContext;
    jobpostings: any[];
    [key: string]: unknown;
}

export interface CreateInterviewRoundProps {
    onSuccess: () => void;
}

export interface EditInterviewRoundProps {
    interviewround: InterviewRound;
    onSuccess: () => void;
}

export interface InterviewRoundShowProps {
    interviewround: InterviewRound;
    [key: string]: unknown;
}