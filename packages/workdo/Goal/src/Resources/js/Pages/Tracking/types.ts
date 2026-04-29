export interface Goal {
    id: number;
    goal_name: string;
    goal_description?: string;
    goal_type?: string;
    target_amount?: number;
    current_amount?: number;
    start_date?: string;
    target_date?: string;
    priority?: string;
    status?: string;
}

export interface GoalTracking {
    id: number;
    goal_id: number;
    tracking_date: string;
    previous_amount: number;
    contribution_amount: number;
    current_amount: number;
    progress_percentage: number;
    days_remaining: number;
    projected_completion_date: string | null;
    on_track_status: 'ahead' | 'on_track' | 'behind' | 'critical';
    goal?: Goal;
}

export interface CreateTrackingFormData {
    goal_id: string;
    tracking_date: string;
    previous_amount: number;
    contribution_amount: number;
    current_amount: number;
    progress_percentage: number;
    days_remaining: number;
    projected_completion_date: string;
    on_track_status: string;
}

export interface EditTrackingFormData {
    goal_id: string;
    tracking_date: string;
    previous_amount: number;
    contribution_amount: number;
    current_amount: number;
    progress_percentage: number;
    days_remaining: number;
    projected_completion_date: string;
    on_track_status: string;
}

export interface CreateTrackingProps {
    goals: Goal[];
    onSuccess: () => void;
}

export interface EditTrackingProps {
    tracking: GoalTracking;
    goals: Goal[];
    onSuccess: () => void;
}

export interface TrackingIndexProps {
    trackings: any;
    goals: Goal[];
    auth: any;
}

export interface TrackingFilters {
    goal_id: string;
    on_track_status: string;
    date_range: string;
}

export interface TrackingModalState {
    isOpen: boolean;
    mode: string;
    data: GoalTracking | null;
}