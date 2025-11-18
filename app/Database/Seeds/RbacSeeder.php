<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now('UTC')->toDateTimeString();

        // ------------------------------------------------------------------
        // Companies
        // ------------------------------------------------------------------
        $companies = [
            ['name' => 'M.P. Power Generating Co. Ltd.',      'code' => 'MPPGCL', 'is_nodal' => 1],
            ['name' => 'M.P. Power Management Co. Ltd.',      'code' => 'MPPMCL', 'is_nodal' => 0],
            ['name' => 'M.P. Power Transmission Co. Ltd.',    'code' => 'MPPTCL', 'is_nodal' => 0],
            ['name' => 'M.P. Madhya Kshetra Vidyut Vitran',   'code' => 'MPCZ',   'is_nodal' => 0],
            ['name' => 'M.P. Paschim Kshetra Vidyut Vitran',  'code' => 'MPWZ',   'is_nodal' => 0],
            ['name' => 'M.P. Poorva Kshetra Vidyut Vitran',   'code' => 'MPEZ',   'is_nodal' => 0],
        ];
        $companyRows = array_map(static function (array $company) use ($now) {
            return $company + [
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $companies);

        $this->db->table('companies')->ignore(true)->insertBatch($companyRows);

        // ------------------------------------------------------------------
        // Permissions catalogue
        // ------------------------------------------------------------------
        $permissionCatalog = [
            // Dashboards & reporting
            ['key' => 'view_dashboard_company',  'name' => 'View company dashboards',         'category' => 'dashboard'],
            ['key' => 'view_dashboard_global',   'name' => 'View cross-company dashboards',   'category' => 'dashboard'],
            ['key' => 'view_financial_reports',  'name' => 'View financial reports',          'category' => 'reports'],
            ['key' => 'view_claims',             'name' => 'View claim data',                 'category' => 'claims'],
            ['key' => 'manage_claims',           'name' => 'Manage claim registry',           'category' => 'claims'],
            ['key' => 'process_claims',          'name' => 'Process claim payments',          'category' => 'claims'],
            ['key' => 'approve_disbursal',       'name' => 'Approve disbursal requests',      'category' => 'claims'],
            ['key' => 'manage_payment_batches',  'name' => 'Manage payment batches',          'category' => 'claims'],
            ['key' => 'export_claims',           'name' => 'Export claim datasets',           'category' => 'claims'],
            ['key' => 'export_mis',              'name' => 'Export MIS reports',              'category' => 'reports'],
            ['key' => 'download_claim_documents','name' => 'Download claim documents',        'category' => 'claims'],

            // Beneficiary data
            ['key' => 'view_beneficiary_profile_full', 'name' => 'View full beneficiary profile', 'category' => 'beneficiary'],
            ['key' => 'edit_beneficiary_profile',      'name' => 'Edit beneficiary profile',      'category' => 'beneficiary'],
            ['key' => 'manage_dependents',             'name' => 'Manage dependents',             'category' => 'beneficiary'],
            ['key' => 'review_profile_update',         'name' => 'Review profile updates',        'category' => 'beneficiary'],
            ['key' => 'approve_profile_update',        'name' => 'Approve profile updates',       'category' => 'beneficiary'],
            ['key' => 'approve_dependent_change',      'name' => 'Approve dependent changes',     'category' => 'beneficiary'],
            ['key' => 'search_beneficiaries',          'name' => 'Search beneficiary directory',  'category' => 'beneficiary'],
            ['key' => 'download_beneficiary_pdf',      'name' => 'Download beneficiary PDF',      'category' => 'beneficiary'],

            // Hospital & network
            ['key' => 'create_hospital_request',     'name' => 'Submit hospital request',         'category' => 'network'],
            ['key' => 'review_hospital_request',     'name' => 'Review hospital request',         'category' => 'network'],
            ['key' => 'approve_hospital_request',    'name' => 'Approve hospital request',        'category' => 'network'],
            ['key' => 'manage_hospital_registry',    'name' => 'Manage hospital registry',        'category' => 'network'],
            ['key' => 'manage_diagnostic_registry',  'name' => 'Manage diagnostic registry',      'category' => 'network'],
            ['key' => 'manage_bloodbank_registry',   'name' => 'Manage blood bank registry',      'category' => 'network'],

            // Data operations
            ['key' => 'upload_pension_data',  'name' => 'Upload pension data',  'category' => 'data'],
            ['key' => 'upload_medical_data',  'name' => 'Upload medical data',  'category' => 'data'],
            ['key' => 'manage_bulk_imports',  'name' => 'Manage bulk imports',  'category' => 'data'],
            ['key' => 'review_data_upload',   'name' => 'Review data upload',   'category' => 'data'],
            ['key' => 'approve_data_upload',  'name' => 'Approve data upload',  'category' => 'data'],

            // Content & branding
            ['key' => 'manage_branding_assets',     'name' => 'Manage branding assets',      'category' => 'content'],
            ['key' => 'submit_blog',                'name' => 'Submit blog/testimonial',     'category' => 'content'],
            ['key' => 'edit_blog',                  'name' => 'Edit blog/testimonial',       'category' => 'content'],
            ['key' => 'approve_blog',               'name' => 'Approve blog/testimonial',    'category' => 'content'],
            ['key' => 'publish_blog',               'name' => 'Publish blog/testimonial',    'category' => 'content'],
            ['key' => 'manage_leadership_connect',  'name' => 'Manage leadership connect',   'category' => 'content'],

            // Administration
            ['key' => 'manage_users_company',  'name' => 'Manage users (company)',  'category' => 'admin'],
            ['key' => 'manage_users_all',      'name' => 'Manage users (global)',   'category' => 'admin'],
            ['key' => 'assign_roles_company',  'name' => 'Assign roles (company)',  'category' => 'admin'],
            ['key' => 'assign_roles_all',      'name' => 'Assign roles (global)',   'category' => 'admin'],
            ['key' => 'view_audit_logs',       'name' => 'View audit logs',         'category' => 'admin'],

            // Workflow approvals
            ['key' => 'review_document',   'name' => 'Review workflow document', 'category' => 'workflow'],
            ['key' => 'approve_document',  'name' => 'Approve workflow document','category' => 'workflow'],
            ['key' => 'override_workflow', 'name' => 'Override workflow',        'category' => 'workflow'],

            // ISA specific
            ['key' => 'view_isa_dashboard',   'name' => 'View ISA dashboard',     'category' => 'isa'],
            ['key' => 'isa_bulk_upload',      'name' => 'ISA bulk upload',        'category' => 'isa'],
            ['key' => 'isa_manage_hospitals', 'name' => 'ISA manage hospitals',   'category' => 'isa'],
        ];

        $permissionRows = array_map(static function (array $permission) use ($now) {
            return $permission + [
                'description' => $permission['description'] ?? null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }, $permissionCatalog);

        $this->db->table('permissions')->ignore(true)->insertBatch($permissionRows);

        // Build permission lookup map
        $permissionMap = [];
        $query = $this->db->table('permissions')->select('id, key')->get();
        foreach ($query->getResultArray() as $row) {
            $permissionMap[$row['key']] = (int) $row['id'];
        }

        // ------------------------------------------------------------------
        // Roles & their permission sets
        // ------------------------------------------------------------------
        $roles = [
            'super_admin' => [
                'name'             => 'Super Admin',
                'description'      => 'MPPGCL nodal super administrator with global access.',
                'is_global'        => 1,
                'priority'         => 1,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => array_keys($permissionMap), // all permissions
            ],
            'company_admin' => [
                'name'             => 'Company Admin',
                'description'      => 'Company administrator for local operations.',
                'is_global'        => 0,
                'priority'         => 5,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_financial_reports',
                    'view_claims',
                    'manage_claims',
                    'download_claim_documents',
                    'export_claims',
                    'export_mis',
                    'manage_users_company',
                    'assign_roles_company',
                    'view_audit_logs',
                    'review_hospital_request',
                    'approve_hospital_request',
                    'manage_hospital_registry',
                    'manage_diagnostic_registry',
                    'manage_bloodbank_registry',
                    'upload_pension_data',
                    'upload_medical_data',
                    'manage_bulk_imports',
                    'review_data_upload',
                    'approve_data_upload',
                    'review_document',
                    'approve_document',
                ],
            ],
            'leadership' => [
                'name'             => 'Leadership',
                'description'      => 'Leadership dashboard access with drill-down reporting.',
                'is_global'        => 0,
                'priority'         => 10,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_financial_reports',
                    'view_claims',
                    'manage_claims',
                    'download_claim_documents',
                    'export_claims',
                    'export_mis',
                ],
            ],
            'rao' => [
                'name'             => 'Regional Accounts Office',
                'description'      => 'RAO operations for claims and expenditure tracking.',
                'is_global'        => 0,
                'priority'         => 20,
                'default_redirect' => '/dashboard/finance',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'process_claims',
                    'review_document',
                    'review_data_upload',
                    'view_financial_reports',
                ],
            ],
            'cfo' => [
                'name'             => 'Chief Financial Officer',
                'description'      => 'Company CFO with approval powers.',
                'is_global'        => 0,
                'priority'         => 15,
                'default_redirect' => '/dashboard/finance',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'view_financial_reports',
                    'process_claims',
                    'approve_disbursal',
                    'manage_payment_batches',
                    'review_document',
                    'approve_document',
                ],
            ],
            'ministry' => [
                'name'             => 'Ministry Viewer',
                'description'      => 'Ministry users with cross-company reporting access.',
                'is_global'        => 1,
                'priority'         => 25,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_global',
                    'view_financial_reports',
                    'view_claims',
                    'manage_claims',
                    'download_claim_documents',
                    'export_claims',
                    'export_mis',
                ],
            ],
            'mis_coordinator' => [
                'name'             => 'MIS Coordinator',
                'description'      => 'MIS coordinators handling reporting and uploads.',
                'is_global'        => 0,
                'priority'         => 30,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'upload_pension_data',
                    'upload_medical_data',
                    'manage_bulk_imports',
                    'review_data_upload',
                    'export_mis',
                ],
            ],
            'department_staff' => [
                'name'             => 'Department Staff',
                'description'      => 'Departmental staff with limited operational rights.',
                'is_global'        => 0,
                'priority'         => 40,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'upload_pension_data',
                    'upload_medical_data',
                ],
            ],
            'isa_view' => [
                'name'             => 'ISA Viewer',
                'description'      => 'Implementation support agency (view only).',
                'is_global'        => 1,
                'priority'         => 50,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'download_claim_documents',
                    'view_isa_dashboard',
                ],
            ],
            'isa_ops' => [
                'name'             => 'ISA Operations',
                'description'      => 'ISA operations team handling uploads and registry.',
                'is_global'        => 1,
                'priority'         => 45,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'view_dashboard_company',
                    'view_claims',
                    'manage_claims',
                    'download_claim_documents',
                    'export_claims',
                    'export_mis',
                    'process_claims',
                    'view_isa_dashboard',
                    'review_hospital_request',
                    'approve_hospital_request',
                    'manage_hospital_registry',
                    'manage_diagnostic_registry',
                    'manage_bloodbank_registry',
                    'isa_bulk_upload',
                    'isa_manage_hospitals',
                    'upload_pension_data',
                    'upload_medical_data',
                    'manage_bulk_imports',
                    'review_data_upload',
                    'approve_data_upload',
                    'review_document',
                ],
            ],
            'branding_editor' => [
                'name'             => 'Branding Editor',
                'description'      => 'Manages portal branding assets.',
                'is_global'        => 0,
                'priority'         => 60,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'manage_branding_assets',
                ],
            ],
            'blog_editor' => [
                'name'             => 'Content Editor',
                'description'      => 'Creates blog/testimonial drafts.',
                'is_global'        => 0,
                'priority'         => 65,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'submit_blog',
                    'edit_blog',
                ],
            ],
            'content_reviewer' => [
                'name'             => 'Content Reviewer',
                'description'      => 'Approves and publishes content.',
                'is_global'        => 0,
                'priority'         => 55,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'approve_blog',
                    'publish_blog',
                    'manage_leadership_connect',
                    'review_document',
                    'approve_document',
                ],
            ],
            'workflow_approver' => [
                'name'             => 'Workflow Approver',
                'description'      => 'Handles workflow approvals and overrides.',
                'is_global'        => 0,
                'priority'         => 35,
                'default_redirect' => '/dashboard/v2',
                'permissions'      => [
                    'review_document',
                    'approve_document',
                    'override_workflow',
                ],
            ],
            'helpdesk_user' => [
                'name'             => 'Helpdesk User',
                'description'      => 'Helpdesk user with read-only beneficiary directory access.',
                'is_global'        => 0,
                'priority'         => 80,
                'default_redirect' => '/helpdesk/beneficiaries',
                'permissions'      => [
                    'view_beneficiary_profile_full',
                    'search_beneficiaries',
                    'download_beneficiary_pdf',
                ],
            ],
            'pensioner' => [
                'name'             => 'Pensioner',
                'description'      => 'Beneficiary self-service user.',
                'is_global'        => 0,
                'priority'         => 100,
                'default_redirect' => '/dashboard',
                'permissions'      => [
                    'edit_beneficiary_profile',
                    'manage_dependents',
                    'create_hospital_request',
                    'view_claims',
                    'download_claim_documents',
                    'view_beneficiary_profile_full',
                ],
            ],
        ];

        $roleRows = [];
        foreach ($roles as $slug => $role) {
            $roleRows[] = [
                'slug'             => $slug,
                'name'             => $role['name'],
                'description'      => $role['description'],
                'is_global'        => $role['is_global'],
                'is_assignable'    => 1,
                'priority'         => $role['priority'],
                'default_redirect' => $role['default_redirect'],
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }
        $this->db->table('roles')->ignore(true)->insertBatch($roleRows);

        // Lookup role IDs
        $roleMap = [];
        $query = $this->db->table('roles')->select('id, slug')->get();
        foreach ($query->getResultArray() as $row) {
            $roleMap[$row['slug']] = (int) $row['id'];
        }

        // role_permissions entries
        $rolePermissionRows = [];
        foreach ($roles as $slug => $role) {
            $roleId = $roleMap[$slug] ?? null;
            if (! $roleId) {
                continue;
            }

            foreach ($role['permissions'] as $permissionKey) {
                $permissionId = $permissionMap[$permissionKey] ?? null;
                if (! $permissionId) {
                    continue;
                }

                $rolePermissionRows[] = [
                    'role_id'        => $roleId,
                    'permission_id'  => $permissionId,
                    'granted_at'     => $now,
                    'granted_by'     => null,
                ];
            }
        }

        if (! empty($rolePermissionRows)) {
            $this->db->table('role_permissions')->ignore(true)->insertBatch($rolePermissionRows, 100);
        }

        $this->seedInitialUsers($now, $roleMap);
    }

    private function seedInitialUsers(string $now, array $roleMap): void
    {
        $companies = $this->db->table('companies')->select('id, code')->get()->getResultArray();
        $companyByCode = [];
        foreach ($companies as $company) {
            $companyByCode[strtoupper($company['code'])] = (int) $company['id'];
        }

        $defaultPassword = (string) env('RBAC_DEFAULT_ADMIN_PASSWORD', 'ChangeMe@2025');
        $passwordHash    = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $adminUsers = [
            [
                'username'     => 'superadmin',
                'display_name' => 'MPPGCL Super Admin',
                'email'        => 'superadmin@mppgcl.in',
                'user_type'    => 'staff',
                'company_code' => 'MPPGCL',
                'roles'        => ['super_admin'],
                'company_scope'=> null,
            ],
            [
                'username'     => 'admin_mppgcl',
                'display_name' => 'MPPGCL Company Admin',
                'email'        => 'admin.mppgcl@mppgcl.in',
                'user_type'    => 'staff',
                'company_code' => 'MPPGCL',
                'roles'        => ['company_admin'],
                'company_scope'=> 'MPPGCL',
            ],
            [
                'username'     => 'admin_mppmcl',
                'display_name' => 'MPPMCL Company Admin',
                'email'        => 'admin.mppmcl@mppgcl.in',
                'user_type'    => 'staff',
                'company_code' => 'MPPMCL',
                'roles'        => ['company_admin'],
                'company_scope'=> 'MPPMCL',
            ],
            [
                'username'     => 'admin_mpptcl',
                'display_name' => 'MPPTCL Company Admin',
                'email'        => 'admin.mpptcl@mppgcl.in',
                'user_type'    => 'staff',
                'company_code' => 'MPPTCL',
                'roles'        => ['company_admin'],
                'company_scope'=> 'MPPTCL',
            ],
            [
                'username'     => 'isa_ops',
                'display_name' => 'ISA Operations',
                'email'        => 'isa.ops@mppgcl.in',
                'user_type'    => 'isa',
                'company_code' => null,
                'roles'        => ['isa_ops'],
                'company_scope'=> null,
            ],
            [
                'username'     => 'isa_view',
                'display_name' => 'ISA Viewer',
                'email'        => 'isa.view@mppgcl.in',
                'user_type'    => 'isa',
                'company_code' => null,
                'roles'        => ['isa_view'],
                'company_scope'=> null,
            ],
        ];

        foreach ($adminUsers as $admin) {
            $companyId = null;
            if (! empty($admin['company_code'])) {
                $code = strtoupper($admin['company_code']);
                $companyId = $companyByCode[$code] ?? null;
            }

            $user = $this->db->table('app_users')
                ->where('username', $admin['username'])
                ->get()
                ->getRowArray();

            if ($user) {
                $userId = (int) $user['id'];
            } else {
                $data = [
                    'username'             => $admin['username'],
                    'display_name'         => $admin['display_name'],
                    'bname'                => $admin['display_name'],
                    'email'                => $admin['email'],
                    'password'             => $passwordHash,
                    'user_type'            => $admin['user_type'] ?? 'staff',
                    'company_id'           => $companyId,
                    'status'               => 'active',
                    'force_password_reset' => 1,
                    'password_changed_at'  => null,
                    'last_login_at'        => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];

                $this->db->table('app_users')->insert($data);
                $userId = (int) $this->db->insertID();
            }

            $assignmentCompanyId = null;
            if (! empty($admin['company_scope'])) {
                $scopeCode = strtoupper((string) $admin['company_scope']);
                $assignmentCompanyId = $companyByCode[$scopeCode] ?? null;
            } elseif ($companyId !== null) {
                $assignmentCompanyId = $companyId;
            }

            foreach ($admin['roles'] as $roleSlug) {
                if (! isset($roleMap[$roleSlug])) {
                    continue;
                }

                $roleId = $roleMap[$roleSlug];

                $exists = $this->db->table('user_roles')
                    ->where([
                        'user_id'    => $userId,
                        'role_id'    => $roleId,
                        'company_id' => $assignmentCompanyId,
                        'revoked_at' => null,
                    ])
                    ->countAllResults();

                if ($exists > 0) {
                    continue;
                }

                $this->db->table('user_roles')->insert([
                    'user_id'    => $userId,
                    'role_id'    => $roleId,
                    'company_id' => $assignmentCompanyId,
                    'region_id'  => null,
                    'assigned_by'=> null,
                    'assigned_at'=> $now,
                    'metadata'   => null,
                ]);
            }
        }
    }
}



