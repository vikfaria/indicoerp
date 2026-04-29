import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface Goal {
    id: number;
    goal_name: string;
    goal_description?: string;
    category_id: number;
    goal_type: 'savings' | 'investment' | 'debt_reduction' | 'revenue' | 'expense_reduction';
    target_amount: number;
    current_amount: number;
    start_date: string;
    target_date: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    status: 'draft' | 'active' | 'paused' | 'completed' | 'cancelled';
    account_id?: number;
    category?: {
        id: number;
        category_name: string;
        color_code: string;
    };
    account?: {
        id: number;
        name: string;
    };
    created_at: string;
}

export interface CreateGoalFormData {
    goal_name: string;
    goal_description: string;
    category_id: number;
    goal_type: 'savings' | 'investment' | 'debt_reduction' | 'revenue' | 'expense_reduction';
    target_amount: number;
    current_amount: number;
    start_date: string;
    target_date: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    status: 'draft' | 'active' | 'paused' | 'completed' | 'cancelled';
    account_id?: number;
}

export interface EditGoalFormData {
    goal_name: string;
    goal_description: string;
    category_id: number;
    goal_type: 'savings' | 'investment' | 'debt_reduction' | 'revenue' | 'expense_reduction';
    target_amount: number;
    current_amount: number;
    start_date: string;
    target_date: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    status: 'draft' | 'active' | 'paused' | 'completed' | 'cancelled';
    account_id?: number;
}

export interface GoalFilters {
    goal_name: string;
    goal_type: string;
    status: string;
    priority: string;
    category_id: string;
}

export type PaginatedGoals = PaginatedData<Goal>;
export type GoalModalState = ModalState<Goal>;

export interface GoalsIndexProps {
    goals: PaginatedGoals;
    categories: Array<{
        id: number;
        category_name: string;
        color_code: string;
    }>;
    chartOfAccounts: Array<{
        id: number;
        name: string;
    }>;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateGoalProps {
    onSuccess: () => void;
}

export interface EditGoalProps {
    goal: Goal;
    onSuccess: () => void;
}