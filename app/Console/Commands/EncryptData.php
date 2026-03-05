<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\EncryptionHelper;
use App\Models\User;

class EncryptData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:encrypt 
                            {model? : The model to encrypt data for}
                            {--fields= : Comma-separated list of fields to encrypt}
                            {--force : Force encryption even if data is already encrypted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt sensitive data in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('encryption.enabled', true)) {
            $this->error('Encryption is disabled. Enable it in your configuration.');
            return 1;
        }

        $model = $this->argument('model') ?? 'User';
        $fields = $this->option('fields') ? explode(',', $this->option('fields')) : null;
        $force = $this->option('force');

        $this->info("Encrypting data for model: {$model}");

        try {
            $modelClass = "App\\Models\\{$model}";
            
            if (!class_exists($modelClass)) {
                $this->error("Model {$modelClass} not found.");
                return 1;
            }

            $encryptedFields = $fields ?? EncryptionHelper::getEncryptedFieldsForModel($model);
            
            if (empty($encryptedFields)) {
                $this->warn("No encrypted fields configured for model {$model}.");
                return 0;
            }

            $this->info("Encrypting fields: " . implode(', ', $encryptedFields));

            $query = $modelClass::query();
            $totalRecords = $query->count();
            $processedRecords = 0;
            $encryptedRecords = 0;

            $this->info("Found {$totalRecords} records to process.");

            $query->chunk(100, function ($records) use ($encryptedFields, $force, &$processedRecords, &$encryptedRecords) {
                foreach ($records as $record) {
                    $needsEncryption = false;
                    
                    foreach ($encryptedFields as $field) {
                        if (isset($record->$field) && (!$force && !EncryptionHelper::isEncrypted($record->$field))) {
                            $needsEncryption = true;
                            break;
                        }
                    }

                    if ($needsEncryption) {
                        $record->encryptFields();
                        $record->save();
                        $encryptedRecords++;
                    }
                    
                    $processedRecords++;
                }
            });

            $this->info("Processed {$processedRecords} records.");
            $this->info("Encrypted {$encryptedRecords} records.");

        } catch (\Exception $e) {
            $this->error("Encryption failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
