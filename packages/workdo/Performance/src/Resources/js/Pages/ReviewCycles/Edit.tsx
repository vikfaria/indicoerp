import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";

interface ReviewCycle {
    id: number;
    name: string;
    frequency: string;
    description: string;
    status: string;
}

interface EditProps {
    reviewCycle: ReviewCycle;
    onSuccess: () => void;
}

interface FormData {
    name: string;
    frequency: string;
    description: string;
    status: string;
}

export default function Edit({ reviewCycle, onSuccess }: EditProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: reviewCycle.name,
        frequency: reviewCycle.frequency,
        description: reviewCycle.description || '',
        status: reviewCycle.status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('performance.review-cycles.update', reviewCycle.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Review Cycle')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name" required>{t('Name')}</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter review cycle name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label htmlFor="frequency" required>{t('Frequency')}</Label>
                    <Select value={data.frequency} onValueChange={(value) => setData('frequency', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select frequency')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="monthly">{t('Monthly')}</SelectItem>
                            <SelectItem value="quarterly">{t('Quarterly')}</SelectItem>
                            <SelectItem value="semi-annual">{t('Semi-Annual')}</SelectItem>
                            <SelectItem value="annual">{t('Annual')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.frequency} />
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter review cycle description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">{t('Active')}</SelectItem>
                            <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                
                <div className="flex justify-end gap-2 pt-4">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}