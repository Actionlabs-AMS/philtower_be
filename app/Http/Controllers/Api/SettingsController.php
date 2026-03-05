<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\OptionService;
use App\Services\MessageService;
use App\Services\TwoFactorAuthService;
use App\Traits\AuditTrailTrait;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Http\Requests\UpdateOptionRequest;
use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Http\Requests\UpdateEmailSettingsRequest;
use App\Http\Requests\UpdateSecuritySettingsRequest;

/**
 * @OA\Tag(
 *     name="Settings Management",
 *     description="API endpoints for application settings management"
 * )
 */
class SettingsController extends BaseController
{
    use AuditTrailTrait;
    
    protected $optionService;
    protected $twoFactorService;

    public function __construct(OptionService $optionService, MessageService $messageService, TwoFactorAuthService $twoFactorService)
    {
        $this->optionService = $optionService;
        $this->messageService = $messageService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Get all system settings
     * 
     * @OA\Get(
     *     path="/api/settings",
     *     summary="Get all system settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $settings = $this->optionService->getSystemSettings();
            
            return response([
                'success' => true,
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve settings');
        }
    }

    /**
     * Update system settings
     * 
     * @OA\Post(
     *     path="/api/settings",
     *     summary="Update system settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="two_factor", type="object"),
     *             @OA\Property(property="security", type="object"),
     *             @OA\Property(property="general", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Settings updated successfully")
     *         )
     *     )
     * )
     */
    public function update(UpdateSystemSettingsRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->optionService->updateSystemSettings($validated);

            return response([
                'success' => true,
                'message' => 'Settings updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update settings');
        }
    }

    /**
     * Get specific option by key
     * 
     * @OA\Get(
     *     path="/api/settings/{key}",
     *     summary="Get specific option by key",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Option key",
     *         @OA\Schema(type="string", example="two_factor_enabled")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option retrieved successfully"
     *     )
     * )
     */
    public function show($key)
    {
        try {
            $value = $this->optionService->getOption($key);
            
            return response([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve option');
        }
    }

    /**
     * Update specific option by key
     * 
     * @OA\Put(
     *     path="/api/settings/{key}",
     *     summary="Update specific option by key",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Option key",
     *         @OA\Schema(type="string", example="two_factor_enabled")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="true"),
     *             @OA\Property(property="type", type="string", example="boolean"),
     *             @OA\Property(property="description", type="string", example="Enable 2FA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option updated successfully"
     *     )
     * )
     */
    public function updateOption(UpdateOptionRequest $request, $key)
    {
        try {
            $validated = $request->validated();

            $option = $this->optionService->setOption(
                $key,
                $validated['value'],
                $validated['type'] ?? 'string',
                $validated['description'] ?? null
            );

            return response([
                'success' => true,
                'message' => 'Option updated successfully',
                'data' => $option
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update option');
        }
    }

    /**
     * Get 2FA settings for current user
     * 
     * @OA\Get(
     *     path="/api/settings/two-factor/status",
     *     summary="Get 2FA status for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA status retrieved successfully"
     *     )
     * )
     */
    public function getTwoFactorStatus()
    {
        try {
            $user = auth()->user();
            $status = $this->twoFactorService->getStatus($user->id);
            
            return response([
                'success' => true,
                'data' => $status
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve 2FA status');
        }
    }

    /**
     * Enable 2FA for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/enable",
     *     summary="Enable 2FA for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA enabled successfully"
     *     )
     * )
     */
    public function enableTwoFactor()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->enable($user->id);
            
            return response([
                'success' => true,
                'message' => '2FA enabled successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to enable 2FA');
        }
    }

    /**
     * Disable 2FA for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/disable",
     *     summary="Disable 2FA for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA disabled successfully"
     *     )
     * )
     */
    public function disableTwoFactor()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->disable($user->id);
            
            return response([
                'success' => true,
                'message' => '2FA disabled successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to disable 2FA');
        }
    }

    /**
     * Generate backup codes for current user
     * 
     * @OA\Post(
     *     path="/api/settings/two-factor/backup-codes",
     *     summary="Generate backup codes for current user",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Backup codes generated successfully"
     *     )
     * )
     */
    public function generateBackupCodes()
    {
        try {
            $user = auth()->user();
            $result = $this->twoFactorService->generateBackupCodes($user->id);
            
            return response([
                'success' => true,
                'message' => 'Backup codes generated successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to generate backup codes');
        }
    }

    /**
     * Get general settings only (excludes security and 2FA)
     * 
     * @OA\Get(
     *     path="/api/system-settings/settings/general",
     *     summary="Get general settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="General settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getGeneralSettings()
    {
        try {
            $settings = $this->optionService->getGeneralSettings();
            
            return response([
                'success' => true,
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve general settings');
        }
    }

    /**
     * Update general settings only (excludes security and 2FA)
     * 
     * @OA\Post(
     *     path="/api/system-settings/settings/general",
     *     summary="Update general settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="site", type="object"),
     *             @OA\Property(property="date_time", type="object"),
     *             @OA\Property(property="email", type="object"),
     *             @OA\Property(property="ui", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="General settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="General settings updated successfully")
     *         )
     *     )
     * )
     */
    public function updateGeneralSettings(UpdateGeneralSettingsRequest $request)
    {
        try {
            $validated = $request->validated();

            // Handle logo uploads - Laravel expects nested array notation for file uploads
            $logoFields = ['auth_logo', 'sidenav_logo'];
            if (!isset($validated['site'])) {
                $validated['site'] = [];
            }
            
            foreach ($logoFields as $field) {
                // Check for file upload with nested array notation: site[auth_logo]
                $fileKey = "site.{$field}";
                $removeKey = "site.remove_{$field}";
                
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/logos', $filename);
                    $validated['site'][$field] = 'storage/logos/' . $filename;
                } elseif ($request->has($removeKey)) {
                    // Remove flag is set - delete the logo
                    $validated['site'][$field] = null;
                } elseif ($request->has($fileKey) && $request->input($fileKey) === '') {
                    // Empty string sent - delete the logo
                    $validated['site'][$field] = null;
                } elseif (isset($validated['site'][$field]) && $validated['site'][$field] === null) {
                    // Handle null value from JSON request (already null)
                    $validated['site'][$field] = null;
                } elseif (isset($validated['site'][$field]) && $validated['site'][$field] === '') {
                    // Empty string in validated data - convert to null
                    $validated['site'][$field] = null;
                }
            }

            $this->optionService->updateGeneralSettings($validated);

            // Log the action
            $this->logUpdate('OPTIONS', [], $validated);

            return response([
                'success' => true,
                'message' => 'General settings updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update general settings');
        }
    }

    /**
     * Get email settings
     * 
     * @OA\Get(
     *     path="/api/system-settings/settings/email",
     *     summary="Get email settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Email settings retrieved successfully"
     *     )
     * )
     */
    public function getEmailSettings()
    {
        try {
            $settings = $this->optionService->getEmailSettings();
            
            return response([
                'success' => true,
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve email settings');
        }
    }

    /**
     * Update email settings
     * 
     * @OA\Post(
     *     path="/api/system-settings/settings/email",
     *     summary="Update email settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="mailer", type="string", example="smtp"),
     *             @OA\Property(property="mail_from_name", type="string"),
     *             @OA\Property(property="mail_from_address", type="string"),
     *             @OA\Property(property="smtp", type="object"),
     *             @OA\Property(property="mailgun", type="object"),
     *             @OA\Property(property="postmark", type="object"),
     *             @OA\Property(property="ses", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email settings updated successfully"
     *     )
     * )
     */
    public function updateEmailSettings(UpdateEmailSettingsRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->optionService->updateEmailSettings($validated);
            
            // Log the action
            $this->logUpdate('EMAIL_SETTINGS', [], $validated);

            return response([
                'success' => true,
                'message' => 'Email settings updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update email settings');
        }
    }

    /**
     * Get security settings (2FA and Session settings)
     * 
     * @OA\Get(
     *     path="/api/system-settings/settings/security",
     *     summary="Get security settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Security settings retrieved successfully"
     *     )
     * )
     */
    public function getSecuritySettings()
    {
        try {
            $settings = $this->optionService->getSecuritySettings();
            
            return response([
                'success' => true,
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to retrieve security settings');
        }
    }

    /**
     * Update security settings (2FA and Session settings)
     * 
     * @OA\Post(
     *     path="/api/system-settings/settings/security",
     *     summary="Update security settings",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="two_factor", type="object"),
     *             @OA\Property(property="session", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Security settings updated successfully"
     *     )
     * )
     */
    public function updateSecuritySettings(UpdateSecuritySettingsRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->optionService->updateSecuritySettings($validated);

            // Log the action
            $this->logUpdate('OPTIONS', [], $validated);

            return response([
                'success' => true,
                'message' => 'Security settings updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to update security settings');
        }
    }

    /**
     * Initialize default options (admin only)
     * 
     * @OA\Post(
     *     path="/api/settings/initialize",
     *     summary="Initialize default options",
     *     tags={"Settings Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Default options initialized successfully"
     *     )
     * )
     */
    public function initialize()
    {
        try {
            $this->optionService->initializeDefaultOptions();
            
            return response([
                'success' => true,
                'message' => 'Default options initialized successfully'
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to initialize default options');
        }
    }
}
