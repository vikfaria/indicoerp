import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/ui/input-error';
import { Save } from "lucide-react";
import { IconPicker } from '@/components/ui/icon-picker';
import SystemSetupSidebar from "../SystemSetupSidebar";

interface DashboardWelcomeCardFormData {
    card_title: string;
    card_description: string;
    button_text: string;
    button_icon: string;
}

export default function DashboardWelcomeCard() {
    const { t } = useTranslation();
    const { dashboardWelcomeCard } = usePage<any>().props;

    const { data, setData, post, processing, errors } = useForm<DashboardWelcomeCardFormData>({
        card_title: dashboardWelcomeCard?.card_title || '',
        card_description: dashboardWelcomeCard?.card_description || '',
        button_text: dashboardWelcomeCard?.button_text || '',
        button_icon: dashboardWelcomeCard?.button_icon || ''
    });


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.dashboard-welcome-card.update'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                { label: t('System Setup') },
                { label: t('Dashboard Welcome Card Settings') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Dashboard Welcome Card Settings')} />

            <div className="flex gap-6">
                <div className="w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="dashboard-welcome-card" />
                </div>
                <div className="flex-1">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <div>
                                    <h3 className="text-lg font-medium">{t('Dashboard Welcome Card Settings')}</h3>
                                    <p className="text-sm text-gray-500 mt-1">{t('Configure the title and description for the dashboard welcome card')}</p>
                                </div>
                            </div>

                            <form id="dashboard-welcome-card-form" onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <Label htmlFor="card_title">{t('Card Title')}</Label>
                                    <Input
                                        id="card_title"
                                        value={data.card_title}
                                        onChange={(e) => setData('card_title', e.target.value)}
                                        placeholder={t('Enter card title')}
                                        required
                                    />
                                    <InputError message={errors.card_title} />
                                </div>

                                <div>
                                    <Label htmlFor="card_description">{t('Card Description')}</Label>
                                    <Textarea
                                        id="card_description"
                                        value={data.card_description}
                                        onChange={(e) => setData('card_description', e.target.value)}
                                        placeholder={t('Enter card description')}
                                        rows={4}
                                        required
                                    />
                                    <InputError message={errors.card_description} />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="button_text">{t('Button Text')}</Label>
                                        <Input
                                            id="button_text"
                                            value={data.button_text}
                                            onChange={(e) => setData('button_text', e.target.value)}
                                            placeholder={t('Enter button text')}
                                            required
                                        />
                                        <InputError message={errors.button_text} />
                                    </div>

                                    <div>
                                        <Label htmlFor="button_icon" required>{t('Button Icon')}</Label>
                                        <IconPicker
                                            value={data.button_icon}
                                            onChange={(icon) => setData('button_icon', icon)}
                                            placeholder={t('Select button icon')}
                                            required
                                        />
                                        <InputError message={errors.button_icon} />
                                    </div>
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {processing ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}