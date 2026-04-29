import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface ItemCategory {
    id: number;
    name: string;
    color: string;
    created_at: string;
}

export interface ItemCategoryFormData {
    name: string;
    color: string;
}

export interface CreateItemCategoryProps extends CreateProps {}

export interface EditItemCategoryProps extends EditProps<ItemCategory> {}



export type PaginatedItemCategories = PaginatedData<ItemCategory>;
export type ItemCategoryModalState = ModalState<ItemCategory>;

export interface ItemCategoriesIndexProps {
    categories: PaginatedItemCategories;
    auth: AuthContext;
    [key: string]: unknown;
}