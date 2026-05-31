<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\DocumentCategory;
use Workdo\Hrm\Models\HrmDocument;

class HrmDocumentRequestIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_document_store_rejects_cross_company_category_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-hrm-documents']);

        $categoryB = $this->makeDocumentCategory($companyB, 'External Category');

        $response = $this->actingAs($companyA)->post(route('hrm.documents.store'), [
            'title' => 'Cross-company document',
            'document_category_id' => $categoryB->id,
            'description' => 'Should fail validation',
            'document' => 'documents/cross-company.pdf',
        ]);

        $response->assertSessionHasErrors(['document_category_id']);
        $this->assertDatabaseMissing('hrm_documents', [
            'title' => 'Cross-company document',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_document_update_rejects_cross_company_category_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-hrm-documents', 'manage-any-hrm-documents']);

        $categoryA = $this->makeDocumentCategory($companyA, 'Category A');
        $categoryB = $this->makeDocumentCategory($companyB, 'Category B');
        $documentA = $this->makeDocument($companyA, $categoryA, 'Document A');

        $response = $this->actingAs($companyA)->put(route('hrm.documents.update', $documentA->id), [
            'title' => 'Document A Updated',
            'document_category_id' => $categoryB->id,
            'description' => 'Invalid cross-company category',
            'document' => 'documents/updated.pdf',
        ]);

        $response->assertSessionHasErrors(['document_category_id']);
        $this->assertDatabaseHas('hrm_documents', [
            'id' => $documentA->id,
            'document_category_id' => $categoryA->id,
            'created_by' => $companyA->id,
        ]);
    }

    public function test_document_update_denies_cross_company_document_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-hrm-documents', 'manage-any-hrm-documents']);

        $categoryA = $this->makeDocumentCategory($companyA, 'Category A');
        $categoryB = $this->makeDocumentCategory($companyB, 'Category B');
        $documentB = $this->makeDocument($companyB, $categoryB, 'Document B');

        $response = $this->actingAs($companyA)->put(route('hrm.documents.update', $documentB->id), [
            'title' => 'Invalid takeover',
            'document_category_id' => $categoryA->id,
            'description' => 'Should be blocked by tenant access.',
            'document' => 'documents/attempt.pdf',
        ]);

        $response->assertRedirect(route('hrm.documents.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('hrm_documents', [
            'id' => $documentB->id,
            'title' => 'Document B',
            'created_by' => $companyB->id,
        ]);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeDocumentCategory(User $company, string $name): DocumentCategory
    {
        return DocumentCategory::query()->create([
            'document_type' => $name,
            'status' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDocument(User $company, DocumentCategory $category, string $title): HrmDocument
    {
        return HrmDocument::query()->create([
            'title' => $title,
            'document_category_id' => $category->id,
            'description' => $title . ' description',
            'document' => 'documents/sample.pdf',
            'uploaded_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'hrm',
                    'module' => 'hrm',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
