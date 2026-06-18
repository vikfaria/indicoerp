<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaLibraryStorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_media_index_exposes_storage_quota_details(): void
    {
        $this->makePermissions();

        $company = User::factory()->create([
            'name' => 'Empresa Media',
            'email' => 'media@example.com',
            'type' => 'company',
            'storage_limit' => 1,
            'created_by' => null,
            'creator_id' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => now()->addMonth(),
        ]);
        $this->grantPermissions($company, ['manage-media']);

        $response = $this->actingAs($company)->getJson(route('media.index'));

        $response->assertOk();
        $response->assertJsonPath('storage.status', 'within_limit');
        $response->assertJsonPath('storage.limit_bytes', 1024);
        $response->assertJsonPath('storage.current_bytes', 0);
        $response->assertJsonPath('storage.available_bytes', 1024);
        $response->assertJsonPath('storage.usage_percent', 0);
        $response->assertJsonPath('storage.requested_bytes', 0);
        $response->assertJsonPath('storage.projected_bytes', 0);
    }

    public function test_media_upload_returns_structured_storage_error_when_quota_is_exceeded(): void
    {
        $this->makePermissions();

        $company = User::factory()->create([
            'name' => 'Empresa Quota',
            'email' => 'quota@example.com',
            'type' => 'company',
            'storage_limit' => 1,
            'created_by' => null,
            'creator_id' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => now()->addMonth(),
        ]);
        $this->grantPermissions($company, ['manage-media', 'create-media']);

        $file = UploadedFile::fake()->create('photo.jpg', 2, 'image/jpeg');

        $response = $this->actingAs($company)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('media.batch'), [
                'files' => [$file],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'storage_limit_exceeded');
        $response->assertJsonPath('storage.status', 'exceeded');
        $response->assertJsonPath('storage.limit_bytes', 1024);
        $response->assertJsonPath('storage.current_bytes', 0);
        $response->assertJsonPath('storage.available_bytes', 1024);
        $response->assertJsonPath('storage.usage_percent', 200);
        $response->assertJsonPath('storage.requested_bytes', 2048);
        $response->assertJsonPath('storage.projected_bytes', 2048);
    }

    private function makePermissions(): void
    {
        Permission::firstOrCreate(
            ['name' => 'manage-media', 'guard_name' => 'web'],
            ['add_on' => 'media', 'module' => 'media', 'label' => 'Manage Media']
        );

        Permission::firstOrCreate(
            ['name' => 'create-media', 'guard_name' => 'web'],
            ['add_on' => 'media', 'module' => 'media', 'label' => 'Create Media']
        );
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'media',
                    'module' => 'media',
                    'label' => ucfirst(str_replace('-', ' ', $permissionName)),
                ]
            );

            $user->givePermissionTo($permission);
        }
    }
}
