import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Save, Eye, Code } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { InputError } from '@/components/ui/input-error';

interface CustomPage {
    id: number;
    title: string;
    slug: string;
    content: string;
    meta_title: string;
    meta_description: string;
    is_active: boolean;
    editable?: boolean;
}

interface EditProps {
    page: CustomPage;
}

export default function Edit({ page }: EditProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm({
        title: page.title,
        slug: page.slug,
        content: page.content,
        meta_title: page.meta_title || '',
        meta_description: page.meta_description || '',
        is_active: page.is_active
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('custom-pages.update', page.id), {
            onSuccess: () => {
                // Success handled by redirect
            },
            onError: () => {
                // Scroll to first error
                const firstError = document.querySelector('.text-red-600');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Custom Pages'), url: route('custom-pages.index') },
                { label: t('Edit Page') }
            ]}
            pageTitle={t('Edit Custom Page')}
            backUrl={route('custom-pages.index')}
            pageActions={
                <Button
                    onClick={handleSubmit}
                    disabled={processing}
                    className="text-white"
                    style={{ backgroundColor: 'hsl(var(--primary))' }}
                >
                    <Save className="h-4 w-4 mr-2" />
                    {processing ? t('Saving...') : t('Update Page')}
                </Button>
            }
        >
            <Head title={t('Edit Custom Page')} />

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <h5><b>{t('Page Details')}</b></h5>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">{t('Page Title')}</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder={t('Enter page title (e.g., About Us, Privacy Policy)')}
                                    error={errors.title}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="slug">{t('URL Slug')}</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    placeholder={t('URL-friendly name (e.g., about-us, privacy-policy)')}
                                    error={errors.slug}
                                    disabled={page.is_disabled === true}
                                />
                                <InputError message={errors.slug} />
                                <p className="text-xs text-gray-500">{t('This will be the URL path for your page')}</p>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) => setData('is_active', checked)}
                            />
                            <Label htmlFor="is_active">{t('Active')}</Label>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <h5><b>{t('Page Content')}</b></h5>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <Label required>{t('Page Content')}</Label>
                            <Tabs defaultValue="html" className="w-full">
                                <TabsList className="grid w-full grid-cols-2">
                                    <TabsTrigger value="html" className="flex items-center gap-2">
                                        <Code className="h-4 w-4" />
                                        {t('HTML')}
                                    </TabsTrigger>
                                    <TabsTrigger value="preview" className="flex items-center gap-2">
                                        <Eye className="h-4 w-4" />
                                        {t('Preview')}
                                    </TabsTrigger>
                                </TabsList>
                                <TabsContent value="html" className="mt-4">
                                    <textarea
                                        className="w-full h-[400px] p-3 border rounded-md font-mono text-sm"
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        placeholder={t('Enter HTML content directly...')}
                                    />
                                </TabsContent>
                                <TabsContent value="preview" className="mt-4">
                                    <div className="border rounded-md p-4 min-h-[300px] bg-white">
                                        <div
                                            className="prose prose-sm max-w-none [&_ul]:list-disc [&_ul]:ml-6 [&_ol]:list-decimal [&_ol]:ml-6 [&_li]:my-1 [&_a]:text-blue-600 [&_a]:underline [&_a]:cursor-pointer hover:[&_a]:text-blue-800"
                                            dangerouslySetInnerHTML={{ __html: data.content }}
                                        />
                                    </div>
                                </TabsContent>
                            </Tabs>
                            <InputError message={errors.content} />
                            {errors.content && <p className="text-sm text-red-600 mt-1">{errors.content}</p>}
                            <p className="text-xs text-gray-500">{t('Use the toolbar above to format your content with headings, lists, links, and images.')}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('SEO Settings')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="meta_title">{t('Meta Title')}</Label>
                            <Input
                                id="meta_title"
                                value={data.meta_title}
                                onChange={(e) => setData('meta_title', e.target.value)}
                                placeholder={t('SEO title for search engines (50-60 characters)')}
                                error={errors.meta_title}
                                maxLength={60}
                            />
                            <InputError message={errors.meta_title} />
                            <p className="text-xs text-gray-500">{data.meta_title.length}/60 {t('characters')}</p>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="meta_description">{t('Meta Description')}</Label>
                            <Input
                                id="meta_description"
                                value={data.meta_description}
                                onChange={(e) => setData('meta_description', e.target.value)}
                                placeholder={t('Brief description for search results (150-160 characters)')}
                                error={errors.meta_description}
                                maxLength={160}
                            />
                            <InputError message={errors.meta_description} />
                            <p className="text-xs text-gray-500">{data.meta_description.length}/160 {t('characters')}</p>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AuthenticatedLayout>
    );
}
