import { useState } from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Clock, Save } from "lucide-react";
import SystemSetupSidebar from "../SystemSetupSidebar";
import { WorkingDaysIndexProps } from './types';

export default function Index() {
    const { t } = useTranslation();
    const { workingDays, auth } = usePage<WorkingDaysIndexProps>().props;

    const daysOfWeek = [
        { key: 'monday', label: t('Monday') },
        { key: 'tuesday', label: t('Tuesday') },
        { key: 'wednesday', label: t('Wednesday') },
        { key: 'thursday', label: t('Thursday') },
        { key: 'friday', label: t('Friday') },
        { key: 'saturday', label: t('Saturday') },
        { key: 'sunday', label: t('Sunday') }
    ];

    const { data, setData, put, processing } = useForm({
        working_days: workingDays || []
    });


    const handleDayChange = (day: string, checked: boolean) => {
        if (checked) {
            setData('working_days', [...data.working_days, day]);
        } else {
            setData('working_days', data.working_days.filter(d => d !== day));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.working-days.update'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                {label: t('System Setup')},
                {label: t('Working Days')}
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Working Days')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="working-days" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium flex items-center gap-2">
                                    <Clock className="h-5 w-5" />
                                    {t('Working Days')}
                                </h3>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="space-y-4">
                                    <p className="text-sm text-gray-600 mb-4">
                                        {t('Select the days of the week that are considered working days for your organization.')}
                                    </p>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {daysOfWeek.map((day) => (
                                            <div key={day.key} className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50">
                                                <Checkbox
                                                    id={day.key}
                                                    checked={data.working_days.includes(day.key)}
                                                    onCheckedChange={(checked) => handleDayChange(day.key, checked as boolean)}
                                                />
                                                <label 
                                                    htmlFor={day.key} 
                                                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                                >
                                                    {day.label}
                                                </label>
                                            </div>
                                        ))}
                                    </div>

                                    {auth.user?.permissions?.includes('edit-working-days') && (
                                        <div className="flex justify-end pt-4">
                                            <Button 
                                                type="submit" 
                                                disabled={processing || data.working_days.length === 0}
                                                className="flex items-center gap-2"
                                            >
                                                {processing ? t('Saving...') : t('Save')}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}