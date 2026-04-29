import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface DocumentCategory {
    id: number;
    document_type: string;
    status: boolean;
    created_at: string;
}

export interface DocumentCategoryFormData {
    document_type: string;
    status: boolean;
}

export interface CreateDocumentCategoryProps extends CreateProps {
}

export interface EditDocumentCategoryProps extends EditProps<DocumentCategory> {
}

export type PaginatedDocumentCategories = PaginatedData<DocumentCategory>;
export type DocumentCategoryModalState = ModalState<DocumentCategory>;

export interface DocumentCategoriesIndexProps {
    documentcategories: PaginatedDocumentCategories;
    auth: AuthContext;
    [key: string]: unknown;
}