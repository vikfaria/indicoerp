import { PaginatedData, ModalState, AuthContext } from '@/types/common';



export interface Holiday {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    holiday_type_id: number;
    description?: string;
    is_paid: boolean;
    is_sync_google_calendar: boolean;
    is_sync_outlook_calendar: boolean;
    created_at: string;
}

export interface CreateHolidayFormData {
    name: string;
    start_date: string;
    end_date: string;
    holiday_type_id: string;
    description: string;
    is_paid: boolean;
    is_sync_google_calendar: boolean;
    is_sync_outlook_calendar: boolean;
}

export interface EditHolidayFormData {
    name: string;
    start_date: string;
    end_date: string;
    holiday_type_id: string;
    description: string;
    is_paid: boolean;
    is_sync_google_calendar: boolean;
    is_sync_outlook_calendar: boolean;
}

export interface HolidayFilters {
    name: string;
    holiday_type_id: string;
}

export type PaginatedHolidays = PaginatedData<Holiday>;
export type HolidayModalState = ModalState<Holiday>;

export interface HolidaysIndexProps {
    holidays: PaginatedHolidays;
    auth: AuthContext;
    holidaytypes: any[];
    [key: string]: unknown;
}

export interface CreateHolidayProps {
    onSuccess: () => void;
}

export interface EditHolidayProps {
    holiday: Holiday;
    onSuccess: () => void;
}

export interface HolidayShowProps {
    holiday: Holiday;
    [key: string]: unknown;
}