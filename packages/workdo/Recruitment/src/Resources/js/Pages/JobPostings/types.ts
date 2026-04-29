import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface JobType {
    id: number;
    name: string;
}

export interface JobLocation {
    id: number;
    name: string;
}

export interface JobPosting {
    id: number;
    title: string;
    min_experience?: number;
    max_experience?: number;
    min_salary?: number;
    max_salary?: number;
    description?: string;
    requirements?: string;
    benefits?: string;
    terms_condition?: string;
    show_terms_condition?: boolean;
    application_deadline?: any;
    is_published?: boolean;
    publish_date?: any;
    is_featured?: boolean;
    status: boolean;
    department_id?: number;
    department_name?: string;
    job_type_id?: number;
    jobType?: JobType;
    location_id?: number;
    location?: JobLocation;
    skills?: string;
    created_at: string;
}

export interface CreateJobPostingFormData {
    title: string;
    min_experience: string;
    max_experience: string;
    min_salary: string;
    max_salary: string;
    description: string;
    requirements: string;
    benefits: string;
    terms_condition: string;
    show_terms_condition: boolean;
    application_deadline: any;
    is_published: boolean;
    publish_date: any;
    is_featured: boolean;
    status: boolean;
    department_id: string;
    job_type_id: string;
    location_id: string;
    custom_questions: number[];
    skills: string[];
}

export interface EditJobPostingFormData {
    title: string;
    min_experience: string;
    max_experience: string;
    min_salary: string;
    max_salary: string;
    description: string;
    requirements: string;
    benefits: string;
    terms_condition: string;
    show_terms_condition: boolean;
    application_deadline: any;
    is_published: boolean;
    publish_date: any;
    is_featured: boolean;
    status: boolean;
    department_id: string;
    job_type_id: string;
    location_id: string;
    custom_questions: number[];
    skills: string[];
}

export interface JobPostingFilters {
    title: string;
    description: string;
    job_type_id: string;
    location_id: string;
    branch_id: string;
    status: string;
}

export type PaginatedJobPostings = PaginatedData<JobPosting>;
export type JobPostingModalState = ModalState<JobPosting>;

export interface JobPostingsIndexProps {
    jobpostings: PaginatedJobPostings;
    auth: AuthContext;
    jobtypes: any[];
    joblocations: any[];
    branches: any[];
    [key: string]: unknown;
}

export interface CreateJobPostingProps {
    onSuccess: () => void;
}

export interface EditJobPostingProps {
    jobposting: JobPosting;
    onSuccess: () => void;
}

export interface JobPostingShowProps {
    jobposting: JobPosting;
    [key: string]: unknown;
}
