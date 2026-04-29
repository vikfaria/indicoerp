import { useAIField } from '../hooks/useAIField';

import { usePage } from '@inertiajs/react';

// Global AI field extension using existing useFormFields pattern
export const aiField = (data: any, setData: any, errors: any, mode: string = 'create', fieldName?: string, fieldLabel?: string, module?: string, submodule?: string) => {
    // Check if AI config is provided in data object (new approach)
    const aiConfig = data._aiConfig;

    // Use config from data object or fallback to parameters (backward compatibility)
    const actualFieldName = aiConfig?.field || fieldName || 'name';
    const actualFieldLabel = aiConfig?.label || fieldLabel || 'Field';
    const actualModule = aiConfig?.module || module || 'general';
    const actualSubmodule = aiConfig?.submodule || submodule;
    const actualMode = aiConfig?.action || mode || 'create';

    if (!actualFieldName || !actualFieldLabel) {
        return [];
    }

    const { auth, companyAllSetting } = usePage().props as any;

    // Check if AIAssistant package is available and active
    const isAIPackageActive = auth?.user?.activatedPackages?.includes('AIAssistant');

    // Check if AI is configured using existing settings
    const aiProvider = companyAllSetting?.ai_provider;
    const aiModel = companyAllSetting?.ai_model;
    const aiApiKey = companyAllSetting?.ai_api_key;
    const isAIConfigured = !!(aiProvider && aiModel && aiApiKey);

    // Return empty array if package not active or not configured
    if (!isAIPackageActive || !isAIConfigured) {
        return [];
    }

    const { AIButton } = useAIField(
        actualFieldName,
        actualFieldLabel,
        (content: string) => setData(actualFieldName, content),
        data,
        actualModule,
        actualSubmodule
    );

    return [{
        id: `ai-${actualFieldName}`,
        order: 999, // Always appear last
        component: <AIButton />
    }];
};
