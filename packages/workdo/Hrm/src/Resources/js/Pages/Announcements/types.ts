import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface AnnouncementCategory {
    id: number;
    name: string;
}

export interface Announcement {
    id: number;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    priority: string;
    status: string;
    announcement_category_id?: number;
    announcementCategory?: AnnouncementCategory;
    created_at: string;
}

export interface CreateAnnouncementFormData {
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    priority: string;
    status: string;
    announcement_category_id: string;
}

export interface EditAnnouncementFormData {
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    priority: string;
    status: string;
    announcement_category_id: string;
}

export interface AnnouncementFilters {
    title: string;
    description: string;
    priority: string;
    status: string;
}

export type PaginatedAnnouncements = PaginatedData<Announcement>;
export type AnnouncementModalState = ModalState<Announcement>;

export interface AnnouncementsIndexProps {
    announcements: PaginatedAnnouncements;
    auth: AuthContext;
    announcementcategories: any[];
    [key: string]: unknown;
}

export interface CreateAnnouncementProps {
    onSuccess: () => void;
}

export interface EditAnnouncementProps {
    announcement: Announcement;
    onSuccess: () => void;
}

export interface AnnouncementShowProps {
    announcement: Announcement;
    [key: string]: unknown;
}