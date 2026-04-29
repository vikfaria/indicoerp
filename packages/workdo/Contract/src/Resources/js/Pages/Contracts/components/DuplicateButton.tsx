import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Copy } from 'lucide-react';
import Duplicate from '../Duplicate';
import { Contract } from '../types';

interface DuplicateButtonProps {
    contract: Contract;
    variant?: 'ghost' | 'default' | 'outline';
    size?: 'sm' | 'default' | 'lg';
    showTooltip?: boolean;
    className?: string;
}

export default function DuplicateButton({ 
    contract, 
    variant = 'ghost', 
    size = 'sm', 
    showTooltip = true,
    className = ''
}: DuplicateButtonProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [isOpen, setIsOpen] = useState(false);

    if (!auth.user?.permissions?.includes('duplicate-contracts')) {
        return null;
    }

    const button = (
        <Button
            variant={variant}
            size={size}
            onClick={() => setIsOpen(true)}
            className={`${variant === 'ghost' ? 'h-8 w-8 p-0 text-orange-600 hover:text-orange-700' : ''} ${className}`}
        >
            <Copy className="h-4 w-4" />
            {size !== 'sm' && <span className="ml-2">{t('Duplicate')}</span>}
        </Button>
    );

    return (
        <>
            {showTooltip ? (
                <TooltipProvider>
                    <Tooltip >
                        <TooltipTrigger asChild>
                            {button}
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{t('Duplicate')}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            ) : (
                button
            )}

            <Dialog open={isOpen} onOpenChange={setIsOpen}>
                <Duplicate
                    contract={contract}
                    onSuccess={() => setIsOpen(false)}
                />
            </Dialog>
        </>
    );
}