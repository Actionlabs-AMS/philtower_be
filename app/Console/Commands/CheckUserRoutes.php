<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserRoutes extends Command
{
    protected $signature = 'user:check-routes {email}';
    protected $description = 'Check user routes for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('user_email', $email)->with('role')->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $this->info("User found:");
        $this->line("  ID: {$user->id}");
        $this->line("  Login: {$user->user_login}");
        $this->line("  Email: {$user->user_email}");
        $this->line("  Role ID: {$user->role_id}");

        $userRole = $user->role;
        
        if (!$userRole) {
            $this->error("  User has no role assigned!");
            return 1;
        }

        $this->line("  Role Name: {$userRole->name}");
        
        // Get permissions
        $permissions = $userRole->getPermissionsFormatted();
        $this->line("  Permissions Count: " . count($permissions));
        
        // Set permissions on role so getUserRoutes can access it
        $userRole->setAttribute('permissions', $permissions);
        
        // Get user routes
        $userRoutes = $userRole->getUserRoutes();
        $this->line("  User Routes Count: " . count($userRoutes));
        
        if (count($userRoutes) > 0) {
            $this->info("\nUser Routes:");
            foreach ($userRoutes as $route) {
                $this->line("  - {$route['name']} ({$route['path']}) - side_nav: {$route['side_nav']}");
                if (isset($route['children']) && count($route['children']) > 0) {
                    foreach ($route['children'] as $child) {
                        $this->line("    └─ {$child['name']} ({$child['path']}) - side_nav: {$child['side_nav']}");
                    }
                }
            }
        } else {
            $this->warn("\n  No routes found! This could mean:");
            $this->line("    1. Role has no permissions assigned");
            $this->line("    2. Navigations don't have 'can_view' permission");
            $this->line("    3. Navigations are not set to show in menu");
        }

        return 0;
    }
}

