import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface HolidayType {
    id: number;
    holiday_type: string;
    created_at: string;
}

export interface HolidayTypeFormData {
    holiday_type: string;
}

export interface CreateHolidayTypeProps extends CreateProps {
}

export interface EditHolidayTypeProps extends EditProps<HolidayType> {
}

export type PaginatedHolidayTypes = PaginatedData<HolidayType>;
export type HolidayTypeModalState = ModalState<HolidayType>;

export interface HolidayTypesIndexProps {
    holidaytypes: PaginatedHolidayTypes;
    auth: AuthContext;
    [key: string]: unknown;
}