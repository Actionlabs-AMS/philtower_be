<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\MicrosoftGraphService;
use App\Services\OptionService;
use App\Helpers\PasswordHelper;
use Illuminate\Support\Facades\Log;

class UserService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new UserResource(new User), new User());
  }
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    $allUsers = $this->getTotalCount();
    $trashedUsers = $this->getTrashedCount();

    return UserResource::collection(User::query()
    ->with('role') // Eager load role relationship
    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
    // Exclude users with Developer Account role
    ->where(function ($query) {
      $query->whereNull('roles.name')
        ->orWhere('roles.name', '!=', 'Developer Account');
    })
    ->when(request('search'), function ($query) {
      $search = request('search');
      return $query->where(function ($q) use ($search) {
        $q->where('users.user_login', 'LIKE', '%' . $search . '%')
          ->orWhere('users.user_email', 'LIKE', '%' . $search . '%')
          ->orWhere('roles.name', 'LIKE', '%' . $search . '%');
      });
    })
    ->when(request()->has('role_name') && request('role_name') !== '', function ($query) {
      // Strict filter: exact match on role name
      return $query->where('roles.name', request('role_name'));
    })
    ->when(request()->has('user_status') && request('user_status') !== '', function ($query) {
      // Strict filter: exact match on user status (handles 0, 1, 2 correctly)
      // Note: Using request()->has() to check existence, not truthiness, so '0' is handled correctly
      return $query->where('users.user_status', request('user_status'));
    })
    ->when(request('order'), function ($query) {
      $order = request('order');
      $sort = request('sort', 'asc');
      
      // Handle ordering by role name
      if ($order === 'role_name') {
        return $query->orderBy('roles.name', $sort);
      }
      
      // Handle other fields
      return $query->orderBy('users.' . $order, $sort);
    })
    ->when(!request('order'), function ($query) {
      return $query->orderBy('users.id', 'desc');
    })
    ->when($trash, function ($query) {
      return $query->onlyTrashed();
    })
    ->select('users.*') // Important to avoid column conflicts
    ->paginate($perPage)->withQueryString()
    )->additional(['meta' => ['all' => $allUsers, 'trashed' => $trashedUsers]]);
  }

  /**
  * Store a newly created resource in storage.
  */
  public function storeWithMeta(array $data, array $metaData)
  {
    $user = parent::store($data); // Call the parent method
    if(count($metaData))
      $user->saveUserMeta($metaData);

    // $user_key = $user->user_activation_key;
    // $this->sendVerifyEmail($user, $user_key);

    return new UserResource($user);
  }

  /**
  * Update the specified resource in storage.
  */
  public function updateWithMeta(array $data, array $metaData, User $user)
  {
    $user->update($data);
    if(count($metaData))
      $user->saveUserMeta($metaData);

    $this->sendForgotPasswordEmail($user);

    return new UserResource($user);
  }

  /**
  * Bulk restore a soft-deleted user.
  */
  public function bulkChangePassword($ids) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $this->genTempPassword($user);
      }
    }
  }

  public function genTempPassword(User $user) 
	{
		if($user) {
			$salt = $user->user_salt;
			$new_password = PasswordHelper::generateSalt();
			$password = PasswordHelper::generatePassword($salt, $new_password);

			$user->update(['user_pass' => $password]);

			$this->sendForgotPasswordEmail($user, $new_password);
		}
	}

  /**
  * Bulk change user password.
  */
  public function bulkChangeRole($ids, $role) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $this->changeRole($user, $role);
      }
    }
  }

  public function changeRole(User $user, $role) 
  {
    if(isset($role)) {
      // Update role_id directly on users table
      $user->update(['role_id' => $role]);
    }
  }

  /**
  * Send verify email using Microsoft Graph.
  */
  public function sendVerifyEmail($user, $user_key)
  {
    try {
      $verify_url = env('ADMIN_APP_URL')."/login/activate/".$user_key;
      $password = request('user_pass') ?? '';
      
      // Build email body
      $userName = $user->user_login;
      $emailBody = "<h2>Welcome to CorePanel!</h2>"
          . "<p>Hello {$userName},</p>"
          . "<p>Your account has been created successfully. Please use the following credentials to log in:</p>"
          . "<p><strong>Username:</strong> {$user->user_login}</p>"
          . "<p><strong>Email:</strong> {$user->user_email}</p>";
      
      if ($password) {
        $emailBody .= "<p><strong>Password:</strong> {$password}</p>";
      }
      
      $emailBody .= "<p>Please click the link below to verify and activate your account:</p>"
          . "<p><a href='{$verify_url}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a></p>"
          . "<p>If the button doesn't work, you can copy and paste this link into your browser:</p>"
          . "<p>{$verify_url}</p>"
          . "<p>Best regards,<br>The CorePanel Team</p>";

      $subject = "Welcome to CorePanel - Verify Your Email";

      // Prefer OptionService (centralized mail config, which may itself use Microsoft Graph)
      try {
        /** @var \App\Services\OptionService $optionService */
        $optionService = app(OptionService::class);
        $optionService->sendEmail($user->user_email, $subject, $emailBody);

        Log::info('[UserService] Verification email sent via OptionService', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
        ]);
      } catch (\Throwable $inner) {
        // Fallback to legacy Microsoft Graph service if OptionService fails
        Log::warning('[UserService] OptionService failed for verification email, falling back to MicrosoftGraphService', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
          'error' => $inner->getMessage(),
        ]);

        MicrosoftGraphService::sendNotificationEmail(
          $user->user_email,
          $subject,
          $emailBody
        );

        Log::info('[UserService] Verification email sent via MicrosoftGraphService fallback', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
        ]);
      }

      Log::info('[UserService] Verification email send process completed', [
        'user_id' => $user->id,
        'user_email' => $user->user_email
      ]);
    } catch (\Exception $e) {
      // Log error but don't throw - allow user creation to succeed even if email fails
      Log::error('[UserService] Failed to send verification email', [
        'user_id' => $user->id,
        'user_email' => $user->user_email,
        'error' => $e->getMessage()
      ]);
      // Don't throw exception - user creation should succeed even if email fails
    }
  }

  /**
  * Send temporary password using Microsoft Graph.
  */
  public function sendForgotPasswordEmail($user, $new_password = '') 
  {
    try {
      $user_pass = ($new_password) ? $new_password : request('user_pass');
      
      if (!$user_pass) {
        Log::warning('[UserService] No password provided for forgot password email', [
          'user_id' => $user->id
        ]);
        return;
      }

      $login_url = env('ADMIN_APP_URL')."/login";
      $userName = $user->user_login;
      
      $emailBody = "<h2>Password Reset</h2>"
          . "<p>Hello {$userName},</p>"
          . "<p>Your temporary password has been generated. Please use the following credentials to log in:</p>"
          . "<p><strong>Username:</strong> {$user->user_login}</p>"
          . "<p><strong>Email:</strong> {$user->user_email}</p>"
          . "<p><strong>Temporary Password:</strong> {$user_pass}</p>"
          . "<p>Please click the link below to log in:</p>"
          . "<p><a href='{$login_url}' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Log In</a></p>"
          . "<p>If the button doesn't work, you can copy and paste this link into your browser:</p>"
          . "<p>{$login_url}</p>"
          . "<p><strong>Important:</strong> Please change your password after logging in for security reasons.</p>"
          . "<p>If you didn't request this password reset, please contact support immediately.</p>"
          . "<p>Best regards,<br>The CorePanel Team</p>";

      $subject = "CorePanel - Temporary Password";

      // Prefer OptionService (centralized mail config)
      try {
        /** @var \App\Services\OptionService $optionService */
        $optionService = app(OptionService::class);
        $optionService->sendEmail($user->user_email, $subject, $emailBody);

        Log::info('[UserService] Password reset email sent via OptionService', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
        ]);
      } catch (\Throwable $inner) {
        // Fallback to legacy Microsoft Graph service if OptionService fails
        Log::warning('[UserService] OptionService failed for password reset email, falling back to MicrosoftGraphService', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
          'error' => $inner->getMessage(),
        ]);

        MicrosoftGraphService::sendNotificationEmail(
          $user->user_email,
          $subject,
          $emailBody
        );

        Log::info('[UserService] Password reset email sent via MicrosoftGraphService fallback', [
          'user_id' => $user->id,
          'user_email' => $user->user_email,
        ]);
      }

      Log::info('[UserService] Password reset email send process completed', [
        'user_id' => $user->id,
        'user_email' => $user->user_email
      ]);
    } catch (\Exception $e) {
      // Log error but don't throw - allow operation to succeed even if email fails
      Log::error('[UserService] Failed to send password reset email', [
        'user_id' => $user->id,
        'user_email' => $user->user_email,
        'error' => $e->getMessage()
      ]);
      // Don't throw exception - password reset should succeed even if email fails
    }
  }
}