import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface JobPosting {
    id: number;
    title: string;
}

export interface CandidateSource {
    id: number;
    name: string;
}

export interface Candidate {
    id: number;
    first_name: string;
    last_name: string;
    email: any;
    phone?: string;
    gender?: string;
    dob?: string;
    country?: string;
    state?: string;
    city?: string;
    current_company?: string;
    current_position?: string;
    experience_years?: number;
    current_salary?: number;
    expected_salary?: number;
    notice_period?: string;
    skills?: string;
    education?: string;
    portfolio_url?: any;
    linkedin_url?: any;
    profile_path?: string;
    resume_path?: string;
    cover_letter_path?: string;
    status: boolean;
    application_date?: any;
    custom_question?: any;
    job_id?: number;
    jobPosting?: JobPosting;
    source_id?: number;
    candidateSource?: CandidateSource;
    created_at: string;
}

export interface CreateCandidateFormData {
    first_name: string;
    last_name: string;
    email: any;
    phone: string;
    gender: string;
    dob: string;
    country: string;
    state: string;
    city: string;
    current_company: string;
    current_position: string;
    experience_years: string;
    current_salary: string;
    expected_salary: string;
    notice_period: string;
    skills: string;
    education: string;
    portfolio_url: any;
    linkedin_url: any;
    profile_photo: File | null;
    resume: File | null;
    cover_letter: File | null;
    status: boolean;
    application_date: any;
    custom_question: any;
    job_id: string;
    source_id: string;
    [key: string]: any;
}

export interface EditCandidateFormData {
    first_name: string;
    last_name: string;
    email: any;
    phone: string;
    gender: string;
    dob: string;
    country: string;
    state: string;
    city: string;
    current_company: string;
    current_position: string;
    experience_years: string;
    current_salary: string;
    expected_salary: string;
    notice_period: string;
    skills: string;
    education: string;
    portfolio_url: any;
    linkedin_url: any;
    profile_photo: File | null;
    resume: File | null;
    cover_letter: File | null;
    status: string;
    application_date: any;
    custom_question: any;
    job_id: string;
    source_id: string;
    [key: string]: any;
}

export interface CandidateFilters {
    name: string;
    job_id: string;
    source_id: string;
    status: string;
    application_date: string;
}

export type PaginatedCandidates = PaginatedData<Candidate>;
export type CandidateModalState = ModalState<Candidate>;

export interface CandidatesIndexProps {
    candidates: PaginatedCandidates;
    auth: AuthContext;
    jobpostings: any[];
    candidatesources: any[];
    [key: string]: unknown;
}

export interface CreateCandidateProps {
    onSuccess: () => void;
}

export interface EditCandidateProps {
    candidate: Candidate;
    onSuccess: () => void;
}

export interface CandidateShowProps {
    candidate: Candidate;
    [key: string]: unknown;
}