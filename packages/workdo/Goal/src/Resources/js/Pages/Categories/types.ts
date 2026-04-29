import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Category {
    id: number;
    category_name: string;
    category_code: string;
    description?: string;
    is_active: boolean;
    created_at: string;
}

export interface CreateCategoryFormData {
    category_name: string;
    category_code: string;
    description: string;
    is_active: boolean;
}

export interface EditCategoryFormData {
    category_name: string;
    category_code: string;
    description: string;
    is_active: boolean;
}

export interface CategoryFilters {
    category_name: string;
    category_code: string;
    is_active: string;
}

export type PaginatedCategories = PaginatedData<Category>;
export type CategoryModalState = ModalState<Category>;

export interface CategoriesIndexProps {
    categories: PaginatedCategories;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateCategoryProps {
    onSuccess: () => void;
}

export interface EditCategoryProps {
    category: Category;
    onSuccess: () => void;
}

export interface CategoryShowProps {
    category: Category;
    [key: string]: unknown;
}