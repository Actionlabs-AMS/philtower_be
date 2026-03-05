<?php

namespace App\Traits;

use App\Helpers\AnonymizationHelper;
use Illuminate\Support\Facades\Log;

trait Anonymizable
{
    /**
     * Boot the trait
     */
    protected static function bootAnonymizable()
    {
        static::deleting(function ($model) {
            if (config('anonymization.triggers.on_delete', true)) {
                $model->anonymizeData('Model deletion');
            }
        });

        static::deleted(function ($model) {
            if (config('anonymization.triggers.on_soft_delete', false) && $model->trashed()) {
                $model->anonymizeData('Soft deletion');
            }
        });
    }

    /**
     * Get anonymization methods for this model
     *
     * @return array
     */
    public function getAnonymizationMethods()
    {
        $modelName = class_basename($this);
        return AnonymizationHelper::getAnonymizationMethodsForModel($modelName);
    }

    /**
     * Anonymize data for this model
     *
     * @param string $reason
     * @return bool
     */
    public function anonymizeData($reason = 'GDPR compliance')
    {
        if (!config('anonymization.enabled', true)) {
            return false;
        }

        try {
            $methods = $this->getAnonymizationMethods();
            $data = $this->toArray();
            $anonymizedData = AnonymizationHelper::anonymizeFields($data, $methods);
            
            // Update the model with anonymized data
            foreach ($anonymizedData as $field => $value) {
                if (isset($methods[$field])) {
                    $this->$field = $value;
                }
            }
            
            $this->save();
            
            // Log anonymization
            if (config('anonymization.logging.enabled', true)) {
                Log::info('Model data anonymized', [
                    'model' => get_class($this),
                    'model_id' => $this->id ?? 'unknown',
                    'reason' => $reason,
                    'anonymized_fields' => array_keys($methods),
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Data anonymization failed', [
                'model' => get_class($this),
                'model_id' => $this->id ?? 'unknown',
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if data should be anonymized
     *
     * @return bool
     */
    public function shouldAnonymize()
    {
        return AnonymizationHelper::shouldAnonymize($this);
    }

    /**
     * Anonymize specific fields
     *
     * @param array $fields
     * @param string $reason
     * @return bool
     */
    public function anonymizeFields(array $fields, $reason = 'GDPR compliance')
    {
        if (!config('anonymization.enabled', true)) {
            return false;
        }

        try {
            $methods = $this->getAnonymizationMethods();
            $fieldMethods = array_intersect_key($methods, array_flip($fields));
            
            $data = $this->toArray();
            $anonymizedData = AnonymizationHelper::anonymizeFields($data, $fieldMethods);
            
            // Update the model with anonymized data
            foreach ($anonymizedData as $field => $value) {
                if (isset($fieldMethods[$field])) {
                    $this->$field = $value;
                }
            }
            
            $this->save();
            
            // Log anonymization
            if (config('anonymization.logging.enabled', true)) {
                Log::info('Specific fields anonymized', [
                    'model' => get_class($this),
                    'model_id' => $this->id ?? 'unknown',
                    'reason' => $reason,
                    'anonymized_fields' => $fields,
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Field anonymization failed', [
                'model' => get_class($this),
                'model_id' => $this->id ?? 'unknown',
                'reason' => $reason,
                'fields' => $fields,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get anonymization status for all fields
     *
     * @return array
     */
    public function getAnonymizationStatus()
    {
        $methods = $this->getAnonymizationMethods();
        $status = [];
        
        foreach ($methods as $field => $method) {
            $status[$field] = [
                'method' => $method,
                'is_anonymized' => $this->isFieldAnonymized($field),
                'original_value' => $this->getOriginal($field),
                'current_value' => $this->$field,
            ];
        }
        
        return $status;
    }

    /**
     * Check if a field is anonymized
     *
     * @param string $field
     * @return bool
     */
    public function isFieldAnonymized($field)
    {
        $methods = $this->getAnonymizationMethods();
        
        if (!isset($methods[$field])) {
            return false;
        }
        
        $method = $methods[$field];
        $value = $this->$field;
        
        switch ($method) {
            case 'hash':
                return str_starts_with($value, config('anonymization.hash.prefix', 'anon:'));
            case 'mask':
                return str_contains($value, '*');
            case 'replace':
                $patterns = config('anonymization.patterns', []);
                $fieldPatterns = $patterns[$field] ?? [];
                return isset($fieldPatterns['replace']) && $value === $fieldPatterns['replace'];
            default:
                return false;
        }
    }

    /**
     * Restore anonymized data (if possible)
     *
     * @param array $fields
     * @return bool
     */
    public function restoreAnonymizedData(array $fields)
    {
        // Note: This is generally not possible for truly anonymized data
        // This method is here for completeness but will return false
        Log::warning('Attempted to restore anonymized data', [
            'model' => get_class($this),
            'model_id' => $this->id ?? 'unknown',
            'fields' => $fields,
        ]);
        
        return false;
    }

    /**
     * Get anonymization methods for specific fields
     *
     * @param array $fields
     * @return array
     */
    public function getAnonymizationMethodsForFields(array $fields)
    {
        $allMethods = $this->getAnonymizationMethods();
        return array_intersect_key($allMethods, array_flip($fields));
    }

    /**
     * Check if model has anonymizable fields
     *
     * @return bool
     */
    public function hasAnonymizableFields()
    {
        return !empty($this->getAnonymizationMethods());
    }

    /**
     * Get anonymization summary
     *
     * @return array
     */
    public function getAnonymizationSummary()
    {
        $methods = $this->getAnonymizationMethods();
        $summary = [
            'total_fields' => count($methods),
            'anonymized_fields' => 0,
            'methods_used' => [],
            'fields' => [],
        ];
        
        foreach ($methods as $field => $method) {
            $isAnonymized = $this->isFieldAnonymized($field);
            $summary['fields'][$field] = [
                'method' => $method,
                'is_anonymized' => $isAnonymized,
            ];
            
            if ($isAnonymized) {
                $summary['anonymized_fields']++;
            }
            
            if (!in_array($method, $summary['methods_used'])) {
                $summary['methods_used'][] = $method;
            }
        }
        
        return $summary;
    }
}
