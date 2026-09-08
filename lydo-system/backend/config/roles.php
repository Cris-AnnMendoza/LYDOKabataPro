<?php
// Role permission matrix
// Each role lists what it CAN do.

define('ROLES', [
  'super_admin' => [
    'manage_admins',
    'view_all_users',
    'edit_users',
    'delete_users',
    'view_all_barangays',
    'manage_programs',
    'manage_events',
    'view_reports',
    'export_data',
    'view_logs',
    'manage_settings',
  ],
  'youth_coordinator' => [
    'view_all_users',
    'edit_users',
    'view_all_barangays',
    'manage_programs',
    'manage_events',
    'view_reports',
    'export_data',
  ],
  'barangay_admin' => [
    'view_barangay_users',   // own barangay only
    'edit_users',
    'manage_events',
    'view_reports',
  ],
  'staff_encoder' => [
    'view_all_users',
    'edit_users',
  ],
]);

function hasPermission(string $role, string $permission): bool {
  return in_array($permission, ROLES[$role] ?? [], true);
}

function requirePermission(array $admin, string $permission): void {
  if (!hasPermission($admin['role'], $permission)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
    exit;
  }
}
