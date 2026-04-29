import { PaginatedData, AuthContext, EditProps } from '@/types/common';

export interface Item {
    id: number;
    name: string;
    sku?: string;
    tax_id?: number;
    category_id?: number;
    description?: string;
    long_description?: string;
    is_active: boolean;
    created_at: string;
    sale_price?: number;
    purchase_price?: number;
    quantity?: number;
    type?: string;
    category?: Category;
    unit_relation?: {
        unit_name: string;
    };
    image?: string;
}

export interface ItemFormData {
    name: string;
    sku?: string;
    tax_ids?: string[];
    category_id?: string;
    description?: string;
    long_description?: string;
    sale_price?: string;
    purchase_price?: string;
    unit?: string;
    quantity?: string;
    image?: string;
    warehouse_id?: string;
    type?: string;
}

export interface Tax {
    id: number;
    tax_name: string;
    rate: number;
}

export interface Category {
    id: number;
    name: string;
    type?: string;
}

export interface Unit {
    id: number;
    unit_name: string;
}

export interface Warehouse {
    id: number;
    name: string;
}

export interface EditItemProps extends EditProps<Item> {}

export interface ItemFilters {
    name: string;
    type: string;
    category_id: string;
}

export type PaginatedItems = PaginatedData<Item>;

export interface ItemsIndexProps {
    items: PaginatedItems;
    categories: Category[];
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateItemPageProps {
    taxes: Tax[];
    categories: Category[];
    units: Unit[];
    warehouses: Warehouse[];
    auth: AuthContext;
    [key: string]: unknown;
}

export interface EditItemPageProps {
    item: Item;
    taxes: Tax[];
    categories: Category[];
    units: Unit[];
    warehouses: Warehouse[];
    auth: AuthContext;
    [key: string]: unknown;
}