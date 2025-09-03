<?php

namespace Tests\Unit;

use App\Models\Translation;
use App\Models\Tag;
use App\Repositories\TranslationRepository;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TranslationService(new TranslationRepository());
    }

    public function test_can_create_translation_with_tags(): void
    {
        $translation = $this->service->create(
            key: 'test.key',
            locale: 'en',
            content: 'Test content',
            tags: ['web', 'mobile']
        );

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('test.key', $translation->key);
        $this->assertEquals('en', $translation->locale);
        $this->assertEquals('Test content', $translation->content);
        $this->assertCount(2, $translation->tags);
        $this->assertTrue($translation->tags->contains('name', 'web'));
        $this->assertTrue($translation->tags->contains('name', 'mobile'));
    }

    public function test_can_update_translation_tags(): void
    {
        $translation = Translation::factory()->create();
        $webTag = Tag::factory()->create(['name' => 'web']);
        $translation->tags()->attach($webTag);

        $updatedTranslation = $this->service->update(
            id: $translation->id,
            content: 'Updated content',
            tags: ['mobile', 'desktop']
        );

        $updatedTranslation->refresh();
        $this->assertEquals('Updated content', $updatedTranslation->content);
        $this->assertCount(2, $updatedTranslation->tags);
        $this->assertFalse($updatedTranslation->tags->contains('name', 'web'));
        $this->assertTrue($updatedTranslation->tags->contains('name', 'mobile'));
        $this->assertTrue($updatedTranslation->tags->contains('name', 'desktop'));
    }

    public function test_get_paginated_returns_paginator(): void
    {
        Translation::factory()->count(15)->create();

        $result = $this->service->getPaginated(perPage: 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(15, $result->total());
    }

    public function test_search_with_multiple_criteria(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);

        $translation1 = Translation::factory()->create([
            'key' => 'search.test',
            'locale' => 'en',
            'content' => 'Searchable content'
        ]);
        $translation1->tags()->attach($webTag);

        $translation2 = Translation::factory()->create([
            'key' => 'other.test',
            'locale' => 'fr',
            'content' => 'Different content'
        ]);

        $results = $this->service->search(
            locale: 'en',
            tags: 'web',
            key: 'search'
        );

        $this->assertCount(1, $results->items());
        $this->assertEquals('search.test', $results->items()[0]->key);
    }

    public function test_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $result = $this->service->delete($translation->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_find_by_id_throws_exception_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->findById(99999);
    }
}
