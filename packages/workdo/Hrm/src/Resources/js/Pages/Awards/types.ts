import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Award {
    id: number;
    employee_id: any;
    award_type_id: any;
    award_date: any;
    description?: any;
    certificate?: any;
    created_at: string;
}

export interface CreateAwardFormData {
    employee_id: any;
    award_type_id: any;
    award_date: any;
    description: any;
    certificate: any;
}

export interface EditAwardFormData {
    employee_id: any;
    award_type_id: any;
    award_date: any;
    description: any;
    certificate: any;
}

export interface AwardFilters {
    name: string;
}

export type PaginatedAwards = PaginatedData<Award>;
export type AwardModalState = ModalState<Award>;

export interface AwardsIndexProps {
    awards: PaginatedAwards;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateAwardProps {
    onSuccess: () => void;
}

export interface EditAwardProps {
    award: Award;
    onSuccess: () => void;
}

export interface AwardShowProps {
    award: Award;
    [key: string]: unknown;
}