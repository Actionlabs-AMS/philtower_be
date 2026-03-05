<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait EncryptsAttributes
{
    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encrypted = [];

    /**
     * Boot the trait.
     */
    public static function bootEncryptsAttributes()
    {
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    /**
     * Encrypt the specified attributes.
     */
    public function encryptAttributes()
    {
        foreach ($this->encrypted as $attribute) {
            if ($this->isDirty($attribute) && !is_null($this->getAttribute($attribute))) {
                try {
                    $this->attributes[$attribute] = Crypt::encryptString($this->getAttribute($attribute));
                } catch (\Exception $e) {
                    Log::error('Failed to encrypt attribute', [
                        'model' => get_class($this),
                        'attribute' => $attribute,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Decrypt the specified attributes.
     */
    public function decryptAttributes()
    {
        foreach ($this->encrypted as $attribute) {
            if (!is_null($this->getAttribute($attribute))) {
                try {
                    $this->attributes[$attribute] = Crypt::decryptString($this->getAttribute($attribute));
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt attribute', [
                        'model' => get_class($this),
                        'attribute' => $attribute,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Set to null if decryption fails
                    $this->attributes[$attribute] = null;
                }
            }
        }
    }

    /**
     * Get the encrypted value of an attribute.
     */
    public function getEncryptedAttribute($attribute)
    {
        if (in_array($attribute, $this->encrypted) && !is_null($this->getAttribute($attribute))) {
            try {
                return Crypt::decryptString($this->getAttribute($attribute));
            } catch (\Exception $e) {
                Log::error('Failed to decrypt attribute for getter', [
                    'model' => get_class($this),
                    'attribute' => $attribute,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }

        return $this->getAttribute($attribute);
    }

    /**
     * Set the encrypted value of an attribute.
     */
    public function setEncryptedAttribute($attribute, $value)
    {
        if (in_array($attribute, $this->encrypted) && !is_null($value)) {
            try {
                $this->attributes[$attribute] = Crypt::encryptString($value);
            } catch (\Exception $e) {
                Log::error('Failed to encrypt attribute for setter', [
                    'model' => get_class($this),
                    'attribute' => $attribute,
                    'error' => $e->getMessage(),
                ]);
                $this->attributes[$attribute] = $value;
            }
        } else {
            $this->attributes[$attribute] = $value;
        }
    }

    /**
     * Check if an attribute is encrypted.
     */
    public function isEncrypted($attribute)
    {
        return in_array($attribute, $this->encrypted);
    }

    /**
     * Get all encrypted attributes.
     */
    public function getEncryptedAttributes()
    {
        return $this->encrypted;
    }
}
