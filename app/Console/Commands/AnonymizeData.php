<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\AnonymizationHelper;
use App\Models\User;

class AnonymizeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:anonymize 
                            {model? : The model to anonymize data for}
                            {--fields= : Comma-separated list of fields to anonymize}
                            {--reason= : Reason for anonymization}
                            {--force : Force anonymization even if data is already anonymized}
                            {--older-than= : Anonymize records older than X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymize sensitive data for GDPR compliance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('anonymization.enabled', true)) {
            $this->error('Anonymization is disabled. Enable it in your configuration.');
            return 1;
        }

        $model = $this->argument('model') ?? 'User';
        $fields = $this->option('fields') ? explode(',', $this->option('fields')) : null;
        $reason = $this->option('reason') ?? 'GDPR compliance';
        $force = $this->option('force');
        $olderThan = $this->option('older-than');

        $this->info("Anonymizing data for model: {$model}");
        $this->info("Reason: {$reason}");

        try {
            $modelClass = "App\\Models\\{$model}";
            
            if (!class_exists($modelClass)) {
                $this->error("Model {$modelClass} not found.");
                return 1;
            }

            $anonymizationMethods = $fields ? 
                array_combine($fields, array_fill(0, count($fields), 'replace')) : 
                AnonymizationHelper::getAnonymizationMethodsForModel($model);
            
            if (empty($anonymizationMethods)) {
                $this->warn("No anonymization methods configured for model {$model}.");
                return 0;
            }

            $this->info("Anonymizing fields: " . implode(', ', array_keys($anonymizationMethods)));

            $query = $modelClass::query();
            
            if ($olderThan) {
                $query->where('created_at', '<', now()->subDays($olderThan));
            }

            $totalRecords = $query->count();
            $processedRecords = 0;
            $anonymizedRecords = 0;

            $this->info("Found {$totalRecords} records to process.");

            if ($this->confirm("Do you want to proceed with anonymization?")) {
                $query->chunk(100, function ($records) use ($anonymizationMethods, $reason, $force, &$processedRecords, &$anonymizedRecords) {
                    foreach ($records as $record) {
                        $needsAnonymization = false;
                        
                        foreach (array_keys($anonymizationMethods) as $field) {
                            if (isset($record->$field) && (!$force && !$record->isFieldAnonymized($field))) {
                                $needsAnonymization = true;
                                break;
                            }
                        }

                        if ($needsAnonymization) {
                            if ($record->anonymizeData($reason)) {
                                $anonymizedRecords++;
                            }
                        }
                        
                        $processedRecords++;
                    }
                });

                $this->info("Processed {$processedRecords} records.");
                $this->info("Anonymized {$anonymizedRecords} records.");
            } else {
                $this->info("Anonymization cancelled.");
            }

        } catch (\Exception $e) {
            $this->error("Anonymization failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
