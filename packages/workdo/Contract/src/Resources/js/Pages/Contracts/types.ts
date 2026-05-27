import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface ContractType {
    id: number;
    name: string;
}

export interface ContractSignature {
    id: number;
    user: User;
    signature_type: string;
    signature_data: string;
    signed_at: string;
    is_finalized: boolean;
}

export interface Contract {
    id: number;
    subject: string;
    user_id: number;
    value?: number;
    type_id: number;
    start_date: string;
    end_date: string;
    description?: string;
    status: boolean | string;
    is_labour_contract?: boolean;
    legal_contract_type?: string | null;
    fixed_term_justification?: string | null;
    probation_category?: string | null;
    legal_notes?: string | null;
    presumed_indefinite_risk?: boolean;
    user?: User;
    contractType?: ContractType;
    contract_type?: ContractType;
    contract_number?: string;
    created_at: string;
    signatures?: ContractSignature[];
    attachments?: any[];
    comments?: any[];
    notes?: any[];
    renewals?: any[];
}

export interface CreateContractFormData {
    subject: string;
    user_id: string;
    value: string;
    type_id: string;
    start_date: string;
    end_date: string;
    description: string;
    status: string;
    is_labour_contract: boolean;
    legal_contract_type: string;
    fixed_term_justification: string;
    probation_category: string;
    legal_notes: string;
    sync_to_google_calendar: boolean;
}

export interface EditContractFormData {
    subject: string;
    user_id: string;
    value: string;
    type_id: string;
    start_date: string;
    end_date: string;
    description: string;
    status: string;
    is_labour_contract: boolean;
    legal_contract_type: string;
    fixed_term_justification: string;
    probation_category: string;
    legal_notes: string;
}

export interface ContractFilters {
    subject: string;
    description: string;
    type_id: string;
    status: string;
    user_id: string;
    start_date: string;
    end_date: string;
}

export type PaginatedContracts = PaginatedData<Contract>;
export type ContractModalState = ModalState<Contract>;

export interface ContractsIndexProps {
    contracts: PaginatedContracts;
    auth: AuthContext;
    users: any[];

    contracttypes: any[];
    [key: string]: unknown;
}

export interface CreateContractProps {
    onSuccess: () => void;
}

export interface EditContractProps {
    contract: Contract;
    onSuccess: () => void;
}

export interface ContractShowProps {
    contract: Contract;
    [key: string]: unknown;
}
