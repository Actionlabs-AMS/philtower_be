<?php

namespace App\Services;

use App\Models\Translation;
use App\Models\Language;
use App\Http\Resources\TranslationResource;
use App\Http\Resources\LanguageResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranslationService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TranslationResource(new Translation), new Translation());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allTranslations = $this->getTotalCount();
            $trashedTranslations = $this->getTrashedCount();

            // Get per_page from request if provided
            $perPage = request('per_page', $perPage);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;

            $query = Translation::with('language');

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Apply search conditions
            if (request('search')) {
                $query->where(function($q) {
                    $q->where('key', 'LIKE', '%' . request('search') . '%')
                      ->orWhere('value', 'LIKE', '%' . request('search') . '%')
                      ->orWhere('group', 'LIKE', '%' . request('search') . '%')
                      ->orWhereHas('language', function($langQuery) {
                          $langQuery->where('name', 'LIKE', '%' . request('search') . '%')
                                    ->orWhere('code', 'LIKE', '%' . request('search') . '%');
                      });
                });
            }

            // Filter by language_id if provided
            if (request('language_id')) {
                $query->where('language_id', request('language_id'));
            }

            // Filter by group if provided
            if (request('group')) {
                $query->where('group', request('group'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort', 'asc'));
            } else {
                $query->orderBy('id', 'desc');
            }

            return TranslationResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allTranslations, 'trashed' => $trashedTranslations]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch translations: ' . $e->getMessage());
        }
    }

    /**
     * Get all languages for dropdown.
     */
    public function getLanguages()
    {
        return LanguageResource::collection(
            Language::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get()
        );
    }

    /**
     * Get all translation groups.
     */
    public function getGroups()
    {
        $groups = Translation::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group', 'asc')
            ->pluck('group')
            ->toArray();

        return $groups;
    }

    /**
     * Import translations from CSV file.
     */
    public function importFromCSV($file, $languageId = null)
    {
        try {
            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Read CSV file
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \Exception('CSV file is empty or invalid');
            }

            // Normalize headers (trim and lowercase)
            $headers = array_map('trim', array_map('strtolower', $headers));

            // Expected headers: key, value, group (optional), language_id (optional if not provided in request)
            $keyIndex = array_search('key', $headers);
            $valueIndex = array_search('value', $headers);
            $groupIndex = array_search('group', $headers);
            $languageIdIndex = array_search('language_id', $headers);
            $languageCodeIndex = array_search('language_code', $headers);

            if ($keyIndex === false || $valueIndex === false) {
                fclose($handle);
                throw new \Exception('CSV must contain "key" and "value" columns');
            }

            // If language_id is not provided in request, it must be in CSV
            if (!$languageId && $languageIdIndex === false && $languageCodeIndex === false) {
                fclose($handle);
                throw new \Exception('Either provide language_id in request or include "language_id" or "language_code" column in CSV');
            }

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Get language_id
                    $langId = $languageId;
                    if (!$langId) {
                        if ($languageIdIndex !== false && isset($row[$languageIdIndex])) {
                            $langId = trim($row[$languageIdIndex]);
                        } elseif ($languageCodeIndex !== false && isset($row[$languageCodeIndex])) {
                            $languageCode = trim($row[$languageCodeIndex]);
                            $language = Language::where('code', $languageCode)->first();
                            if (!$language) {
                                $errors[] = "Row $rowNumber: Language with code '$languageCode' not found";
                                $skipped++;
                                continue;
                            }
                            $langId = $language->id;
                        } else {
                            $errors[] = "Row $rowNumber: Language ID or code is required";
                            $skipped++;
                            continue;
                        }
                    }

                    // Validate language exists
                    $language = Language::find($langId);
                    if (!$language) {
                        $errors[] = "Row $rowNumber: Language with ID $langId not found";
                        $skipped++;
                        continue;
                    }

                    $key = trim($row[$keyIndex] ?? '');
                    $value = trim($row[$valueIndex] ?? '');
                    $group = $groupIndex !== false && isset($row[$groupIndex]) ? trim($row[$groupIndex]) : null;

                    if (empty($key) || empty($value)) {
                        $errors[] = "Row $rowNumber: Key and value are required";
                        $skipped++;
                        continue;
                    }

                    // Check if translation already exists
                    $existing = Translation::where('language_id', $langId)
                        ->where('key', $key)
                        ->where('group', $group)
                        ->first();

                    if ($existing) {
                        // Update existing translation
                        $existing->update(['value' => $value]);
                        $imported++;
                    } else {
                        // Create new translation
                        Translation::create([
                            'language_id' => $langId,
                            'key' => $key,
                            'value' => $value,
                            'group' => $group,
                        ]);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNumber: " . $e->getMessage();
                    $skipped++;
                }
            }

            fclose($handle);

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('CSV Import Error: ' . $e->getMessage());
            throw new \Exception('Failed to import CSV: ' . $e->getMessage());
        }
    }
}

