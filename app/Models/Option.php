<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Encryptable;
use App\Helpers\EncryptionHelper;

class Option extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        'option_key',
        'option_value',
        'option_type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $table = 'options';

    /**
     * Get list of sensitive email option keys that should be encrypted
     * 
     * @return array
     */
    public static function getEncryptedOptionKeys()
    {
        return [
            'mail_password',           // SMTP password
            'mailgun_secret',          // Mailgun API secret
            'postmark_token',          // Postmark API token
            'ses_key',                 // AWS SES access key
            'ses_secret',             // AWS SES secret key
            'microsoft_tenant_id',     // Microsoft Graph tenant ID
            'microsoft_client_id',     // Microsoft Graph client ID
            'microsoft_client_secret', // Microsoft Graph client secret
        ];
    }

    /**
     * Check if an option key should be encrypted
     * 
     * @param string $key
     * @return bool
     */
    public static function shouldEncrypt($key)
    {
        return in_array($key, self::getEncryptedOptionKeys());
    }

    /**
     * Get option value with type casting and decryption
     * Note: Decryption is handled by decryptFields() on model retrieval,
     * but we check here as a safety measure in case the trait didn't run
     */
    public function getValueAttribute()
    {
        $value = $this->option_value;
        
        // Decrypt if this is a sensitive email setting and still encrypted
        // (decryptFields() should have already decrypted it, but check as safety)
        if (self::shouldEncrypt($this->option_key) && EncryptionHelper::isEncrypted($value)) {
            $value = EncryptionHelper::decrypt($value);
        }
        
        // Apply type casting
        switch ($this->option_type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set option value with type casting and encryption
     */
    public function setValueAttribute($value)
    {
        // Format value based on type
        switch ($this->option_type) {
            case 'boolean':
                $formattedValue = $value ? 'true' : 'false';
                break;
            case 'json':
                $formattedValue = is_string($value) ? $value : json_encode($value);
                break;
            default:
                $formattedValue = (string) $value;
        }
        
        // Encrypt if this is a sensitive email setting
        if (self::shouldEncrypt($this->option_key) && !EncryptionHelper::isEncrypted($formattedValue)) {
            $this->option_value = EncryptionHelper::encrypt($formattedValue);
        } else {
            $this->option_value = $formattedValue;
        }
    }

    /**
     * Get option by key (with automatic decryption for sensitive fields)
     */
    public static function get($key, $default = null)
    {
        $option = self::where('option_key', $key)->first();
        if (!$option) {
            return $default;
        }
        
        // The value attribute accessor will handle decryption automatically
        return $option->value;
    }

    /**
     * Set option by key (with automatic encryption for sensitive fields)
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        // Handle null values - delete the option
        if ($value === null) {
            self::where('option_key', $key)->delete();
            return null;
        }
        
        // Format value based on type
        $formattedValue = $value;
        switch ($type) {
            case 'boolean':
                $formattedValue = $value ? 'true' : 'false';
                break;
            case 'json':
                $formattedValue = is_string($value) ? $value : json_encode($value);
                break;
            default:
                $formattedValue = (string) $value;
        }
        
        // Encrypt if this is a sensitive email setting
        if (self::shouldEncrypt($key) && !EncryptionHelper::isEncrypted($formattedValue)) {
            $formattedValue = EncryptionHelper::encrypt($formattedValue);
        }
        
        return self::updateOrCreate(
            ['option_key' => $key],
            [
                'option_value' => $formattedValue,
                'option_type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Override getEncryptedFields to return option_value for sensitive keys
     * This is used by the Encryptable trait
     */
    public function getEncryptedFields()
    {
        // Only encrypt option_value if the option_key is in the sensitive list
        if (self::shouldEncrypt($this->option_key)) {
            return ['option_value'];
        }
        return [];
    }

    /**
     * Override encryptFields to prevent double encryption
     * We handle encryption in setValueAttribute, so we need to check if already encrypted
     */
    public function encryptFields()
    {
        if (!config('encryption.enabled', true)) {
            return;
        }

        $encryptedFields = $this->getEncryptedFields();
        
        foreach ($encryptedFields as $field) {
            // Only encrypt if not already encrypted (setValueAttribute may have already encrypted it)
            if (isset($this->attributes[$field]) && !EncryptionHelper::isEncrypted($this->attributes[$field])) {
                $this->attributes[$field] = EncryptionHelper::encrypt($this->attributes[$field]);
            }
        }
    }

    /**
     * Override decryptFields to prevent double decryption
     * We handle decryption in getValueAttribute, but the trait runs on retrieved event
     * So we'll decrypt here and the accessor will just return the already-decrypted value
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
}
