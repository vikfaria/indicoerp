export const ACCOUNT_TYPE_OPTIONS = [
    { value: 'current', label: 'Checking' },
    { value: 'savings', label: 'Savings' },
    { value: 'credit', label: 'Credit' },
    { value: 'loan', label: 'Loan' },
    { value: 'cash', label: 'Cash' },
    { value: 'petty_cash', label: 'Petty Cash' },
    { value: 'cashbox', label: 'Cashbox' },
    { value: 'mobile_money', label: 'Mobile Money' },
] as const;

const LEGACY_ACCOUNT_TYPE_MAP: Record<string, string> = {
    '0': 'current',
    '1': 'savings',
    '2': 'credit',
    '3': 'loan',
};

export function normalizeAccountType(value: string | number | null | undefined): string {
    const raw = String(value ?? '').trim().toLowerCase();
    if (raw === '') {
        return 'current';
    }

    return LEGACY_ACCOUNT_TYPE_MAP[raw] ?? raw;
}

export function getAccountTypeLabel(value: string | number | null | undefined): string {
    const normalized = normalizeAccountType(value);
    return ACCOUNT_TYPE_OPTIONS.find((option) => option.value === normalized)?.label ?? normalized;
}

export function isCashAccountType(value: string | number | null | undefined): boolean {
    return ['cash', 'petty_cash', 'cashbox', 'caixa', 'caixa_menor'].includes(normalizeAccountType(value));
}
