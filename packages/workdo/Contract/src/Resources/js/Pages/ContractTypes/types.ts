import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface ContractType {
    id: number;
    name: string;
    is_active: boolean;
    contracts_count?: number;
    contracts?: Array<{
        id: number;
        subject: string;
        contract_number?: string;
    }>;
    created_at: string;
}

export interface CreateContractTypeFormData {
    name: string;
    is_active: boolean;
}

export interface EditContractTypeFormData {
    name: string;
    is_active: boolean;
}

export interface ContractTypeFilters {
    name: string;
    is_active: string;
}

export type PaginatedContractTypes = PaginatedData<ContractType>;
export type ContractTypeModalState = ModalState<ContractType>;

export interface ContractTypesIndexProps {
    contracttypes: PaginatedContractTypes;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateContractTypeProps {
    onSuccess: () => void;
}

export interface EditContractTypeProps {
    contracttype: ContractType;
    onSuccess: () => void;
}

export interface ContractTypeShowProps {
    contracttype: ContractType;
    [key: string]: unknown;
}