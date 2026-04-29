import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Candidate {
    id: number;
    name: string;
}

export interface JobPosting {
    id: number;
    name: string;
}

export interface InterviewRound {
    id: number;
    name: string;
}

export interface InterviewType {
    id: number;
    name: string;
}

export interface Interview {
    id: number;
    scheduled_date: any;
    scheduled_time: any;
    duration?: number;
    location?: string;
    meeting_link?: any;
    interviewers?: string;
    interviewer_ids?: string[];
    interviewer_names?: string;
    status: boolean;
    feedback_submitted?: boolean;
    candidate_id?: number;
    candidate?: Candidate;
    job_id?: number;
    jobPosting?: JobPosting;
    round_id?: number;
    interviewRound?: InterviewRound;
    interview_type_id?: number;
    interviewType?: InterviewType;
    created_at: string;
}

export interface CreateInterviewFormData {
    scheduled_date: any;
    scheduled_time: any;
    duration: string;
    location: string;
    meeting_link: any;
    interviewer_ids: string[];
    status: boolean;
    feedback_submitted: boolean;
    candidate_id: string;
    job_id: string;
    round_id: string;
    interview_type_id: string;
    sync_to_google_calendar: boolean;
    sync_to_outlook_calendar: boolean;
}

export interface EditInterviewFormData {
    scheduled_date: any;
    scheduled_time: any;
    duration: string;
    location: string;
    meeting_link: any;
    interviewer_ids: string[];
    status: boolean;
    feedback_submitted: boolean;
    candidate_id: string;
    job_id: string;
    round_id: string;
    interview_type_id: string;
}

export interface InterviewFilters {
    location: string;
    interview_date: string;
    feedback: string;
    status: string;
    interview_type_id: string;
}

export type PaginatedInterviews = PaginatedData<Interview>;
export type InterviewModalState = ModalState<Interview>;

export interface InterviewsIndexProps {
    interviews: PaginatedInterviews;
    auth: AuthContext;
    candidates: any[];
    jobpostings: any[];
    interviewrounds: any[];
    interviewtypes: any[];
    [key: string]: unknown;
}

export interface CreateInterviewProps {
    onSuccess: () => void;
}

export interface EditInterviewProps {
    interview: Interview;
    onSuccess: () => void;
}

export interface InterviewShowProps {
    interview: Interview;
    [key: string]: unknown;
}