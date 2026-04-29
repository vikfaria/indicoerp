export interface Tax {
    id: number;
    tax_name: string;
    rate: number;
    created_at: string;
}

export interface TaxesIndexProps {
    taxes: Tax[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export interface TaxModalState {
    isOpen: boolean;
    mode: string;
    data: Tax | null;
}

export interface TaxFormData {
    tax_name: string;
    rate: number;
}

export interface TaxCreateProps {
    onSuccess: () => void;
}

export interface TaxEditProps {
    tax: Tax;
    onSuccess: () => void;
}