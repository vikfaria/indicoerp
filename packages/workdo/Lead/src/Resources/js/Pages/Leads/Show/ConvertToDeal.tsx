import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { ArrowRightLeft } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { InputError } from '@/components/ui/input-error';
import { Lead } from '../types';

interface Client {
    id: number;
    name: string;
    email: string;
}

interface ConvertToDealProps {
    lead: Lead;
    deal?: {
        id: number;
        is_active: boolean;
    };
}

export default function ConvertToDeal({ lead, deal }: ConvertToDealProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [clients, setClients] = useState<Client[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        name: lead.subject || lead.name,
        price: '0',
        client_check: 'new',
        clients: '',
        client_name: lead.name,
        client_email: lead.email,
        client_password: '',
        is_transfer: ['products', 'sources', 'files', 'discussion', 'notes', 'calls', 'emails']
    });

    useEffect(() => {
        if (formData.client_check === 'exist') {
            fetch(route('lead.leads.existing-clients'))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => setClients(Array.isArray(data) ? data : []))
                .catch(error => {
                    setClients([]);
                });
        }
    }, [formData.client_check]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('lead.leads.convert-to-deal', lead.id), formData, {
            onSuccess: () => {
                setOpen(false);
                setErrors({});
            },
            onError: (errors) => {
                setErrors(errors);
            }
        });
    };

    const handleTransferChange = (value: string, checked: boolean) => {
        setFormData(prev => ({
            ...prev,
            is_transfer: checked
                ? [...prev.is_transfer, value]
                : prev.is_transfer.filter(item => item !== value)
        }));
    };

    if (deal) {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link href={deal.is_active ? route('lead.deals.show', deal.id) : '#'}>
                            <Button size="sm">
                                <ArrowRightLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{t('Already Converted To Deal')}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <DialogTrigger asChild>
                            <Button size="sm">
                                <ArrowRightLeft className="h-4 w-4" />
                            </Button>
                        </DialogTrigger>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{t('Convert to Deal')}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{t('Convert Lead to Deal')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="name">{t('Deal Name')}</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                                placeholder={t('Enter Name')}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="price">{t('Price')}</Label>
                            <Input
                                id="price"
                                type="number"
                                min="0"
                                value={formData.price}
                                onChange={(e) => setFormData(prev => ({ ...prev, price: e.target.value }))}
                            />
                            <InputError message={errors.price} />
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Label>{t('Client Type')}</Label>
                        <RadioGroup value={formData.client_check} onValueChange={(value) => setFormData(prev => ({ ...prev, client_check: value }))}>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="new" id="new_client" />
                                <Label htmlFor="new_client">{t('New Client')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="exist" id="existing_client" />
                                <Label htmlFor="existing_client">{t('Existing Client')}</Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {formData.client_check === 'exist' ? (
                            <div>
                                <Label htmlFor="clients">{t('Client')}</Label>
                                <Select value={formData.clients} onValueChange={(value) => setFormData(prev => ({ ...prev, clients: value }))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select Client')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {clients.map((client) => (
                                            <SelectItem key={client.id} value={client.email}>
                                                {client.name} ({client.email})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.clients} />
                            </div>
                        ) : (
                            <>
                                <div>
                                    <Label htmlFor="client_name">{t('Client Name')}</Label>
                                    <Input
                                        id="client_name"
                                        onChange={(e) => setFormData(prev => ({ ...prev, client_name: e.target.value }))}
                                        placeholder={t('Enter Client Name')}
                                        required
                                    />
                                    <InputError message={errors.client_name} />
                                </div>
                                <div>
                                    <Label htmlFor="client_email">{t('Client Email')}</Label>
                                    <Input
                                        id="client_email"
                                        type="email"
                                        value={formData.client_email}
                                        onChange={(e) => setFormData(prev => ({ ...prev, client_email: e.target.value }))}
                                        placeholder={t('Enter Client Email')}
                                        required
                                    />
                                    <InputError message={errors.client_email} />
                                </div>
                                <div>
                                    <Label htmlFor="client_password">{t('Client Password')}</Label>
                                    <Input
                                        id="client_password"
                                        type="password"
                                        value={formData.client_password}
                                        onChange={(e) => setFormData(prev => ({ ...prev, client_password: e.target.value }))}
                                        placeholder={t('Enter Client Password')}
                                        required
                                    />
                                    <InputError message={errors.client_password} />
                                </div>
                            </>
                        )}
                    </div>

                    <div>
                        <Label className="font-bold text-dark">{t('Copy To')}</Label>
                        <div className="grid grid-cols-4 gap-2 mt-2">
                            {[
                                { key: 'products', label: 'Products' },
                                { key: 'sources', label: 'Sources' },
                                { key: 'files', label: 'Files' },
                                { key: 'discussion', label: 'Discussion' },
                                { key: 'notes', label: 'Notes' },
                                { key: 'calls', label: 'Calls' },
                                { key: 'emails', label: 'Emails' }
                            ].map(item => (
                                <div key={item.key} className="flex items-center space-x-2">
                                    <Checkbox
                                        id={`is_transfer_${item.key}`}
                                        checked={formData.is_transfer.includes(item.key)}
                                        onCheckedChange={(checked) => handleTransferChange(item.key, checked as boolean)}
                                    />
                                    <Label htmlFor={`is_transfer_${item.key}`} className="text-sm">{t(item.label)}</Label>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit">{t('Convert')}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
