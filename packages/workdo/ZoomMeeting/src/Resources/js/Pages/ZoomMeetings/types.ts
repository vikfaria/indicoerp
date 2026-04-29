import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface User {
    id: number;
    name: string;
}

export interface ZoomMeeting {
    id: number;
    title: string;
    description?: string;
    meeting_id?: string;
    meeting_password?: string;
    start_time: any;
    duration: number;
    host_video: boolean;
    participant_video: boolean;
    waiting_room: boolean;
    recording: boolean;
    status: string;
    participants?: string[];
    host_id?: number;
    host?: User;
    created_at: string;
}

export interface CreateZoomMeetingFormData {
    title: string;
    description: string;
    meeting_password: string;
    start_time: any;
    duration: string;
    host_video: boolean;
    participant_video: boolean;
    waiting_room: boolean;
    recording: boolean;
    status: string;
    participants: string[];
    host_id: string;
    sync_to_google_calendar: boolean;
}

export interface EditZoomMeetingFormData {
    title: string;
    description: string;
    meeting_password: string;
    start_time: any;
    duration: string;
    host_video: boolean;
    participant_video: boolean;
    waiting_room: boolean;
    recording: boolean;
    status: string;
    participants: string[];
    host_id: string;
}

export interface ZoomMeetingFilters {
    title: string;
    description: string;
    status: string;
    host_video: string;
    participant_video: string;
    recording: string;
    date_range: string;
}

export type PaginatedZoomMeetings = PaginatedData<ZoomMeeting>;
export type ZoomMeetingModalState = ModalState<ZoomMeeting>;

export interface ZoomMeetingsIndexProps {
    zoommeetings: PaginatedZoomMeetings;
    auth: AuthContext;
    users: any[];
    [key: string]: unknown;
}

export interface CreateZoomMeetingProps {
    onSuccess: () => void;
}

export interface EditZoomMeetingProps {
    zoommeeting: ZoomMeeting;
    onSuccess: () => void;
}

export interface ZoomMeetingShowProps {
    zoommeeting: ZoomMeeting;
    [key: string]: unknown;
}