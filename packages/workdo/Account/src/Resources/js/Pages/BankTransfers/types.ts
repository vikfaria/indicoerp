export interface BankAccount {
    id: number;
    account_name: string;
    account_number: string;
    current_balance: number;
}

export interface BankTransfer {
    id: number;
    transfer_number: string;
    transfer_date: string;
    transfer_amount: number;
    transfer_charges: number;
    reference_number: string;
    description: string;
    status: 'pending' | 'completed' | 'failed';
    from_account: BankAccount;
    to_account: BankAccount;
    created_at: string;
}

export interface BankTransferFilters {
    transfer_number: string;
    status: string;
    from_account_id: string;
    to_account_id: string;
}

export interface BankTransferModalState {
    isOpen: boolean;
    mode: string;
    data: BankTransfer | null;
}

export interface BankTransfersIndexProps {
    banktransfers: {
        data: BankTransfer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any;
        meta: any;
    };
    bankaccounts: BankAccount[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}