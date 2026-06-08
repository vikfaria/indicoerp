import { LucideIcon } from 'lucide-react';

export interface AssistantActivationMenuModuleState {
    key: string;
    label: string;
    blocked: boolean;
    block_count: number;
    message?: string | null;
    cta_label?: string | null;
    cta_href?: string | null;
    cta_action?: string | null;
    cta_message?: string | null;
    cta_tone?: string | null;
    primary_block?: {
        type?: string;
        code?: string;
        key?: string;
        label?: string;
        message?: string;
    } | null;
}

export interface AssistantActivationMenuState {
    meta: {
        catalog_version: string;
        generated_at: string;
        company_id?: number | null;
        company_name?: string | null;
        plan_label?: string | null;
        session_status?: string | null;
        readiness_state?: string | null;
        signature: string;
    };
    summary: {
        readiness_state: string;
        overall_score: number;
        critical_blocks_total: number;
        blocked_modules_total: number;
        available_modules_total?: number;
    };
    modules: Record<string, AssistantActivationMenuModuleState>;
    blocked_module_keys: string[];
}

export interface MenuAssistantActivation {
    status: 'blocked';
    code?: string;
    moduleKey?: string;
    moduleLabel?: string;
    moduleKeys?: string[];
    moduleLabels?: string[];
    blockCount?: number;
    ctaHref?: string;
    ctaLabel?: string;
    ctaAction?: string;
    ctaMessage?: string;
    ctaTone?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    type: string;
    email_verified_at?: string;
    lang?: string;
    permissions?: string[];
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    permission?: string;
    permissionsAny?: string[];
    children?: NavItem[];
    isActive?: boolean;
    parent?: string;
    name?: string;
    order?: number;
    activePaths?: string[];
    assistantActivation?: MenuAssistantActivation;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        permissions?: string[];
        roles?: string[];
        impersonating?: boolean;
    };
    flash?: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
        subscription_gate?: Record<string, any> | null;
        feature_gate?: Record<string, any> | null;
        module_gate?: Record<string, any> | null;
        plan_limit?: Record<string, any> | null;
    };
    assistantActivation?: {
        menu?: AssistantActivationMenuState;
    };
};
