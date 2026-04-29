import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Save, FileText } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { InputError } from '@/components/ui/input-error';

export default function Create() {
    const { t } = useTranslation();
    const [editorKey, setEditorKey] = useState(0);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        content: '',
        meta_title: '',
        meta_description: '',
        is_active: true
    });

    // Auto-generate slug from title
    useEffect(() => {
        if (data.title) {
            const slug = data.title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            setData('slug', slug);
        } else {
            setData('slug', '');
        }
    }, [data.title]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('custom-pages.store'), {
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

    const insertDummyHTML = () => {
        const dummyContent = `<div class="container">
    <h1>Welcome to Our Company</h1>
    <p>This is a sample paragraph with <strong>bold text</strong> and <em>italic text</em>.</p>

    <h2>Our Services</h2>
    <ul>
        <li>Web Development</li>
        <li>Mobile App Development</li>
        <li>Digital Marketing</li>
    </ul>

    <h3>Contact Information</h3>
    <p>Email: <a href="mailto:info@company.com">info@company.com</a></p>
    <p>Phone: +1 (555) 123-4567</p>

    <div class="highlight-box" style="background-color: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4>Special Offer!</h4>
        <p>Get 20% off on all our services this month.</p>
    </div>
</div>`;
        setData('content', dummyContent);
        setEditorKey(prev => prev + 1); // Force re-render
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Custom Pages'), url: route('custom-pages.index') },
                { label: t('Create Page') }
            ]}
            pageTitle={t('Create Custom Page')}
            backUrl={route('custom-pages.index')}
            pageActions={
                <Button
                    onClick={handleSubmit}
                    disabled={processing}
                    className="text-white"
                    style={{ backgroundColor: 'hsl(var(--primary))' }}
                >
                    <Save className="h-4 w-4 mr-2" />
                    {processing ? t('Saving...') : t('Save Page')}
                </Button>
            }
        >
            <Head title={t('Create Custom Page')} />

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <h4><b>{t('Page Details')}</b></h4>
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
                                />
                                <InputError message={errors.slug} />
                                <p className="text-xs text-gray-500">{t('Auto-generated from title. You can customize it.')}</p>
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
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label required>{t('Page Content')}</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={insertDummyHTML}
                                    className="text-xs"
                                >
                                    <FileText className="h-3 w-3 mr-1" />
                                    {t('Insert Sample HTML')}
                                </Button>
                            </div>
                            <textarea
                                key={editorKey}
                                value={data.content}
                                onChange={(e) => setData('content', e.target.value)}
                                placeholder={t('Write your page content here. You can use rich text formatting, add images, links, and more.')}
                                className="w-full min-h-[400px] p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                                rows={15}
                                required
                            />
                            <InputError message={errors.content} />
                            {errors.content && <p className="text-sm text-red-600 mt-1">{errors.content}</p>}
                            <p className="text-xs text-gray-500">{t('Use the toolbar above to format your content with headings, lists, links, and images.')}</p>
                            <div className="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p className="text-sm text-blue-800">
                                    <strong>{t('Note')}:</strong> {t('Write HTML content without using html, head, and body tags. Only write the content that goes inside the page.')}
                                </p>
                            </div>
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
