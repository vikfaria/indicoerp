import { useState } from 'react';
import { useForm, Head } from '@inertiajs/react';
import { toast } from 'sonner';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import SystemSetupSidebar from '../SystemSetupSidebar';
import { Save, MapPin, Phone, Mail } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { PhoneInputComponent } from '@/components/ui/phone-input';

interface ContactInformationProps {
    contactInformation: {
        map_embed_url: string;
        address: string;
        phone: string;
        email: string;
    } | null;
}

export default function ContactInformation({ contactInformation }: ContactInformationProps) {
    const { t } = useTranslation();
    const defaultData = {
        map_embed_url: contactInformation?.map_embed_url ?? '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d9581841.830083132!2d-14.999203219951713!3d54.10358663935295!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x25a3b1142c791a9%3A0xc4f8a0433288257a!2sUnited%20Kingdom!5e0!3m2!1sen!2sin!4v1762249682876!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
        address: contactInformation?.address ?? '350 Fifth Avenue, New York, NY 10118',
        phone: contactInformation?.phone ?? '+1 (212) 736-3100',
        email: contactInformation?.email ?? 'info@dashsupport.com'
    };
    
    const { data, setData, post, processing, errors } = useForm(defaultData);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support-ticket.contact-information.store'), {
            onSuccess: () => {
                toast.success('Contact information updated successfully');
            },
            onError: () => {
                toast.error('Failed to update contact information');
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Support Ticket'), url: route('support-tickets.index') },
                { label: t('System Setup'), url: route('support-ticket.settings.brand') },
                { label: t('Contact Information') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Contact Information')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="contact-information" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('Contact Information')}</h3>
                                <Button onClick={handleSubmit} disabled={processing}>
                                    <Save className="h-4 w-4 mr-2" />
                                    {processing ? t('Saving...') : t('Save Changes')}
                                </Button>
                            </div>
                                <div className="space-y-6">
                                    {/* Map Section */}
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Map Settings')}</h3>
                                        <div>
                                            <Label htmlFor="map_embed_url" required>{t('Google Maps Embed URL')}</Label>
                                            <Textarea  
                                                id="map_embed_url"
                                                value={data.map_embed_url}
                                                onChange={(e) => setData('map_embed_url', e.target.value)}
                                                placeholder="https://www.google.com/maps/embed?pb=..."
                                                rows={3}
                                            />
                                            {errors.map_embed_url && <p className="text-red-500 text-sm mt-1">{errors.map_embed_url}</p>}
                                        </div>
                                    </div>

                                    <Separator />

                                    {/* Contact Details */}
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-semibold">{t('Contact Details')}</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="address" required>{t('Address')}</Label>
                                                <Textarea
                                                    id="address"
                                                    value={data.address}
                                                    onChange={(e) => setData('address', e.target.value)}
                                                    placeholder="350 Fifth Avenue, New York, NY 10118"
                                                    rows={3}
                                                />
                                                {errors.address && <p className="text-red-500 text-sm mt-1">{errors.address}</p>}
                                            </div>
                                            <div className="space-y-4">
                                                <div>
                                                    <PhoneInputComponent
                                                        label={t('Phone Number')}
                                                        value={data.phone}
                                                        onChange={(value) => setData('phone', value)}
                                                        placeholder="+1234567890"
                                                        error={errors.phone}
                                                        required
                                                    />
                                                    
                                                </div>
                                                <div>
                                                    <Label htmlFor="email" required>{t('Email Address')}</Label>
                                                    <Input
                                                        id="email"
                                                        type="email"
                                                        value={data.email}
                                                        onChange={(e) => setData('email', e.target.value)}
                                                        placeholder="info@dashsupport.com"
                                                    />
                                                    {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}