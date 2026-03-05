<?php

namespace App\Helpers;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Helper
{
    /**
     * Get an S3 client instance with SSL verification disabled (for local/testing only).
     *
     * @return S3Client
     */
    protected static function getClient()
    {
        return new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => false,
            ],
        ]);
    }

    /**
     * Generate a unique filename and S3 folder path (year/month).
     *
     * @param string $originalName
     * @param string $subFolder
     * @return array [folder, uniqueFilename]
     */
    protected static function generateS3PathAndFilename($originalName, $subFolder = '')
    {
        $yr = date('Y');
        $mon = date('m');
        $folder = ($subFolder ? $subFolder . '/' : '') . $yr . '/' . $mon;
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $unique = $base . '_' . time() . '_' . uniqid() . ($ext ? ".{$ext}" : '');
        return [$folder, $unique];
    }

    /**
     * Upload a file from disk to S3 with unique filename and year/month foldering.
     *
     * @param string $fileTempSrc
     * @param string $fileName
     * @param string $subFolder
     * @return string File URL on success, error message on failure
     */
    public static function uploadFile($fileTempSrc, $fileName, $subFolder = '')
    {
        $bucket = env('AWS_BUCKET');
        $s3Client = self::getClient();
        list($folder, $uniqueFilename) = self::generateS3PathAndFilename($fileName, $subFolder);
        $key = $folder . '/' . $uniqueFilename;
        try {
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $fileTempSrc,
                'ContentType' => mime_content_type($fileTempSrc),
                'ACL' => 'public-read',
            ]);
            return $result->get('ObjectURL');
        } catch (S3Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Upload string content as a file to S3 with unique filename and year/month foldering.
     *
     * @param string $content
     * @param string $fileName
     * @param string $subFolder
     * @param string $contentType
     * @return string File URL on success, error message on failure
     */
    public static function uploadContent($content, $fileName, $subFolder = '', $contentType = 'text/plain')
    {
        $bucket = env('AWS_BUCKET');
        $s3Client = self::getClient();
        list($folder, $uniqueFilename) = self::generateS3PathAndFilename($fileName, $subFolder);
        $key = $folder . '/' . $uniqueFilename;
        try {
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $content,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);
            return $result->get('ObjectURL');
        } catch (S3Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get the public S3 URL for a file.
     *
     * @param string $fileName
     * @param string $subFolder
     * @return string
     */
    public static function getObjectUrl($fileName, $subFolder = '')
    {
        $bucket = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION', 'ap-southeast-1');
        $key = ($subFolder ? $subFolder . '/' : '') . $fileName;
        return "https://{$bucket}.s3.{$region}.amazonaws.com/{$key}";
    }

    /**
     * Delete a file from S3.
     *
     * @param string $fileName
     * @param string $subFolder
     * @return bool
     */
    public static function deleteFile($fileName, $subFolder = '')
    {
        $bucket = env('AWS_BUCKET');
        $s3Client = self::getClient();
        $key = ($subFolder ? $subFolder . '/' : '') . $fileName;
        try {
            $s3Client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Check if a file exists in S3.
     *
     * @param string $fileName
     * @param string $subFolder
     * @return bool
     */
    public static function fileExists($fileName, $subFolder = '')
    {
        $bucket = env('AWS_BUCKET');
        $s3Client = self::getClient();
        $key = ($subFolder ? $subFolder . '/' : '') . $fileName;
        try {
            return $s3Client->doesObjectExist($bucket, $key);
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * List all files in a folder in S3.
     *
     * @param string $subFolder
     * @return array
     */
    public static function listFiles($subFolder = '')
    {
        $bucket = env('AWS_BUCKET');
        $s3Client = self::getClient();
        $prefix = $subFolder ? $subFolder . '/' : '';
        try {
            $result = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ]);
            $files = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    $files[] = $object['Key'];
                }
            }
            return $files;
        } catch (S3Exception $e) {
            return [];
        }
    }
} 