import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Source {
    id: number;
    name: string;
    created_at: string;
}

export interface SourceFormData {
    name: string;
}

export interface CreateSourceProps extends CreateProps {
}

export interface EditSourceProps extends EditProps<Source> {
}

export type PaginatedSources = PaginatedData<Source>;
export type SourceModalState = ModalState<Source>;

export interface SourcesIndexProps {
    sources: PaginatedSources;
    auth: AuthContext;
    [key: string]: unknown;
}