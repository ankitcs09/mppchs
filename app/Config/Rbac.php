<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * RBAC (Role Based Access Control) configuration stub.
 *
 * This file documents the canonical permission keys and role slugs
 * seeded by {@see \App\Database\Seeds\RbacSeeder}. Use this as a single
 * source of truth when adding middleware, feature flags, or UI guards.
 *
 * Runtime code should load permissions from the database; this config
 * exists to provide discoverability for developers and to enable
 * environment-specific overrides if required later.
 */
class Rbac extends BaseConfig
{
    /**
     * Permission categories mapped to the keys seeded in the database.
     *
     * @var array<string,string[]>
     */
    public array $permissionCatalogue = [
        'dashboard' => [
            'view_dashboard_company',
            'view_dashboard_global',
        ],
        'reports' => [
            'view_financial_reports',
            'export_mis',
        ],
        'claims' => [
            'view_claims',
            'manage_claims',
            'process_claims',
            'approve_disbursal',
            'manage_payment_batches',
            'download_claim_documents',
            'export_claims',
        ],
        'beneficiary' => [
            'view_beneficiary_profile_full',
            'edit_beneficiary_profile',
            'manage_dependents',
            'review_profile_update',
            'approve_profile_update',
            'approve_dependent_change',
            'search_beneficiaries',
            'download_beneficiary_pdf',
        ],
        'network' => [
            'create_hospital_request',
            'review_hospital_request',
            'approve_hospital_request',
            'manage_hospital_registry',
            'manage_diagnostic_registry',
            'manage_bloodbank_registry',
        ],
        'data' => [
            'upload_pension_data',
            'upload_medical_data',
            'manage_bulk_imports',
            'review_data_upload',
            'approve_data_upload',
        ],
        'content' => [
            'manage_branding_assets',
            'submit_blog',
            'edit_blog',
            'approve_blog',
            'publish_blog',
            'manage_leadership_connect',
        ],
        'admin' => [
            'manage_users_company',
            'manage_users_all',
            'assign_roles_company',
            'assign_roles_all',
            'view_audit_logs',
        ],
        'workflow' => [
            'review_document',
            'approve_document',
            'override_workflow',
        ],
        'isa' => [
            'view_isa_dashboard',
            'isa_bulk_upload',
            'isa_manage_hospitals',
        ],
    ];

    /**
     * Canonical role slugs seeded via the RBAC seeder.
     *
     * Modify the database seed (not this config) to add/remove roles;
     * this list exists purely for IDE discoverability and references
     * across the codebase.
     *
     * @var string[]
     */
    public array $roleSlugs = [
        'super_admin',
        'company_admin',
        'leadership',
        'rao',
        'cfo',
        'ministry',
        'mis_coordinator',
        'department_staff',
        'isa_view',
        'isa_ops',
        'branding_editor',
        'blog_editor',
        'content_reviewer',
        'workflow_approver',
        'pensioner',
        'helpdesk_user',
    ];
}
