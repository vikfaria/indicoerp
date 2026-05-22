<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationControllerPackageMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_translation_endpoint_merges_package_translations_from_filesystem(): void
    {
        $response = $this->get(route('languages.translations', 'pt'));

        $response->assertOk();
        $response->assertJsonPath('locale', 'pt');

        $translations = $response->json('translations');
        $this->assertIsArray($translations);
        $this->assertArrayHasKey('Mozambique Tax Mapping', $translations);
        $this->assertSame('Mapeamento Fiscal de Moçambique', $translations['Mozambique Tax Mapping']);
    }
}

