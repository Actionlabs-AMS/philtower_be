<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use App\Services\OptionService;
use App\Models\User;
use App\Http\Resources\AuthResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Two-Factor Authentication",
 *     description="API Endpoints for two-factor authentication management"
 * )
 */
class TwoFactorAuthController extends Controller
{
    protected $twoFactorService;
    protected $optionService;

    public function __construct(TwoFactorAuthService $twoFactorService, OptionService $optionService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->optionService = $optionService;
    }

    /**
     * Send 2FA code via email
     * 
     * @OA\Post(
     *     path="/api/2fa/send-code",
     *     summary="Send 2FA verification code",
     *     tags={"Two-Factor Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User email address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="2FA code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="2FA code sent to your email address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Too many code requests. Please try again in 60 seconds.")
     *         )
     *     )
     * )
     */
    public function sendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,user_email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rate limiting for 2FA code requests
        $key = '2fa_code:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many code requests. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 300); // 5 minutes

        $user = User::where('user_email', $request->email)->first();
        $result = $this->twoFactorService->sendEmailCode($user);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Verify 2FA code
     * 
     * @OA\Post(
     *     path="/api/2fa/verify-code",
     *     summary="Verify 2FA code",
     *     tags={"Two-Factor Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "code"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User email address"),
     *             @OA\Property(property="code", type="string", example="123456", description="6-digit verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="2FA code verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Two-factor authentication verified successfully"),
     *             @OA\Property(property="token", type="string", example="1|abc123def456...", description="Bearer token for API authentication"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="backup_code_used", type="boolean", example=false, description="Whether a backup code was used"),
     *             @OA\Property(property="remaining_backup_codes", type="integer", example=9, description="Number of remaining backup codes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid or expired verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many verification attempts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Too many verification attempts. Please try again in 60 seconds.")
     *         )
     *     )
     * )
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,user_email',
            'code' => 'required|string|size:6',
            'remember_me' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rate limiting for 2FA verification attempts
        $key = '2fa_verify:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many verification attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $user = User::where('user_email', $request->email)->first();
        $result = $this->twoFactorService->verifyCode($user, $request->code);

        if ($result['success']) {
            RateLimiter::clear($key);
            
            // Clear any existing tokens for this user
            $user->tokens()->delete();
            
            // Get session timeout settings for token expiration
            $sessionEnabled = $this->optionService->getOption('session_enabled', true);
            $sessionTimeoutMinutes = (int) $this->optionService->getOption('session_timeout', 30);
            
            // Check if remember_me was passed (from 2FA flow)
            $rememberMe = $request->boolean('remember_me', false);
            $tokenName = $rememberMe ? 'admin-remember' : 'admin';
            
            // Set expiration based on remember me preference and session timeout settings
            if ($sessionEnabled) {
                // Session timeout is enabled - use configured timeout
                if ($rememberMe) {
                    // Remember me: Use 10x the session timeout (or minimum 7 days, maximum 30 days)
                    $rememberMeTimeout = max(7 * 24 * 60, min(30 * 24 * 60, $sessionTimeoutMinutes * 10));
                    $expiresAt = now()->addMinutes($rememberMeTimeout);
                } else {
                    // Standard session: Use configured session timeout
                    $expiresAt = now()->addMinutes($sessionTimeoutMinutes);
                }
            } else {
                // Session timeout is disabled - set longer expiration
                if ($rememberMe) {
                    // Remember me: 30 days when session timeout is disabled
                    $expiresAt = now()->addDays(30);
                } else {
                    // Standard: 24 hours when session timeout is disabled
                    $expiresAt = now()->addHours(24);
                }
            }
            
            // Generate new token for successful 2FA verification with expiration
            $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
            
            // Create full user resource with all necessary data
            $userResource = new AuthResource($user);
            
            Log::info('2FA verification successful', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication verified successfully',
                'token' => $token,
                'user' => $userResource,
                'backup_code_used' => $result['backup_code_used'] ?? false,
                'remaining_backup_codes' => $result['remaining_backup_codes'] ?? null,
            ], 200);
        }

        RateLimiter::hit($key, 300); // 5 minutes

        return response()->json($result, 400);
    }

    /**
     * Enable 2FA for user
     * 
     * @OA\Post(
     *     path="/api/2fa/enable",
     *     summary="Enable 2FA for user",
     *     tags={"Two-Factor Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA enabled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Two-factor authentication enabled successfully"),
     *             @OA\Property(property="backup_codes", type="array", @OA\Items(type="string"), example={"ABC123", "DEF456", "GHI789"}, description="Backup codes for account recovery")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to enable 2FA",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to enable two-factor authentication")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $result = $this->twoFactorService->enableTwoFactor($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'backup_codes' => $result['backup_codes'],
            ], 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Disable 2FA for user
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $result = $this->twoFactorService->disableTwoFactor($user);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get 2FA status
     */
    public function status(Request $request): JsonResponse
    {
        \Log::info('2FA Status API called', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user = $request->user();
        
        if (!$user) {
            \Log::warning('2FA Status API: User not authenticated');
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $status = $this->twoFactorService->getTwoFactorStatus($user);
        
        // Add system-wide 2FA settings
        $status['system_enabled'] = $this->twoFactorService->isTwoFactorEnabledSystemWide();
        $status['system_required'] = $this->twoFactorService->isTwoFactorRequiredSystemWide();
        $status['can_disable'] = !$this->twoFactorService->isTwoFactorRequiredSystemWide();

        \Log::info('2FA Status API response', [
            'user_id' => $user->id,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'two_factor' => $status,
        ], 200);
    }

    /**
     * Generate new backup codes
     */
    public function generateBackupCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $result = $this->twoFactorService->generateNewBackupCodes($user);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Check if 2FA is required for login
     */
    public function isRequired(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,user_email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('user_email', $request->email)->first();
        $isRequired = $this->twoFactorService->isTwoFactorRequiredForUser($user);

        return response()->json([
            'success' => true,
            'two_factor_required' => $isRequired,
        ], 200);
    }

    /**
     * Anonymize email for logging
     */
    private function anonymizeEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            $anonymizedUsername = str_repeat('*', strlen($username));
        } else {
            $anonymizedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $anonymizedUsername . '@' . $domain;
    }
}
