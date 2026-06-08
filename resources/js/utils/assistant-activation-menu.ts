import { AssistantActivationMenuState, MenuAssistantActivation, NavItem } from '@/types';

type DecoratedMenuResult = {
    item: NavItem;
    activation?: MenuAssistantActivation;
    moduleKeys: string[];
};

const normalizePath = (href?: string): string => {
    if (!href) {
        return '';
    }

    try {
        const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
        return new URL(href, base).pathname;
    } catch {
        return href;
    }
};

const MENU_ROUTE_MODULE_KEYS: Record<string, string[]> = {
    [normalizePath(route('account.index'))]: ['accounting'],
    [normalizePath(route('account.chart-of-accounts.index'))]: ['accounting'],
    [normalizePath(route('account.reports.index'))]: ['accounting'],
    [normalizePath(route('account.account-types.index'))]: ['accounting'],
    [normalizePath(route('account.customers.index'))]: ['billing'],
    [normalizePath(route('sales-invoices.index'))]: ['billing'],
    [normalizePath(route('sales-returns.index'))]: ['billing'],
    [normalizePath(route('product-service.items.index'))]: ['billing'],
    [normalizePath(route('product-service.stock.index'))]: ['billing'],
    [normalizePath(route('product-service.item-categories.index'))]: ['billing'],
    [normalizePath(route('sce.fiscal.index'))]: ['billing', 'accounting'],
    [normalizePath(route('sce.fiscal.series'))]: ['billing'],
    [normalizePath(route('sce.fiscal.calendar'))]: ['billing', 'accounting'],
    [normalizePath(route('sce.fiscal.pgc'))]: ['accounting'],
    [normalizePath(route('sce.fiscal.saft-export'))]: ['accounting'],
    [normalizePath(route('sce.journals.index'))]: ['accounting'],
    [normalizePath(route('sce.monthly-closing.index'))]: ['billing', 'accounting'],
    [normalizePath(route('hrm.index'))]: ['hr'],
    [normalizePath(route('hrm.employees.index'))]: ['hr'],
    [normalizePath(route('hrm.set-salary.index'))]: ['hr'],
    [normalizePath(route('hrm.payrolls.index'))]: ['hr'],
    [normalizePath(route('hrm.leave-types.index'))]: ['hr'],
    [normalizePath(route('hrm.leave-applications.index'))]: ['hr'],
    [normalizePath(route('hrm.leave-balance.index'))]: ['hr'],
    [normalizePath(route('hrm.working-days.index'))]: ['hr'],
    [normalizePath(route('hrm.holidays.index'))]: ['hr'],
    [normalizePath(route('hrm.awards.index'))]: ['hr'],
    [normalizePath(route('hrm.promotions.index'))]: ['hr'],
    [normalizePath(route('hrm.resignations.index'))]: ['hr'],
    [normalizePath(route('hrm.terminations.index'))]: ['hr'],
    [normalizePath(route('hrm.warnings.index'))]: ['hr'],
    [normalizePath(route('hrm.complaints.index'))]: ['hr'],
    [normalizePath(route('hrm.employee-transfers.index'))]: ['hr'],
    [normalizePath(route('hrm.documents.index'))]: ['hr'],
    [normalizePath(route('hrm.acknowledgments.index'))]: ['hr'],
    [normalizePath(route('hrm.announcements.index'))]: ['hr'],
    [normalizePath(route('hrm.events.index'))]: ['hr'],
    [normalizePath(route('hrm.branches.index'))]: ['hr'],
    [normalizePath(route('account.bank-accounts.index'))]: ['treasury'],
    [normalizePath(route('account.bank-transactions.index'))]: ['treasury'],
    [normalizePath(route('account.bank-transfers.index'))]: ['treasury'],
    [normalizePath(route('account.customer-payments.index'))]: ['treasury'],
    [normalizePath(route('account.vendor-payments.index'))]: ['treasury'],
};

const unique = (values: string[]): string[] => Array.from(new Set(values.filter(Boolean)));

const resolveModuleKeysForPath = (pathname: string): string[] => MENU_ROUTE_MODULE_KEYS[pathname] ?? [];

const resolveItemModuleKeys = (item: NavItem): string[] => {
    const paths = unique([
        item.href,
        ...(item.activePaths ?? []),
    ].map(normalizePath));

    return unique(paths.flatMap((path) => resolveModuleKeysForPath(path)));
};

const buildActivation = (
    moduleKeys: string[],
    menuState: AssistantActivationMenuState | null
): MenuAssistantActivation | undefined => {
    if (! menuState || moduleKeys.length === 0) {
        return undefined;
    }

    const activeModuleStates = moduleKeys
        .map((moduleKey) => menuState.modules[moduleKey])
        .filter((state) => Boolean(state?.blocked));

    if (activeModuleStates.length === 0) {
        return undefined;
    }

    const moduleLabels = unique(activeModuleStates.map((state) => state.label));
    const blockCount = activeModuleStates.reduce((total, state) => total + (state.block_count || 0), 0);
    const primaryState = activeModuleStates[0];

    return {
        status: 'blocked',
        code: primaryState?.primary_block?.code ?? 'onboarding_pending',
        moduleKey: primaryState?.key,
        moduleLabel: primaryState?.label,
        moduleKeys,
        moduleLabels,
        blockCount,
        ctaHref: primaryState?.cta_href ?? route('onboarding.index'),
        ctaLabel: primaryState?.cta_label ?? 'Abrir onboarding',
        ctaAction: primaryState?.cta_action ?? 'review',
        ctaMessage: primaryState?.cta_message ?? 'Abra o onboarding para resolver as pendências deste módulo.',
        ctaTone: primaryState?.cta_tone ?? 'secondary',
    };
};

const decorateMenuItem = (
    item: NavItem,
    menuState: AssistantActivationMenuState | null
): DecoratedMenuResult => {
    const decoratedChildren = (item.children ?? []).map((child) => decorateMenuItem(child, menuState));
    const children = decoratedChildren.map((result) => result.item);
    const childModuleKeys = unique(decoratedChildren.flatMap((result) => result.moduleKeys));
    const directModuleKeys = resolveItemModuleKeys(item);
    const moduleKeys = unique([...directModuleKeys, ...childModuleKeys]);
    const activation = buildActivation(moduleKeys, menuState);

    return {
        item: {
            ...item,
            children: children.length > 0 ? children : item.children,
            assistantActivation: activation,
        },
        activation,
        moduleKeys,
    };
};

export const decorateMenuItemsWithAssistantActivation = (
    items: NavItem[],
    menuState: AssistantActivationMenuState | null
): NavItem[] => {
    return items.map((item) => decorateMenuItem(item, menuState).item);
};
