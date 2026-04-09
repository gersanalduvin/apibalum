<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Service
{
    protected S3Client $s3Client;
    protected string $bucket;

    public function __construct(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
        $this->bucket = config('aws.s3.bucket');
    }

    /**
     * Upload raw content (bytes) to S3 at a specific key
     */
    public function uploadContent(string $content, string $key, string $contentType = 'application/octet-stream', string $acl = 'private'): array
    {
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $content,
                'ContentType' => $contentType,
                'ACL' => $acl,
            ]);

            return [
                'success' => true,
                'key' => $key,
                'bucket' => $this->bucket,
                'url' => $result['ObjectURL'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload a file to S3
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads'): array
    {
        $fileName = $this->generateFileName($file);
        $filePath = $directory . '/' . $fileName;

        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $filePath,
                'Body' => fopen($file->getRealPath(), 'r'),
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read', // Opcional: hacer el archivo público
            ]);

            return [
                'success' => true,
                'url' => $result['ObjectURL'],
                'key' => $filePath,
                'bucket' => $this->bucket,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a file from S3
     */
    public function deleteFile(string $key): bool
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a presigned URL for a file
     */
    public function getPresignedUrl(string $key, int $expiresInMinutes = 60): string
    {
        $command = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        $request = $this->s3Client->createPresignedRequest(
            $command,
            '+' . $expiresInMinutes . ' minutes'
        );

        return (string) $request->getUri();
    }

    /**
     * Check if a file exists in S3
     */
    public function fileExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a unique file name
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$name}_{$timestamp}_{$random}.{$extension}";
    }
}