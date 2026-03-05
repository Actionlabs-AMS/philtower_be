<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class AnonymizationHelper
{
    /**
     * Anonymize a value based on method
     *
     * @param mixed $value
     * @param string $method
     * @param string $fieldType
     * @return string
     */
    public static function anonymize($value, $method = 'replace', $fieldType = 'default')
    {
        if (empty($value)) {
            return $value;
        }

        if (!config('anonymization.enabled', true)) {
            return $value;
        }

        try {
            switch ($method) {
                case 'hash':
                    return self::hashValue($value, $fieldType);
                case 'mask':
                    return self::maskValue($value, $fieldType);
                case 'replace':
                    return self::replaceValue($value, $fieldType);
                default:
                    return self::replaceValue($value, $fieldType);
            }
        } catch (Exception $e) {
            Log::error('Anonymization failed', [
                'error' => $e->getMessage(),
                'value_length' => is_string($value) ? strlen($value) : 'non-string',
                'method' => $method,
                'field_type' => $fieldType,
            ]);
            return '[ANONYMIZATION_ERROR]';
        }
    }

    /**
     * Hash a value for anonymization
     *
     * @param mixed $value
     * @param string $fieldType
     * @return string
     */
    private static function hashValue($value, $fieldType)
    {
        $salt = config('anonymization.hash.salt', config('app.key'));
        $algorithm = config('anonymization.hash.algorithm', 'sha256');
        $prefix = config('anonymization.hash.prefix', 'anon:');

        $hash = hash($algorithm, $value . $salt);
        return $prefix . $hash;
    }

    /**
     * Mask a value for anonymization
     *
     * @param mixed $value
     * @param string $fieldType
     * @return string
     */
    private static function maskValue($value, $fieldType)
    {
        $patterns = config('anonymization.patterns', []);
        $fieldPatterns = $patterns[$fieldType] ?? $patterns['default'] ?? [];

        if (isset($fieldPatterns['mask'])) {
            return $fieldPatterns['mask'];
        }

        // Default masking based on field type
        switch ($fieldType) {
            case 'email':
                return '***@***.***';
            case 'phone':
                return '***-***-****';
            case 'name':
                return '*** ***';
            case 'address':
                return '*** *** St, ***, ** *****';
            case 'ssn':
                return '***-**-****';
            case 'credit_card':
                return '****-****-****-****';
            case 'ip_address':
                return '***.***.***.***';
            default:
                return str_repeat('*', min(strlen($value), 8));
        }
    }

    /**
     * Replace a value for anonymization
     *
     * @param mixed $value
     * @param string $fieldType
     * @return string
     */
    private static function replaceValue($value, $fieldType)
    {
        $patterns = config('anonymization.patterns', []);
        $fieldPatterns = $patterns[$fieldType] ?? $patterns['default'] ?? [];

        if (isset($fieldPatterns['replace'])) {
            return $fieldPatterns['replace'];
        }

        // Default replacement based on field type
        switch ($fieldType) {
            case 'email':
                return 'anonymized@example.com';
            case 'phone':
                return '000-000-0000';
            case 'name':
                return 'Anonymous User';
            case 'address':
                return '123 Anonymous Street, City, ST 12345';
            case 'ssn':
                return '000-00-0000';
            case 'credit_card':
                return '0000-0000-0000-0000';
            case 'ip_address':
                return '0.0.0.0';
            default:
                return '[ANONYMIZED]';
        }
    }

    /**
     * Anonymize multiple fields in data
     *
     * @param array $data
     * @param array $fieldMethods
     * @return array
     */
    public static function anonymizeFields(array $data, array $fieldMethods)
    {
        foreach ($fieldMethods as $field => $method) {
            if (isset($data[$field])) {
                $data[$field] = self::anonymize($data[$field], $method, $field);
            }
        }

        return $data;
    }

    /**
     * Get anonymization methods for a model
     *
     * @param string $modelName
     * @return array
     */
    public static function getAnonymizationMethodsForModel($modelName)
    {
        $modelAnonymization = config('anonymization.model_anonymization', []);
        
        if (isset($modelAnonymization[$modelName])) {
            return $modelAnonymization[$modelName];
        }

        return config('anonymization.methods', []);
    }

    /**
     * Anonymize a model instance
     *
     * @param mixed $model
     * @param string $reason
     * @return bool
     */
    public static function anonymizeModel($model, $reason = 'GDPR compliance')
    {
        if (!config('anonymization.enabled', true)) {
            return false;
        }

        try {
            $modelName = class_basename($model);
            $methods = self::getAnonymizationMethodsForModel($modelName);
            
            $data = $model->toArray();
            $anonymizedData = self::anonymizeFields($data, $methods);
            
            // Update the model with anonymized data
            foreach ($anonymizedData as $field => $value) {
                if (isset($methods[$field])) {
                    $model->$field = $value;
                }
            }
            
            $model->save();
            
            // Log anonymization
            if (config('anonymization.logging.enabled', true)) {
                Log::info('Model anonymized', [
                    'model' => $modelName,
                    'model_id' => $model->id ?? 'unknown',
                    'reason' => $reason,
                    'anonymized_fields' => array_keys($methods),
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Model anonymization failed', [
                'error' => $e->getMessage(),
                'model' => get_class($model),
                'model_id' => $model->id ?? 'unknown',
                'reason' => $reason,
            ]);
            return false;
        }
    }

    /**
     * Check if data should be anonymized based on GDPR settings
     *
     * @param mixed $model
     * @return bool
     */
    public static function shouldAnonymize($model)
    {
        if (!config('anonymization.enabled', true)) {
            return false;
        }

        $retentionDays = config('anonymization.gdpr.retention_period_days', 2555);
        $automaticDays = config('anonymization.triggers.automatic_after_days', 2555);
        
        // Check if model has created_at and is older than retention period
        if (isset($model->created_at)) {
            $ageInDays = $model->created_at->diffInDays(now());
            return $ageInDays >= min($retentionDays, $automaticDays);
        }

        return false;
    }

    /**
     * Batch anonymize multiple models
     *
     * @param array $models
     * @param string $reason
     * @return array
     */
    public static function batchAnonymize(array $models, $reason = 'GDPR compliance')
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($models as $model) {
            if (self::anonymizeModel($model, $reason)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to anonymize model ID: " . ($model->id ?? 'unknown');
            }
        }

        return $results;
    }

    /**
     * Validate anonymization configuration
     *
     * @return array
     */
    public static function validateConfiguration()
    {
        $errors = [];

        if (!config('anonymization.enabled')) {
            return $errors;
        }

        $methods = config('anonymization.methods', []);
        $validMethods = ['hash', 'mask', 'replace'];
        
        foreach ($methods as $field => $method) {
            if (!in_array($method, $validMethods)) {
                $errors[] = "Invalid anonymization method '{$method}' for field '{$field}'";
            }
        }

        $hashAlgorithm = config('anonymization.hash.algorithm', 'sha256');
        if (!in_array($hashAlgorithm, hash_algos())) {
            $errors[] = "Invalid hash algorithm '{$hashAlgorithm}'";
        }

        return $errors;
    }
}
