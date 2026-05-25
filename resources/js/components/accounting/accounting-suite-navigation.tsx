import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

type SectionKey = 'accounting' | 'fiscal' | 'tax' | 'assets' | 'reports';

interface AccountingSuiteNavigationProps {
    section: SectionKey;
    className?: string;
}

interface NavItem {
    label: string;
    href: string;
}

function getPath(href: string): string {
    try {
        return new URL(href, 'http://localhost').pathname;
    } catch (_error) {
        return href.split('?')[0];
    }
}

export default function AccountingSuiteNavigation({ section, className }: AccountingSuiteNavigationProps) {
    const page = usePage();
    const { t } = useTranslation();
    const currentPath = page.url.split('?')[0];

    const sections: Array<NavItem & { key: SectionKey }> = [
        { key: 'accounting', label: t('Contabilidade SCE'), href: route('sce.journals.index') },
        { key: 'fiscal', label: t('Fiscal'), href: route('sce.fiscal.index') },
        { key: 'tax', label: t('Impostos'), href: route('sce.tax.vat-map') },
        { key: 'assets', label: t('Activos'), href: route('sce.fixed-assets.index') },
        { key: 'reports', label: t('Relatórios'), href: route('sce.reports.balance-sheet') },
    ];

    const sectionLinks: Record<SectionKey, NavItem[]> = {
        accounting: [
            { label: t('Diários'), href: route('sce.journals.index') },
            { label: t('Fecho Mensal'), href: route('sce.monthly-closing.index') },
        ],
        fiscal: [
            { label: t('Perfil Fiscal'), href: route('sce.fiscal.index') },
            { label: t('Calendário Fiscal'), href: route('sce.fiscal.calendar') },
            { label: t('Plano de Contas PGC'), href: route('sce.fiscal.pgc') },
            { label: t('Séries Documentais'), href: route('sce.fiscal.series') },
        ],
        tax: [
            { label: t('Mapa IVA'), href: route('sce.tax.vat-map') },
            { label: t('IRPC'), href: route('sce.tax.irpc') },
            { label: t('Retenções na Fonte'), href: route('sce.tax.withholding') },
            { label: t('Declaração de Retenções'), href: route('sce.tax.withholding.declaration.page') },
            { label: t('Modelo 20'), href: route('sce.tax.modelo20.page') },
            { label: t('Declaração Anual'), href: route('sce.tax.annual-declaration.page') },
        ],
        assets: [
            { label: t('Activos Fixos'), href: route('sce.fixed-assets.index') },
            { label: t('Novo Activo'), href: route('sce.fixed-assets.create') },
        ],
        reports: [
            { label: t('Balanço'), href: route('sce.reports.balance-sheet') },
            { label: t('Demonstração Resultados'), href: route('sce.reports.income-statement') },
            { label: t('Fluxos de Caixa'), href: route('sce.reports.cash-flow') },
        ],
    };

    return (
        <div className={cn('space-y-2', className)}>
            <div className="flex gap-2 overflow-x-auto pb-1">
                {sections.map((item) => {
                    const itemPath = getPath(item.href);
                    const isActive = section === item.key || currentPath === itemPath || currentPath.startsWith(itemPath + '/');

                    return (
                        <Link
                            key={item.key}
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

            <div className="flex gap-2 overflow-x-auto pb-1">
                {sectionLinks[section].map((item) => {
                    const itemPath = getPath(item.href);
                    const isActive = currentPath === itemPath || currentPath.startsWith(itemPath + '/');

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'inline-flex items-center whitespace-nowrap rounded-md border px-3 py-1.5 text-sm transition-colors',
                                isActive
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-muted-foreground/20 text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                            )}
                        >
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
