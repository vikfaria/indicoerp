export interface ProjectBug {
    id: number;
    title: string;
    priority: 'Low' | 'Medium' | 'High';
    assigned_to?: string;
    stage_id: number;
    description?: string;
    project?: {
        id: number;
        name: string;
    };
    bugStage?: {
        id: number;
        name: string;
    };
    assignedUser?: {
        id: number;
        name: string;
        avatar?: string;
    };
    assignedUsers?: Array<{
        id: number;
        name: string;
        avatar?: string;
    }>;
    created_at: string;
}

export interface ProjectBugsIndexProps {
    bugs: {
        data: ProjectBug[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    project?: {
        id: number;
        name: string;
        teamMembers: Array<{ id: number; name: string; avatar?: string; }>;
    };
    teamMembers: Array<{ id: number; name: string; avatar?: string; }>;
    bugStages: Array<{ id: number; name: string; color?: string; order: number; }>;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export interface ProjectBugFilters {
    title: string;
    priority: string;
    user_id: string;
}

export interface ProjectBugModalState {
    isOpen: boolean;
    mode: string;
    data: ProjectBug | null;
}
