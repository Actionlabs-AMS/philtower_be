<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Navigation;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing navigations (use delete instead of truncate to avoid foreign key issues)
        Navigation::query()->delete();

        // Define navigation structure with parent-child relationships
        $navigationStructure = [
            // Dashboard (standalone)
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'home',
                'description' => 'Main dashboard with overview of system metrics and quick access to key features',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],
            
            // Hidden Profile page (accessible via header menu)
            [
                'name' => 'Profile',
                'slug' => 'profile',
                'icon' => 'user',
                'description' => 'Manage personal profile information, password, and two-factor authentication',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => false,
                'children' => [],
            ],
            
            // User Management Section
            [
                'name' => 'User Management',
                'slug' => 'user-management',
                'icon' => 'users',
                'description' => 'Manage users, roles, permissions, and user activity tracking',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'All Users',
                        'slug' => 'users',
                        'icon' => 'user-group',
                        'description' => 'View and manage all registered users, their profiles and account status',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Roles & Permissions',
                        'slug' => 'roles',
                        'icon' => 'shield-check',
                        'description' => 'Manage user roles and permissions to control what users can access and do (authorization)',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'User Activity',
                        'slug' => 'user-activity/:userId',
                        'icon' => 'activity',
                        'description' => 'Review the detailed activity timeline, login history, and sessions for a specific user',
                        'active' => true,
                        'show_in_menu' => false,
                    ],
                ],
            ],
            
            // Service Catalog Section (no child-ticket-statuses / child SLA)
            [
                'name' => 'Service Catalog',
                'slug' => 'service-catalog',
                'icon' => 'list-bullet',
                'description' => 'Manage service types and categories for the catalog',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Service Types',
                        'slug' => 'service-types',
                        'icon' => 'cube',
                        'description' => 'Manage service types and categories for the catalog',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Ticket Statuses',
                        'slug' => 'ticket-statuses',
                        'icon' => 'check-circle',
                        'description' => 'Manage ticket statuses for the catalog',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'SLA & Timing',
                        'slug' => 'sla-timing',
                        'icon' => 'clock',
                        'description' => 'Manage SLA and timing rules for the catalog',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],

            // Ticket Management
            [
                'name' => 'Ticket Management',
                'slug' => 'ticket-management',
                'icon' => 'ticket',
                'description' => 'View and manage tickets',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'All Tickets',
                        'slug' => 'all-tickets',
                        'icon' => 'clipboard-document-list',
                        'description' => 'View and manage all tickets',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Tickets for Approval',
                        'slug' => 'tickets-for-approval',
                        'icon' => 'clipboard-document-check',
                        'description' => 'Tickets pending your approval',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],

            // Knowledge Solutions
            [
                'name' => 'Knowledge Solutions',
                'slug' => 'knowledge-solutions',
                'icon' => 'book-open',
                'description' => 'Centralized repository of approved technical solutions and knowledge entries',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Knowledge Library',
                        'slug' => 'knowledge-library',
                        'icon' => 'book-open',
                        'description' => 'Browse and search approved knowledge base entries',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Pending KB Approval',
                        'slug' => 'knowledge-pending',
                        'icon' => 'clipboard-document-check',
                        'description' => 'Review and approve or reject KB submissions',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],
            
            // Analytics & Reports Section
            [
                'name' => 'Analytics & Reports',
                'slug' => 'analytics',
                'icon' => 'chart-bar',
                'description' => 'View detailed analytics, reports, and insights about system usage and performance',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'Analytics Overview',
                        'slug' => 'analytics-dashboard',
                        'icon' => 'presentation-chart-line',
                        'description' => 'Overview of key metrics and analytics in a visual dashboard format',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Activity Logs',
                        'slug' => 'activity-logs',
                        'icon' => 'clipboard-document-list',
                        'description' => 'Comprehensive system-wide audit trail of all administrative actions, module activities, and system operations across all users. Complete audit log for compliance and security monitoring',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],
            
            // System Settings Section
            [
                'name' => 'System Settings',
                'slug' => 'system-settings',
                'icon' => 'cog-6-tooth',
                'description' => 'Configure system preferences, security, and administrative settings',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [
                    [
                        'name' => 'General Settings',
                        'slug' => 'settings',
                        'icon' => 'adjustments-horizontal',
                        'description' => 'Configure general application settings, site information, and preferences',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Navigation',
                        'slug' => 'navigation',
                        'icon' => 'list-bullet',
                        'description' => 'Customize and manage navigation menu structure and order',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Security',
                        'slug' => 'security',
                        'icon' => 'shield-exclamation',
                        'description' => 'Configure two-factor authentication (2FA) and session security settings',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Email Settings',
                        'slug' => 'email-settings',
                        'icon' => 'envelope',
                        'description' => 'Configure email server settings and notification templates',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Localization',
                        'slug' => 'language',
                        'icon' => 'language',
                        'description' => 'Manage application languages, translations, and localization settings',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                    [
                        'name' => 'Backup & Restore',
                        'slug' => 'backup-restore',
                        'icon' => 'server',
                        'description' => 'Create backups, restore data, and manage system recovery options',
                        'active' => true,
                        'show_in_menu' => true,
                    ],
                ],
            ],

            // ============================================
            // Support / Requestor application (Dashboard shared above; Profile shared above)
            // ============================================
            [
                'name' => 'My Requests',
                'slug' => 'my-request',
                'icon' => 'document-text',
                'description' => 'View and manage your support ticket requests',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],
            [
                'name' => 'Help Center',
                'slug' => 'help-center',
                'icon' => 'help-circle',
                'description' => 'Frequently asked questions and help using the portal',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
                'children' => [],
            ],
        ];

        // Create navigations recursively
        foreach ($navigationStructure as $navData) {
            $this->createNavigation($navData, null);
        }
    }

    /**
     * Recursively create navigation items and their children
     */
    private function createNavigation(array $navData, ?int $parentId): void
    {
        // Prepare navigation data
        $navigation = [
            'name' => $navData['name'],
            'slug' => $navData['slug'],
            'icon' => $navData['icon'],
            'description' => $navData['description'] ?? null,
            'parent_id' => $parentId,
            'active' => $navData['active'] ?? true,
            'show_in_menu' => $navData['show_in_menu'] ?? true,
        ];

        // Create the navigation item
        $nav = Navigation::create($navigation);

        // Create children if they exist
        if (isset($navData['children']) && is_array($navData['children'])) {
            foreach ($navData['children'] as $childData) {
                $this->createNavigation($childData, $nav->id);
            }
        }
    }
}
