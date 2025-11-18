<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\I18n\Time;

class AddHelpdeskRole extends Migration
{
    public function up(): void
    {
        $db  = \Config\Database::connect();
        $now = Time::now('UTC')->toDateTimeString();

        $permissions = [
            [
                'key'         => 'search_beneficiaries',
                'name'        => 'Search beneficiary directory',
                'category'    => 'beneficiary',
                'description' => 'Allows the user to search beneficiaries across their organisation.',
            ],
            [
                'key'         => 'download_beneficiary_pdf',
                'name'        => 'Download beneficiary PDF',
                'category'    => 'beneficiary',
                'description' => 'Allows the user to download beneficiary profile PDFs.',
            ],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $row = $db->table('permissions')
                ->select('id')
                ->where('key', $permission['key'])
                ->get()
                ->getRowArray();

            if ($row) {
                $permissionIds[$permission['key']] = (int) $row['id'];
                continue;
            }

            $db->table('permissions')->insert([
                'key'         => $permission['key'],
                'name'        => $permission['name'],
                'description' => $permission['description'],
                'category'    => $permission['category'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $permissionIds[$permission['key']] = (int) $db->insertID();
        }

        $roleRow = $db->table('roles')->where('slug', 'helpdesk_user')->get()->getRowArray();
        if (! $roleRow) {
            $db->table('roles')->insert([
                'slug'             => 'helpdesk_user',
                'name'             => 'Helpdesk User',
                'description'      => 'Helpdesk user with read-only access to beneficiary profiles within their company.',
                'is_global'        => 0,
                'is_assignable'    => 1,
                'priority'         => 60,
                'default_redirect' => '/helpdesk/beneficiaries',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $roleId = (int) $db->insertID();
        } else {
            $roleId = (int) $roleRow['id'];
        }

        if (! $roleId) {
            return;
        }

        $requiredPermissions = array_merge(
            ['view_beneficiary_profile_full'],
            array_keys($permissionIds)
        );

        $permissionMap = $db->table('permissions')
            ->select('id, key')
            ->whereIn('key', $requiredPermissions)
            ->get()
            ->getResultArray();

        $permissionKeyToId = [];
        foreach ($permissionMap as $row) {
            $permissionKeyToId[$row['key']] = (int) $row['id'];
        }

        foreach ($requiredPermissions as $permissionKey) {
            $permissionId = $permissionKeyToId[$permissionKey] ?? null;
            if (! $permissionId) {
                continue;
            }

            $exists = $db->table('role_permissions')
                ->where([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ])
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $db->table('role_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'granted_at'    => $now,
                'granted_by'    => null,
            ]);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();

        $role = $db->table('roles')->select('id')->where('slug', 'helpdesk_user')->get()->getRowArray();
        if ($role) {
            $db->table('role_permissions')->where('role_id', $role['id'])->delete();
            $db->table('user_roles')->where('role_id', $role['id'])->delete();
            $db->table('roles')->where('id', $role['id'])->delete();
        }

        $db->table('permissions')
            ->whereIn('key', ['search_beneficiaries', 'download_beneficiary_pdf'])
            ->delete();
    }
}
