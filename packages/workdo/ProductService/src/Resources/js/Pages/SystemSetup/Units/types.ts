export interface Unit {
    id: number;
    unit_name: string;
    created_at: string;
}

export interface UnitsIndexProps {
    units: Unit[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export interface UnitModalState {
    isOpen: boolean;
    mode: string;
    data: Unit | null;
}

export interface UnitFormData {
    unit_name: string;
}

export interface UnitCreateProps {
    onSuccess: () => void;
}

export interface UnitEditProps {
    unit: Unit;
    onSuccess: () => void;
}