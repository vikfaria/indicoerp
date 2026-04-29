import React from 'react';
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { useTranslation } from 'react-i18next';
import InputError from '@/components/ui/input-error';
import { isPackageActive } from '@/utils/helpers';

export const createCalendarSyncField = (data: any, setData: any, errors: any) => {
    const { t } = useTranslation();
    
    if (!isPackageActive('Calendar')) {
        return [];
    }

    return [{
        id: 'calendar-sync',
        order: 100,
        component: (
            <div className="flex items-center space-x-2">
                <Switch
                    id="sync_to_google_calendar"
                    checked={data.sync_to_google_calendar || false}
                    onCheckedChange={(checked) => setData('sync_to_google_calendar', !!checked)}
                />
                <Label htmlFor="sync_to_google_calendar" className="cursor-pointer">{t('Sync to Google Calendar')}</Label>
                <InputError message={errors.sync_to_google_calendar} />
            </div>
        )
    }];
};