import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Candidate {
    id: number;
    name: string;
}

export interface OnboardingChecklist {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface CandidateOnboarding {
    id: number;
    candidate_id: any;
    checklist_id: any;
    start_date: any;
    buddy_employee_id: any;
    status: boolean;
    candidate?: Candidate;
    checklist?: OnboardingChecklist;
    buddy?: User;
    created_at: string;
}

export interface CreateCandidateOnboardingFormData {
    candidate_id: any;
    checklist_id: any;
    start_date: any;
    buddy_employee_id: any;
}

export interface EditCandidateOnboardingFormData {
    candidate_id: any;
    checklist_id: any;
    start_date: any;
    buddy_employee_id: any;
    status: string;
}

export interface CandidateOnboardingFilters {
    search: string;
    candidate_id: string;
    checklist_id: string;
    buddy_employee_id: string;
    status: string;
    start_date_from: string;
    start_date_to: string;
}

export type PaginatedCandidateOnboardings = PaginatedData<CandidateOnboarding>;
export type CandidateOnboardingModalState = ModalState<CandidateOnboarding>;

export interface CandidateOnboardingsIndexProps {
    candidateonboardings: PaginatedCandidateOnboardings;
    auth: AuthContext;
    candidates: any[];
    onboardingchecklists: any[];
    users: any[];
    [key: string]: unknown;
}

export interface CreateCandidateOnboardingProps {
    onSuccess: () => void;
}

export interface EditCandidateOnboardingProps {
    candidateonboarding: CandidateOnboarding;
    onSuccess: () => void;
}

export interface CandidateOnboardingShowProps {
    candidateonboarding: CandidateOnboarding;
    [key: string]: unknown;
}