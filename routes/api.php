<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\SecurityDashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserActivityController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\UserReportsController;
use App\Http\Controllers\Api\ContentReportsController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\TicketPriorityController;
use App\Http\Controllers\Api\Support\ServiceTypeController;
use App\Http\Controllers\Api\Support\TicketStatusController;
use App\Http\Controllers\Api\Support\SlaController;
use App\Http\Controllers\Api\Support\TicketRequestController;
use App\Http\Controllers\Api\Support\TicketUpdateController;
use App\Http\Controllers\Api\Support\KnowledgeBaseController;
use App\Http\Controllers\Api\Support\MyRequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public route for general settings (site name, logos) - needed for login page
// MUST be defined BEFORE auth middleware group to avoid being caught by /{key} catch-all route
Route::get('/system-settings/settings/general', [SettingsController::class, 'getGeneralSettings']);

// Public CSAT survey rating endpoint (no auth required — accessed via email link)
Route::post('/support/csat/{token}', [\App\Http\Controllers\Api\Support\CsatController::class, 'rate']);

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/user/me', [UserController::class, 'getUser']);
	Route::post('/logout', [AuthController::class, 'logout']);
	
	// Dashboard Routes
	Route::prefix('dashboard')->group(function () {
		Route::get('/stats', [DashboardController::class, 'getStats']);
		Route::get('/enhanced-stats', [DashboardController::class, 'getEnhancedStats']);
		Route::get('/widgets', [DashboardController::class, 'getWidgets']);
		Route::get('/widgets/{id}/data', [DashboardController::class, 'getWidgetData']);
		Route::get('/user-registration-trend', [DashboardController::class, 'getUserRegistrationTrend']);
		Route::get('/pages-by-status', [DashboardController::class, 'getPagesByStatus']);
		Route::get('/ticket-stats', [DashboardController::class, 'getTicketStats']);
		// Requestor dashboard (when user has Requestor role)
		Route::get('/requestor/stats', [DashboardController::class, 'getRequestorStats']);
		Route::get('/requestor/latest-requests', [DashboardController::class, 'getRequestorLatestRequests']);
	});

	// Analytics & Reports Routes
	Route::prefix('analytics')->group(function () {
		// Analytics Overview
		Route::get('/overview', [AnalyticsController::class, 'getOverview']);
		Route::get('/tickets-overview', [AnalyticsController::class, 'getTicketsOverview']);

		// User Reports
		Route::prefix('user-reports')->group(function () {
			Route::get('/registration', [UserReportsController::class, 'getRegistrationReport']);
			Route::get('/engagement', [UserReportsController::class, 'getEngagementReport']);
			Route::get('/demographics', [UserReportsController::class, 'getDemographicsReport']);
		});

		// Content Reports
		Route::prefix('content-reports')->group(function () {
			Route::get('/performance', [ContentReportsController::class, 'getPerformanceReport']);
			Route::get('/categories', [ContentReportsController::class, 'getCategoryAnalysis']);
			Route::get('/tags', [ContentReportsController::class, 'getTagAnalysis']);
			Route::get('/media', [ContentReportsController::class, 'getMediaReport']);
		});

	});

	/*
	|--------------------------------------------------------------------------
	| Audit Trail Routes
	|--------------------------------------------------------------------------
	|
	| File-based audit trail viewing operations
	|
	*/
	Route::prefix('audit-trail')->group(function () {
		Route::get('/logs/date', [AuditTrailController::class, 'getLogsForDate']);
		Route::get('/logs/range', [AuditTrailController::class, 'getLogsForDateRange']);
		Route::get('/logs/search', [AuditTrailController::class, 'searchLogs']);
	});
	
	/*
	|--------------------------------------------------------------------------
	| Options Management Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('options')->group(function () {
		// media date folder
		Route::get('/dates', [MediaController::class, 'dateFolder']);
		// categories-related routes
		Route::get('/categories', [CategoryController::class, 'getCategories']);  // Retrieve all categories for dropdown
		Route::get('/categories/{id}', [CategoryController::class, 'getSubCategories']);  // Retrieve subcategories for a specific category		
		Route::get('/tags', [TagController::class, 'getTags']);  // Retrieve all tags for dropdown
		// navigation-related routes
		Route::get('/navigations', [NavigationController::class, 'getNavigations']);  // Retrieve all categories for dropdown
		Route::get('/navigations/{id}', [NavigationController::class, 'getSubNavigations']);  // Retrieve subcategories for a specific category		
		Route::get('/routes', [NavigationController::class, 'getRoutes']);  // Retrieve all routes
		Route::get('/roles', [RoleController::class, 'getRoles']);  // Retrieve all roles
		Route::get('/languages', [TranslationController::class, 'getLanguages']);
		Route::get('/service-types', [ServiceTypeController::class, 'getServiceTypes']);
		Route::get('/service-types/request-types', [ServiceTypeController::class, 'getRequestTypes']);
	});

	// Service Catalog - Service Types
	Route::prefix('service-catalog/service-types')->group(function () {
		Route::get('/', [ServiceTypeController::class, 'index']);
		Route::post('/', [ServiceTypeController::class, 'store']);
		Route::get('/archived', [ServiceTypeController::class, 'getTrashed']);
		Route::put('/restore/{id}', [ServiceTypeController::class, 'restore']);
		Route::patch('/restore/{id}', [ServiceTypeController::class, 'restore']);
		Route::delete('/force-delete/{id}', [ServiceTypeController::class, 'forceDelete']);
		Route::get('/{id}', [ServiceTypeController::class, 'show']);
		Route::put('/{id}', [ServiceTypeController::class, 'update']);
		Route::delete('/{id}', [ServiceTypeController::class, 'destroy']);
	});

	// Service Catalog - Ticket Statuses (renamed from Parent Ticket Statuses)
	Route::prefix('service-catalog/ticket-statuses')->group(function () {
		Route::get('/', [TicketStatusController::class, 'index']);
		Route::post('/', [TicketStatusController::class, 'store']);
		Route::post('/bulk/delete', [TicketStatusController::class, 'bulkDelete']);
		Route::post('/bulk/restore', [TicketStatusController::class, 'bulkRestore']);
		Route::post('/bulk/force-delete', [TicketStatusController::class, 'bulkForceDelete']);
		Route::get('/archived', [TicketStatusController::class, 'getTrashed']);
		Route::put('/restore/{id}', [TicketStatusController::class, 'restore']);
		Route::patch('/restore/{id}', [TicketStatusController::class, 'restore']);
		Route::delete('/force-delete/{id}', [TicketStatusController::class, 'forceDelete']);
		Route::get('/{id}', [TicketStatusController::class, 'show']);
		Route::put('/{id}', [TicketStatusController::class, 'update']);
		Route::delete('/{id}', [TicketStatusController::class, 'destroy']);
	});

	// Support - My Request (requestor: ticket_requests scoped to current user)
	Route::prefix('support/my-request')->group(function () {
		Route::post('/upload-attachments', [MyRequestController::class, 'uploadAttachments']);
		Route::get('/', [MyRequestController::class, 'index']);
		Route::post('/', [MyRequestController::class, 'store']);
		Route::post('/bulk/delete', [MyRequestController::class, 'bulkDelete']);
		Route::post('/bulk/restore', [MyRequestController::class, 'bulkRestore']);
		Route::post('/bulk/force-delete', [MyRequestController::class, 'bulkForceDelete']);
		Route::get('/archived', [MyRequestController::class, 'getTrashed']);
		Route::put('/restore/{id}', [MyRequestController::class, 'restore']);
		Route::patch('/restore/{id}', [MyRequestController::class, 'restore']);
		Route::delete('/force-delete/{id}', [MyRequestController::class, 'forceDelete']);
		Route::get('/{id}/updates', [MyRequestController::class, 'getUpdates']);
		Route::get('/{id}', [MyRequestController::class, 'show']);
		Route::put('/{id}', [MyRequestController::class, 'update']);
		Route::delete('/{id}', [MyRequestController::class, 'destroy']);
	});

	// Ticket Management - All Tickets (ticket_requests)
	Route::prefix('ticket-management/all-tickets')->group(function () {
		Route::post('/upload-attachments', [TicketRequestController::class, 'uploadAttachments']);
		Route::get('/', [TicketRequestController::class, 'index']);
		Route::post('/', [TicketRequestController::class, 'store']);
		Route::post('/bulk/delete', [TicketRequestController::class, 'bulkDelete']);
		Route::post('/bulk/restore', [TicketRequestController::class, 'bulkRestore']);
		Route::post('/bulk/force-delete', [TicketRequestController::class, 'bulkForceDelete']);
		Route::get('/archived', [TicketRequestController::class, 'getTrashed']);
		Route::put('/restore/{id}', [TicketRequestController::class, 'restore']);
		Route::patch('/restore/{id}', [TicketRequestController::class, 'restore']);
		Route::delete('/force-delete/{id}', [TicketRequestController::class, 'forceDelete']);
		Route::post('/approve/{id}', [TicketRequestController::class, 'approve']);
		Route::post('/reject/{id}', [TicketRequestController::class, 'reject']);
		Route::post('/{id}/request-approval', [TicketRequestController::class, 'requestApproval']);
		Route::get('/{id}/relationships', [TicketRequestController::class, 'relationships']);
		Route::post('/{id}/relationships', [TicketRequestController::class, 'createRelationship']);
		Route::delete('/{id}/relationships/{relationshipId}', [TicketRequestController::class, 'deleteRelationship']);
		Route::post('/{id}/reassign', [TicketRequestController::class, 'reassign']);
		Route::get('/{id}/updates', [TicketUpdateController::class, 'index']);
		Route::post('/{id}/updates', [TicketUpdateController::class, 'store']);
		Route::get('/{id}', [TicketRequestController::class, 'show']);
		Route::put('/{id}', [TicketRequestController::class, 'update']);
		Route::delete('/{id}', [TicketRequestController::class, 'destroy']);
	});

	Route::prefix('knowledge-base')->group(function () {
		Route::get('/', [KnowledgeBaseController::class, 'index']);
		Route::get('/pending', [KnowledgeBaseController::class, 'pending']);
		Route::post('/{id}/approve', [KnowledgeBaseController::class, 'approve']);
		Route::post('/{id}/reject', [KnowledgeBaseController::class, 'reject']);
	});
	Route::patch('/ticket-management/all-tickets/{ticketId}/updates/{updateId}/tag-kb', [KnowledgeBaseController::class, 'tagFromUpdate']);

	// Service Catalog - SLA & Timing
	Route::prefix('service-catalog/sla-timing')->group(function () {
		Route::get('/', [SlaController::class, 'index']);
		Route::post('/', [SlaController::class, 'store']);
		Route::post('/bulk/delete', [SlaController::class, 'bulkDelete']);
		Route::post('/bulk/restore', [SlaController::class, 'bulkRestore']);
		Route::post('/bulk/force-delete', [SlaController::class, 'bulkForceDelete']);
		Route::get('/archived', [SlaController::class, 'getTrashed']);
		Route::put('/restore/{id}', [SlaController::class, 'restore']);
		Route::patch('/restore/{id}', [SlaController::class, 'restore']);
		Route::delete('/force-delete/{id}', [SlaController::class, 'forceDelete']);
		Route::get('/{id}', [SlaController::class, 'show']);
		Route::put('/{id}', [SlaController::class, 'update']);
		Route::delete('/{id}', [SlaController::class, 'destroy']);
	});

	// Ticket Priority Management
	// Currently has no view in the frontend but included for completeness and future extensibility (e.g. allowing users to set priority when creating a ticket request)
	Route::prefix('service-catalog/ticket-priorities')->group(function () {
		Route::get('/', [TicketPriorityController::class, 'index']);
		Route::post('/', [TicketPriorityController::class, 'store']);
		Route::get('/{ticketPriority}', [TicketPriorityController::class, 'show']);
		Route::put('/{ticketPriority}', [TicketPriorityController::class, 'update']);
		Route::delete('/{ticketPriority}', [TicketPriorityController::class, 'destroy']);
	});

	/*
	|--------------------------------------------------------------------------
	| User Management Routes
	|--------------------------------------------------------------------------
	|
	| All Users
	| Roles & Permissions
	| Shortcut Link to Create User
	|
	*/
	Route::prefix('user-management')->group(function () {
		Route::prefix('users')->group(function () {
			// Standard CRUD operations
			Route::get('/', [UserController::class, 'index']);  // Retrieve all users
			// Dedicated endpoint for designated approvers only (must be before `/{id}` route).
			Route::get('/approvers', [UserController::class, 'approvers']);
			Route::get('/{id}', [UserController::class, 'show']);  // Retrieve a user
			Route::post('/', [UserController::class, 'store']);  // Create a new user
			Route::put('/{id}', [UserController::class, 'update']);  // Update an existing user
			Route::delete('/{id}', [UserController::class, 'destroy']);  // Delete a user
			
			// Bulk operations
			Route::post('/bulk/delete', [UserController::class, 'bulkDelete']);  // Bulk delete users
			Route::post('/bulk/restore', [UserController::class, 'bulkRestore']);  // Bulk restore users
			Route::post('/bulk/force-delete', [UserController::class, 'bulkForceDelete']);  // Bulk permanently delete users
			Route::post('/bulk/role', [UserController::class, 'bulkChangeRole']);
			Route::post('/bulk/password', [UserController::class, 'bulkChangePassword']);  // Bulk change password
			
			// Import/Export operations
			Route::post('/import', [UserController::class, 'import']);  // Import users from CSV
		});

		// Custom route for archived (trashed) users with a distinct prefix
		Route::prefix('archived/users')->group(function () {
			Route::get('/', [UserController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [UserController::class, 'restore']);
			Route::delete('/{id}', [UserController::class, 'forceDelete']);
		});

		// User Activity Routes
		Route::prefix('user-activity')->group(function () {
			Route::get('/export/login-history', [UserActivityController::class, 'exportAllLoginHistory']);
			Route::get('/{userId}', [UserActivityController::class, 'getUserActivities']);
			Route::get('/{userId}/login-history', [UserActivityController::class, 'getLoginHistory']);
			Route::get('/{userId}/sessions', [UserActivityController::class, 'getActiveSessions']);
			Route::post('/{userId}/sessions/{tokenId}/revoke', [UserActivityController::class, 'revokeSession']);
			Route::get('/{userId}/timeline', [UserActivityController::class, 'getUserTimeline']);
			Route::get('/{userId}/statistics', [UserActivityController::class, 'getActivityStatistics']);
		});

		Route::prefix('roles')->group(function () {
			// Standard CRUD operations
			Route::get('/', [RoleController::class, 'index']);  // Retrieve all roles
			Route::get('/{id}', [RoleController::class, 'show']);  // Retrieve a role
			Route::post('/', [RoleController::class, 'store']);  // Create a new role
			Route::put('/{id}', [RoleController::class, 'update']);  // Update an existing role
			Route::delete('/{id}', [RoleController::class, 'destroy']);  // Delete a role
			
			// Bulk operations
			Route::post('/bulk/delete', [RoleController::class, 'bulkDelete']);  // Bulk delete roles
			Route::post('/bulk/restore', [RoleController::class, 'bulkRestore']);  // Bulk restore roles
			Route::post('/bulk/force-delete', [RoleController::class, 'bulkForceDelete']);  // Bulk permanently delete roles
		});

		// Custom route for archived (trashed) roles with a distinct prefix
		Route::prefix('archived/roles')->group(function () {
			Route::get('/', [RoleController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [RoleController::class, 'restore']);
			Route::delete('/{id}', [RoleController::class, 'forceDelete']);
		});
	});

	/*
	|--------------------------------------------------------------------------
	| Content Management Routes
	|--------------------------------------------------------------------------
	|
	| Media Library
	| Categories
	| Tags
	|
	*/
	Route::prefix('content-management')->group(function () {

		// Specific routes must come before apiResource to avoid route conflicts
		Route::post('/media-library/bulk/delete', [MediaController::class, 'bulkDelete']);
		Route::apiResource('/media-library', MediaController::class);	

		Route::prefix('categories')->group(function () {
			// Standard CRUD operations
			Route::get('/', [CategoryController::class, 'index']);  // Retrieve all categories
			Route::get('/{id}', [CategoryController::class, 'show']);  // Retrieve a single category
			Route::post('/', [CategoryController::class, 'store']);  // Create a new category
			Route::put('/{id}', [CategoryController::class, 'update']);  // Update an existing category
			Route::delete('/{id}', [CategoryController::class, 'destroy']);  // Delete a category
			
			// Bulk operations
			Route::post('/bulk/delete', [CategoryController::class, 'bulkDelete']);  // Bulk delete categories
			Route::post('/bulk/restore', [CategoryController::class, 'bulkRestore']);  // Bulk restore categories
			Route::post('/bulk/force-delete', [CategoryController::class, 'bulkForceDelete']);  // Bulk permanently delete categories
		});

		// Additional category management routes
		Route::prefix('archived/categories')->group(function () {
			Route::get('/', [CategoryController::class, 'getTrashed']); // Retrieve soft-deleted categories
			Route::patch('/restore/{id}', [CategoryController::class, 'restore']); // Restore a soft-deleted category
			Route::delete('/{id}', [CategoryController::class, 'forceDelete']); // Permanently delete a soft-deleted category
		});

		Route::prefix('tags')->group(function () {
			// Standard CRUD operations
			Route::get('/', [TagController::class, 'index']);  // Retrieve all tags
			Route::get('/{id}', [TagController::class, 'show']);  // Retrieve a single tag
			Route::post('/', [TagController::class, 'store']);  // Create a new tag
			Route::put('/{id}', [TagController::class, 'update']);  // Update an existing tag
			Route::delete('/{id}', [TagController::class, 'destroy']);  // Delete a tag
			
			// Bulk operations
			Route::post('/bulk/delete', [TagController::class, 'bulkDelete']);  // Bulk delete tags
			Route::post('/bulk/restore', [TagController::class, 'bulkRestore']);  // Bulk restore tags
			Route::post('/bulk/force-delete', [TagController::class, 'bulkForceDelete']);  // Bulk permanently delete tags
		});

		// Custom route for archived (trashed) tags with a distinct prefix
		Route::prefix('archived/tags')->group(function () {
			Route::get('/', [TagController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [TagController::class, 'restore']);
			Route::delete('/{id}', [TagController::class, 'forceDelete']);
		});

		Route::prefix('pages')->group(function () {
			// Standard CRUD operations
			Route::get('/', [PageController::class, 'index']);  // Retrieve all pages
			Route::get('/{id}', [PageController::class, 'show']);  // Retrieve a single page
			Route::post('/', [PageController::class, 'store']);  // Create a new page
			Route::put('/{id}', [PageController::class, 'update']);  // Update an existing page
			Route::delete('/{id}', [PageController::class, 'destroy']);  // Delete a page
			
			// Bulk operations
			Route::post('/bulk/delete', [PageController::class, 'bulkDelete']);  // Bulk delete pages
			Route::post('/bulk/restore', [PageController::class, 'bulkRestore']);  // Bulk restore pages
			Route::post('/bulk/force-delete', [PageController::class, 'bulkForceDelete']);  // Bulk permanently delete pages
		});

		// Custom route for archived (trashed) pages with a distinct prefix
		Route::prefix('archived/pages')->group(function () {
			Route::get('/', [PageController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [PageController::class, 'restore']);
			Route::delete('/{id}', [PageController::class, 'forceDelete']);
		});
	});

	/*
	|--------------------------------------------------------------------------
	| System Settings Routes
	|--------------------------------------------------------------------------
	|
	| Navigations
	|
	*/
	Route::prefix('system-settings')->group(function () {
		Route::prefix('navigation')->group(function () {
			// Standard CRUD operations
			Route::get('/', [NavigationController::class, 'index']);  // Retrieve all navigation
			Route::get('/{id}', [NavigationController::class, 'show']);  // Retrieve a navigation
			Route::post('/', [NavigationController::class, 'store']);  // Create a new navigation
			Route::put('/{id}', [NavigationController::class, 'update']);  // Update an existing navigation
			Route::delete('/{id}', [NavigationController::class, 'destroy']);  // Delete a navigation
			
			// Bulk operations
			Route::post('/bulk/delete', [NavigationController::class, 'bulkDelete']);  // Bulk delete navigations
			Route::post('/bulk/restore', [NavigationController::class, 'bulkRestore']);  // Bulk restore navigations
			Route::post('/bulk/force-delete', [NavigationController::class, 'bulkForceDelete']);  // Bulk permanently delete navigations
		});

		// Custom route for archived (trashed) navigations with a distinct prefix
		Route::prefix('archived/navigation')->group(function () {
			Route::get('/', [NavigationController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [NavigationController::class, 'restore']);
			Route::delete('/{id}', [NavigationController::class, 'forceDelete']);
		});

		// Language & Translation Management Routes
		Route::prefix('language')->group(function () {
			// Language Management Routes
			Route::prefix('languages')->group(function () {
				// Standard CRUD operations
				Route::get('/', [LanguageController::class, 'index']);  // Retrieve all languages
				Route::get('/{id}', [LanguageController::class, 'show']);  // Retrieve a language
				Route::post('/', [LanguageController::class, 'store']);  // Create a new language
				Route::put('/{id}', [LanguageController::class, 'update']);  // Update an existing language
				Route::delete('/{id}', [LanguageController::class, 'destroy']);  // Delete a language
				
				// Bulk operations
				Route::post('/bulk/delete', [LanguageController::class, 'bulkDelete']);  // Bulk delete languages
				Route::post('/bulk/restore', [LanguageController::class, 'bulkRestore']);  // Bulk restore languages
				Route::post('/bulk/force-delete', [LanguageController::class, 'bulkForceDelete']);  // Bulk permanently delete languages
				
				// Special operations
				Route::post('/{id}/set-default', [LanguageController::class, 'setDefault']);  // Set language as default
			});

			// Custom route for archived (trashed) languages with a distinct prefix
			Route::prefix('archived/languages')->group(function () {
				Route::get('/', [LanguageController::class, 'getTrashed']);
				Route::patch('/restore/{id}', [LanguageController::class, 'restore']);
				Route::delete('/{id}', [LanguageController::class, 'forceDelete']);
			});

			// Get languages and groups for dropdowns (must be before translations routes)
			// Route::get('/languages', [TranslationController::class, 'getLanguages']);
			// Route::get('/groups', [TranslationController::class, 'getGroups']);

			Route::prefix('translations')->group(function () {
				// Standard CRUD operations
				Route::get('/', [TranslationController::class, 'index']);  // Retrieve all translations
				Route::get('/{id}', [TranslationController::class, 'show']);  // Retrieve a translation
				Route::post('/', [TranslationController::class, 'store']);  // Create a new translation
				Route::put('/{id}', [TranslationController::class, 'update']);  // Update an existing translation
				Route::delete('/{id}', [TranslationController::class, 'destroy']);  // Delete a translation
				
				// Bulk operations
				Route::post('/bulk/delete', [TranslationController::class, 'bulkDelete']);  // Bulk delete translations
				Route::post('/bulk/restore', [TranslationController::class, 'bulkRestore']);  // Bulk restore translations
				Route::post('/bulk/force-delete', [TranslationController::class, 'bulkForceDelete']);  // Bulk permanently delete translations
				
				// Import/Export operations
				Route::post('/import', [TranslationController::class, 'import']);  // Import translations from CSV
			});

			// Custom route for archived (trashed) translations with a distinct prefix
			Route::prefix('archived/translations')->group(function () {
				Route::get('/', [TranslationController::class, 'getTrashed']);
				Route::patch('/restore/{id}', [TranslationController::class, 'restore']);
				Route::delete('/{id}', [TranslationController::class, 'forceDelete']);
			});
		});

		// Settings Management Routes (moved under system-settings)
		Route::prefix('settings')->group(function () {
			Route::get('/', [SettingsController::class, 'index']);
			Route::post('/', [SettingsController::class, 'update']);
			Route::post('/initialize', [SettingsController::class, 'initialize']);

			// General Settings (excludes security and 2FA) - Must be before /{key} route
			// Note: GET /general is public (defined outside auth middleware) for login/2FA pages
			// POST requires authentication (inside auth middleware)
			Route::post('/general', [SettingsController::class, 'updateGeneralSettings']);

			// Email Settings - Must be before /{key} route
			Route::get('/email', [SettingsController::class, 'getEmailSettings']);
			Route::post('/email', [SettingsController::class, 'updateEmailSettings']);

			// Security Settings (2FA and Session) - Must be before /{key} route
			Route::get('/security', [SettingsController::class, 'getSecuritySettings']);
			Route::post('/security', [SettingsController::class, 'updateSecuritySettings']);

			// Individual option routes (must be after specific routes)
			Route::get('/{key}', [SettingsController::class, 'show']);
			Route::put('/{key}', [SettingsController::class, 'updateOption']);

			// 2FA Settings
			Route::prefix('two-factor')->group(function () {
				Route::get('/status', [SettingsController::class, 'getTwoFactorStatus']);
				Route::post('/enable', [SettingsController::class, 'enableTwoFactor']);
				Route::post('/disable', [SettingsController::class, 'disableTwoFactor']);
				Route::post('/backup-codes', [SettingsController::class, 'generateBackupCodes']);
			});
		});
	});

	// PROFILE ROUTES
	Route::post('/profile', [UserController::class, 'updateProfile']);
	
	// SECURITY DASHBOARD ROUTES
	Route::prefix('security')->group(function () {
		Route::get('/metrics', [SecurityDashboardController::class, 'getMetrics']);
		Route::post('/scan', [SecurityDashboardController::class, 'runSecurityScan']);
		Route::get('/events', [SecurityDashboardController::class, 'getSecurityEvents']);
		Route::get('/blocked-ips', [SecurityDashboardController::class, 'getBlockedIPs']);
		Route::post('/unblock-ip', [SecurityDashboardController::class, 'unblockIP']);
		Route::get('/config', [SecurityDashboardController::class, 'getSecurityConfig']);
		Route::post('/config', [SecurityDashboardController::class, 'updateSecurityConfig']);
	});

	/*
	|--------------------------------------------------------------------------
	| Backup and Restore Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('backups')->group(function () {
		// Options (most specific routes first)
		Route::get('/options/tables', [\App\Http\Controllers\Api\BackupController::class, 'getTables']);
		Route::get('/options/disks', [\App\Http\Controllers\Api\BackupController::class, 'getDisks']);

		// Schedule Management (before /{id} routes)
		Route::get('/schedules', [\App\Http\Controllers\Api\BackupController::class, 'schedules']);
		Route::post('/schedules', [\App\Http\Controllers\Api\BackupController::class, 'createSchedule']);
		Route::get('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'getSchedule']);
		Route::put('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'updateSchedule']);
		Route::delete('/schedules/{id}', [\App\Http\Controllers\Api\BackupController::class, 'deleteSchedule']);
		Route::post('/schedules/{id}/run', [\App\Http\Controllers\Api\BackupController::class, 'runSchedule']);

		// Backup Management
		Route::get('/', [\App\Http\Controllers\Api\BackupController::class, 'index']);
		Route::post('/', [\App\Http\Controllers\Api\BackupController::class, 'store']);
		Route::get('/stats', [\App\Http\Controllers\Api\BackupController::class, 'stats']);
		Route::get('/{id}', [\App\Http\Controllers\Api\BackupController::class, 'show']);
		Route::delete('/{id}', [\App\Http\Controllers\Api\BackupController::class, 'destroy']);
		Route::get('/{id}/download', [\App\Http\Controllers\Api\BackupController::class, 'download']);
		Route::post('/{id}/restore', [\App\Http\Controllers\Api\BackupController::class, 'restoreBackup']);
		Route::get('/{id}/validate', [\App\Http\Controllers\Api\BackupController::class, 'validateBackup']);
	});
});

// Webhook endpoint (no auth required, but token protected)
Route::post('/backups/webhook/trigger', [\App\Http\Controllers\Api\BackupController::class, 'webhookTrigger']);

Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:auth');
Route::post('/validate', [AuthController::class, 'activateUser'])->middleware('throttle:auth');
Route::post('/generate-password', [AuthController::class, 'genTempPassword'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/enable-2fa-setup', [AuthController::class, 'enable2FASetup'])->middleware('throttle:login');

// Microsoft Graph Test Route (for testing integration)
Route::get('/test-microsoft-graph', function () {
    try {
        $result = \App\Services\MicrosoftGraphService::sendNotificationEmail(
            'bautistael23@gmail.com',
            'BaseCode Microsoft Graph Test',
            '<h1>Microsoft Graph Integration Test</h1><p>This is a test email from BaseCode using Microsoft Graph API.</p><p>If you receive this email, the integration is working correctly!</p>'
        );
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Test email sent successfully via Microsoft Graph' : 'Failed to send test email'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Microsoft Graph test failed'
        ], 500);
    }
});

// Test email: uses Microsoft Graph only when mail_mailer is "microsoft" and Graph is configured; otherwise uses Laravel Mail (sendmail/smtp/log from options)
Route::post('/test-smtp-email', function (\Illuminate\Http\Request $request) {
    $optionService = app(\App\Services\OptionService::class);
    $mailer = $optionService->getOption('mail_mailer', env('MAIL_MAILER', 'smtp'));
    $useMicrosoftGraph = ($mailer === 'microsoft'
        && $optionService->getOption('microsoft_tenant_id')
        && $optionService->getOption('microsoft_sender_email'));

    $mailConfig = [
        'source' => $useMicrosoftGraph ? 'options + .env (Microsoft Graph)' : 'options + .env (Laravel Mail: ' . $mailer . ')',
        'mail_mailer' => $mailer,
        'mail_from_address' => $optionService->getOption('mail_from_address', env('MAIL_FROM_ADDRESS')),
        'mail_from_name' => $optionService->getOption('mail_from_name', env('MAIL_FROM_NAME')),
    ];
    if ($useMicrosoftGraph) {
        $mailConfig['microsoft_sender_email'] = $optionService->getOption('microsoft_sender_email') ? '***set***' : 'not set';
    }

    $email = $request->input('email', 'bautistael23@gmail.com');
    $subject = 'BaseCode Test Email';
    $body = '<p>This is a test email. If you receive this, your email configuration is working correctly.</p>';

    try {
        if ($useMicrosoftGraph) {
            \App\Helpers\MicrosoftGraphHelper::sendEmail($email, $subject, $body);
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $email . ' via Microsoft Graph',
                'mail_config' => $mailConfig,
            ]);
        }

        // Use Laravel Mail (sendmail, smtp, log, etc.) with from address/name from options
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $subject, $body) {
            $message->to($email)->subject($subject)->html($body);
        });

        $driver = config('mail.default');
        $message = 'Test email sent successfully to ' . $email;
        if ($driver === 'log') {
            $message .= ' (written to log – on Windows with sendmail selected, emails are logged only)';
        }
        return response()->json([
            'success' => true,
            'message' => $message,
            'mail_config' => array_merge($mailConfig, ['driver_used' => $driver]),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send test email',
            'error' => $e->getMessage(),
            'mail_config' => $mailConfig,
        ], 500);
    }
});

// Two-Factor Authentication Routes
Route::prefix('2fa')->group(function () {
    Route::post('/send-code', [TwoFactorAuthController::class, 'sendCode'])->middleware('throttle:auth');
    Route::post('/verify-code', [TwoFactorAuthController::class, 'verifyCode'])->middleware('throttle:auth');
    Route::post('/is-required', [TwoFactorAuthController::class, 'isRequired'])->middleware('throttle:auth');
    
    // Protected 2FA management routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/enable', [TwoFactorAuthController::class, 'enable']);
        Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
        Route::get('/status', [TwoFactorAuthController::class, 'status']);
        Route::post('/generate-backup-codes', [TwoFactorAuthController::class, 'generateBackupCodes']);
    });
});

// (merged into system-settings/settings above)
