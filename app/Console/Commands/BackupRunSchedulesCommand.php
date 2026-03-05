<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class BackupRunSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run due backup schedules';

    protected $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for due backup schedules...');

        try {
            $this->backupService->runDueSchedules();
            $this->info('Backup schedules checked successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to run backup schedules: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

