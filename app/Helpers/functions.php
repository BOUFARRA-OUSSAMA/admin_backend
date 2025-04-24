<?php

use App\Models\ActivityLog;

if (!function_exists('activity_log')) {
    /**
     * Log activity in the system
     *
     * @param int|null $userId
     * @param string $action
     * @param string $module
     * @param string $description
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $ipAddress
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    function activity_log(
        $userId,
        $action,
        $module,
        $description,
        $entityType = null,
        $entityId = null,
        $ipAddress = null,
        $oldValues = null,
        $newValues = null
    ) {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $ipAddress,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null
        ]);
    }
}
