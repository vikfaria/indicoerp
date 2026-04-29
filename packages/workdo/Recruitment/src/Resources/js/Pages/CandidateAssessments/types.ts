import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Candidate {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface CandidateAssessment {
    id: number;
    assessment_name: any;
    score: number;
    max_score: number;
    pass_fail_status: boolean;
    comments?: string;
    assessment_date: any;
    candidate_id?: number;
    candidate?: Candidate;
    conducted_by?: number;
    conductedBy?: User;
    created_at: string;
}

export interface CreateCandidateAssessmentFormData {
    assessment_name: any;
    score: string;
    max_score: string;
    pass_fail_status: boolean;
    comments: string;
    assessment_date: any;
    candidate_id: string;
    conducted_by: string;
}

export interface EditCandidateAssessmentFormData {
    assessment_name: any;
    score: string;
    max_score: string;
    pass_fail_status: boolean;
    comments: string;
    assessment_date: any;
    candidate_id: string;
    conducted_by: string;
}

export interface CandidateAssessmentFilters {
    assessment_name: string;
    comments: string;
    pass_fail_status: string;
    start_date: string;
    end_date: string;
}

export type PaginatedCandidateAssessments = PaginatedData<CandidateAssessment>;
export type CandidateAssessmentModalState = ModalState<CandidateAssessment>;

export interface CandidateAssessmentsIndexProps {
    candidateassessments: PaginatedCandidateAssessments;
    auth: AuthContext;
    candidates: any[];
    users: any[];
    [key: string]: unknown;
}

export interface CreateCandidateAssessmentProps {
    onSuccess: () => void;
}

export interface EditCandidateAssessmentProps {
    candidateassessment: CandidateAssessment;
    onSuccess: () => void;
}

export interface CandidateAssessmentShowProps {
    candidateassessment: CandidateAssessment;
    [key: string]: unknown;
}