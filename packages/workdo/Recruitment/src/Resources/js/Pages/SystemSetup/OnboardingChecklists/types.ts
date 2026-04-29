import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface OnboardingChecklist {
    id: number;
    name: string;
    description?: string;
    is_default: boolean;
    status: boolean;
    created_at: string;
    checklist_items_count?: number;
}

export interface OnboardingChecklistFormData {
    name: string;
    description: string;
    is_default: boolean;
    status: boolean;
}

export interface CreateOnboardingChecklistProps extends CreateProps {
}

export interface EditOnboardingChecklistProps extends EditProps<OnboardingChecklist> {
}

export type PaginatedOnboardingChecklists = PaginatedData<OnboardingChecklist>;
export type OnboardingChecklistModalState = ModalState<OnboardingChecklist>;

export interface OnboardingChecklistsIndexProps {
    onboardingchecklists: PaginatedOnboardingChecklists;
    auth: AuthContext;
    [key: string]: unknown;
}