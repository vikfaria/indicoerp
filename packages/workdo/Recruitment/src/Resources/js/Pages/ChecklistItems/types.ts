import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface OnboardingChecklist {
    id: number;
    name: string;
}

export interface ChecklistItem {
    id: number;
    checklist_id: number;
    task_name: string;
    description?: string;
    category?: string;
    assigned_to_role?: string;
    due_day?: number;
    is_required: boolean;
    status: boolean;
    checklist?: OnboardingChecklist;
    created_at: string;
}

export interface CreateChecklistItemFormData {
    checklist_id: string;
    task_name: string;
    description: string;
    category: string;
    assigned_to_role: string;
    due_day: string;
    is_required: boolean;
    status: boolean;
}

export interface EditChecklistItemFormData {
    checklist_id: string;
    task_name: string;
    description: string;
    category: string;
    assigned_to_role: string;
    due_day: string;
    is_required: boolean;
    status: boolean;
}

export interface ChecklistItemFilters {
    task_name: string;
    description: string;
    assigned_to_role: string;
    checklist_id: string;
    category: string;
    is_required: string;
    status: string;
}

export type PaginatedChecklistItems = PaginatedData<ChecklistItem>;
export type ChecklistItemModalState = ModalState<ChecklistItem>;

export interface ChecklistItemsIndexProps {
    checklistitems: PaginatedChecklistItems;
    auth: AuthContext;
    onboardingchecklists: any[];
    [key: string]: unknown;
}

export interface CreateChecklistItemProps {
    onSuccess: () => void;
}

export interface EditChecklistItemProps {
    checklistitem: ChecklistItem;
    onSuccess: () => void;
}

export interface ChecklistItemShowProps {
    checklistitem: ChecklistItem;
    [key: string]: unknown;
}