<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DynamicStorageService;
use App\Services\StorageConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SeedCompanyDemoDataCommand extends Command
{
    protected $signature = 'demo:seed-company
                            {email=teste@gmail.com : Company email}
                            {--password=1234 : Password for created demo users}
                            {--skip-permissions : Skip HRM permission seeder}';

    protected $description = 'Seed HRM demo data for one company email (users + HRM records + demo media files).';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->option('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email: {$email}");
            return self::FAILURE;
        }

        $company = $this->resolveOrCreateCompany($email, $password);
        if (!$company) {
            return self::FAILURE;
        }

        $this->line("Company target: #{$company->id} {$company->email}");

        $this->ensureCompanyAccess($company);
        $this->seedSupportUsers($company, $password);
        $this->ensureDemoMediaFiles();

        if (!$this->option('skip-permissions')) {
            $this->runHrmPermissionSeeder();
        } else {
            $this->warn('Skipping HRM permission seeder by option.');
        }

        $this->runHrmDemoSeeders((int) $company->id);

        $this->showSummary((int) $company->id);

        $this->info('Demo seeding completed successfully.');

        return self::SUCCESS;
    }

    private function resolveOrCreateCompany(string $email, string $password): ?User
    {
        $company = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($company) {
            if ($company->type !== 'company') {
                $this->error("User exists but is not company type: {$email} ({$company->type}).");
                return null;
            }

            return $company;
        }

        $superAdmin = User::superAdminQuery()->orderBy('id')->first();
        $ownerId = $superAdmin?->id ?? 1;
        $name = Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString();
        if ($name === '') {
            $name = 'Demo Company';
        }

        $company = User::create([
            'name' => $name,
            'email' => $email,
            'mobile_no' => '+25884' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => Hash::make($password),
            'type' => 'company',
            'lang' => 'en',
            'email_verified_at' => now(),
            'creator_id' => $ownerId,
            'created_by' => $ownerId,
        ]);

        $this->warn("Company was not found and has been created: #{$company->id} {$company->email}");

        return $company;
    }

    private function ensureCompanyAccess(User $company): void
    {
        $company->ensureCompanyAccessRole();
        User::CompanySetting((int) $company->id);
        User::MakeRole((int) $company->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedSupportUsers(User $company, string $password): void
    {
        $companyId = (int) $company->id;
        $token = $this->companyToken($company);

        $this->seedUsersByRole(
            $companyId,
            $password,
            'staff',
            [
                'Ana Mucavele', 'Carlos Tembe', 'Lina Chissano', 'Nelson Sumbana', 'Marta Machel',
                'Joao Nhantumbo', 'Sofia Cossa', 'Paulo Uamusse', 'Teresa Matusse', 'Emilio Mussa',
                'Helena Arone', 'David Matola', 'Sergio Mondlane', 'Ivone Macia', 'Rui Chivambo',
                'Yara Chongo', 'Bruno Nhapi', 'Mila Cuambe',
            ],
            fn (int $index): string => "demo.staff{$index}.{$token}.c{$companyId}@indicoerp.demo"
        );

        $this->seedUsersByRole(
            $companyId,
            $password,
            'client',
            [
                'Maputo Trading Lda', 'Beira Retail Group', 'Nampula Foods SA', 'Tete Services',
                'Inhambane Transportes', 'Chimoio Agro Client',
            ],
            fn (int $index): string => "demo.client{$index}.{$token}.c{$companyId}@indicoerp.demo"
        );

        $this->seedUsersByRole(
            $companyId,
            $password,
            'vendor',
            [
                'Moz Supplies Co', 'Indico Logistics', 'Solar Equipamentos', 'ConstruPro Vendor',
                'Prime Imports MZ',
            ],
            fn (int $index): string => "demo.vendor{$index}.{$token}.c{$companyId}@indicoerp.demo"
        );

        if (empty($company->avatar)) {
            $company->avatar = 'avatar.png';
            $company->save();
        }
    }

    private function seedUsersByRole(
        int $companyId,
        string $password,
        string $roleName,
        array $names,
        callable $emailFactory
    ): void {
        $role = Role::where('name', $roleName)
            ->where('created_by', $companyId)
            ->where('guard_name', 'web')
            ->first();

        if (!$role) {
            $this->warn("Role not found for company #{$companyId}: {$roleName}. Users will be created without role binding.");
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (array_values($names) as $i => $name) {
            $index = $i + 1;
            $email = $emailFactory($index);
            $existing = User::where('email', $email)->first();

            if ($existing && (int) $existing->created_by !== $companyId) {
                $skipped++;
                $this->warn("Skipping email already owned by another company: {$email}");
                continue;
            }

            $user = $existing ?? new User();
            $isNew = !$user->exists;

            $user->name = $name;
            $user->email = $email;
            $user->mobile_no = '+25885' . str_pad((string) (1000000 + ($index * 17)), 7, '0', STR_PAD_LEFT);
            $user->password = Hash::make($password);
            $user->type = $roleName;
            $user->lang = 'en';
            $user->email_verified_at = $user->email_verified_at ?: now();
            $user->creator_id = $companyId;
            $user->created_by = $companyId;
            $user->avatar = $user->avatar ?: 'avatar.png';
            $user->save();

            if ($role) {
                $user->syncRoles([$role]);
            }

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->line("Users {$roleName}: created {$created}, updated {$updated}, skipped {$skipped}.");
    }

    private function companyToken(User $company): string
    {
        $token = Str::lower(Str::before($company->email, '@'));
        $token = preg_replace('/[^a-z0-9]+/', '', $token ?? '') ?: 'company';
        return Str::limit($token, 16, '');
    }

    private function runHrmPermissionSeeder(): void
    {
        $this->line('Running HRM permission seeder...');
        (new \Workdo\Hrm\Database\Seeders\PermissionTableSeeder())->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function runHrmDemoSeeders(int $companyId): void
    {
        $this->line("Running HRM demo seeders for company #{$companyId}...");

        $seeders = [
            \Workdo\Hrm\Database\Seeders\DemoBranchSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoDepartmentSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoDesignationSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoEmployeeDocumentTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoShiftSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoEmployeeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAwardTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAwardSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoPromotionSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoResignationSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoTerminationTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoTerminationSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoWarningTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoWarningSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoComplaintTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoComplaintSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoEmployeeTransferSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoHolidayTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoHolidaySeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoDocumentCategorySeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoHrmDocumentSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAcknowledgmentSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAnnouncementCategorySeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAnnouncementSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoEventTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoEventSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoLeaveTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoLeaveApplicationSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAttendanceSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAllowanceTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoDeductionTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoLoanTypeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoAllowanceSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoDeductionSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoLoanSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoOvertimeSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoPayrollSeeder::class,
            \Workdo\Hrm\Database\Seeders\DemoIpRestrictSeeder::class,
        ];

        foreach ($seeders as $seederClass) {
            if (!class_exists($seederClass)) {
                $this->warn('Seeder class not found: ' . $seederClass);
                continue;
            }

            $this->line('  - ' . class_basename($seederClass));
            $seeder = app($seederClass);
            $seeder->run($companyId);
        }
    }

    private function ensureDemoMediaFiles(): void
    {
        DynamicStorageService::configureDynamicDisks();
        $diskName = StorageConfigService::getActiveDisk();
        $disk = Storage::disk($diskName);

        $pngPlaceholder = $this->resolvePngPlaceholderBytes($diskName);
        $pdfPlaceholder = $this->minimalPdfBytes();

        $requiredFiles = [
            'leave-application.png',
            'award1.png', 'award2.png', 'award3.png', 'award4.png',
            'complaint1.png', 'complaint2.png', 'complaint3.png', 'complaint4.png',
            'promotion1.png', 'promotion2.png', 'promotion3.png', 'promotion4.png',
            'resignation1.png', 'resignation2.png', 'resignation3.png', 'resignation4.png',
            'termination1.png', 'termination2.png', 'termination3.png', 'termination4.png',
            'transfer1.png', 'transfer2.png', 'transfer3.png', 'transfer4.png',
            'warning1.png', 'warning2.png', 'warning3.png', 'warning4.png',
            'hrm_document1.png', 'hrm_document2.png', 'hrm_document3.png', 'hrm_document4.png',
            'flexible_schedule.pdf',
            'retirement_guide.pdf',
            'vendor_policy.pdf',
            'innovation_guide.pdf',
            'sustainability.pdf',
            'service_standards.pdf',
            'continuity_plan.pdf',
        ];

        $created = 0;
        foreach ($requiredFiles as $file) {
            $path = 'media/' . $file;
            if ($disk->exists($path)) {
                continue;
            }

            if (Str::endsWith($file, '.pdf')) {
                $disk->put($path, $pdfPlaceholder);
            } else {
                $disk->put($path, $pngPlaceholder);
            }

            $created++;
        }

        $this->line("Demo media files created: {$created} on disk [{$diskName}].");
    }

    private function resolvePngPlaceholderBytes(string $diskName): string
    {
        $disk = Storage::disk($diskName);
        if ($disk->exists('media/avatar.png')) {
            return (string) $disk->get('media/avatar.png');
        }

        $localAvatar = storage_path('app/public/media/avatar.png');
        if (is_file($localAvatar)) {
            $bytes = (string) file_get_contents($localAvatar);
            $disk->put('media/avatar.png', $bytes);
            return $bytes;
        }

        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAgMBgJ2B9vQAAAAASUVORK5CYII='
        );
    }

    private function minimalPdfBytes(): string
    {
        $pdfBase64 = 'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA2MTIgNzkyXSAvQ29udGVudHMgNCAwIFIgL1Jlc291cmNlcyA8PCAvRm9udCA8PCAvRjEgNSAwIFIgPj4gPj4gPj4KZW5kb2JqCjQgMCBvYmoKPDwgL0xlbmd0aCA0NCA+PgpzdHJlYW0KQlQKL0YxIDI0IFRmCjEwMCA3MDAgVGQKKEhlbGxvLCBQREYhKSBUagpFVAplbmRzdHJlYW0KZW5kb2JqCjUgMCBvYmoKPDwgL1R5cGUgL0ZvbnQgL1N1YnR5cGUgL1R5cGUxIC9CYXNlRm9udCAvSGVsdmV0aWNhID4+CmVuZG9iagp4cmVmCjAgNgowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDAwMDkgMDAwMDAgbiAKMDAwMDAwMDA1OCAwMDAwMCBuIAowMDAwMDAwMTE1IDAwMDAwIG4gCjAwMDAwMDAyNzAgMDAwMDAgbiAKMDAwMDAwMDM2MyAwMDAwMCBuIAp0cmFpbGVyCjw8IC9TaXplIDYgL1Jvb3QgMSAwIFIgPj4Kc3RhcnR4cmVmCjQ0MwolJUVPRgo=';

        return (string) base64_decode($pdfBase64);
    }

    private function showSummary(int $companyId): void
    {
        $employees = \Workdo\Hrm\Models\Employee::where('created_by', $companyId)->count();
        $branches = \Workdo\Hrm\Models\Branch::where('created_by', $companyId)->count();
        $departments = \Workdo\Hrm\Models\Department::where('created_by', $companyId)->count();
        $payrolls = \Workdo\Hrm\Models\Payroll::where('created_by', $companyId)->count();
        $attendances = \Workdo\Hrm\Models\Attendance::where('created_by', $companyId)->count();
        $leaveApplications = \Workdo\Hrm\Models\LeaveApplication::where('created_by', $companyId)->count();

        $staff = User::where('created_by', $companyId)->where('type', 'staff')->count();
        $clients = User::where('created_by', $companyId)->where('type', 'client')->count();
        $vendors = User::where('created_by', $companyId)->where('type', 'vendor')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Staff users', $staff],
                ['Client users', $clients],
                ['Vendor users', $vendors],
                ['Employees', $employees],
                ['Branches', $branches],
                ['Departments', $departments],
                ['Attendance rows', $attendances],
                ['Leave applications', $leaveApplications],
                ['Payrolls', $payrolls],
            ]
        );
    }
}
