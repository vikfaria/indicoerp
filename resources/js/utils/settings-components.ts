import { lazy, type ComponentType, type LazyExoticComponent } from 'react';
import { usePage } from '@inertiajs/react';

// Core settings components
const coreComponents: Record<string, LazyExoticComponent<ComponentType<any>>> = {
  'brand-settings': lazy(() => import('@/pages/settings/components/brand-settings')),
  'company-settings': lazy(() => import('@/pages/settings/components/company-settings')),
  'system-settings': lazy(() => import('@/pages/settings/components/system-settings')),
  'currency-settings': lazy(() => import('@/pages/settings/components/currency-settings')),
  'seo-settings': lazy(() => import('@/pages/settings/components/seo-settings')),
  'storage-settings': lazy(() => import('@/pages/settings/components/storage-settings')),
  'email-settings': lazy(() => import('@/pages/settings/components/email-settings')),
  'pusher-settings': lazy(() => import('@/pages/settings/components/pusher-settings')),
  'email-notification-settings': lazy(() => import('@/pages/settings/components/email-notification-settings')),
  'cookie-settings': lazy(() => import('@/pages/settings/components/cookie-settings')),
  'bank-transfer-settings': lazy(() => import('@/pages/settings/components/bank-transfer-settings')),
  'company-overrides-settings': lazy(() => import('@/pages/settings/components/company-overrides-settings')),
  'cache-settings': lazy(() => import('@/pages/settings/components/cache-settings')),
};

// Auto-load package components
const getPackageComponents = (activatedPackages: string[]) => {
  try {
    const modules = import.meta.glob('../../../packages/workdo/*/src/Resources/js/settings/components/*.tsx');
    const packageComponents: Record<string, LazyExoticComponent<ComponentType<any>>> = {};

    activatedPackages.forEach(packageName => {
      Object.entries(modules).forEach(([path, moduleLoader]) => {
        if (path.includes(`/packages/workdo/${packageName}/`)) {
          const match = path.match(/\/([^/]+)\.tsx$/);
          if (match) {
            const componentName = match[1];
            packageComponents[componentName] = lazy(() => moduleLoader() as any);
          }
        }
      });
    });

    return packageComponents;
  } catch (error) {
    return {};
  }
};

// Combined components registry
export const getSettingsComponent = (componentName: string): ComponentType<any> | null => {
  const { auth } = usePage().props as any;
  const activatedPackages = auth?.user?.activatedPackages || [];
  const allComponents = { ...coreComponents, ...getPackageComponents(activatedPackages) };
  return allComponents[componentName as keyof typeof allComponents] || null;
};
