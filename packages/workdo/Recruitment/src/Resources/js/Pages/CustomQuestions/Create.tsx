import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { CreateCustomQuestionProps, CreateCustomQuestionFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Repeater, RepeaterItem } from '@/components/ui/repeater';

export default function Create({ onSuccess }: CreateCustomQuestionProps) {
    const {  } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateCustomQuestionFormData>({
        question: '',
        type: 'text',
        options: '[]',
        is_required: false,
        is_active: true,
        sort_order: 0,
    });

    const [optionsArray, setOptionsArray] = useState<RepeaterItem[]>([
        { id: '1', option: '' }
    ]);

    const handleOptionsChange = (items: RepeaterItem[]) => {
        setOptionsArray(items);
        const options = items
            .map((item) => (item.option ?? '').toString())
            .map((option) => option.trim())
            .filter((option) => option !== '');
        setData('options', JSON.stringify(options));
    };



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.custom-questions.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Custom Question')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="question">{t('Question')}</Label>
                    <Input
                        id="question"
                        type="text"
                        value={data.question}
                        onChange={(e) => setData('question', e.target.value)}
                        placeholder={t('Enter Question')}
                        required
                    />
                    <InputError message={errors.question} />
                </div>

                <div>
                    <Label htmlFor="type">{t('Type')}</Label>
                    <Select value={data.type || 'text'} onValueChange={(value) => {
                        setData('type', value);
                        if (['text', 'textarea', 'date', 'number'].includes(value)) {
                            setData('options', '[]');
                            setOptionsArray([]);
                        } else {
                            // For select, radio, checkbox - ensure we have at least one option
                            if (optionsArray.length === 0) {
                                setOptionsArray([{ id: '1', option: '' }]);
                            }
                        }
                    }}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="text">{t('Text')}</SelectItem>
                            <SelectItem value="textarea">{t('Textarea')}</SelectItem>
                            <SelectItem value="select">{t('Select')}</SelectItem>
                            <SelectItem value="radio">{t('Radio')}</SelectItem>
                            <SelectItem value="checkbox">{t('Checkbox')}</SelectItem>
                            <SelectItem value="date">{t('Date')}</SelectItem>
                            <SelectItem value="number">{t('Number')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                {!['text', 'textarea', 'date', 'number'].includes(data.type) && (
                    <div>
                        <Label>{t('Options')}</Label>
                        <Repeater
                            fields={[
                                {
                                    name: 'option',
                                    label: t('Option'),
                                    type: 'text',
                                    placeholder: t('Enter Option'),
                                    required: true
                                }
                            ]}
                            value={optionsArray}
                            onChange={handleOptionsChange}
                            addButtonText={t('Add Option')}
                            deleteTooltipText={t('Delete')}
                            minItems={1}
                            showDefault={true}
                            className="space-y-2"
                            layout={{ type: 'stack', gap: '2' }}
                        />
                        <InputError message={errors.options} />
                    </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="is_required"
                            checked={data.is_required || false}
                            onCheckedChange={(checked) => setData('is_required', !!checked)}
                        />
                        <Label htmlFor="is_required" className="cursor-pointer">{t('Is Required')}</Label>
                        <InputError message={errors.is_required} />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Switch
                            id="is_active"
                            checked={data.is_active || false}
                            onCheckedChange={(checked) => setData('is_active', !!checked)}
                        />
                        <Label htmlFor="is_active" className="cursor-pointer">{t('Is Active')}</Label>
                        <InputError message={errors.is_active} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="sort_order">{t('Sort Order')}</Label>
                    <Input
                        id="sort_order"
                        type="number"
                        step="1"
                        min="0"
                        value={data.sort_order?.toString() || '0'}
                        onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                        placeholder={t('Enter Sort Order')}
                    />
                    <InputError message={errors.sort_order} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}