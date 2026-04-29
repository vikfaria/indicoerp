import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface EventType {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface Event {
    id: number;
    title: string;
    description?: string;
    event_type_id: number;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    location?: string;
    status: string;
    approved_by?: number;
    eventType?: EventType;
    approvedBy?: User;
    created_at: string;
}

export interface CreateEventFormData {
    title: string;
    description: string;
    event_type_id: string;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    location: string;
    status: string;
    approved_by: string;
}

export interface EditEventFormData {
    title: string;
    description: string;
    event_type_id: string;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    location: string;
    status: string;
    approved_by: string;
}

export interface EventFilters {
    title: string;
    description: string;
    location: string;
    status: string;
}

export type PaginatedEvents = PaginatedData<Event>;
export type EventModalState = ModalState<Event>;

export interface EventsIndexProps {
    events: PaginatedEvents;
    auth: AuthContext;
    eventtypes: any[];
    users: any[];
    [key: string]: unknown;
}

export interface CreateEventProps {
    onSuccess: () => void;
}

export interface EditEventProps {
    event: Event;
    onSuccess: () => void;
}

export interface EventShowProps {
    event: Event;
    [key: string]: unknown;
}