import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface AnnouncementCategory {
    id: number;
    announcement_category: string;
    created_at: string;
}

export interface AnnouncementCategoryFormData {
    announcement_category: string;
}

export interface CreateAnnouncementCategoryProps extends CreateProps {
}

export interface EditAnnouncementCategoryProps extends EditProps<AnnouncementCategory> {
}

export type PaginatedAnnouncementCategories = PaginatedData<AnnouncementCategory>;
export type AnnouncementCategoryModalState = ModalState<AnnouncementCategory>;

export interface AnnouncementCategoriesIndexProps {
    announcementcategories: PaginatedAnnouncementCategories;
    auth: AuthContext;
    [key: string]: unknown;
}