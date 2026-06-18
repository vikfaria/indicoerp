import React from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatFileSize } from '@/utils/helpers';
import { HardDrive, AlertTriangle, ShieldCheck, ArrowRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export interface MediaStorageQuota {
  status: 'unlimited' | 'within_limit' | 'near_limit' | 'exceeded';
  limit_bytes: number;
  current_bytes: number;
  available_bytes: number | null;
  usage_percent: number | null;
  requested_bytes?: number;
  projected_bytes?: number;
  is_unlimited: boolean;
}

interface MediaStorageQuotaBannerProps {
  quota: MediaStorageQuota | null | undefined;
  upgradeHref?: string;
  compact?: boolean;
  className?: string;
}

export default function MediaStorageQuotaBanner({
  quota,
  upgradeHref,
  compact = false,
  className = '',
}: MediaStorageQuotaBannerProps) {
  const { t } = useTranslation();

  if (!quota) {
    return null;
  }

  const limitLabel = quota.is_unlimited || quota.limit_bytes < 0
    ? t('Unlimited storage')
    : formatFileSize(quota.limit_bytes);

  const currentLabel = formatFileSize(quota.current_bytes);
  const availableLabel = quota.is_unlimited || quota.available_bytes === null
    ? t('Unlimited')
    : formatFileSize(quota.available_bytes);

  const usagePercent = quota.is_unlimited
    ? 0
    : Math.max(0, Math.min(100, quota.usage_percent ?? 0));

  const statusConfig = (() => {
    switch (quota.status) {
      case 'exceeded':
        return {
          badge: t('Quota exceeded'),
          badgeClass: 'bg-red-100 text-red-700 border-red-200',
          icon: AlertTriangle,
          accent: 'bg-red-500',
          title: t('Storage limit reached'),
          description: t('Uploads are blocked until you free storage or upgrade your plan.'),
        };
      case 'near_limit':
        return {
          badge: t('Near limit'),
          badgeClass: 'bg-amber-100 text-amber-700 border-amber-200',
          icon: HardDrive,
          accent: 'bg-amber-500',
          title: t('Storage is almost full'),
          description: t('You are close to the storage limit. Clean up old files to avoid upload errors.'),
        };
      case 'unlimited':
        return {
          badge: t('Unlimited'),
          badgeClass: 'bg-emerald-100 text-emerald-700 border-emerald-200',
          icon: ShieldCheck,
          accent: 'bg-emerald-500',
          title: t('Unlimited storage'),
          description: t('This plan does not enforce a storage quota.'),
        };
      default:
        return {
          badge: t('Available'),
          badgeClass: 'bg-emerald-100 text-emerald-700 border-emerald-200',
          icon: HardDrive,
          accent: 'bg-emerald-500',
          title: t('Storage available'),
          description: t('Uploads can continue within the current plan quota.'),
        };
    }
  })();

  const Icon = statusConfig.icon;
  const paddingClass = compact ? 'p-3' : 'p-4';

  return (
    <div className={`rounded-2xl border bg-gradient-to-r from-background to-muted/40 ${paddingClass} ${className}`}>
      <div className="flex flex-col gap-4">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <div className="rounded-xl bg-primary/10 p-2 text-primary">
              <Icon className="h-5 w-5" />
            </div>
            <div className="space-y-1">
              <div className="flex flex-wrap items-center gap-2">
                <h4 className="font-semibold text-foreground">{statusConfig.title}</h4>
                <Badge variant="outline" className={statusConfig.badgeClass}>
                  {statusConfig.badge}
                </Badge>
              </div>
              <p className="text-sm text-muted-foreground">{statusConfig.description}</p>
            </div>
          </div>

          {upgradeHref && quota.status === 'exceeded' && (
            <Button asChild size="sm" variant="outline">
              <Link href={upgradeHref} className="inline-flex items-center gap-2">
                {t('View plan')}
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          )}
        </div>

        {!quota.is_unlimited && (
          <>
            <div className="h-2 overflow-hidden rounded-full bg-muted">
              <div
                className={`h-full rounded-full ${statusConfig.accent} transition-all`}
                style={{ width: `${usagePercent}%` }}
              />
            </div>

            <div className="grid gap-3 text-sm sm:grid-cols-3">
              <div className="rounded-xl border bg-background/80 p-3">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Current usage')}</p>
                <p className="mt-1 font-semibold text-foreground">{currentLabel}</p>
              </div>
              <div className="rounded-xl border bg-background/80 p-3">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Limit')}</p>
                <p className="mt-1 font-semibold text-foreground">{limitLabel}</p>
              </div>
              <div className="rounded-xl border bg-background/80 p-3">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Available space')}</p>
                <p className="mt-1 font-semibold text-foreground">{availableLabel}</p>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
