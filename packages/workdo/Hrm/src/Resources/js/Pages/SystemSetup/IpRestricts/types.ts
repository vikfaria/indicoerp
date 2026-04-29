import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface IpRestrict {
    id: number;
    ip: string;
    created_at: string;
}

export interface IpRestrictFormData {
    ip: string;
}

export interface CreateIpRestrictProps extends CreateProps {
}

export interface EditIpRestrictProps extends EditProps<IpRestrict> {
}

export type PaginatedIpRestricts = PaginatedData<IpRestrict>;
export type IpRestrictModalState = ModalState<IpRestrict>;

export interface IpRestrictsIndexProps {
    iprestricts: PaginatedIpRestricts;
    auth: AuthContext;
    ipRestrictEnabled: boolean;
    [key: string]: unknown;
}