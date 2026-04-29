export interface WorkingDaysIndexProps {
    workingDays: string[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}