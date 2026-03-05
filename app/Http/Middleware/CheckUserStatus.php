<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Role;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     * 
     * Automatically logs out users who are:
     * - Soft deleted
     * - Inactive (status = 0)
     * - Suspended (status = 2)
     * - Have no role assigned
     * - Have a role that has been deleted (soft-deleted)
     * - Have a role that is inactive
     * 
     * Only allows active users (status = 1) with active roles to continue.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is authenticated, check their status
        if ($user) {
            // Refresh user from database to get latest status (including soft-deleted)
            $user = User::withTrashed()->with('role')->find($user->id);

            // Check if user is soft-deleted
            if ($user && $user->trashed()) {
                // Delete all tokens to force logout
                $user->tokens()->delete();
                
                return response()->json([
                    'message' => 'Your account has been deleted. Please contact administrator.',
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }

            // Check if user exists and is active (status = 1)
            // Status values: 0 = Inactive, 1 = Active, 2 = Suspended
            if (!$user || $user->user_status != 1) {
                $errorMessage = 'Your account is inactive. Please contact administrator.';
                
                if ($user) {
                    if ($user->user_status === 0) {
                        $errorMessage = 'Your account is inactive. Please contact administrator.';
                    } elseif ($user->user_status === 2) {
                        $errorMessage = 'Your account has been suspended. Please contact administrator.';
                    }
                }

                // Delete all tokens to force logout
                if ($user) {
                    $user->tokens()->delete();
                }

                return response()->json([
                    'message' => $errorMessage,
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }

            // Check if user has a role assigned
            if (!$user->role_id) {
                // Delete all tokens to force logout
                $user->tokens()->delete();

                return response()->json([
                    'message' => 'Your role is not assigned. Please contact administrator.',
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }

            // Check if user's role exists (including soft-deleted roles)
            // We need to check withTrashed() because belongsTo relationship won't load soft-deleted roles
            $role = Role::withTrashed()->find($user->role_id);
            
            // Check if role doesn't exist at all
            if (!$role) {
                // Delete all tokens to force logout
                $user->tokens()->delete();

                return response()->json([
                    'message' => 'Your role has been deleted. Please contact administrator.',
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }

            // Check if role is soft-deleted
            if ($role->trashed()) {
                // Delete all tokens to force logout
                $user->tokens()->delete();

                return response()->json([
                    'message' => 'Your role has been deleted. Please contact administrator.',
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }

            // Check if role is inactive
            // Explicitly check if role active status is false or null
            if ($role->active === false || $role->active === 0 || $role->active === null) {
                // Delete all tokens to force logout
                $user->tokens()->delete();

                return response()->json([
                    'message' => 'Your role is inactive. Please contact administrator.',
                    'logout_required' => true,
                    'status' => false,
                    'status_code' => 403,
                ], 403);
            }
        }

        return $next($request);
    }
}

