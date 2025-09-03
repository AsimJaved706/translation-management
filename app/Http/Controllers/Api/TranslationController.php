<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\StoreTranslationRequest;
use App\Http\Requests\Translation\UpdateTranslationRequest;
use App\Http\Requests\Translation\SearchTranslationRequest;
use App\Http\Requests\Translation\ExportTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use App\Services\TranslationExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Translation;
class TranslationController extends Controller
{
 public function __construct(
        private TranslationService $translationService,
        private TranslationExportService $exportService
    ) {}

    /**
     * Display a paginated list of translations.
     */
    public function index(Request $request): JsonResponse
    {
        $translations = $this->translationService->getPaginated(
            locale: $request->get('locale'),
            tags: $request->get('tags'),
            perPage: (int) $request->get('per_page', 50)
        );

        return TranslationResource::collection($translations)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a new translation.
     */
  /**
     * Store a new translation with explicit validation.
     */
    public function store(Request $request): JsonResponse
    {
        // Manual validation that WILL work
        $rules = [
            'key' => 'required|string|max:255',
            'locale' => 'required|string|size:2|alpha',
            'content' => 'required|string|max:65535',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ];

        $messages = [
            'key.required' => 'The key field is required.',
            'locale.required' => 'The locale field is required.',
            'locale.size' => 'The locale must be exactly 2 characters.',
            'locale.alpha' => 'The locale may only contain letters.',
            'content.required' => 'The content field is required.',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for unique key-locale combination manually
        $existing = Translation::where('key', $request->key)
            ->where('locale', strtolower($request->locale))
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'key' => ['A translation with this key already exists for the specified locale.']
                ]
            ], 422);
        }

        // Create the translation
        $translation = $this->translationService->create(
            key: $request->key,
            locale: strtolower($request->locale),
            content: $request->content,
            tags: $request->tags ?? []
        );

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }


    /**
     * Display the specified translation.
     */
    public function show(int $id): JsonResponse
    {
        $translation = $this->translationService->findById($id);

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified translation.
     */
    public function update(UpdateTranslationRequest $request, int $id): JsonResponse
    {
        $translation = $this->translationService->update(
            id: $id,
            key: $request->key,
            locale: $request->locale,
            content: $request->content,
            tags: $request->tags ?? []
        );

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->translationService->delete($id);

        return response()->json([
            'message' => 'Translation deleted successfully',
        ], 204);
    }

    /**
     * Search translations with various filters.
     */
    public function search(SearchTranslationRequest $request): JsonResponse
    {
        $translations = $this->translationService->search(
            locale: $request->get('locale'),
            tags: $request->get('tags'),
            key: $request->get('key'),
            content: $request->get('content'),
            perPage: (int) $request->get('per_page', 50)
        );

        return TranslationResource::collection($translations)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Export translations as JSON for frontend applications.
     */
    public function export(ExportTranslationRequest $request): JsonResponse
    {
        $exportData = $this->exportService->export(
            locale: $request->get('locale'),
            tags: $request->get('tags'),
            format: $request->get('format', 'flat')
        );

        return response()->json($exportData, 200, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'application/json',
        ]);
    }
}
