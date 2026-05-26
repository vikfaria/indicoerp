import { NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { getSuperAdminMenu } from './menus/superadmin-menu';
import { getCompanyMenu } from './menus/company-menu';
import * as LucideIcons from 'lucide-react';

const packageMenuModules = import.meta.glob(
    '../../../packages/workdo/*/src/Resources/js/menus/*.ts',
    { eager: true }
);

let cachedMenuKey: string | null = null;
let cachedMenuItems: NavItem[] = [];

// Get role-based core menu items
const getCoreMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    if (userRoles.includes('superadmin')) {
        return getSuperAdminMenu(t);
    }
    return getCompanyMenu(t);
};

// Auto-load package menus based on activated packages
const getPackageMenuItems = (userRoles: string[], activatedPackages: string[], t: (key: string) => string): NavItem[] => {
    const menuItems: NavItem[] = [];
    const menuType = userRoles.includes('superadmin') ? 'superadmin-menu' : 'company-menu';

    // Ensure activatedPackages is an array before iterating
    if (!Array.isArray(activatedPackages) || activatedPackages.length === 0) {
        return menuItems;
    }

    activatedPackages.forEach(packageName => {
        const menuPath = `../../../packages/workdo/${packageName}/src/Resources/js/menus/${menuType}.ts`;
        const module = packageMenuModules[menuPath] as any;

        if (module) {
            Object.values(module).forEach((item: any) => {
                const result = typeof item === 'function' ? item(t) : item;
                const items = Array.isArray(result) ? result : [result];
                menuItems.push(...items);
            });
        }
    });

    return menuItems;
};

// Get custom menu items from database
const getCustomMenuItems = (customMenus: any[]): NavItem[] => {
    return customMenus.map((menu: any) => {
        // Convert string icon to Lucide icon component
        let iconComponent = null;
        if (menu.icon && typeof menu.icon === 'string') {
            const IconComponent = (LucideIcons as any)[menu.icon];
            if (IconComponent) {
                iconComponent = IconComponent;
            }
        }
        
        return {
            ...menu,
            icon: iconComponent,
        };
    });
};

// Group menu items by parent
const groupMenusByParent = (menuItems: NavItem[], packageMenuItems: NavItem[]): NavItem[] => {
    const groupedItems = [...menuItems];

    packageMenuItems.forEach(packageItem => {
        if (packageItem.parent) {
            const parentMenu = groupedItems.find(item =>
                item.name === packageItem.parent
            );

            if (parentMenu) {
                if (!parentMenu.children) {
                    parentMenu.children = [];
                }
                parentMenu.children.push({
                    ...packageItem,
                    parent: undefined
                });

                // Sort children by order
                if (parentMenu.children) {
                    parentMenu.children.sort((a, b) => (a.order || 999) - (b.order || 999));
                }
            } else {
                groupedItems.push(packageItem);
            }
        } else {
            groupedItems.push(packageItem);
        }
    });

    return groupedItems;
};

// Filter menu items based on permissions
const filterByPermission = (items: NavItem[], userPermissions: string[]): NavItem[] => {
    return items.filter(item => {
        const requiresSinglePermission = !!item.permission;
        const requiresAnyPermission = Array.isArray(item.permissionsAny) && item.permissionsAny.length > 0;

        const singlePermissionGranted = !requiresSinglePermission || userPermissions.includes(item.permission as string);
        const anyPermissionGranted = !requiresAnyPermission || (item.permissionsAny as string[]).some((permission) => userPermissions.includes(permission));

        if (!singlePermissionGranted || !anyPermissionGranted) {
            return false;
        }

        if (item.children) {
            item.children = filterByPermission(item.children, userPermissions);
            return item.children.length > 0;
        }

        return true;
    });
};

// Main function to get filtered menu items
export const allMenuItems = (): NavItem[] => {
    const { auth } = usePage().props as any;
    const { t, i18n } = useTranslation();
    const userPermissions = auth?.user?.permissions || [];
    const userRoles = auth?.user?.roles || [];
    const activatedPackages = auth?.user?.activatedPackages || [];
    const customMenus = auth?.customMenus || [];

    const menuCacheKey = [
        i18n.language,
        userRoles.join('|'),
        userPermissions.join('|'),
        activatedPackages.join('|'),
        customMenus
            .map((menu: any) => `${menu.id ?? menu.name ?? menu.title}:${menu.icon ?? ''}:${menu.parent ?? ''}:${menu.order ?? ''}:${menu.permission ?? ''}`)
            .join(';')
    ].join('::');

    if (cachedMenuKey === menuCacheKey) {
        return cachedMenuItems;
    }

    const coreMenuItems = getCoreMenuItems(userRoles, t);

    const packageMenuItems = getPackageMenuItems(userRoles, activatedPackages, t);
    
    const customMenuItems = getCustomMenuItems(customMenus);
    
    // Separate custom menus into parents and children
    const customParentMenus = customMenuItems.filter(menu => !menu.parent);
    const customChildMenus = customMenuItems.filter(menu => menu.parent);
    
    // First add custom parent menus to core menus
    const coreWithCustomParents = [...coreMenuItems, ...customParentMenus];
    
    // Then group all children (package + custom children) with their parents
    const allChildMenus = [...packageMenuItems, ...customChildMenus];
    const finalGroupedMenuItems = groupMenusByParent(coreWithCustomParents, allChildMenus);

    const sortedMenuItems = finalGroupedMenuItems.sort((a, b) => (a.order || 999) - (b.order || 999));

    const finalMenuItems = filterByPermission(sortedMenuItems, userPermissions);
    cachedMenuKey = menuCacheKey;
    cachedMenuItems = finalMenuItems;

    return finalMenuItems;
};
