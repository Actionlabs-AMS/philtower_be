<?php

namespace App\Http\Controllers\Api;

use App\Models\Translation;
use Illuminate\Http\Request;
use App\Http\Requests\TranslationRequest;
use App\Services\TranslationService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Translation Management",
 *     description="API endpoints for translation management"
 * )
 */
class TranslationController extends BaseController
{
    public function __construct(TranslationService $translationService, MessageService $messageService)
    {
        parent::__construct($translationService, $messageService);
    }

    /**
     * Display a listing of translations.
     * 
     * @OA\Get(
     *     path="/api/system-settings/language/translations",
     *     summary="Get list of translations",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="language_id",
     *         in="query",
     *         description="Filter by language ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="group",
     *         in="query",
     *         description="Filter by group",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of translations retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * Display the specified translation.
     */
    public function show($id)
    {
        return parent::show($id);
    }

    /**
     * Store a newly created translation.
     * 
     * @OA\Post(
     *     path="/api/system-settings/language/translations",
     *     summary="Create a new translation",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"language_id", "key", "value"},
     *             @OA\Property(property="language_id", type="integer", example=1),
     *             @OA\Property(property="key", type="string", example="welcome.message"),
     *             @OA\Property(property="value", type="string", example="Welcome to our site"),
     *             @OA\Property(property="group", type="string", example="common"),
     *             @OA\Property(property="notes", type="string", example="Main welcome message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(TranslationRequest $request)
    {
        try {
            $data = $request->all();
            $translation = $this->service->store($data);
            return response($translation, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified translation.
     * 
     * @OA\Put(
     *     path="/api/system-settings/language/translations/{id}",
     *     summary="Update a translation",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"language_id", "key", "value"},
     *             @OA\Property(property="language_id", type="integer", example=1),
     *             @OA\Property(property="key", type="string", example="welcome.message"),
     *             @OA\Property(property="value", type="string", example="Welcome to our site"),
     *             @OA\Property(property="group", type="string", example="common"),
     *             @OA\Property(property="notes", type="string", example="Main welcome message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found"
     *     )
     * )
     */
    public function update(TranslationRequest $request, Int $id)
    {
        try {
            $data = $request->all();
            $translation = $this->service->update($data, $id);
            return response($translation, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Remove the specified translation from storage (soft delete).
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * Bulk delete multiple translations.
     */
    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
    }

    /**
     * Get trashed translations.
     */
    public function getTrashed()
    {
        return parent::getTrashed();
    }

    /**
     * Restore a trashed translation.
     */
    public function restore($id)
    {
        return parent::restore($id);
    }

    /**
     * Bulk restore multiple trashed translations.
     */
    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    /**
     * Permanently delete a translation.
     */
    public function forceDelete($id)
    {
        return parent::forceDelete($id);
    }

    /**
     * Bulk permanently delete multiple translations.
     */
    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }

    /**
     * Get all languages for dropdown.
     * 
     * @OA\Get(
     *     path="/api/system-settings/language/languages",
     *     summary="Get all languages",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Languages retrieved successfully"
     *     )
     * )
     */
    public function getLanguages()
    {
        try {
            return $this->service->getLanguages();
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get all translation groups.
     * 
     * @OA\Get(
     *     path="/api/system-settings/language/groups",
     *     summary="Get all translation groups",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Groups retrieved successfully"
     *     )
     * )
     */
    public function getGroups()
    {
        try {
            $groups = $this->service->getGroups();
            return response()->json(['data' => $groups]);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Import translations from CSV file.
     * 
     * @OA\Post(
     *     path="/api/system-settings/language/translations/import",
     *     summary="Import translations from CSV",
     *     tags={"Translation Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV file to import"
     *                 ),
     *                 @OA\Property(
     *                     property="language_id",
     *                     type="integer",
     *                     description="Language ID (optional if included in CSV)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translations imported successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
                'language_id' => 'nullable|exists:languages,id',
            ]);

            $file = $request->file('file');
            $languageId = $request->input('language_id');

            $result = $this->service->importFromCSV($file, $languageId);

            return response()->json([
                'message' => 'Import completed',
                'data' => $result,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

