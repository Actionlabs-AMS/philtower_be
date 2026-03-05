<?php

namespace App\Services;

use App\Models\Language;
use App\Http\Resources\LanguageResource;

class LanguageService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new LanguageResource(new Language), new Language());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allLanguages = $this->getTotalCount();
        $trashedLanguages = $this->getTrashedCount();

        return LanguageResource::collection(Language::query()
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                $search = request('search');
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('code', 'LIKE', '%' . $search . '%')
                      ->orWhere('native_name', 'LIKE', '%' . $search . '%');
                });
            })
            ->when(request()->has('is_active') && request('is_active') !== '', function ($query) {
                return $query->where('is_active', request('is_active'));
            })
            ->when(request('order'), function ($query) {
                $order = request('order');
                $sort = request('sort', 'asc');
                return $query->orderBy($order, $sort);
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allLanguages, 'trashed' => $trashedLanguages]]);
    }

    /**
     * Get all active languages for dropdown.
     */
    public function getActiveLanguages()
    {
        return LanguageResource::collection(
            Language::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get()
        );
    }

    /**
     * Set a language as default (and unset others).
     */
    public function setDefault($id)
    {
        // Unset all defaults
        Language::where('is_default', true)->update(['is_default' => false]);
        
        // Set the selected language as default
        $language = Language::findOrFail($id);
        $language->update(['is_default' => true]);
        
        return $this->resource::make($language);
    }
}

