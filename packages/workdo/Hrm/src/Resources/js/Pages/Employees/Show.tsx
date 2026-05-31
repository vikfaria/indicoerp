import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import InputError from "@/components/ui/input-error";
import { Eye } from 'lucide-react';
import { formatDate, getImagePath, getCurrencySymbol } from '@/utils/helpers';

export default function Show() {
    const { employee, documents, foreignQuota, probationAlerts, probationCategoryLimits, auth, canViewSensitiveEmployeeData } = usePage<any>().props;
    const { t } = useTranslation();
    const canEdit = auth?.user?.permissions?.includes('edit-employees') ?? false;
    const canViewSensitive = canViewSensitiveEmployeeData ?? false;

    const toDateInput = (value?: string | null) => value ? value.substring(0, 10) : '';

    const socialProfileForm = useForm({
        inss_number: employee.social_security_profile?.inss_number ?? '',
        registration_date: toDateInput(employee.social_security_profile?.registration_date),
        registration_status: employee.social_security_profile?.registration_status ?? 'pending',
        identification_document_type: employee.social_security_profile?.identification_document_type ?? '',
        identification_document_number: employee.social_security_profile?.identification_document_number ?? '',
        evidence_file_path: employee.social_security_profile?.evidence_file_path ?? '',
    });

    const foreignProfileForm = useForm({
        is_foreign_worker: !!employee.foreign_worker_profile?.is_foreign_worker,
        nationality: employee.foreign_worker_profile?.nationality ?? '',
        residency_status: employee.foreign_worker_profile?.residency_status ?? 'resident',
        passport_number: employee.foreign_worker_profile?.passport_number ?? '',
        passport_expires_at: toDateInput(employee.foreign_worker_profile?.passport_expires_at),
        visa_type: employee.foreign_worker_profile?.visa_type ?? '',
        visa_expires_at: toDateInput(employee.foreign_worker_profile?.visa_expires_at),
        work_authorization_number: employee.foreign_worker_profile?.work_authorization_number ?? '',
        work_authorization_expires_at: toDateInput(employee.foreign_worker_profile?.work_authorization_expires_at),
        hiring_regime: employee.foreign_worker_profile?.hiring_regime ?? 'quota',
        work_province: employee.foreign_worker_profile?.work_province ?? '',
        mozambique_entry_date: toDateInput(employee.foreign_worker_profile?.mozambique_entry_date),
        cessation_effective_date: toDateInput(employee.foreign_worker_profile?.cessation_effective_date),
        cessation_notification_due_at: toDateInput(employee.foreign_worker_profile?.cessation_notification_due_at),
        cessation_notified_at: toDateInput(employee.foreign_worker_profile?.cessation_notified_at),
    });

    const probationForm = useForm({
        probation_category: employee.probation_profile?.probation_category ?? 'general',
        starts_at: toDateInput(employee.probation_profile?.starts_at),
        expected_end_at: toDateInput(employee.probation_profile?.expected_end_at),
        evaluation_status: employee.probation_profile?.evaluation_status ?? 'pending',
        technical_score: employee.probation_profile?.technical_score?.toString() ?? '',
        attendance_score: employee.probation_profile?.attendance_score?.toString() ?? '',
        punctuality_score: employee.probation_profile?.punctuality_score?.toString() ?? '',
        conduct_score: employee.probation_profile?.conduct_score?.toString() ?? '',
        adaptation_score: employee.probation_profile?.adaptation_score?.toString() ?? '',
        recommendation: employee.probation_profile?.recommendation ?? '',
        decision_status: employee.probation_profile?.decision_status ?? 'ongoing',
        decision_date: toDateInput(employee.probation_profile?.decision_date),
        cessation_reason: employee.probation_profile?.cessation_reason ?? '',
        notes: employee.probation_profile?.notes ?? '',
    });

    const probationLabels: Record<string, string> = {
        base_indefinite: t('Base (Indefinite)'),
        general: t('General'),
        technician_mid: t('Mid Technician'),
        technician_high: t('High Technician'),
        leadership: t('Leadership'),
    };

    const dependentForm = useForm({
        full_name: '',
        relationship: '',
        date_of_birth: '',
        document_number: '',
        is_student: false,
        is_tax_eligible: true,
        valid_until: '',
        notes: '',
    });

    const getGenderText = (gender: string) => {
        // Handle both old numeric values and new string values
        const genders: any = { "0": "Male", "1": "Female", "2": "Other" };
        return genders[gender] || gender || "Male";
    };

    const getEmploymentTypeText = (type: string) => {
        const types: any = { "0": "Full Time", "1": "Part Time", "2": "Temporary", "3": "Contract" };
        return types[type] || type;
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Employees'), url: route('hrm.employees.index') },
                { label: t('View Employee') }
            ]}
            pageTitle={t('Employee Details')}
            backUrl={route('hrm.employees.index')}
        >
            <Head title={t('Employee Details')} />

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Left Sidebar - Profile */}
                <div className="lg:col-span-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6 text-center">
                            <div className="mb-4">
                                <img 
                                    src={employee.user?.avatar ? getImagePath(employee.user.avatar) : '/default-avatar.png'} 
                                    alt={employee.user?.name || 'Employee'}
                                    className="w-24 h-24 rounded-full object-cover mx-auto border-4 border-gray-100"
                                    onError={(e) => { e.currentTarget.src = '/default-avatar.png'; }}
                                />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">{employee.user?.name}</h3>
                            <p className="text-muted-foreground mb-4">{employee.user?.email}</p>
                            
                            <div className="space-y-3 text-left">
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Employee ID')}</p>
                                    <p className="font-medium">{employee.employee_id}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Date of Birth')}</p>
                                    <p className="font-medium">{formatDate(employee.date_of_birth)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Gender')}</p>
                                    <p className="font-medium">{t(getGenderText(employee.gender))}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Branch')}</p>
                                    <p className="font-medium">{employee.branch?.branch_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Department')}</p>
                                    <p className="font-medium">{employee.department?.department_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">{t('Designation')}</p>
                                    <p className="font-medium">{employee.designation?.designation_name}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Right Content - Tabs */}
                <div className="lg:col-span-3">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <Tabs defaultValue="employment" className="w-full">
                                <TabsList className="grid w-full grid-cols-6">
                                    <TabsTrigger value="employment">{t('Employment')}</TabsTrigger>
                                    <TabsTrigger value="contact">{t('Contact')}</TabsTrigger>
                                    <TabsTrigger value="banking">{t('Banking')}</TabsTrigger>
                                    <TabsTrigger value="hours">{t('Hours & Rates')}</TabsTrigger>
                                    <TabsTrigger value="documents">{t('Documents')}</TabsTrigger>
                                    <TabsTrigger value="legal">{t('Legal')}</TabsTrigger>
                                </TabsList>

                                <TabsContent value="employment" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Employment Type')}</p>
                                            <p className="font-medium">{t(getEmploymentTypeText(employee.employment_type))}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Date of Joining')}</p>
                                            <p className="font-medium">{formatDate(employee.date_of_joining)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Shift')}</p>
                                            <p className="font-medium">{employee.shift?.shift_name || 'N/A'}</p>
                                        </div>
                                    </div>
                                </TabsContent>

                                <TabsContent value="contact" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Address Line 1')}</p>
                                            <p className="font-medium">{employee.address_line_1}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Address Line 2')}</p>
                                            <p className="font-medium">{employee.address_line_2 || '-'}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('City')}</p>
                                            <p className="font-medium">{employee.city}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('State')}</p>
                                            <p className="font-medium">{employee.state}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Country')}</p>
                                            <p className="font-medium">{employee.country}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Postal Code')}</p>
                                            <p className="font-medium">{employee.postal_code}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Emergency Contact Name')}</p>
                                            <p className="font-medium">{employee.emergency_contact_name}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Emergency Contact Relationship')}</p>
                                            <p className="font-medium">{employee.emergency_contact_relationship}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Emergency Contact Number')}</p>
                                            <p className="font-medium">{employee.emergency_contact_number}</p>
                                        </div>
                                    </div>
                                </TabsContent>

                                <TabsContent value="banking" className="space-y-6 mt-6">
                                    {canViewSensitive ? (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Bank Name')}</p>
                                                <p className="font-medium">{employee.bank_name}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Account Holder Name')}</p>
                                                <p className="font-medium">{employee.account_holder_name}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Account Number')}</p>
                                                <p className="font-medium">{employee.account_number}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Bank Identifier Code')}</p>
                                                <p className="font-medium">{employee.bank_identifier_code}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Bank Branch')}</p>
                                                <p className="font-medium">{employee.bank_branch}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-1">{t('Tax Payer ID')}</p>
                                                <p className="font-medium">{employee.tax_payer_id || '-'}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                                            {t('Sensitive banking and fiscal data is restricted for your profile.')}
                                        </div>
                                    )}
                                </TabsContent>

                                <TabsContent value="hours" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Hours Per Day')}</p>
                                            <p className="font-medium">{employee.hours_per_day || 'N/A'}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Days Per Week')}</p>
                                            <p className="font-medium">{employee.days_per_week || 'N/A'}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground mb-1">{t('Rate Per Hour')}</p>
                                            <p className="font-medium">{employee.rate_per_hour ? `${getCurrencySymbol()}${employee.rate_per_hour}` : 'N/A'}</p>
                                        </div>
                                    </div>
                                </TabsContent>

                                <TabsContent value="documents" className="space-y-6 mt-6">
                                    {documents && documents.length > 0 ? (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {documents.map((doc: any, index: number) => (
                                                <Card key={doc.id || index} className="p-4">
                                                    <div className="flex justify-between items-center">
                                                        <div className="flex-1">
                                                            <p className="font-medium">{doc.document_name || doc.title || 'Document'}</p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {doc.file_path ? doc.file_path.split('/').pop() : doc.document ? doc.document.split('/').pop() : 'No file'}
                                                            </p>
                                                            {doc.document_type && (
                                                                <Badge variant="secondary" className="mt-1 text-xs">
                                                                    {doc.document_type}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {(doc.file_path || doc.document) && (
                                                            <a
                                                                href={getImagePath(doc.file_path || doc.document)}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </a>
                                                        )}
                                                    </div>
                                                </Card>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8 text-muted-foreground">
                                            <p>{t('No documents uploaded.')}</p>
                                            {/* Debug info - remove in production */}
                                            {process.env.NODE_ENV === 'development' && (
                                                <p className="text-xs mt-2">Debug: {documents ? `Found ${documents.length} documents` : 'Documents is null/undefined'}</p>
                                            )}
                                        </div>
                                    )}
                                </TabsContent>

                                <TabsContent value="legal" className="space-y-6 mt-6">
                                    <Card>
                                        <CardContent className="p-4 space-y-2">
                                            <p className="text-sm text-muted-foreground">{t('Foreign Worker Quota')}</p>
                                            <div className="flex flex-wrap gap-2 text-xs">
                                                <Badge variant="outline">{t('Employer Type')}: {foreignQuota?.employer_type ?? '-'}</Badge>
                                                <Badge variant="outline">{t('Workers')}: {foreignQuota?.total_workers ?? 0}</Badge>
                                                <Badge variant="outline">{t('Quota Slots')}: {foreignQuota?.quota_slots ?? 0}</Badge>
                                                <Badge variant="outline">{t('Current Foreign')}: {foreignQuota?.current_foreign_workers ?? 0}</Badge>
                                                <Badge variant="outline">{t('Remaining')}: {foreignQuota?.remaining_slots ?? 0}</Badge>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {canViewSensitive && (
                                        <Card>
                                            <CardContent className="p-4 space-y-4">
                                                <h4 className="font-semibold">{t('INSS Profile')}</h4>
                                                <form
                                                    className="grid grid-cols-1 md:grid-cols-3 gap-3"
                                                    onSubmit={(e) => {
                                                        e.preventDefault();
                                                        socialProfileForm.put(route('hrm.employees.social-security-profile.upsert', employee.id), { preserveScroll: true });
                                                    }}
                                                >
                                                    <div>
                                                        <Label>{t('INSS Number')}</Label>
                                                        <Input value={socialProfileForm.data.inss_number} onChange={(e) => socialProfileForm.setData('inss_number', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.inss_number} />
                                                    </div>
                                                    <div>
                                                        <Label>{t('Registration Date')}</Label>
                                                        <Input type="date" value={socialProfileForm.data.registration_date} onChange={(e) => socialProfileForm.setData('registration_date', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.registration_date} />
                                                    </div>
                                                    <div>
                                                        <Label>{t('Status')}</Label>
                                                        <Input value={socialProfileForm.data.registration_status} onChange={(e) => socialProfileForm.setData('registration_status', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.registration_status} />
                                                    </div>
                                                    <div>
                                                        <Label>{t('ID Document Type')}</Label>
                                                        <Input value={socialProfileForm.data.identification_document_type} onChange={(e) => socialProfileForm.setData('identification_document_type', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.identification_document_type} />
                                                    </div>
                                                    <div>
                                                        <Label>{t('ID Document Number')}</Label>
                                                        <Input value={socialProfileForm.data.identification_document_number} onChange={(e) => socialProfileForm.setData('identification_document_number', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.identification_document_number} />
                                                    </div>
                                                    <div>
                                                        <Label>{t('Evidence Path')}</Label>
                                                        <Input value={socialProfileForm.data.evidence_file_path} onChange={(e) => socialProfileForm.setData('evidence_file_path', e.target.value)} />
                                                        <InputError message={socialProfileForm.errors.evidence_file_path} />
                                                    </div>
                                                    <div className="md:col-span-3">
                                                        <Button type="submit" disabled={!canEdit || socialProfileForm.processing}>
                                                            {t('Save INSS Profile')}
                                                        </Button>
                                                    </div>
                                                </form>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {canViewSensitive && (
                                        <Card>
                                            <CardContent className="p-4 space-y-4">
                                                <h4 className="font-semibold">{t('Foreign Worker Profile')}</h4>
                                                <form
                                                className="grid grid-cols-1 md:grid-cols-3 gap-3"
                                                onSubmit={(e) => {
                                                    e.preventDefault();
                                                    foreignProfileForm.put(route('hrm.employees.foreign-worker-profile.upsert', employee.id), { preserveScroll: true });
                                                }}
                                            >
                                                <div className="md:col-span-3">
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input
                                                            type="checkbox"
                                                            checked={foreignProfileForm.data.is_foreign_worker}
                                                            onChange={(e) => foreignProfileForm.setData('is_foreign_worker', e.target.checked)}
                                                        />
                                                        {t('Is Foreign Worker')}
                                                    </label>
                                                    <InputError message={foreignProfileForm.errors.is_foreign_worker} />
                                                </div>
                                                <div>
                                                    <Label>{t('Nationality')}</Label>
                                                    <Input value={foreignProfileForm.data.nationality} onChange={(e) => foreignProfileForm.setData('nationality', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.nationality} />
                                                </div>
                                                <div>
                                                    <Label>{t('Residency Status')}</Label>
                                                    <Input value={foreignProfileForm.data.residency_status} onChange={(e) => foreignProfileForm.setData('residency_status', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.residency_status} />
                                                </div>
                                                <div>
                                                    <Label>{t('Work Regime')}</Label>
                                                    <Input value={foreignProfileForm.data.hiring_regime} onChange={(e) => foreignProfileForm.setData('hiring_regime', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.hiring_regime} />
                                                </div>
                                                <div>
                                                    <Label>{t('Passport Number')}</Label>
                                                    <Input value={foreignProfileForm.data.passport_number} onChange={(e) => foreignProfileForm.setData('passport_number', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.passport_number} />
                                                </div>
                                                <div>
                                                    <Label>{t('Passport Expiry')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.passport_expires_at} onChange={(e) => foreignProfileForm.setData('passport_expires_at', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.passport_expires_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Visa Type')}</Label>
                                                    <Input value={foreignProfileForm.data.visa_type} onChange={(e) => foreignProfileForm.setData('visa_type', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.visa_type} />
                                                </div>
                                                <div>
                                                    <Label>{t('Visa Expiry')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.visa_expires_at} onChange={(e) => foreignProfileForm.setData('visa_expires_at', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.visa_expires_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Work Authorization')}</Label>
                                                    <Input value={foreignProfileForm.data.work_authorization_number} onChange={(e) => foreignProfileForm.setData('work_authorization_number', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.work_authorization_number} />
                                                </div>
                                                <div>
                                                    <Label>{t('Authorization Expiry')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.work_authorization_expires_at} onChange={(e) => foreignProfileForm.setData('work_authorization_expires_at', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.work_authorization_expires_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Work Province')}</Label>
                                                    <Input value={foreignProfileForm.data.work_province} onChange={(e) => foreignProfileForm.setData('work_province', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.work_province} />
                                                </div>
                                                <div>
                                                    <Label>{t('Entry Date')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.mozambique_entry_date} onChange={(e) => foreignProfileForm.setData('mozambique_entry_date', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.mozambique_entry_date} />
                                                </div>
                                                <div>
                                                    <Label>{t('Cessation Effective Date')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.cessation_effective_date} onChange={(e) => foreignProfileForm.setData('cessation_effective_date', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.cessation_effective_date} />
                                                </div>
                                                <div>
                                                    <Label>{t('Cessation Notification Due')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.cessation_notification_due_at} onChange={(e) => foreignProfileForm.setData('cessation_notification_due_at', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.cessation_notification_due_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Cessation Notified At')}</Label>
                                                    <Input type="date" value={foreignProfileForm.data.cessation_notified_at} onChange={(e) => foreignProfileForm.setData('cessation_notified_at', e.target.value)} />
                                                    <InputError message={foreignProfileForm.errors.cessation_notified_at} />
                                                </div>
                                                    <div className="md:col-span-3">
                                                        <Button type="submit" disabled={!canEdit || foreignProfileForm.processing}>
                                                            {t('Save Foreign Worker Profile')}
                                                        </Button>
                                                    </div>
                                                </form>
                                            </CardContent>
                                        </Card>
                                    )}

                                    <Card>
                                        <CardContent className="p-4 space-y-3">
                                            <h4 className="font-semibold">{t('Probation Alerts')}</h4>
                                            <div className="flex flex-wrap gap-2 text-xs">
                                                <Badge variant="outline">
                                                    {t('Days Remaining')}: {probationAlerts?.days_remaining ?? '-'}
                                                </Badge>
                                                <Badge variant={probationAlerts?.is_overdue ? 'destructive' : 'outline'}>
                                                    {t('Overdue')}: {probationAlerts?.is_overdue ? t('Yes') : t('No')}
                                                </Badge>
                                                <Badge variant={probationAlerts?.alert_15_days ? 'destructive' : 'outline'}>
                                                    {t('15 Days Alert')}: {probationAlerts?.alert_15_days ? t('Yes') : t('No')}
                                                </Badge>
                                                <Badge variant={probationAlerts?.alert_7_days ? 'destructive' : 'outline'}>
                                                    {t('7 Days Alert')}: {probationAlerts?.alert_7_days ? t('Yes') : t('No')}
                                                </Badge>
                                                <Badge variant={probationAlerts?.alert_last_day ? 'destructive' : 'outline'}>
                                                    {t('Last Day Alert')}: {probationAlerts?.alert_last_day ? t('Yes') : t('No')}
                                                </Badge>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardContent className="p-4 space-y-4">
                                            <h4 className="font-semibold">{t('Probation Profile')}</h4>
                                            <form
                                                className="grid grid-cols-1 md:grid-cols-3 gap-3"
                                                onSubmit={(e) => {
                                                    e.preventDefault();
                                                    probationForm.put(route('hrm.employees.probation-profile.upsert', employee.id), { preserveScroll: true });
                                                }}
                                            >
                                                <div>
                                                    <Label>{t('Probation Category')}</Label>
                                                    <select
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                        value={probationForm.data.probation_category}
                                                        onChange={(e) => probationForm.setData('probation_category', e.target.value)}
                                                    >
                                                        {Object.entries(probationCategoryLimits ?? {}).map(([category, maxDays]) => (
                                                            <option key={category} value={category}>
                                                                {probationLabels[category] ?? category} ({maxDays} {t('days')})
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError message={probationForm.errors.probation_category} />
                                                </div>
                                                <div>
                                                    <Label>{t('Start Date')}</Label>
                                                    <Input type="date" value={probationForm.data.starts_at} onChange={(e) => probationForm.setData('starts_at', e.target.value)} />
                                                    <InputError message={probationForm.errors.starts_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Expected End Date')}</Label>
                                                    <Input type="date" value={probationForm.data.expected_end_at} onChange={(e) => probationForm.setData('expected_end_at', e.target.value)} />
                                                    <InputError message={probationForm.errors.expected_end_at} />
                                                </div>
                                                <div>
                                                    <Label>{t('Evaluation Status')}</Label>
                                                    <select
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                        value={probationForm.data.evaluation_status}
                                                        onChange={(e) => probationForm.setData('evaluation_status', e.target.value)}
                                                    >
                                                        <option value="pending">{t('Pending')}</option>
                                                        <option value="approved">{t('Approved')}</option>
                                                        <option value="failed">{t('Failed')}</option>
                                                    </select>
                                                    <InputError message={probationForm.errors.evaluation_status} />
                                                </div>
                                                <div>
                                                    <Label>{t('Decision Status')}</Label>
                                                    <select
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                        value={probationForm.data.decision_status}
                                                        onChange={(e) => probationForm.setData('decision_status', e.target.value)}
                                                    >
                                                        <option value="ongoing">{t('Ongoing')}</option>
                                                        <option value="confirmed">{t('Confirmed')}</option>
                                                        <option value="ceased">{t('Ceased')}</option>
                                                    </select>
                                                    <InputError message={probationForm.errors.decision_status} />
                                                </div>
                                                <div>
                                                    <Label>{t('Recommendation')}</Label>
                                                    <select
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                        value={probationForm.data.recommendation}
                                                        onChange={(e) => probationForm.setData('recommendation', e.target.value)}
                                                    >
                                                        <option value="">{t('Select')}</option>
                                                        <option value="continue">{t('Continue')}</option>
                                                        <option value="cease">{t('Cease')}</option>
                                                    </select>
                                                    <InputError message={probationForm.errors.recommendation} />
                                                </div>
                                                <div>
                                                    <Label>{t('Decision Date')}</Label>
                                                    <Input type="date" value={probationForm.data.decision_date} onChange={(e) => probationForm.setData('decision_date', e.target.value)} />
                                                    <InputError message={probationForm.errors.decision_date} />
                                                </div>
                                                <div>
                                                    <Label>{t('Technical Score (1-5)')}</Label>
                                                    <Input type="number" min={1} max={5} value={probationForm.data.technical_score} onChange={(e) => probationForm.setData('technical_score', e.target.value)} />
                                                    <InputError message={probationForm.errors.technical_score} />
                                                </div>
                                                <div>
                                                    <Label>{t('Attendance Score (1-5)')}</Label>
                                                    <Input type="number" min={1} max={5} value={probationForm.data.attendance_score} onChange={(e) => probationForm.setData('attendance_score', e.target.value)} />
                                                    <InputError message={probationForm.errors.attendance_score} />
                                                </div>
                                                <div>
                                                    <Label>{t('Punctuality Score (1-5)')}</Label>
                                                    <Input type="number" min={1} max={5} value={probationForm.data.punctuality_score} onChange={(e) => probationForm.setData('punctuality_score', e.target.value)} />
                                                    <InputError message={probationForm.errors.punctuality_score} />
                                                </div>
                                                <div>
                                                    <Label>{t('Conduct Score (1-5)')}</Label>
                                                    <Input type="number" min={1} max={5} value={probationForm.data.conduct_score} onChange={(e) => probationForm.setData('conduct_score', e.target.value)} />
                                                    <InputError message={probationForm.errors.conduct_score} />
                                                </div>
                                                <div>
                                                    <Label>{t('Adaptation Score (1-5)')}</Label>
                                                    <Input type="number" min={1} max={5} value={probationForm.data.adaptation_score} onChange={(e) => probationForm.setData('adaptation_score', e.target.value)} />
                                                    <InputError message={probationForm.errors.adaptation_score} />
                                                </div>
                                                <div className="md:col-span-3">
                                                    <Label>{t('Cessation Reason')}</Label>
                                                    <Input value={probationForm.data.cessation_reason} onChange={(e) => probationForm.setData('cessation_reason', e.target.value)} />
                                                    <InputError message={probationForm.errors.cessation_reason} />
                                                </div>
                                                <div className="md:col-span-3">
                                                    <Label>{t('Notes')}</Label>
                                                    <Input value={probationForm.data.notes} onChange={(e) => probationForm.setData('notes', e.target.value)} />
                                                    <InputError message={probationForm.errors.notes} />
                                                </div>
                                                <div className="md:col-span-3">
                                                    <Button type="submit" disabled={!canEdit || probationForm.processing}>
                                                        {t('Save Probation Profile')}
                                                    </Button>
                                                </div>
                                            </form>
                                        </CardContent>
                                    </Card>

                                    {canViewSensitive ? (
                                        <Card>
                                            <CardContent className="p-4 space-y-4">
                                                <h4 className="font-semibold">{t('IRPS Dependents')}</h4>
                                                <form
                                                className="grid grid-cols-1 md:grid-cols-3 gap-3"
                                                onSubmit={(e) => {
                                                    e.preventDefault();
                                                    dependentForm.post(route('hrm.employees.dependents.store', employee.id), {
                                                        preserveScroll: true,
                                                        onSuccess: () => dependentForm.reset(),
                                                    });
                                                }}
                                            >
                                                <div>
                                                    <Label>{t('Full Name')}</Label>
                                                    <Input value={dependentForm.data.full_name} onChange={(e) => dependentForm.setData('full_name', e.target.value)} />
                                                    <InputError message={dependentForm.errors.full_name} />
                                                </div>
                                                <div>
                                                    <Label>{t('Relationship')}</Label>
                                                    <Input value={dependentForm.data.relationship} onChange={(e) => dependentForm.setData('relationship', e.target.value)} />
                                                    <InputError message={dependentForm.errors.relationship} />
                                                </div>
                                                <div>
                                                    <Label>{t('Date of Birth')}</Label>
                                                    <Input type="date" value={dependentForm.data.date_of_birth} onChange={(e) => dependentForm.setData('date_of_birth', e.target.value)} />
                                                    <InputError message={dependentForm.errors.date_of_birth} />
                                                </div>
                                                <div>
                                                    <Label>{t('Document Number')}</Label>
                                                    <Input value={dependentForm.data.document_number} onChange={(e) => dependentForm.setData('document_number', e.target.value)} />
                                                    <InputError message={dependentForm.errors.document_number} />
                                                </div>
                                                <div>
                                                    <Label>{t('Valid Until')}</Label>
                                                    <Input type="date" value={dependentForm.data.valid_until} onChange={(e) => dependentForm.setData('valid_until', e.target.value)} />
                                                    <InputError message={dependentForm.errors.valid_until} />
                                                </div>
                                                <div>
                                                    <Label>{t('Notes')}</Label>
                                                    <Input value={dependentForm.data.notes} onChange={(e) => dependentForm.setData('notes', e.target.value)} />
                                                    <InputError message={dependentForm.errors.notes} />
                                                </div>
                                                <div className="md:col-span-3 flex flex-wrap items-center gap-4">
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" checked={dependentForm.data.is_student} onChange={(e) => dependentForm.setData('is_student', e.target.checked)} />
                                                        {t('Student')}
                                                    </label>
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" checked={dependentForm.data.is_tax_eligible} onChange={(e) => dependentForm.setData('is_tax_eligible', e.target.checked)} />
                                                        {t('Tax Eligible')}
                                                    </label>
                                                    <Button type="submit" disabled={!canEdit || dependentForm.processing}>
                                                        {t('Add Dependent')}
                                                    </Button>
                                                </div>
                                            </form>

                                            <div className="space-y-2">
                                                {(employee.dependents ?? []).length === 0 && (
                                                    <p className="text-sm text-muted-foreground">{t('No dependents registered yet.')}</p>
                                                )}
                                                {(employee.dependents ?? []).map((dependent: any) => (
                                                    <div key={dependent.id} className="flex items-center justify-between rounded border p-3 text-sm">
                                                        <div>
                                                            <p className="font-medium">{dependent.full_name}</p>
                                                            <p className="text-muted-foreground">
                                                                {dependent.relationship} · {dependent.date_of_birth ? formatDate(dependent.date_of_birth) : '-'}
                                                            </p>
                                                        </div>
                                                        {canEdit && (
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                onClick={() => router.delete(route('hrm.employees.dependents.destroy', [employee.id, dependent.id]), { preserveScroll: true })}
                                                            >
                                                                {t('Remove')}
                                                            </Button>
                                                        )}
                                                    </div>
                                                ))}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ) : (
                                        <Card>
                                            <CardContent className="p-4 text-sm text-muted-foreground">
                                                {t('Sensitive legal profiles (INSS, foreign worker and tax dependents) are restricted for your profile.')}
                                            </CardContent>
                                        </Card>
                                    )}
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
