<?php
require 'vendor/autoload.php';
require 'app/Config/Boot/development.php';
$db = Config\Database::connect();
$rows = $db->table('user_roles ur')
    ->select('u.username, r.slug AS role, p.key AS permission')
    ->join('app_users u', 'u.id = ur.user_id', 'inner')
    ->join('roles r', 'r.id = ur.role_id', 'inner')
    ->join('role_permissions rp', 'rp.role_id = r.id', 'inner')
    ->join('permissions p', 'p.id = rp.permission_id', 'inner')
    ->where('u.username', 'content_creator')
    ->where('ur.revoked_at', null)
    ->get()
    ->getResultArray();
foreach ($rows as $row) {
    echo $row['username'] . "\t" . $row['role'] . "\t" . $row['permission'] . PHP_EOL;
}
