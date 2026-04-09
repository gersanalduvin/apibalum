<?php

namespace App\Http\Controllers;

use App\Services\S3Service;
use App\Services\SesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AwsController extends Controller
{
    protected S3Service $s3Service;
    protected SesService $sesService;

    public function __construct(S3Service $s3Service, SesService $sesService)
    {
        $this->s3Service = $s3Service;
        $this->sesService = $sesService;
    }

    /**
     * Upload file to S3
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // Max 10MB
            'directory' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $directory = $request->input('directory', 'uploads');
        $result = $this->s3Service->uploadFile($request->file('file'), $directory);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Delete file from S3
     */
    public function deleteFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $success = $this->s3Service->deleteFile($request->input('key'));

        return response()->json([
            'success' => $success,
            'message' => $success ? 'File deleted successfully' : 'Failed to delete file',
        ], $success ? 200 : 500);
    }

    /**
     * Get presigned URL for a file
     */
    public function getPresignedUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'expires_in_minutes' => 'sometimes|integer|min:1|max:1440', // Max 24 hours
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $key = $request->input('key');
        $expiresIn = $request->input('expires_in_minutes', 60);

        if (!$this->s3Service->fileExists($key)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        $url = $this->s3Service->getPresignedUrl($key, $expiresIn);

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_in_minutes' => $expiresIn,
        ]);
    }

    /**
     * Send email via SES
     */
    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'text' => 'sometimes|string',
            'html' => 'sometimes|string',
            'from' => 'sometimes|email',
            'cc' => 'sometimes|array',
            'cc.*' => 'email',
            'bcc' => 'sometimes|array',
            'bcc.*' => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sesService->sendEmail($request->all());

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Send templated email via SES
     */
    public function sendTemplatedEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'template' => 'required|string',
            'template_data' => 'sometimes|array',
            'from' => 'sometimes|email',
            'cc' => 'sometimes|array',
            'cc.*' => 'email',
            'bcc' => 'sometimes|array',
            'bcc.*' => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sesService->sendTemplatedEmail($request->all());

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sesService->verifyEmailAddress($request->input('email'));

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get SES sending statistics
     */
    public function getSendingStatistics(): JsonResponse
    {
        $result = $this->sesService->getSendingStatistics();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get verified email addresses
     */
    public function getVerifiedEmails(): JsonResponse
    {
        $result = $this->sesService->getVerifiedEmailAddresses();

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}