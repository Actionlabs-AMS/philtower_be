<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;

class EncryptionHelper
{
    /**
     * Encrypt a value
     *
     * @param mixed $value
     * @return string|null
     */
    public static function encrypt($value)
    {
        if (empty($value) || !config('encryption.enabled', true)) {
            return $value;
        }

        try {
            $encrypted = Crypt::encryptString($value);
            return config('encryption.prefix', 'encrypted:') . $encrypted;
        } catch (Exception $e) {
            Log::error('Encryption failed', [
                'error' => $e->getMessage(),
                'value_length' => is_string($value) ? strlen($value) : 'non-string',
            ]);
            return $value; // Return original value if encryption fails
        }
    }

    /**
     * Decrypt a value
     *
     * @param string $value
     * @return mixed
     */
    public static function decrypt($value)
    {
        if (empty($value) || !config('encryption.enabled', true)) {
            return $value;
        }

        $prefix = config('encryption.prefix', 'encrypted:');
        
        if (!str_starts_with($value, $prefix)) {
            return $value; // Not encrypted
        }

        try {
            $encryptedValue = substr($value, strlen($prefix));
            return Crypt::decryptString($encryptedValue);
        } catch (Exception $e) {
            Log::error('Decryption failed', [
                'error' => $e->getMessage(),
                'value_length' => strlen($value),
            ]);
            return $value; // Return original value if decryption fails
        }
    }

    /**
     * Check if a value is encrypted
     *
     * @param string $value
     * @return bool
     */
    public static function isEncrypted($value)
    {
        if (empty($value)) {
            return false;
        }

        $prefix = config('encryption.prefix', 'encrypted:');
        return str_starts_with($value, $prefix);
    }

    /**
     * Encrypt multiple values
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public static function encryptFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !self::isEncrypted($data[$field])) {
                $data[$field] = self::encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypt multiple values
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public static function decryptFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && self::isEncrypted($data[$field])) {
                $data[$field] = self::decrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Get encrypted fields for a model
     *
     * @param string $modelName
     * @return array
     */
    public static function getEncryptedFieldsForModel($modelName)
    {
        $modelEncryption = config('encryption.model_encryption', []);
        
        if (isset($modelEncryption[$modelName])) {
            return $modelEncryption[$modelName];
        }

        return config('encryption.default_encrypted_fields', []);
    }

    /**
     * Batch encrypt data
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public static function batchEncrypt(array $data, array $fields)
    {
        $batchSize = config('encryption.performance.batch_size', 100);
        $chunks = array_chunk($data, $batchSize);

        $results = [];
        foreach ($chunks as $chunk) {
            $results = array_merge($results, self::encryptFields($chunk, $fields));
        }

        return $results;
    }

    /**
     * Generate encryption key
     *
     * @return string
     */
    public static function generateKey()
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Validate encryption configuration
     *
     * @return array
     */
    public static function validateConfiguration()
    {
        $errors = [];

        if (!config('encryption.enabled')) {
            return $errors;
        }

        $key = config('encryption.key');
        if (empty($key) || $key === config('app.key')) {
            $errors[] = 'Encryption key should be different from app key';
        }

        $algorithm = config('encryption.algorithm');
        if (!in_array($algorithm, ['AES-256-CBC', 'AES-128-CBC'])) {
            $errors[] = 'Invalid encryption algorithm';
        }

        return $errors;
    }
}
