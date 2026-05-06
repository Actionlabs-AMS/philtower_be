<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Helpers\PasswordHelper;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('is_super_admin', true)->first() 
            ?? Role::where('name', 'Developer Account')->first()
            ?? Role::where('name', 'Super Admin')->first();
        
        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
            return;
        }

        // ✅ Department list
        $departments = [
            'IT',
            'HR',
            'Finance',
            'Operations',
            'Customer Support',
            'Engineering',
            'Admin'
        ];

        // Create Super Admin
        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'admin123'); 
        
        $superAdmin = User::updateOrCreate(
            ['user_email' => 'admin@basecode.com'],
            [
                'user_login' => 'admin',
                'user_email' => 'admin@basecode.com',
                'user_pass' => $password,
                'user_salt' => $salt,
                'user_status' => 1,
                'user_activation_key' => null,
                'role_id' => $superAdminRole->id,
            ]
        );

        // ✅ Assign department to super admin
        $superAdmin->saveUserMeta([
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'nickname' => 'Admin',
            'department' => 'IT', // fixed for admin
        ]);

        $availableRoles = Role::where('name', '!=', 'Developer Account')->get();
        
        if ($availableRoles->isEmpty()) {
            $this->command->warn('No roles found. Creating users with Super Admin role.');
            $availableRoles = collect([$superAdminRole]);
        }

        $firstNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Emma', 'Chris', 'Lisa', 'Tom', 'Amy', 'Mark', 'Julia', 'Paul', 'Rachel', 'Steve', 'Olivia', 'Dan', 'Sophia', 'Ryan', 'Emma'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee'];
        
        $roles = $availableRoles->toArray();
        $roleCount = count($roles);

        for ($i = 1; $i <= 20; $i++) {

            $roleIndex = ($i - 1) % $roleCount;
            $selectedRole = $availableRoles[$roleIndex];
            
            $firstName = $firstNames[($i - 1) % count($firstNames)];
            $lastName = $lastNames[($i - 1) % count($lastNames)];
            $userLogin = strtolower($firstName . $i);
            $userEmail = $userLogin . '@basecode.com';
            $password = 'password123';
            
            $salt = PasswordHelper::generateSalt();
            $hashedPassword = PasswordHelper::generatePassword($salt, $password);
            
            $userStatus = [1, 1, 1, 0, 2][($i - 1) % 5];

            $user = User::updateOrCreate(
                ['user_email' => $userEmail],
                [
                    'user_login' => $userLogin,
                    'user_email' => $userEmail,
                    'user_pass' => $hashedPassword,
                    'user_salt' => $salt,
                    'user_status' => $userStatus,
                    'user_activation_key' => null,
                    'role_id' => $selectedRole->id,
                ]
            );

            // ✅ Random department
            $department = $departments[array_rand($departments)];

            // ✅ Save meta INCLUDING department
            $user->saveUserMeta([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'nickname' => $firstName,
                'department' => $department,
            ]);
        }

        $this->command->info('Users seeded with departments successfully.');
    }
}