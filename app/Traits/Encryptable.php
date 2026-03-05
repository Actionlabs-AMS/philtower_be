<?php

namespace App\Traits;

use App\Helpers\EncryptionHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Encryptable
{
    /**
     * Boot the trait
     */
    protected static function bootEncryptable()
    {
        static::saving(function ($model) {
            $model->encryptFields();
        });

        static::retrieved(function ($model) {
            $model->decryptFields();
        });
    }

    /**
     * Get encrypted fields for this model
     *
     * @return array
     */
    public function getEncryptedFields()
    {
        $modelName = class_basename($this);
        return EncryptionHelper::getEncryptedFieldsForModel($modelName);
    }

    /**
     * Encrypt fields before saving
     */
    public function encryptFields()
    {
        if (!config('encryption.enabled', true)) {
            return;
        }

        $encryptedFields = $this->getEncryptedFields();
        
        foreach ($encryptedFields as $field) {
            if (isset($this->attributes[$field]) && !EncryptionHelper::isEncrypted($this->attributes[$field])) {
                $this->attributes[$field] = EncryptionHelper::encrypt($this->attributes[$field]);
            }
        }
    }

    /**
     * Decrypt fields after retrieving
     */
    public function decryptFields()
    {
        if (!config('encryption.enabled', true)) {
            return;
        }

        $encryptedFields = $this->getEncryptedFields();
        
        foreach ($encryptedFields as $field) {
            if (isset($this->attributes[$field]) && EncryptionHelper::isEncrypted($this->attributes[$field])) {
                $this->attributes[$field] = EncryptionHelper::decrypt($this->attributes[$field]);
            }
        }
    }

    /**
     * Get a decrypted attribute
     *
     * @param string $key
     * @return mixed
     */
    public function getDecryptedAttribute($key)
    {
        $encryptedFields = $this->getEncryptedFields();
        
        if (in_array($key, $encryptedFields) && isset($this->attributes[$key])) {
            return EncryptionHelper::decrypt($this->attributes[$key]);
        }
        
        return $this->getAttribute($key);
    }

    /**
     * Set an encrypted attribute
     *
     * @param string $key
     * @param mixed $value
     */
    public function setEncryptedAttribute($key, $value)
    {
        $encryptedFields = $this->getEncryptedFields();
        
        if (in_array($key, $encryptedFields)) {
            $this->attributes[$key] = EncryptionHelper::encrypt($value);
        } else {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Create accessor for encrypted fields
     *
     * @param string $field
     * @return Attribute
     */
    protected function makeEncryptedAttribute($field)
    {
        return Attribute::make(
            get: fn ($value) => EncryptionHelper::decrypt($value),
            set: fn ($value) => EncryptionHelper::encrypt($value)
        );
    }

    /**
     * Get all decrypted attributes
     *
     * @return array
     */
    public function getDecryptedAttributes()
    {
        $encryptedFields = $this->getEncryptedFields();
        $attributes = $this->getAttributes();
        
        foreach ($encryptedFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = EncryptionHelper::decrypt($attributes[$field]);
            }
        }
        
        return $attributes;
    }

    /**
     * Check if a field is encrypted
     *
     * @param string $field
     * @return bool
     */
    public function isFieldEncrypted($field)
    {
        return isset($this->attributes[$field]) && EncryptionHelper::isEncrypted($this->attributes[$field]);
    }

    /**
     * Get encryption status for all fields
     *
     * @return array
     */
    public function getEncryptionStatus()
    {
        $encryptedFields = $this->getEncryptedFields();
        $status = [];
        
        foreach ($encryptedFields as $field) {
            $status[$field] = $this->isFieldEncrypted($field);
        }
        
        return $status;
    }

    /**
     * Re-encrypt all encrypted fields (useful for key rotation)
     *
     * @return bool
     */
    public function reEncryptFields()
    {
        if (!config('encryption.enabled', true)) {
            return false;
        }

        try {
            $encryptedFields = $this->getEncryptedFields();
            
            foreach ($encryptedFields as $field) {
                if (isset($this->attributes[$field]) && EncryptionHelper::isEncrypted($this->attributes[$field])) {
                    // Decrypt and re-encrypt
                    $decrypted = EncryptionHelper::decrypt($this->attributes[$field]);
                    $this->attributes[$field] = EncryptionHelper::encrypt($decrypted);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Re-encryption failed', [
                'model' => get_class($this),
                'id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
