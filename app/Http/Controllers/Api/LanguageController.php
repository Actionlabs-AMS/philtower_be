<?php

namespace App\Http\Controllers\Api;

use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Requests\LanguageRequest;
use App\Services\LanguageService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Language Management",
 *     description="API endpoints for language management"
 * )
 */
class LanguageController extends BaseController
{
    public function __construct(LanguageService $languageService, MessageService $messageService)
    {
        parent::__construct($languageService, $messageService);
    }

    /**
     * Display a listing of languages.
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * Display the specified language.
     */
    public function show($id)
    {
        return parent::show($id);
    }

    /**
     * Store a newly created language.
     */
    public function store(LanguageRequest $request)
    {
        try {
            $data = $request->all();
            
            // If setting as default, unset other defaults
            if (isset($data['is_default']) && $data['is_default']) {
                Language::where('is_default', true)->update(['is_default' => false]);
            }
            
            $language = $this->service->store($data);
            return response($language, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Update the specified language.
     */
    public function update(LanguageRequest $request, Int $id)
    {
        try {
            $data = $request->all();
            
            // If setting as default, unset other defaults
            if (isset($data['is_default']) && $data['is_default']) {
                Language::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
            }
            
            $language = $this->service->update($data, $id);
            return response($language, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Remove the specified language from storage (soft delete).
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * Bulk delete multiple languages.
     */
    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
    }

    /**
     * Get trashed languages.
     */
    public function getTrashed()
    {
        return parent::getTrashed();
    }

    /**
     * Restore a trashed language.
     */
    public function restore($id)
    {
        return parent::restore($id);
    }

    /**
     * Bulk restore multiple trashed languages.
     */
    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    /**
     * Permanently delete a language.
     */
    public function forceDelete($id)
    {
        return parent::forceDelete($id);
    }

    /**
     * Bulk permanently delete multiple languages.
     */
    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }

    /**
     * Get all active languages for dropdown.
     */
    public function getActiveLanguages()
    {
        try {
            return $this->service->getActiveLanguages();
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Set a language as default.
     */
    public function setDefault($id)
    {
        try {
            $language = $this->service->setDefault($id);
            return response([
                'message' => 'Language has been set as default.',
                'data' => $language
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}

