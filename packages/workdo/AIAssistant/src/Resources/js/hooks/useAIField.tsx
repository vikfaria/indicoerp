import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AIGenerationModal } from '../components/AIGenerationModal';

export const useAIField = (
    fieldType: string,
    fieldLabel: string,
    setValue: Function,
    context?: any,
    module?: string,
    submodule?: string
) => {
    const { t } = useTranslation();
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleGenerate = (results: string[]) => {
        if (results.length > 0) {
            setValue(results[0]);
        }
    };

    const AIButton = () => (
        <>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        size="icon"
                        variant="outline"
                        onClick={() => setIsModalOpen(true)}
                        type="button"
                    >
                        <Sparkles className="h-4 w-4" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p className="text-center whitespace-pre-line break-words max-w-[85px]">
                        {t('Generate')} {fieldLabel} {t('with AI')}
                    </p>
                </TooltipContent>
            </Tooltip>

            <AIGenerationModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                fieldType={fieldType}
                fieldLabel={fieldLabel}
                onGenerate={handleGenerate}
                context={context}
                module={module}
                submodule={submodule}
            />
        </>
    );

    return { AIButton };
};
