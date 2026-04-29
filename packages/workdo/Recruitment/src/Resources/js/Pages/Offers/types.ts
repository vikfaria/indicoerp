import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Candidate {
    id: number;
    name: string;
}

export interface JobPosting {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface Offer {
    id: number;
    candidate_id: any;
    job_id: any;
    offer_date: any;
    position: any;
    department_id: boolean;
    salary: number;
    bonus?: number;
    equity?: any;
    benefits?: string;
    start_date: any;
    expiration_date: any;
    offer_letter_path?: any;
    status: boolean;
    response_date?: any;
    decline_reason?: string;
    approved_by?: any;
    approval_status: string;
    candidate?: Candidate;
    job?: JobPosting;
    approvedBy?: User;
    created_at: string;
}

export interface CreateOfferFormData {
    candidate_id: any;
    job_id: any;
    offer_date: any;
    position: any;
    department_id: boolean;
    salary: string;
    bonus: string;
    equity: any;
    benefits: string;
    start_date: any;
    expiration_date: any;
    offer_letter_path: any;
    status: boolean;
    response_date: any;
    decline_reason: string;
    approved_by: any;
}

export interface EditOfferFormData {
    candidate_id: any;
    job_id: any;
    offer_date: any;
    position: any;
    department_id: boolean;
    salary: string;
    bonus: string;
    equity: any;
    benefits: string;
    start_date: any;
    expiration_date: any;
    offer_letter_path: any;
    status: boolean;
    response_date: any;
    decline_reason: string;
    approved_by: any;
}

export interface OfferFilters {
    position: string;
    benefits: string;
    decline_reason: string;
    candidate_id: string;
    job_id: string;
    status: string;
    approval_status: string;
    offer_date: string;
    start_date: string;
    expiration_date: string;
}

export type PaginatedOffers = PaginatedData<Offer>;
export type OfferModalState = ModalState<Offer>;

export interface OffersIndexProps {
    offers: PaginatedOffers;
    auth: AuthContext;
    candidates: any[];
    jobpostings: any[];
    users: any[];
    [key: string]: unknown;
}

export interface CreateOfferProps {
    onSuccess: () => void;
}

export interface EditOfferProps {
    offer: Offer;
    onSuccess: () => void;
}

export interface OfferShowProps {
    offer: Offer;
    [key: string]: unknown;
}