import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/ui/input-error';
import { Save } from "lucide-react";
import SystemSetupSidebar from "./SystemSetupSidebar";

interface CtaSectionData {
    knowledge_base: {
        title: string;
        description: string;
    };
    faq: {
        title: string;
        description: string;
    };
}

interface CtaSectionProps {
    ctaSections: CtaSectionData;
}

export default function CtaSection({ ctaSections }: CtaSectionProps) {
    const { t } = useTranslation();

    const defaultCtaSections: CtaSectionData = {
        knowledge_base: { title: '', description: '' },
        faq: { title: '', description: '' }
    };

    const { data, setData, post, processing, errors } = useForm<CtaSectionData>(ctaSections || defaultCtaSections);


    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.cta-sections.store'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Support Ticket'), url: route('support-tickets.index') },
                { label: t('System Setup'), url: route('support-ticket.settings.brand') },
                { label: t('CTA Sections') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('CTA Sections')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="cta-sections" />
                </div>

                <div className="flex-1">
                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle>{t('CTA Sections')}</CardTitle>
                                <Button type="submit" disabled={processing} form="cta-sections-form">
                                    <Save className="h-4 w-4 mr-2" />
                                    {processing ? t('Saving...') : t('Save Changes')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <form id="cta-sections-form" onSubmit={submit} className="space-y-8">
                                {/* Knowledge Base & FAQ Row (6x6) */}
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {/* Knowledge Base CTA Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Knowledge Base CTA')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="knowledge-base-title">{t('Title')}</Label>
                                                <Input
                                                    id="knowledge-base-title"
                                                    type="text"
                                                    value={data.knowledge_base?.title || ''}
                                                    onChange={(e) => setData('knowledge_base', { ...data.knowledge_base, title: e.target.value })}
                                                    placeholder={t('Enter knowledge base CTA title')}
                                                    required
                                                />
                                                <InputError message={errors['knowledge_base.title']} />
                                            </div>
                                            <div>
                                                <Label htmlFor="knowledge-base-description">{t('Description')}</Label>
                                                <Textarea
                                                    id="knowledge-base-description"
                                                    value={data.knowledge_base?.description || ''}
                                                    onChange={(e) => setData('knowledge_base', { ...data.knowledge_base, description: e.target.value })}
                                                    placeholder={t('Enter knowledge base CTA description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['knowledge_base.description']} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* FAQ CTA Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('FAQ CTA')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="faq-title">{t('Title')}</Label>
                                                <Input
                                                    id="faq-title"
                                                    type="text"
                                                    value={data.faq?.title || ''}
                                                    onChange={(e) => setData('faq', { ...data.faq, title: e.target.value })}
                                                    placeholder={t('Enter FAQ CTA title')}
                                                    required
                                                />
                                                <InputError message={errors['faq.title']} />
                                            </div>
                                            <div>
                                                <Label htmlFor="faq-description">{t('Description')}</Label>
                                                <Textarea
                                                    id="faq-description"
                                                    value={data.faq?.description || ''}
                                                    onChange={(e) => setData('faq', { ...data.faq, description: e.target.value })}
                                                    placeholder={t('Enter FAQ CTA description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['faq.description']} />
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}