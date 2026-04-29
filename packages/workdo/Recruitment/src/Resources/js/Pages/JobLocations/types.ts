import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface JobLocation {
    id: number;
    name: string;
    remote_work: boolean;
    address: string;
    city: any;
    state: any;
    country: any;
    postal_code: any;
    status: boolean;
    created_at: string;
}

export interface CreateJobLocationFormData {
    name: string;
    remote_work: boolean;
    address: string;
    city: any;
    state: any;
    country: any;
    postal_code: any;
    status: boolean;
}

export interface EditJobLocationFormData {
    name: string;
    remote_work: boolean;
    address: string;
    city: any;
    state: any;
    country: any;
    postal_code: any;
    status: boolean;
}

export interface JobLocationFilters {
    name: string;
    city: string;
    state: string;
    country: string;
    status: string;
    remote_work: string;
}

export type PaginatedJobLocations = PaginatedData<JobLocation>;
export type JobLocationModalState = ModalState<JobLocation>;

export interface JobLocationsIndexProps {
    joblocations: PaginatedJobLocations;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateJobLocationProps {
    onSuccess: () => void;
}

export interface EditJobLocationProps {
    joblocation: JobLocation;
    onSuccess: () => void;
}

export interface JobLocationShowProps {
    joblocation: JobLocation;
    [key: string]: unknown;
}