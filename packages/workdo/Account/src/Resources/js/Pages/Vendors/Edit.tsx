import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import InputError from "@/components/ui/input-error";
import { PhoneInputComponent } from "@/components/ui/phone-input";
import { EditVendorProps, CreateVendorFormData } from './types';

export default function Edit({ vendor, onSuccess }: EditVendorProps) {
    const { t } = useTranslation();
    const residencyOptions = [
        { value: 'resident', label: t('Resident') },
        { value: 'non_resident', label: t('Non-resident') },
    ];
    const vendorTypeOptions = [
        { value: 'public_entity', label: t('Public entity') },
        { value: 'private_company', label: t('Private company') },
        { value: 'service_provider', label: t('Service provider') },
        { value: 'import_supplier', label: t('Import supplier') },
        { value: 'exempt', label: t('Exempt') },
        { value: 'special_regime', label: t('Special regime') },
    ];
    const { data, setData, put, processing, errors } = useForm<CreateVendorFormData>({
        ...vendor,
        user_id: vendor.user_id ? vendor.user_id.toString() : '0',
        tax_number: vendor.tax_number ?? '',
        fiscal_residency_status: vendor.fiscal_residency_status ?? 'resident',
        vendor_type: vendor.vendor_type ?? '',
        fiscal_country: vendor.fiscal_country ?? '',
        vat_regime: vendor.vat_regime ?? '',
        supply_type: vendor.supply_type ?? '',
        payment_currency_code: (vendor.payment_currency_code ?? 'MZN').toUpperCase(),
        foreign_tax_number: vendor.foreign_tax_number ?? '',
        withholding_tax_applicable: !!vendor.withholding_tax_applicable,
        reverse_charge_applicable: !!vendor.reverse_charge_applicable,
        adt_eligible: !!vendor.adt_eligible,
        adt_country: vendor.adt_country ?? '',
        compliance_documents: vendor.compliance_documents ?? [],
        fiscal_identity_lock_reason: vendor.fiscal_identity_lock_reason ?? '',
        payment_terms: vendor.payment_terms ?? '',
        billing_address: vendor.billing_address ?? {
            name: '',
            address_line_1: '',
            address_line_2: '',
            city: '',
            state: '',
            country: '',
            zip_code: '',
        },
        shipping_address: vendor.shipping_address ?? vendor.billing_address ?? {
            name: '',
            address_line_1: '',
            address_line_2: '',
            city: '',
            state: '',
            country: '',
            zip_code: '',
        },
        same_as_billing: !!vendor.same_as_billing,
        notes: vendor.notes ?? '',
    });
    const complianceDocumentsText = data.compliance_documents.join('\n');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('account.vendors.update', vendor.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Vendor')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="company_name">{t('Company Name')}</Label>
                    <Input
                        id="company_name"
                        value={data.company_name}
                        onChange={(e) => setData('company_name', e.target.value)}
                        placeholder={t('Enter company name')}
                        required
                    />
                    <InputError message={errors.company_name} />
                </div>
                <div>
                    <Label htmlFor="contact_person_name">{t('Contact Person')}</Label>
                    <Input
                        id="contact_person_name"
                        value={data.contact_person_name}
                        onChange={(e) => setData('contact_person_name', e.target.value)}
                        placeholder={t('Enter contact person name')}
                        required
                    />
                    <InputError message={errors.contact_person_name} />
                </div>
                <div>
                    <Label htmlFor="contact_person_email">{t('Email')}</Label>
                    <Input
                        id="contact_person_email"
                        type="email"
                        value={data.contact_person_email}
                        onChange={(e) => setData('contact_person_email', e.target.value)}
                        placeholder={t('Enter email address')}
                        required
                    />
                    <InputError message={errors.contact_person_email} />
                </div>
                <div>
                    <PhoneInputComponent
                        label={t('Mobile Number')}
                        value={data.contact_person_mobile}
                        onChange={(value) => setData('contact_person_mobile', value)}
                        placeholder="+1234567890"
                        error={errors.contact_person_mobile}
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="tax_number">{t('Tax Number')}</Label>
                        <Input
                            id="tax_number"
                            value={data.tax_number}
                            onChange={(e) => setData('tax_number', e.target.value)}
                            placeholder={t('Enter tax number')}
                        />
                        <InputError message={errors.tax_number} />
                    </div>
                    <div>
                        <Label htmlFor="payment_terms">{t('Payment Terms')}</Label>
                        <Input
                            id="payment_terms"
                            value={data.payment_terms}
                            onChange={(e) => setData('payment_terms', e.target.value)}
                            placeholder={t('e.g., Net 30')}
                        />
                        <InputError message={errors.payment_terms} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="fiscal_residency_status">{t('Fiscal Residency')}</Label>
                        <Select value={data.fiscal_residency_status} onValueChange={(value) => setData('fiscal_residency_status', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select residency')} />
                            </SelectTrigger>
                            <SelectContent>
                                {residencyOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.fiscal_residency_status} />
                    </div>
                    <div>
                        <Label htmlFor="vendor_type" required>{t('Vendor Type')}</Label>
                        <Select value={data.vendor_type} onValueChange={(value) => setData('vendor_type', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select vendor type')} />
                            </SelectTrigger>
                            <SelectContent>
                                {vendorTypeOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.vendor_type} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="fiscal_country">{t('Fiscal Country')}</Label>
                        <Input
                            id="fiscal_country"
                            value={data.fiscal_country}
                            onChange={(e) => setData('fiscal_country', e.target.value)}
                            placeholder={t('Enter fiscal country')}
                        />
                        <InputError message={errors.fiscal_country} />
                    </div>
                    <div>
                        <Label htmlFor="payment_currency_code">{t('Payment Currency')}</Label>
                        <Input
                            id="payment_currency_code"
                            value={data.payment_currency_code}
                            onChange={(e) => setData('payment_currency_code', e.target.value.toUpperCase())}
                            placeholder="MZN"
                            maxLength={3}
                        />
                        <InputError message={errors.payment_currency_code} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="vat_regime">{t('VAT Regime')}</Label>
                        <Input
                            id="vat_regime"
                            value={data.vat_regime}
                            onChange={(e) => setData('vat_regime', e.target.value)}
                            placeholder={t('e.g., standard, exempt')}
                        />
                        <InputError message={errors.vat_regime} />
                    </div>
                    <div>
                        <Label htmlFor="supply_type" required>{t('Supply Type')}</Label>
                        <Input
                            id="supply_type"
                            value={data.supply_type}
                            onChange={(e) => setData('supply_type', e.target.value)}
                            placeholder={t('e.g., services, goods, import')}
                        />
                        <InputError message={errors.supply_type} />
                    </div>
                </div>
                <div>
                    <Label htmlFor="foreign_tax_number">{t('Foreign Tax Number')}</Label>
                    <Input
                        id="foreign_tax_number"
                        value={data.foreign_tax_number}
                        onChange={(e) => setData('foreign_tax_number', e.target.value)}
                        placeholder={t('Enter foreign tax identifier')}
                    />
                    <InputError message={errors.foreign_tax_number} />
                </div>
                <div>
                    <Label htmlFor="fiscal_identity_lock_reason">{t('Fiscal Lock Reason')}</Label>
                    <Textarea
                        id="fiscal_identity_lock_reason"
                        value={data.fiscal_identity_lock_reason ?? ''}
                        onChange={(e) => setData('fiscal_identity_lock_reason', e.target.value)}
                        placeholder={t('Required only when overriding critical fiscal data after documents have been issued')}
                        rows={3}
                    />
                    <InputError message={errors.fiscal_identity_lock_reason} />
                </div>
                <div className="grid grid-cols-3 gap-4">
                    <div className="flex items-center space-x-2 pt-6">
                        <Checkbox
                            id="withholding_tax_applicable"
                            checked={data.withholding_tax_applicable}
                            onCheckedChange={(checked) => setData('withholding_tax_applicable', !!checked)}
                        />
                        <Label htmlFor="withholding_tax_applicable">{t('Withholding tax applies')}</Label>
                    </div>
                    <div className="flex items-center space-x-2 pt-6">
                        <Checkbox
                            id="reverse_charge_applicable"
                            checked={data.reverse_charge_applicable}
                            onCheckedChange={(checked) => setData('reverse_charge_applicable', !!checked)}
                        />
                        <Label htmlFor="reverse_charge_applicable">{t('Reverse charge applies')}</Label>
                    </div>
                    <div className="flex items-center space-x-2 pt-6">
                        <Checkbox
                            id="adt_eligible"
                            checked={data.adt_eligible}
                            onCheckedChange={(checked) => setData('adt_eligible', !!checked)}
                        />
                        <Label htmlFor="adt_eligible">{t('ADT eligible')}</Label>
                    </div>
                </div>
                {data.adt_eligible && (
                    <div className="space-y-4 border-t pt-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="adt_country">{t('ADT Country')}</Label>
                                <Input
                                    id="adt_country"
                                    value={data.adt_country}
                                    onChange={(e) => setData('adt_country', e.target.value)}
                                    placeholder={t('Enter ADT country')}
                                />
                                <InputError message={errors.adt_country} />
                            </div>
                            <div>
                                <Label htmlFor="compliance_documents">{t('Compliance Documents')}</Label>
                                <Textarea
                                    id="compliance_documents"
                                    value={complianceDocumentsText}
                                    onChange={(e) => setData(
                                        'compliance_documents',
                                        e.target.value
                                            .split(/\r?\n|,/)
                                            .map((item) => item.trim())
                                            .filter((item) => item !== '')
                                    )}
                                    placeholder={t('Enter one document per line or separated by commas')}
                                    rows={4}
                                />
                                <InputError message={errors.compliance_documents} />
                            </div>
                        </div>
                    </div>
                )}
                <div>
                    <Label htmlFor="billing_name">{t('Billing Name')}</Label>
                    <Input
                        id="billing_name"
                        value={data.billing_address.name}
                        onChange={(e) => setData('billing_address', {...data.billing_address, name: e.target.value})}
                        placeholder={t('Enter billing name')}
                        required
                    />
                    <InputError message={errors['billing_address.name']} />
                </div>
                <div>
                    <Label htmlFor="billing_address">{t('Billing Address')}</Label>
                    <Input
                        id="billing_address"
                        value={data.billing_address.address_line_1}
                        onChange={(e) => setData('billing_address', {...data.billing_address, address_line_1: e.target.value})}
                        placeholder={t('Enter address')}
                        required
                    />
                    <InputError message={errors['billing_address.address_line_1']} />
                </div>
                <div>
                    <Label htmlFor="billing_address_2">{t('Address Line 2')}</Label>
                    <Input
                        id="billing_address_2"
                        value={data.billing_address.address_line_2}
                        onChange={(e) => setData('billing_address', {...data.billing_address, address_line_2: e.target.value})}
                        placeholder={t('Apartment, suite, etc. (optional)')}
                    />
                    <InputError message={errors['billing_address.address_line_2']} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="billing_city">{t('City')}</Label>
                        <Input
                            id="billing_city"
                            value={data.billing_address.city}
                            onChange={(e) => setData('billing_address', {...data.billing_address, city: e.target.value})}
                            placeholder={t('Enter city')}
                            required
                        />
                        <InputError message={errors['billing_address.city']} />
                    </div>
                    <div>
                        <Label htmlFor="billing_state">{t('State')}</Label>
                        <Input
                            id="billing_state"
                            value={data.billing_address.state}
                            onChange={(e) => setData('billing_address', {...data.billing_address, state: e.target.value})}
                            placeholder={t('Enter state')}
                            required
                        />
                        <InputError message={errors['billing_address.state']} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="billing_country">{t('Country')}</Label>
                        <Input
                            id="billing_country"
                            value={data.billing_address.country}
                            onChange={(e) => setData('billing_address', {...data.billing_address, country: e.target.value})}
                            placeholder={t('Enter country')}
                            required
                        />
                        <InputError message={errors['billing_address.country']} />
                    </div>
                    <div>
                        <Label htmlFor="billing_zip">{t('Zip Code')}</Label>
                        <Input
                            id="billing_zip"
                            value={data.billing_address.zip_code}
                            onChange={(e) => setData('billing_address', {...data.billing_address, zip_code: e.target.value})}
                            placeholder={t('Enter zip code')}
                            required
                        />
                        <InputError message={errors['billing_address.zip_code']} />
                    </div>
                </div>
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="same_as_billing"
                        checked={data.same_as_billing}
                        onCheckedChange={(checked) => {
                            setData('same_as_billing', !!checked);
                            if (checked) {
                                setData('shipping_address', {...data.billing_address});
                            }
                        }}
                    />
                    <Label htmlFor="same_as_billing">{t('Shipping address same as billing')}</Label>
                </div>

                {!data.same_as_billing && (
                    <div className="space-y-4 border-t pt-4">
                        <h3 className="text-lg font-medium">{t('Shipping Address')}</h3>
                        <div>
                            <Label htmlFor="shipping_name">{t('Shipping Name')}</Label>
                            <Input
                                id="shipping_name"
                                value={data.shipping_address.name}
                                onChange={(e) => setData('shipping_address', {...data.shipping_address, name: e.target.value})}
                                placeholder={t('Enter shipping name')}
                                required
                            />
                            <InputError message={errors['shipping_address.name']} />
                        </div>
                        <div>
                            <Label htmlFor="shipping_address">{t('Shipping Address')}</Label>
                            <Input
                                id="shipping_address"
                                value={data.shipping_address.address_line_1}
                                onChange={(e) => setData('shipping_address', {...data.shipping_address, address_line_1: e.target.value})}
                                placeholder={t('Enter shipping address')}
                                required
                            />
                            <InputError message={errors['shipping_address.address_line_1']} />
                        </div>
                        <div>
                            <Label htmlFor="shipping_address_2">{t('Address Line 2')}</Label>
                            <Input
                                id="shipping_address_2"
                                value={data.shipping_address.address_line_2}
                                onChange={(e) => setData('shipping_address', {...data.shipping_address, address_line_2: e.target.value})}
                                placeholder={t('Apartment, suite, etc. (optional)')}
                            />
                            <InputError message={errors['shipping_address.address_line_2']} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="shipping_city">{t('City')}</Label>
                                <Input
                                    id="shipping_city"
                                    value={data.shipping_address.city}
                                    onChange={(e) => setData('shipping_address', {...data.shipping_address, city: e.target.value})}
                                    placeholder={t('Enter city')}
                                    required
                                />
                                <InputError message={errors['shipping_address.city']} />
                            </div>
                            <div>
                                <Label htmlFor="shipping_state">{t('State')}</Label>
                                <Input
                                    id="shipping_state"
                                    value={data.shipping_address.state}
                                    onChange={(e) => setData('shipping_address', {...data.shipping_address, state: e.target.value})}
                                    placeholder={t('Enter state')}
                                    required
                                />
                                <InputError message={errors['shipping_address.state']} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="shipping_country">{t('Country')}</Label>
                                <Input
                                    id="shipping_country"
                                    value={data.shipping_address.country}
                                    onChange={(e) => setData('shipping_address', {...data.shipping_address, country: e.target.value})}
                                    placeholder={t('Enter country')}
                                    required
                                />
                                <InputError message={errors['shipping_address.country']} />
                            </div>
                            <div>
                                <Label htmlFor="shipping_zip">{t('Zip Code')}</Label>
                                <Input
                                    id="shipping_zip"
                                    value={data.shipping_address.zip_code}
                                    onChange={(e) => setData('shipping_address', {...data.shipping_address, zip_code: e.target.value})}
                                    placeholder={t('Enter zip code')}
                                    required
                                />
                                <InputError message={errors['shipping_address.zip_code']} />
                            </div>
                        </div>
                    </div>
                )}

                {/* Notes */}
                <div>
                    <Label htmlFor="edit_notes">{t('Notes')}</Label>
                    <Textarea
                        id="edit_notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={t('Enter notes')}
                        rows={3}
                    />
                    <InputError message={errors.notes} />
                </div>

                <div className="flex justify-end gap-2">
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
