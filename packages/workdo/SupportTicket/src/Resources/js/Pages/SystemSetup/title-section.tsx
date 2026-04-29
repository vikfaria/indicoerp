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

interface TitleSectionData {
    create_ticket: {
        title: string;
        description: string;
    };
    search_ticket: {
        title: string;
        description: string;
    };
    knowledge_base: {
        title: string;
        description: string;
    };
    faq: {
        title: string;
        description: string;
    };
    contact: {
        title: string;
        description: string;
    };
}

interface TitleSectionProps {
    titleSections: TitleSectionData;
}

export default function TitleSection({ titleSections }: TitleSectionProps) {
    const { t } = useTranslation();

    const defaultTitleSections: TitleSectionData = {
        create_ticket: { title: '', description: '' },
        search_ticket: { title: '', description: '' },
        knowledge_base: { title: '', description: '' },
        faq: { title: '', description: '' },
        contact: { title: '', description: '' }
    };

    const { data, setData, post, processing, errors } = useForm<TitleSectionData>(titleSections || defaultTitleSections);


    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.title-sections.store'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Support Ticket'), url: route('support-tickets.index') },
                { label: t('System Setup'), url: route('support-ticket.settings.brand') },
                { label: t('Title Sections') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Title Sections')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="title-sections" />
                </div>

                <div className="flex-1">
                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle>{t('Title Sections')}</CardTitle>
                                <Button type="submit" disabled={processing} form="title-sections-form">
                                    <Save className="h-4 w-4 mr-2" />
                                    {processing ? t('Saving...') : t('Save Changes')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <form id="title-sections-form" onSubmit={submit} className="space-y-8">
                                {/* Create Ticket & Search Ticket Row (6x6) */}
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {/* Create Ticket Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Create Ticket Section')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="create-ticket-title">{t('Title')}</Label>
                                                <Input
                                                    id="create-ticket-title"
                                                    type="text"
                                                    value={data.create_ticket?.title || ''}
                                                    onChange={(e) => setData('create_ticket', { ...data.create_ticket, title: e.target.value })}
                                                    placeholder={t('Enter create ticket title')}
                                                    required
                                                />
                                                <InputError message={errors['create_ticket.title']} />
                                            </div>
                                            <div>
                                                <Label htmlFor="create-ticket-description">{t('Description')}</Label>
                                                <Textarea
                                                    id="create-ticket-description"
                                                    value={data.create_ticket?.description || ''}
                                                    onChange={(e) => setData('create_ticket', { ...data.create_ticket, description: e.target.value })}
                                                    placeholder={t('Enter create ticket description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['create_ticket.description']} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Search Ticket Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Search Ticket Section')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="search-ticket-title">{t('Title')}</Label>
                                                <Input
                                                    id="search-ticket-title"
                                                    type="text"
                                                    value={data.search_ticket?.title || ''}
                                                    onChange={(e) => setData('search_ticket', { ...data.search_ticket, title: e.target.value })}
                                                    placeholder={t('Enter search ticket title')}
                                                    required
                                                />
                                                <InputError message={errors['search_ticket.title']} />
                                            </div>
                                            <div>
                                                <Label htmlFor="search-ticket-description">{t('Description')}</Label>
                                                <Textarea
                                                    id="search-ticket-description"
                                                    value={data.search_ticket?.description || ''}
                                                    onChange={(e) => setData('search_ticket', { ...data.search_ticket, description: e.target.value })}
                                                    placeholder={t('Enter search ticket description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['search_ticket.description']} />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Knowledge Base & FAQ Row (6x6) */}
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {/* Knowledge Base Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Knowledge Base Section')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="knowledge-base-title">{t('Title')}</Label>
                                                <Input
                                                    id="knowledge-base-title"
                                                    type="text"
                                                    value={data.knowledge_base?.title || ''}
                                                    onChange={(e) => setData('knowledge_base', { ...data.knowledge_base, title: e.target.value })}
                                                    placeholder={t('Enter knowledge base title')}
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
                                                    placeholder={t('Enter knowledge base description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['knowledge_base.description']} />
                                            </div>

                                        </div>
                                    </div>

                                    {/* FAQ Section */}
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('FAQ Section')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <Label htmlFor="faq-title">{t('Title')}</Label>
                                                <Input
                                                    id="faq-title"
                                                    type="text"
                                                    value={data.faq?.title || ''}
                                                    onChange={(e) => setData('faq', { ...data.faq, title: e.target.value })}
                                                    placeholder={t('Enter FAQ title')}
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
                                                    placeholder={t('Enter FAQ description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['faq.description']} />
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {/* Contact Section (Full Width) */}
                                <div className="grid grid-cols-1 gap-6">
                                    <div className="border rounded-lg p-6 space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Contact Section')}</h3>
                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="contact-title">{t('Title')}</Label>
                                                <Input
                                                    id="contact-title"
                                                    type="text"
                                                    value={data.contact?.title || ''}
                                                    onChange={(e) => setData('contact', { ...data.contact, title: e.target.value })}
                                                    placeholder={t('Enter contact title')}
                                                    required
                                                />
                                                <InputError message={errors['contact.title']} />
                                            </div>
                                            <div>
                                                <Label htmlFor="contact-description">{t('Description')}</Label>
                                                <Textarea
                                                    id="contact-description"
                                                    value={data.contact?.description || ''}
                                                    onChange={(e) => setData('contact', { ...data.contact, description: e.target.value })}
                                                    placeholder={t('Enter contact description')}
                                                    rows={4}
                                                    required
                                                />
                                                <InputError message={errors['contact.description']} />
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