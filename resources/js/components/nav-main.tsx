import { Link, usePage } from '@inertiajs/react';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuItem, SidebarMenuButton, SidebarMenuSub, SidebarMenuSubItem, SidebarMenuSubButton } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown, Lock } from 'lucide-react';
import { MenuAssistantActivation, NavItem } from '@/types';
import { useTranslation } from 'react-i18next';

export function NavMain({ items = [], searchQuery = "" }: { items: NavItem[], searchQuery?: string }) {
    const page = usePage();
    const { t } = useTranslation();
    const translateTitle = (title?: string) => title ? t(title) : '';

    // Filter items based on search query
    const filterItems = (items: NavItem[], query: string): NavItem[] => {
        if (!query) return items;
        const queryLower = query.toLowerCase();
        
        return items.reduce((acc, item) => {
            const translatedTitle = translateTitle(item.title);
            const matchesTitle = item.title.toLowerCase().includes(queryLower) || translatedTitle.toLowerCase().includes(queryLower);
            const filteredChildren = item.children ? filterItems(item.children, query) : [];
            
            if (matchesTitle || filteredChildren.length > 0) {
                acc.push({
                    ...item,
                    children: filteredChildren.length > 0 ? filteredChildren : item.children
                });
            }
            return acc;
        }, [] as NavItem[]);
    };

    const filteredItems = filterItems(items, searchQuery);

    // Helper function to check if URL matches (exact or starts with for detail pages)
    const isUrlActive = (itemPath: string, activePaths?: string[], exact = false): boolean => {
        const currentPath = page.url.split('?')[0];

        if (exact) {
            if (currentPath === itemPath) return true;
        } else {
            if (currentPath === itemPath || currentPath.startsWith(itemPath + '/')) return true;
        }

        if (activePaths) {
            return activePaths.some(p => {
                try {
                    const pathToCheck = p.startsWith('http') ? new URL(p).pathname : p;
                    if (exact) return currentPath === pathToCheck;
                    return currentPath === pathToCheck || currentPath.startsWith(pathToCheck + '/');
                } catch {
                    return currentPath.includes(p);
                }
            });
        }
        return false;
    };

    // Helper function to check if any child is active (recursive for nested children)
    const isChildActive = (children: NavItem[]): boolean => {
        return children.some(child => {
            if (child.href) {
                const childPath = new URL(child.href, window.location.origin).pathname;
                return isUrlActive(childPath, child.activePaths);
            }
            if (child.activePaths) {
                return isUrlActive('', child.activePaths);
            }
            if (child.children) {
                return isChildActive(child.children);
            }
            return false;
        });
    };

    const buildBlockedTooltip = (title: string, activation: MenuAssistantActivation) => ({
        children: (
            <div className="space-y-2 max-w-sm">
                <p className="text-sm font-medium leading-none">{title}</p>
                <p className="text-xs text-muted-foreground">
                    {activation.moduleLabels && activation.moduleLabels.length > 0
                        ? t('Existem {{count}} pendências críticas em {{modules}}.', {
                            count: activation.blockCount || 0,
                            modules: activation.moduleLabels.join(', '),
                        })
                        : t('Esta secção tem pendências críticas de onboarding.')}
                </p>
                {activation.criticalItems && activation.criticalItems.length > 0 && (
                    <div className="space-y-1.5 rounded-md border border-amber-200/70 bg-amber-50/70 p-2">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-900">
                            {t('Pendências exactas')}
                        </p>
                        <div className="max-h-48 space-y-1 overflow-auto pr-1">
                            {activation.criticalItems.slice(0, 3).map((item, index) => (
                                <div key={`${item.moduleKey ?? 'module'}-${item.key ?? item.code ?? index}`} className="rounded-md bg-white/90 px-2 py-1 shadow-sm ring-1 ring-amber-200/60">
                                    <p className="text-xs font-medium text-slate-900">
                                        {item.label ?? t('Pendência sem título')}
                                    </p>
                                    {item.message && (
                                        <p className="mt-0.5 text-[11px] leading-snug text-slate-600">
                                            {item.message}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                        {activation.criticalItems.length > 3 && (
                            <p className="text-[11px] text-muted-foreground">
                                {t('Mostrando as 3 primeiras pendências exactas.')}
                            </p>
                        )}
                    </div>
                )}
                <p className="text-xs text-muted-foreground">
                    {activation.ctaMessage ?? t('Abra o onboarding para resolver este bloqueio.')}
                </p>
                <Link
                    href={activation.ctaHref ?? route('onboarding.index')}
                    className="text-xs font-medium text-primary hover:underline"
                >
                    {activation.ctaLabel ?? t('Abrir onboarding')}
                </Link>
            </div>
        ),
        className: 'max-w-xs',
    });

    return (
        <SidebarGroup>
            <SidebarMenu>
                {filteredItems.map((item, itemIndex) => {
                  const itemTitle = translateTitle(item.title);
                  const itemPath = item.href ? new URL(item.href, window.location.origin).pathname : '';
                  const isActive = !!(itemPath && isUrlActive(itemPath));
                  const itemActivation = item.assistantActivation?.status === 'blocked' ? item.assistantActivation : undefined;
                  const itemIsBlocked = !!itemActivation;

                  // Check if any child is active for parent menus
                  const hasActiveChild = item.children ? isChildActive(item.children) : false;
                  const shouldBeActive = isActive || hasActiveChild;
                    if (item.children && item.children.length > 0) {
                        return (
                            <SidebarMenuItem key={`${item.title}-${itemIndex}`}>
                                {/* Expanded sidebar - use collapsible */}
                                <Collapsible asChild defaultOpen={shouldBeActive} className="group/collapsible group-data-[collapsible=icon]:hidden">
                                    <div>
                                        <CollapsibleTrigger asChild>
                                            <SidebarMenuButton
                                                tooltip={itemActivation ? buildBlockedTooltip(itemTitle, itemActivation) : itemTitle}
                                                showTooltipWhenExpanded={itemIsBlocked}
                                                isActive={shouldBeActive}
                                                data-current={false}
                                                data-blocked={itemIsBlocked}
                                                className={itemIsBlocked ? 'data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : undefined}
                                            >
                                                {item.icon && <item.icon />}
                                                {itemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                <span>{itemTitle}</span>
                                                <ChevronDown className="ml-auto h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                            </SidebarMenuButton>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <SidebarMenuSub>
                                                {item.children.map((subItem) => {
                                                    const subItemTitle = translateTitle(subItem.title);
                                                    const subItemActive = !!(subItem.href && isUrlActive(new URL(subItem.href, window.location.origin).pathname, subItem.activePaths));
                                                    const hasActiveSubChild = subItem.children ? isChildActive(subItem.children) : false;
                                                    const subItemShouldBeActive = subItemActive || hasActiveSubChild;
                                                    const subItemActivation = subItem.assistantActivation?.status === 'blocked' ? subItem.assistantActivation : undefined;
                                                    const subItemIsBlocked = !!subItemActivation;
                                                    
                                                    if (subItem.children && subItem.children.length > 0) {
                                                        return (
                                                            <SidebarMenuSubItem key={subItem.title}>
                                                                <Collapsible asChild defaultOpen={subItemShouldBeActive} className="group/subcollapsible">
                                                                    <div>
                                                                        <CollapsibleTrigger asChild>
                                                                            <SidebarMenuSubButton
                                                                                tooltip={subItemActivation ? buildBlockedTooltip(subItemTitle, subItemActivation) : undefined}
                                                                                showTooltipWhenExpanded={subItemIsBlocked}
                                                                                isActive={subItemShouldBeActive}
                                                                                data-current={false}
                                                                                data-blocked={subItemIsBlocked}
                                                                                className={subItemIsBlocked ? 'data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : undefined}
                                                                            >
                                                                                {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                                {subItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                                                <span>{subItemTitle}</span>
                                                                                <ChevronDown className="ml-auto h-3 w-3 transition-transform group-data-[state=open]/subcollapsible:rotate-180" />
                                                                            </SidebarMenuSubButton>
                                                                        </CollapsibleTrigger>
                                                                        <CollapsibleContent>
                                                                            <SidebarMenuSub>
                                                                                {subItem.children.map((subSubItem) => {
                                                                                    const subSubItemTitle = translateTitle(subSubItem.title);
                                                                                    const isSubSubActive = !!(subSubItem.href && isUrlActive(new URL(subSubItem.href, window.location.origin).pathname, subSubItem.activePaths));
                                                                                    const subSubItemActivation = subSubItem.assistantActivation?.status === 'blocked' ? subSubItem.assistantActivation : undefined;
                                                                                    const subSubItemIsBlocked = !!subSubItemActivation;
                                                                                    return (
                                                                                    <SidebarMenuSubItem key={subSubItem.title}>
                                                                                        <SidebarMenuSubButton
                                                                                            tooltip={subSubItemActivation ? buildBlockedTooltip(subSubItemTitle, subSubItemActivation) : undefined}
                                                                                            showTooltipWhenExpanded={subSubItemIsBlocked}
                                                                                            asChild
                                                                                            isActive={isSubSubActive}
                                                                                            data-current={isSubSubActive}
                                                                                            data-blocked={subSubItemIsBlocked}
                                                                                            className={subSubItemIsBlocked ? 'text-sm data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : 'text-sm'}
                                                                                        >
                                                                                            <Link href={subSubItem.href!}>
                                                                                                {subSubItem.icon && <subSubItem.icon className="h-3 w-3" />}
                                                                                                {subSubItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                                                                <span>{subSubItemTitle}</span>
                                                                                            </Link>
                                                                                        </SidebarMenuSubButton>
                                                                                    </SidebarMenuSubItem>
                                                                                );
                                                                                })}</SidebarMenuSub>
                                                                        </CollapsibleContent>
                                                                    </div>
                                                                </Collapsible>
                                                            </SidebarMenuSubItem>
                                                        );
                                                    }
                                                    
                                                    return (
                                                            <SidebarMenuSubItem key={subItem.title}>
                                                                <SidebarMenuSubButton
                                                                    tooltip={subItemActivation ? buildBlockedTooltip(subItemTitle, subItemActivation) : undefined}
                                                                    showTooltipWhenExpanded={subItemIsBlocked}
                                                                    asChild
                                                                    isActive={subItemActive}
                                                                    data-current={subItemActive}
                                                                    data-blocked={subItemIsBlocked}
                                                                    className={subItemIsBlocked ? 'data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : undefined}
                                                                >
                                                                    <Link href={subItem.href!}>
                                                                        {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                        {subItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                                        <span>{subItemTitle}</span>
                                                                    </Link>
                                                                </SidebarMenuSubButton>
                                                            </SidebarMenuSubItem>
                                                        );
                                                })}
                                            </SidebarMenuSub>
                                        </CollapsibleContent>
                                    </div>
                                </Collapsible>
                                
                                {/* Collapsed sidebar - use dropdown */}
                                <div className="hidden group-data-[collapsible=icon]:block" onMouseEnter={(e) => e.stopPropagation()} onMouseLeave={(e) => e.stopPropagation()}>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <SidebarMenuButton
                                                tooltip={itemActivation ? buildBlockedTooltip(itemTitle, itemActivation) : itemTitle}
                                                showTooltipWhenExpanded={itemIsBlocked}
                                                isActive={shouldBeActive}
                                                data-blocked={itemIsBlocked}
                                                className={itemIsBlocked ? 'data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : undefined}
                                            >
                                                {item.icon && <item.icon />}
                                                {itemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                <span>{itemTitle}</span>
                                            </SidebarMenuButton>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="right" align="start" className="w-48">
                                        {item.children.map((subItem) => {
                                                const subItemTitle = translateTitle(subItem.title);
                                                const subItemActivation = subItem.assistantActivation?.status === 'blocked' ? subItem.assistantActivation : undefined;
                                                const subItemIsBlocked = !!subItemActivation;
                                                if (subItem.children && subItem.children.length > 0) {
                                                    return (
                                                        <DropdownMenu key={subItem.title}>
                                                            <DropdownMenuTrigger asChild>
                                                                <DropdownMenuItem className={subItemIsBlocked ? 'flex items-center gap-2 cursor-pointer text-amber-700' : 'flex items-center gap-2 cursor-pointer'}>
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                    {subItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                                    <span>{subItemTitle}</span>
                                                                    <ChevronDown className="ml-auto h-3 w-3" />
                                                                </DropdownMenuItem>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent side="right" align="start" className="w-44">
                                                                {subItem.children.map((subSubItem) => {
                                                                    const subSubItemTitle = translateTitle(subSubItem.title);
                                                                    const subSubItemActivation = subSubItem.assistantActivation?.status === 'blocked' ? subSubItem.assistantActivation : undefined;
                                                                    const subSubItemIsBlocked = !!subSubItemActivation;
                                                                    return (
                                                                        <DropdownMenuItem key={subSubItem.title} asChild>
                                                                            <Link href={subSubItem.href!} className={subSubItemIsBlocked ? 'flex items-center gap-2 text-amber-700' : 'flex items-center gap-2'}>
                                                                                {subSubItem.icon && <subSubItem.icon className="h-3 w-3" />}
                                                                                {subSubItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                                                <span className="text-sm">{subSubItemTitle}</span>
                                                                            </Link>
                                                                        </DropdownMenuItem>
                                                                    );
                                                                })}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    );
                                                }
                                                
                                                return (
                                                    <DropdownMenuItem key={subItem.title} asChild>
                                                        <Link href={subItem.href!} className="flex items-center gap-2">
                                                            {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                            {subItemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                                            <span>{subItemTitle}</span>
                                                        </Link>
                                                    </DropdownMenuItem>
                                                );
                                            })}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={`${item.title}-${itemIndex}`}>
                            <SidebarMenuButton
                                asChild
                                isActive={shouldBeActive}
                                data-current={false}
                                data-blocked={itemIsBlocked}
                                showTooltipWhenExpanded={itemIsBlocked}
                                tooltip={itemActivation ? buildBlockedTooltip(itemTitle, itemActivation) : itemTitle}
                                className={itemIsBlocked ? 'data-[blocked=true]:bg-amber-50/80 data-[blocked=true]:text-amber-900 data-[blocked=true]:hover:bg-amber-100' : undefined}
                            >
                                <Link href={item.href!}>
                                    {item.icon && <item.icon />}
                                    {itemIsBlocked && <Lock className="h-3 w-3 text-amber-600" />}
                                    <span>{itemTitle}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
