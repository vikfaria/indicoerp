import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface AwardType {
    id: number;
    name: any;
    description?: any;
    created_at: string;
}

export interface AwardTypeFormData {
    name: any;
    description: any;
}

export interface CreateAwardTypeProps extends CreateProps {
}

export interface EditAwardTypeProps extends EditProps<AwardType> {
}

export type PaginatedAwardTypes = PaginatedData<AwardType>;
export type AwardTypeModalState = ModalState<AwardType>;

export interface AwardTypesIndexProps {
    awardtypes: PaginatedAwardTypes;
    auth: AuthContext;
    [key: string]: unknown;
}