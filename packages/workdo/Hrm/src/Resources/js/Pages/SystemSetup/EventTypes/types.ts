import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface EventType {
    id: number;
    event_type: string;
    created_at: string;
}

export interface EventTypeFormData {
    event_type: string;
}

export interface CreateEventTypeProps extends CreateProps {
}

export interface EditEventTypeProps extends EditProps<EventType> {
}

export type PaginatedEventTypes = PaginatedData<EventType>;
export type EventTypeModalState = ModalState<EventType>;

export interface EventTypesIndexProps {
    eventtypes: PaginatedEventTypes;
    auth: AuthContext;
    [key: string]: unknown;
}