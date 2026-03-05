<?php

namespace App\Traits;

use App\Helpers\AuditTrailHelper;

trait AuditTrailTrait
{
    /**
     * Log a create action
     *
     * @param string $module
     * @param array $data
     * @param string|null $resourceId
     * @return bool
     */
    protected function logCreate(string $module, array $data = [], ?string $resourceId = null): bool
    {
        return AuditTrailHelper::log(
            $module,
            'CREATE',
            $data,
            $resourceId,
            null,
            $data
        );
    }

    /**
     * Log an update action
     *
     * @param string $module
     * @param array $oldData
     * @param array $newData
     * @param string|null $resourceId
     * @return bool
     */
    protected function logUpdate(string $module, array $oldData = [], array $newData = [], ?string $resourceId = null): bool
    {
        return AuditTrailHelper::log(
            $module,
            'UPDATE',
            ['changes' => $this->getChanges($oldData, $newData)],
            $resourceId,
            $oldData,
            $newData
        );
    }

    /**
     * Log a delete action
     *
     * @param string $module
     * @param array $data
     * @param string|null $resourceId
     * @return bool
     */
    protected function logDelete(string $module, array $data = [], ?string $resourceId = null): bool
    {
        return AuditTrailHelper::log(
            $module,
            'DELETE',
            $data,
            $resourceId,
            $data,
            null
        );
    }

    /**
     * Log a view action
     *
     * @param string $module
     * @param array $data
     * @param string|null $resourceId
     * @return bool
     */
    protected function logView(string $module, array $data = [], ?string $resourceId = null): bool
    {
        return AuditTrailHelper::log(
            $module,
            'VIEW',
            $data,
            $resourceId
        );
    }

    /**
     * Log a custom action
     *
     * @param string $module
     * @param string $action
     * @param array $data
     * @param string|null $resourceId
     * @param array|null $oldData
     * @param array|null $newData
     * @return bool
     */
    protected function logAction(
        string $module,
        string $action,
        array $data = [],
        ?string $resourceId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): bool {
        return AuditTrailHelper::log(
            $module,
            $action,
            $data,
            $resourceId,
            $oldData,
            $newData
        );
    }

    /**
     * Log bulk operations
     *
     * @param string $module
     * @param string $action
     * @param array $resourceIds
     * @param array $data
     * @return bool
     */
    protected function logBulkAction(string $module, string $action, array $resourceIds = [], array $data = []): bool
    {
        return AuditTrailHelper::log(
            $module,
            'BULK_' . strtoupper($action),
            array_merge($data, ['affected_ids' => $resourceIds, 'count' => count($resourceIds)]),
            implode(',', $resourceIds)
        );
    }

    /**
     * Log import operations
     *
     * @param string $module
     * @param array $data
     * @param int $successCount
     * @param int $errorCount
     * @return bool
     */
    protected function logImport(string $module, array $data = [], int $successCount = 0, int $errorCount = 0): bool
    {
        return AuditTrailHelper::log(
            $module,
            'IMPORT',
            array_merge($data, [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_processed' => $successCount + $errorCount
            ])
        );
    }

    /**
     * Log export operations
     *
     * @param string $module
     * @param array $data
     * @param int $recordCount
     * @return bool
     */
    protected function logExport(string $module, array $data = [], int $recordCount = 0): bool
    {
        return AuditTrailHelper::log(
            $module,
            'EXPORT',
            array_merge($data, ['record_count' => $recordCount])
        );
    }

    /**
     * Get changes between old and new data
     *
     * @param array $oldData
     * @param array $newData
     * @return array
     */
    private function getChanges(array $oldData, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Get module name from controller class
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        $className = class_basename($this);
        $moduleName = str_replace('Controller', '', $className);
        
        // Map controller names to module names
        $moduleMap = [
            'Auth' => 'AUTHENTICATION',
            'User' => 'USER_MANAGEMENT',
            'Role' => 'USER_MANAGEMENT',
            'Category' => 'CONTENT_MANAGEMENT',
            'Brand' => 'CONTENT_MANAGEMENT',
            'Tag' => 'CONTENT_MANAGEMENT',
            'Amenity' => 'CONTENT_MANAGEMENT',
            'Site' => 'SITE_MANAGEMENT',
            'Building' => 'SITE_MANAGEMENT',
            'Floor' => 'SITE_MANAGEMENT',
            'Landmark' => 'SITE_MANAGEMENT',
            'Navigation' => 'SYSTEM_SETTINGS',
            'Media' => 'CONTENT_MANAGEMENT',
        ];
        
        return $moduleMap[$moduleName] ?? strtoupper($moduleName);
    }
}
