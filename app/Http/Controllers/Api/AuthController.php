<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SignupRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\Enable2FASetupRequest;
use App\Helpers\PasswordHelper;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use App\Services\MicrosoftGraphService;
use App\Services\OptionService;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Mail\ForgotPasswordEmail;
use Illuminate\Support\Facades\Auth; 
use App\Http\Resources\AuthResource;
use App\Traits\AuditTrailTrait;
use Illuminate\Support\Facades\RateLimiter;

use Hash;

/**
 * @OA\Info(
 *     title="BaseCode API",
 *     version="1.0.0",
 *     description="A comprehensive Laravel API with authentication, role management, and security features",
 *     @OA\Contact(
 *         email="admin@basecode.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="BaseCode API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1, description="User ID"),
 *     @OA\Property(property="user_login", type="string", example="johndoe", description="Username"),
 *     @OA\Property(property="user_email", type="string", format="email", example="john@example.com", description="User email"),
 *     @OA\Property(property="user_status", type="integer", example=1, description="User status (0=inactive, 1=active, 2=suspended)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp"),
 *     @OA\Property(property="user_details", type="object", description="User metadata"),
 *     @OA\Property(property="user_role", type="string", example="admin", description="User role")
 * )
 */
class AuthController extends Controller
{
	use AuditTrailTrait;

	protected $twoFactorService;
	protected $optionService;

	public function __construct(TwoFactorAuthService $twoFactorService, OptionService $optionService)
	{
		$this->twoFactorService = $twoFactorService;
		$this->optionService = $optionService;
	}
	/**
	 * Create a new user.
	 * 
	 * @OA\Post(
	 *     path="/api/signup",
	 *     summary="Register a new user",
	 *     tags={"Authentication"},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"username", "email", "password", "password_confirmation"},
	 *             @OA\Property(property="username", type="string", example="johndoe", description="Unique username"),
	 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Valid email address"),
	 *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Strong password (min 8 chars, mixed case, numbers, symbols)"),
	 *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!", description="Password confirmation")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="User registered successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Aww yeah, you have successfuly registered. Verification email has been sent to your registered email.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error or weak password",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="errors", type="array", @OA\Items(type="string")),
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="status_code", type="integer", example=422)
	 *         )
	 *     )
	 * )
	 */
	public function signup(SignupRequest $request) 
	{
		$data = $request->validated();

		// Check password strength
		$passwordStrength = PasswordHelper::checkPasswordStrength($data['password']);
		if (!$passwordStrength['is_strong']) {
			return response([
				'errors' => $passwordStrength['feedback'],
				'status' => false,
				'status_code' => 422,
			], 422);
		}

		$salt = PasswordHelper::generateSalt();
		$password = PasswordHelper::generatePassword($salt, $request->password);
		$activation_key = PasswordHelper::generateResetToken();

		$user = User::create([
			'user_login' => $data['username'],
			'user_email' => $data['email'],
			'user_salt' => $salt,
			'user_pass' => $password,
			'user_activation_key' => $activation_key,
		]);

		$user_key = $user->user_activation_key;
		$verify_url = env('ADMIN_APP_URL')."/login/activate/".$user_key;

		$message = '';
		try {
			// Send verification email using Microsoft Graph
			$emailSent = MicrosoftGraphService::sendUserRegistrationEmail($user, $verify_url);
			if ($emailSent) {
				$message = 'Aww yeah, you have successfuly registered. Verification email has been sent to your registered email.';
			} else {
				$message = 'Registration successful, but there was an issue sending the verification email. Please contact support.';
			}
		} catch (\Exception $e) {
			// Fallback to Laravel Mail if Microsoft Graph fails
			$options = array('verify_url' => $verify_url);
			if(Mail::to($user->user_email)->send(new VerifyEmail($user, $options))) {
				$message = 'Aww yeah, you have successfuly registered. Verification email has been sent to your registered email.';
			} else {
				$message = 'Registration successful, but there was an issue sending the verification email. Please contact support.';
			}
		}

		// Log the user registration
		$this->logAction(
			'AUTHENTICATION',
			'REGISTER',
			[
				'username' => $data['username'],
				'email' => $this->anonymizeEmail($data['email']),
				'activation_key' => $activation_key,
				'email_sent' => !empty($message),
				'password_strength' => $passwordStrength['strength']
			],
			(string) $user->id
		);

		return response(compact('message'));
		
	}

	/**
	 * Activate registered user.
	 * 
	 * @OA\Get(
	 *     path="/api/activate/{activation_key}",
	 *     summary="Activate user account",
	 *     tags={"Authentication"},
	 *     @OA\Parameter(
	 *         name="activation_key",
	 *         in="path",
	 *         required=true,
	 *         description="User activation key received via email",
	 *         @OA\Schema(type="string", example="abc123def456")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Account activated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Your registered email address has been validated, you can login you account and enjoy.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Invalid or expired activation key",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Invalid or expired activation key")
	 *         )
	 *     )
	 * )
	 */
	public function activateUser(Request $request) 
	{
		$message = '';

		$user = User::where('user_activation_key', $request->activation_key)
		->where('user_status', 0)->first();
		
		if($user) {
			$user->update(['user_status' => 1]);
			$message = 'Your registered email address has been validated, you can login you account and enjoy.';

			// Log activation
			$this->logAction(
				'AUTHENTICATION',
				'ACTIVATE',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($user->user_email)
				],
				(string) $user->id
			);
		}

		return response(compact('message'));

	}

	/**
	 * Generate a temporary password.
	 * 
	 * @OA\Post(
	 *     path="/api/forgot-password",
	 *     summary="Request password reset",
	 *     tags={"Authentication"},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"email"},
	 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Registered email address")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Password reset email sent successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Your temporary password has been sent to your registered email.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error or invalid email",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="User does not have a valid email address for password reset."),
	 *             @OA\Property(property="error", type="string", example="invalid_email")
	 *         )
	 *     )
	 * )
	 */
	public function genTempPassword(ForgotPasswordRequest $request) 
	{
		$data = $request->validated();
		$message = '';

		$user = User::where('user_email', $data['email'])->first();

		if($user) {
			// Check if user has a valid email address
			if(empty($user->user_email) || !filter_var($user->user_email, FILTER_VALIDATE_EMAIL)) {
				return response()->json([
					'message' => 'User does not have a valid email address for password reset.',
					'error' => 'invalid_email'
				], 422);
			}

			$salt = $user->user_salt;
			$new_password = PasswordHelper::generateTemporaryPassword();
			$password = PasswordHelper::generatePassword($salt, $new_password);

			$user->update(['user_pass' => $password]);

			$login_url = env('ADMIN_APP_URL')."/login";
			
			try {
				// Send password reset email using Microsoft Graph
				$emailSent = MicrosoftGraphService::sendPasswordResetEmail($user, $login_url);
				if ($emailSent) {
					$message = 'Your temporary password has been sent to your registered email.';
				} else {
					$message = 'Password reset successful, but there was an issue sending the email. Please contact support.';
				}
			} catch (\Exception $e) {
				// Fallback to Laravel Mail if Microsoft Graph fails
				$options = array(
					'login_url' => $login_url,
					'new_password' => $new_password
				);
				if(Mail::to($user->user_email)->send(new ForgotPasswordEmail($user, $options))) {
					$message = 'Your temporary password has been sent to your registered email.';
				} else {
					$message = 'Password reset successful, but there was an issue sending the email. Please contact support.';
				}
			}

			// Log password reset
			$this->logAction(
				'AUTHENTICATION',
				'PASSWORD_RESET',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($user->user_email),
					'temporary_password_generated' => true
				],
				(string) $user->id
			);
		}

		return response(compact('message'));
	}

	/**
	 * Login a user.
	 * 
	 * @OA\Post(
	 *     path="/api/login",
	 *     summary="Authenticate user",
	 *     tags={"Authentication"},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"email", "password"},
	 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User email address"),
	 *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="User password")
	 *         )
	 *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful or 2FA required",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="user", ref="#/components/schemas/User"),
     *                     @OA\Property(property="token", type="string", example="1|abc123def456...", description="Bearer token for API authentication")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Two-factor authentication required. Please check your email for the verification code."),
     *                     @OA\Property(property="two_factor_required", type="boolean", example=true),
     *                     @OA\Property(property="status", type="boolean", example=true),
     *                     @OA\Property(property="status_code", type="integer", example=200)
     *                 )
     *             }
     *         )
     *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Invalid credentials",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="errors", type="array", @OA\Items(type="string"), example={"Invalid email or password."}),
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="status_code", type="integer", example=422)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=429,
	 *         description="Too many login attempts",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="errors", type="array", @OA\Items(type="string"), example={"Too many login attempts. Please try again later."}),
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="status_code", type="integer", example=429)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=500,
	 *         description="Failed to send 2FA code",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="errors", type="array", @OA\Items(type="string"), example={"Failed to send verification code. Please try again."}),
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="status_code", type="integer", example=500)
	 *         )
	 *     )
	 * )
	 */
	public function login(LoginRequest $request) 
	{
		$credentials = $request->validated();
		$ip = $request->ip();

		// Get max login attempts and lockout duration from database settings
		$maxLoginAttempts = (int) $this->optionService->getOption('max_login_attempts', 5);
		$lockoutDurationMinutes = (int) $this->optionService->getOption('lockout_duration', 15);
		$lockoutDurationSeconds = $lockoutDurationMinutes * 60; // Convert minutes to seconds

		// Check rate limiting using database settings
		if (RateLimiter::tooManyAttempts('login:' . $ip, $maxLoginAttempts)) {
			// Try to find user to include user_id
			$userForBlock = User::where('user_email', '=', $credentials['email'])->first();
			
			// Get remaining lockout time
			$secondsRemaining = RateLimiter::availableIn('login:' . $ip);
			$minutesRemaining = ceil($secondsRemaining / 60);
			
			$this->logAction(
				'AUTHENTICATION',
				'LOGIN_BLOCKED',
				[
					'user_id' => $userForBlock ? $userForBlock->id : null,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => 'Too many attempts',
					'ip_address' => $ip,
					'max_attempts' => $maxLoginAttempts,
					'lockout_duration_minutes' => $lockoutDurationMinutes
				],
				$userForBlock ? (string) $userForBlock->id : null
			);

			return response([
				'errors' => [
					"Too many login attempts. Please try again in {$minutesRemaining} minute(s)."
				],
				'status' => false,
				'status_code' => 429,
				'lockout_remaining_minutes' => $minutesRemaining,
			], 429);
		}

		// First check if user exists (without status check)
		$user = User::with('role')->where('user_email', '=', $credentials['email'])->first();
		
		// Check if user exists
		if (!$user) {
			// Increment rate limiter
			RateLimiter::hit('login:' . $ip, $lockoutDurationSeconds);
			
			// Log failed login attempt
			$this->logAction(
				'AUTHENTICATION',
				'LOGIN_FAILED',
				[
					'user_id' => null,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => 'User not found',
					'ip_address' => $ip,
					'user_agent' => $request->userAgent()
				],
				null
			);
			
			return response([
				'errors' => ['Invalid email or password.'],
				'status' => false,
				'status_code' => 422,
			], 422);
		}
		
		// Check if user is active (status = 1)
		// Status values: 0 = Inactive, 1 = Active, 2 = Suspended
		if ($user->user_status != 1) {
			// Increment rate limiter
			RateLimiter::hit('login:' . $ip, $lockoutDurationSeconds);

			// Determine the specific reason based on status
			$reason = 'User account is inactive';
			$errorMessage = 'Your account is inactive. Please contact administrator.';
			
			if ($user->user_status === 0) {
				$reason = 'User account is inactive';
				$errorMessage = 'Your account is inactive. Please contact administrator.';
			} elseif ($user->user_status === 2) {
				$reason = 'User account is suspended';
				$errorMessage = 'Your account has been suspended. Please contact administrator.';
			}

			// Log failed login attempt
			$this->logAction(
				'AUTHENTICATION',
				'LOGIN_FAILED',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => $reason,
					'user_status' => $user->user_status,
					'ip_address' => $ip,
					'user_agent' => $request->userAgent()
				],
				(string) $user->id
			);

			return response([
				'errors' => [$errorMessage],
				'status' => false,
				'status_code' => 403,
			], 403);
		}

		// Check if user has a role and if the role is active and not deleted
		$role = $user->role;
		if (!$user->role_id || !$role || !$role->active || $role->trashed()) {
			// Increment rate limiter
			RateLimiter::hit('login:' . $ip, $lockoutDurationSeconds);

			// Determine the specific reason for login failure
			$reason = 'User role is inactive or missing';
			if ($role) {
				if ($role->trashed()) {
					$reason = 'User role has been deleted';
				} elseif (!$role->active) {
					$reason = 'User role is inactive';
				}
			} else {
				$reason = 'User role not found';
			}

			// Log failed login attempt
			$this->logAction(
				'AUTHENTICATION',
				'LOGIN_FAILED',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => $reason,
					'role_id' => $user->role_id,
					'role_exists' => $role ? true : false,
					'role_active' => $role ? $role->active : false,
					'role_deleted' => $role ? $role->trashed() : false,
					'ip_address' => $ip,
					'user_agent' => $request->userAgent()
				],
				(string) $user->id
			);

			$errorMessage = $role && $role->trashed() 
				? 'Your role has been deleted. Please contact administrator.'
				: 'Your role is inactive. Please contact administrator.';

			return response([
				'errors' => [$errorMessage],
				'status' => false,
				'status_code' => 403,
			], 403);
		}
		
		// Verify password
		// Debug logging for password verification
		\Log::info('[AuthController] Password verification attempt:', [
			'user_id' => $user->id,
			'email' => $credentials['email'],
			'password_length' => strlen($credentials['password']),
			'password_preview' => substr($credentials['password'], 0, 3) . '...',
			'has_salt' => !empty($user->user_salt),
			'has_hash' => !empty($user->user_pass),
			'salt_length' => strlen($user->user_salt ?? ''),
		]);
		
		if (!PasswordHelper::verifyPassword($credentials['password'], $user->user_salt, $user->user_pass)) {
			// Increment rate limiter
			RateLimiter::hit('login:' . $ip, $lockoutDurationSeconds);

			// Log failed login attempt with more details
			\Log::warning('[AuthController] Password verification failed:', [
				'user_id' => $user->id,
				'email' => $credentials['email'],
				'password_received_length' => strlen($credentials['password']),
				'user_salt_length' => strlen($user->user_salt ?? ''),
				'user_hash_length' => strlen($user->user_pass ?? ''),
			]);

			$this->logAction(
				'AUTHENTICATION',
				'LOGIN_FAILED',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => 'Invalid password',
					'ip_address' => $ip,
					'user_agent' => $request->userAgent()
				],
				(string) $user->id
			);
			
			return response([
				'errors' => ['Invalid email or password.'],
				'status' => false,
				'status_code' => 422,
			], 422);
		}

		// Clear rate limiter on successful login
		RateLimiter::clear('login:' . $ip);
		
		// Check if 2FA is required system-wide but user hasn't enabled it
		if ($this->twoFactorService->isTwoFactorRequiredSystemWide() && !$this->twoFactorService->isTwoFactorEnabled($user)) {
			// User must enable 2FA before logging in
			return response([
				'message' => 'Two-factor authentication is required. You will be redirected to set up 2FA.',
				'two_factor_required' => true,
				'two_factor_setup_required' => true,
				'status' => false,
				'status_code' => 403,
			], 403);
		}
		
		// Check if 2FA is required for this user (system-wide required OR user has it enabled)
		if ($this->twoFactorService->isTwoFactorRequiredForUser($user)) {
			// Send 2FA code
			$twoFactorResult = $this->twoFactorService->sendEmailCode($user);
			
			if ($twoFactorResult['success']) {
				// Log 2FA code sent
				$this->logAction(
					'AUTHENTICATION',
					'2FA_CODE_SENT',
					[
						'email' => $this->anonymizeEmail($credentials['email']),
						'user_id' => $user->id,
						'ip_address' => $ip,
						'user_agent' => $request->userAgent()
					],
					(string) $user->id
				);

				return response([
					'message' => 'Two-factor authentication required. Please check your email for the verification code.',
					'two_factor_required' => true,
					'status' => true,
					'status_code' => 200,
				], 200);
			} else {
				// Log 2FA code send failure
				$this->logAction(
					'AUTHENTICATION',
					'2FA_CODE_FAILED',
					[
						'email' => $this->anonymizeEmail($credentials['email']),
						'user_id' => $user->id,
						'ip_address' => $ip,
						'error' => $twoFactorResult['message']
					],
					(string) $user->id
				);

				return response([
					'errors' => ['Failed to send verification code. Please try again.'],
					'status' => false,
					'status_code' => 500,
				], 500);
			}
		}

		// No 2FA required, proceed with normal login
		$user->tokens()->delete();

		Auth::login($user);
		
		// Handle remember me - use different token name and expiration
		$rememberMe = $request->boolean('remember_me', false);
		$tokenName = $rememberMe ? 'admin-remember' : 'admin';
		
		// Get session timeout settings
		$sessionEnabled = $this->optionService->getOption('session_enabled', true);
		$sessionTimeoutMinutes = (int) $this->optionService->getOption('session_timeout', 30);
		
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
		
		// Create token with expiration
		$token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
		$userResource = new AuthResource($user);

		// Log successful login
		$this->logAction(
			'AUTHENTICATION',
			'LOGIN_SUCCESS',
			[
				'email' => $this->anonymizeEmail($credentials['email']),
				'user_id' => $user->id,
				'user_login' => $user->user_login,
				'ip_address' => $ip,
				'user_agent' => $request->userAgent(),
				'token_created' => true,
				'previous_tokens_deleted' => true
			],
			(string) $user->id
		);

		return response(['user' => $userResource, 'token' => $token]);	

	}

	/**
	 * Logout a user.
	 * 
	 * @OA\Post(
	 *     path="/api/logout",
	 *     summary="Logout user",
	 *     tags={"Authentication"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=204,
	 *         description="Logout successful"
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function logout(Request $request) 
	{
		// Log that logout endpoint was called
		\Log::info('AuthController: Logout endpoint called', [
			'has_user' => $request->user() !== null,
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
		]);
		
		$user = $request->user();
		
		if (!$user) {
			\Log::warning('AuthController: Logout called but no authenticated user');
			return response('', 204);
		}
		
		// Log the logout action
		try {
			\Log::info('AuthController: Attempting to log logout action', [
				'user_id' => $user->id,
				'user_login' => $user->user_login,
			]);
			
			$logged = $this->logAction(
				'AUTHENTICATION',
				'LOGOUT',
				[
					'user_id' => $user->id,
					'user_login' => $user->user_login,
					'email' => $this->anonymizeEmail($user->user_email),
					'ip_address' => $request->ip(),
					'token_deleted' => true
				],
				(string) $user->id
			);
			
			// Log if logging failed
			if (!$logged) {
				\Log::error('AuthController: Failed to log logout action', [
					'user_id' => $user->id,
					'user_login' => $user->user_login,
				]);
			} else {
				\Log::info('AuthController: Logout action logged successfully', [
					'user_id' => $user->id,
					'user_login' => $user->user_login,
				]);
			}
		} catch (\Exception $e) {
			\Log::error('AuthController: Exception while logging logout', [
				'user_id' => $user->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
		}
		
		$user->currentAccessToken()->delete();
		\Log::info('AuthController: Logout completed, token deleted', [
			'user_id' => $user->id,
		]);
		
		return response('', 204);
	}

	/**
	 * Enable 2FA during login (for first-time users when 2FA is mandatory)
	 * 
	 * @OA\Post(
	 *     path="/api/auth/enable-2fa-setup",
	 *     summary="Enable 2FA during login setup",
	 *     tags={"Authentication"},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"email", "password"},
	 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
	 *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="2FA enabled successfully, code sent to email",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="2FA enabled successfully. Please check your email for the verification code."),
	 *             @OA\Property(property="two_factor_required", type="boolean", example=true)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Invalid credentials",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid email or password")
	 *         )
	 *     )
	 * )
	 */
	public function enable2FASetup(Enable2FASetupRequest $request)
	{

		$credentials = $request->only('email', 'password');
		$ip = $request->ip();

		// Verify user credentials
		$user = User::with('role')->where('user_email', $credentials['email'])->first();

		if (!$user || !PasswordHelper::verifyPassword($credentials['password'], $user->user_salt, $user->user_pass)) {
			return response([
				'success' => false,
				'message' => 'Invalid email or password.',
				'status' => false,
				'status_code' => 401,
			], 401);
		}

		// Check if user is active (status = 1)
		// Status values: 0 = Inactive, 1 = Active, 2 = Suspended
		if ($user->user_status !== 1) {
			// Determine the specific reason based on status
			$reason = 'User account is inactive';
			$errorMessage = 'Your account is inactive. Please contact administrator.';
			
			if ($user->user_status === 0) {
				$reason = 'User account is inactive';
				$errorMessage = 'Your account is inactive. Please contact administrator.';
			} elseif ($user->user_status === 2) {
				$reason = 'User account is suspended';
				$errorMessage = 'Your account has been suspended. Please contact administrator.';
			}

			// Log the failure
			$this->logAction(
				'AUTHENTICATION',
				'2FA_SETUP_BLOCKED',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => $reason,
					'user_status' => $user->user_status,
					'ip_address' => $ip,
				],
				(string) $user->id
			);

			return response([
				'success' => false,
				'message' => $errorMessage,
				'status' => false,
				'status_code' => 403,
			], 403);
		}

		// Check if user has a role and if the role is active and not deleted
		$role = $user->role;
		if (!$user->role_id || !$role || !$role->active || $role->trashed()) {
			// Determine the specific reason for failure
			$reason = 'User role is inactive or missing';
			$errorMessage = 'Your role is inactive. Please contact administrator.';
			
			if ($role) {
				if ($role->trashed()) {
					$reason = 'User role has been deleted';
					$errorMessage = 'Your role has been deleted. Please contact administrator.';
				} elseif (!$role->active) {
					$reason = 'User role is inactive';
					$errorMessage = 'Your role is inactive. Please contact administrator.';
				}
			} else {
				$reason = 'User role not found';
				$errorMessage = 'Your role is not assigned. Please contact administrator.';
			}

			// Log the failure
			$this->logAction(
				'AUTHENTICATION',
				'2FA_SETUP_BLOCKED',
				[
					'user_id' => $user->id,
					'email' => $this->anonymizeEmail($credentials['email']),
					'reason' => $reason,
					'role_id' => $user->role_id,
					'role_exists' => $role ? true : false,
					'role_active' => $role ? $role->active : false,
					'role_deleted' => $role ? $role->trashed() : false,
					'ip_address' => $ip,
				],
				(string) $user->id
			);

			return response([
				'success' => false,
				'message' => $errorMessage,
				'status' => false,
				'status_code' => 403,
			], 403);
		}

		// Verify that 2FA is required system-wide and user hasn't enabled it
		if (!$this->twoFactorService->isTwoFactorRequiredSystemWide()) {
			return response([
				'success' => false,
				'message' => 'Two-factor authentication is not required system-wide.',
				'status' => false,
				'status_code' => 400,
			], 400);
		}

		if ($this->twoFactorService->isTwoFactorEnabled($user)) {
			return response([
				'success' => false,
				'message' => 'Two-factor authentication is already enabled for your account.',
				'status' => false,
				'status_code' => 400,
			], 400);
		}

		// Enable 2FA for the user
		$enableResult = $this->twoFactorService->enableTwoFactor($user);

		if (!$enableResult['success']) {
			return response([
				'success' => false,
				'message' => $enableResult['message'],
				'status' => false,
				'status_code' => 500,
			], 500);
		}

		// Send 2FA code to user's email
		$twoFactorResult = $this->twoFactorService->sendEmailCode($user);

		if ($twoFactorResult['success']) {
			// Log 2FA setup and code sent
			$this->logAction(
				'AUTHENTICATION',
				'2FA_SETUP_COMPLETED',
				[
					'email' => $this->anonymizeEmail($credentials['email']),
					'user_id' => $user->id,
					'ip_address' => $ip,
					'user_agent' => $request->userAgent()
				],
				(string) $user->id
			);

			return response([
				'success' => true,
				'message' => 'Two-factor authentication enabled successfully. Please check your email for the verification code.',
				'two_factor_required' => true,
				'status' => true,
				'status_code' => 200,
			], 200);
		} else {
			// Log 2FA code send failure
			$this->logAction(
				'AUTHENTICATION',
				'2FA_CODE_FAILED',
				[
					'email' => $this->anonymizeEmail($credentials['email']),
					'user_id' => $user->id,
					'ip_address' => $ip,
					'error' => $twoFactorResult['message']
				],
				(string) $user->id
			);

			return response([
				'success' => false,
				'message' => '2FA was enabled but failed to send verification code. Please try logging in again.',
				'status' => false,
				'status_code' => 500,
			], 500);
		}
	}

	/**
	 * Anonymize email for logging
	 */
	private function anonymizeEmail($email)
	{
		$parts = explode('@', $email);
		$username = $parts[0];
		$domain = $parts[1];
		
		$anonymized = substr($username, 0, 2) . '***@' . $domain;
		return $anonymized;
	}
}
