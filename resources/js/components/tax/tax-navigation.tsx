import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface TaxNavigationProps {
    className?: string;
}

interface TaxNavItem {
    label: string;
    href: string;
}

export default function TaxNavigation({ className }: TaxNavigationProps) {
    const page = usePage();
    const { t } = useTranslation();
    const userPermissions: string[] = (page.props as any)?.auth?.user?.permissions || [];

    const canViewTax = userPermissions.includes('view-tax-summary') || userPermissions.includes('manage-account-reports');

    if (!canViewTax) {
        return null;
    }

    const items: TaxNavItem[] = [
        { label: t('Mapa IVA'), href: route('sce.tax.vat-map') },
        { label: t('IRPC'), href: route('sce.tax.irpc') },
        { label: t('Retenções na Fonte'), href: route('sce.tax.withholding') },
        { label: t('Declaração de Retenções'), href: route('sce.tax.withholding.declaration.page') },
        { label: t('Modelo 20'), href: route('sce.tax.modelo20.page') },
        { label: t('Declaração Anual'), href: route('sce.tax.annual-declaration.page') },
    ];

    const currentPath = page.url.split('?')[0];

    return (
        <div className={cn('flex gap-2 overflow-x-auto pb-1', className)}>
            {items.map((item) => {
                const itemPath = new URL(item.href, window.location.origin).pathname;
                const isActive = currentPath === itemPath || currentPath.startsWith(itemPath + '/');

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'inline-flex items-center whitespace-nowrap rounded-md border px-3 py-1.5 text-sm transition-colors',
                            isActive
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-muted-foreground/30 text-muted-foreground hover:bg-muted/60 hover:text-foreground'
                        )}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </div>
    );
}
