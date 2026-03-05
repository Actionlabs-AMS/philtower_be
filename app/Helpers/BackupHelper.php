<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Exception;

class BackupHelper
{
    /**
     * Export database to SQL file.
     *
     * @param array $tables Array of table names to export (empty = all tables)
     * @return string Path to SQL file
     * @throws Exception
     */
    public static function exportDatabase(array $tables = []): string
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $tempFile = storage_path('app/temp/backup_' . time() . '_' . uniqid() . '.sql');
        $tempDir = dirname($tempFile);
        
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            $database . (!empty($tables) ? ' ' . implode(' ', array_map('escapeshellarg', $tables)) : ''),
            escapeshellarg($tempFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !File::exists($tempFile)) {
            throw new Exception('Database export failed: ' . implode("\n", $output));
        }

        return $tempFile;
    }

    /**
     * Import database from SQL file.
     *
     * @param string $sqlFile Path to SQL file
     * @return bool
     * @throws Exception
     */
    public static function importDatabase(string $sqlFile): bool
    {
        if (!File::exists($sqlFile)) {
            throw new Exception('SQL file not found: ' . $sqlFile);
        }

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $command = sprintf(
            'mysql -h %s -P %s -u %s %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($sqlFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception('Database import failed: ' . implode("\n", $output));
        }

        return true;
    }

    /**
     * Get list of all database tables.
     *
     * @return array
     */
    public static function getTableList(): array
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = config('database.connections.mysql.database');
        $key = 'Tables_in_' . $databaseName;
        
        return array_map(function ($table) use ($key) {
            return $table->$key;
        }, $tables);
    }

    /**
     * Get database size in bytes.
     *
     * @return int
     */
    public static function getDatabaseSize(): int
    {
        $database = config('database.connections.mysql.database');
        $result = DB::selectOne(
            "SELECT SUM(data_length + index_length) AS size 
             FROM information_schema.TABLES 
             WHERE table_schema = ?",
            [$database]
        );

        return (int) ($result->size ?? 0);
    }

    /**
     * Backup storage files.
     *
     * @param array $paths Array of paths to backup (empty = all storage)
     * @return string Path to backup archive
     * @throws Exception
     */
    public static function backupStorageFiles(array $paths = []): string
    {
        $tempFile = storage_path('app/temp/files_backup_' . time() . '_' . uniqid() . '.zip');
        $tempDir = dirname($tempFile);
        
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip archive');
        }

        $storagePath = storage_path('app');
        
        if (empty($paths)) {
            // Backup all storage except temp and backups
            $excludeDirs = ['temp', 'backups', 'framework'];
            $directories = File::directories($storagePath);
            
            foreach ($directories as $directory) {
                $dirName = basename($directory);
                if (!in_array($dirName, $excludeDirs)) {
                    self::addDirectoryToZip($zip, $directory, $storagePath);
                }
            }
        } else {
            // Backup specific paths
            foreach ($paths as $path) {
                $fullPath = $storagePath . '/' . ltrim($path, '/');
                if (File::exists($fullPath)) {
                    if (File::isDirectory($fullPath)) {
                        self::addDirectoryToZip($zip, $fullPath, $storagePath);
                    } else {
                        $zip->addFile($fullPath, $path);
                    }
                }
            }
        }

        $zip->close();

        if (!File::exists($tempFile)) {
            throw new Exception('Failed to create backup archive');
        }

        return $tempFile;
    }

    /**
     * Add directory to zip recursively.
     */
    private static function addDirectoryToZip(ZipArchive $zip, string $directory, string $basePath): void
    {
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            $relativePath = str_replace($basePath . '/', '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }
    }

    /**
     * Restore storage files from backup.
     *
     * @param string $backupPath Path to backup archive
     * @param array $paths Array of paths to restore (empty = all)
     * @return bool
     * @throws Exception
     */
    public static function restoreStorageFiles(string $backupPath, array $paths = []): bool
    {
        if (!File::exists($backupPath)) {
            throw new Exception('Backup file not found: ' . $backupPath);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($backupPath) !== true) {
            throw new Exception('Failed to open backup archive');
        }

        $storagePath = storage_path('app');
        $extractTo = storage_path('app/temp/restore_' . time());

        if (!File::exists($extractTo)) {
            File::makeDirectory($extractTo, 0755, true);
        }

        // Extract all or specific files
        if (empty($paths)) {
            $zip->extractTo($extractTo);
        } else {
            foreach ($paths as $path) {
                $zip->extractTo($extractTo, $path);
            }
        }

        $zip->close();

        // Move extracted files to storage
        $extractedFiles = File::allFiles($extractTo);
        foreach ($extractedFiles as $file) {
            $relativePath = str_replace($extractTo . '/', '', $file->getPathname());
            $targetPath = $storagePath . '/' . $relativePath;
            $targetDir = dirname($targetPath);
            
            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }
            
            File::copy($file->getPathname(), $targetPath);
        }

        // Cleanup
        File::deleteDirectory($extractTo);

        return true;
    }

    /**
     * Get storage size in bytes.
     *
     * @return int
     */
    public static function getStorageSize(): int
    {
        $storagePath = storage_path('app');
        $size = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    /**
     * Compress file using gzip.
     *
     * @param string $filePath Path to file
     * @return string Path to compressed file
     * @throws Exception
     */
    public static function compressGzip(string $filePath): string
    {
        if (!File::exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $compressedPath = $filePath . '.gz';
        
        $fp_in = fopen($filePath, 'rb');
        $fp_out = gzopen($compressedPath, 'wb9');
        
        if (!$fp_in || !$fp_out) {
            throw new Exception('Failed to open files for compression');
        }
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 8192));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
        
        return $compressedPath;
    }

    /**
     * Decompress gzip file.
     *
     * @param string $filePath Path to compressed file
     * @return string Path to decompressed file
     * @throws Exception
     */
    public static function decompressGzip(string $filePath): string
    {
        if (!File::exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $decompressedPath = str_replace('.gz', '', $filePath);
        
        $fp_in = gzopen($filePath, 'rb');
        $fp_out = fopen($decompressedPath, 'wb');
        
        if (!$fp_in || !$fp_out) {
            throw new Exception('Failed to open files for decompression');
        }
        
        while (!gzeof($fp_in)) {
            fwrite($fp_out, gzread($fp_in, 8192));
        }
        
        gzclose($fp_in);
        fclose($fp_out);
        
        return $decompressedPath;
    }

    /**
     * Compress files into zip archive.
     *
     * @param array $files Array of file paths
     * @param string $outputPath Output zip file path
     * @return string Path to zip file
     * @throws Exception
     */
    public static function compressZip(array $files, string $outputPath): string
    {
        $zip = new ZipArchive();
        
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip archive');
        }
        
        foreach ($files as $file) {
            if (File::exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        
        $zip->close();
        
        return $outputPath;
    }

    /**
     * Extract zip archive.
     *
     * @param string $filePath Path to zip file
     * @param string $extractTo Directory to extract to
     * @return bool
     * @throws Exception
     */
    public static function extractZip(string $filePath, string $extractTo): bool
    {
        if (!File::exists($filePath)) {
            throw new Exception('Zip file not found: ' . $filePath);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($filePath) !== true) {
            throw new Exception('Failed to open zip archive');
        }
        
        if (!File::exists($extractTo)) {
            File::makeDirectory($extractTo, 0755, true);
        }
        
        $zip->extractTo($extractTo);
        $zip->close();
        
        return true;
    }

    /**
     * Encrypt file.
     *
     * @param string $filePath Path to file
     * @param string|null $key Encryption key (uses Laravel key if null)
     * @return string Path to encrypted file
     * @throws Exception
     */
    public static function encryptFile(string $filePath, string $key = null): string
    {
        if (!File::exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $encryptedPath = $filePath . '.encrypted';
        $content = File::get($filePath);
        $encrypted = encrypt($content);
        
        File::put($encryptedPath, $encrypted);
        
        return $encryptedPath;
    }

    /**
     * Decrypt file.
     *
     * @param string $filePath Path to encrypted file
     * @param string|null $key Decryption key (uses Laravel key if null)
     * @return string Path to decrypted file
     * @throws Exception
     */
    public static function decryptFile(string $filePath, string $key = null): string
    {
        if (!File::exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $decryptedPath = str_replace('.encrypted', '', $filePath);
        $encrypted = File::get($filePath);
        $decrypted = decrypt($encrypted);
        
        File::put($decryptedPath, $decrypted);
        
        return $decryptedPath;
    }

    /**
     * Validate backup file.
     *
     * @param string $filePath Path to backup file
     * @return array Validation result
     */
    public static function validateBackupFile(string $filePath): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'size' => 0,
            'type' => null,
        ];

        if (!File::exists($filePath)) {
            $result['errors'][] = 'File does not exist';
            return $result;
        }

        $result['size'] = File::size($filePath);
        
        if ($result['size'] === 0) {
            $result['errors'][] = 'File is empty';
            return $result;
        }

        // Check file type
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'gz') {
            $result['type'] = 'gzip';
            // Try to open gzip file
            $gz = gzopen($filePath, 'rb');
            if (!$gz) {
                $result['errors'][] = 'Invalid gzip file';
                return $result;
            }
            gzclose($gz);
        } elseif ($extension === 'zip') {
            $result['type'] = 'zip';
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                $result['errors'][] = 'Invalid zip file';
                return $result;
            }
            $zip->close();
        } elseif ($extension === 'sql') {
            $result['type'] = 'sql';
        }

        $result['valid'] = empty($result['errors']);
        return $result;
    }

    /**
     * Check if there's enough disk space.
     *
     * @param int $requiredSize Required size in bytes
     * @return bool
     */
    public static function checkDiskSpace(int $requiredSize): bool
    {
        $storagePath = storage_path('app');
        $freeSpace = disk_free_space($storagePath);
        
        return $freeSpace >= $requiredSize;
    }
}

