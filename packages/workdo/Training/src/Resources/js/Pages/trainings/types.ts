export interface Training {
    id: number;
    title: string;
    description?: string;
    training_type_id: number;
    trainer_id: number;
    branch_id: number;
    department_id: number;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    location?: string;
    max_participants?: number;
    cost?: number;
    status: 'scheduled' | 'ongoing' | 'completed' | 'cancelled';
    trainingType?: TrainingType;
    trainer?: Trainer;
    branch?: Branch;
    department?: Department;
    created_at: string;
    updated_at: string;
}

export interface TrainingType {
    id: number;
    name: string;
}

export interface Trainer {
    id: number;
    name: string;
}

export interface Branch {
    id: number;
    branch_name: string;
}

export interface Department {
    id: number;
    department_name: string;
    branch_id: number;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface TrainingsIndexProps {
    trainings: {
        data: Training[];
        links: any[];
        meta: any;
    };
    trainingTypes: TrainingType[];
    trainers: Trainer[];
    branches: Branch[];
    departments: Department[];
    users: User[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export interface TrainingFilters {
    title: string;
    status: string;
    branch_id: string;
    department_id: string;
}

export interface TrainingModalState {
    isOpen: boolean;
    mode: string;
    data: Training | null;
}