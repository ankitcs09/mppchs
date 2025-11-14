<?php

namespace App\Services;

use Config\Services;

class NavigationService
{
    /**
     * Builds the navigation items array used by the default layout.
     */
    public function items(): array
    {
        $rbac = null;
        try {
            $rbac = Services::rbac();
        } catch (\Throwable $exception) {
            $rbac = null;
        }

        $can = static function (string $permission) use ($rbac): bool {
            if ($rbac === null) {
                return false;
            }

            if ($rbac->hasPermission($permission)) {
                return true;
            }

            if (str_ends_with($permission, '_company')) {
                $fallback = substr($permission, 0, -strlen('_company')) . '_all';
                return $rbac->hasPermission($fallback);
            }

            return false;
        };

        $items = [];

        $baseItems = [
            [
                'id'    => 'dashboard',
                'label' => 'Dashboard',
                'href'  => base_url('dashboard'),
                'icon'  => 'fa-solid fa-gauge-high',
            ],
            [
                'id'         => 'view-submitted-form',
                'label'      => 'View My Submitted Form',
                'href'       => base_url('cashless_form_view_readonly_datatable'),
                'icon'       => 'fa-solid fa-file-lines',
                'permission' => 'view_beneficiary_profile_full',
            ],
            [
                'id'         => 'edit-profile',
                'label'      => 'Edit My Details',
                'href'       => site_url('enrollment/edit'),
                'icon'       => 'fa-solid fa-pen-to-square',
                'permission' => 'edit_beneficiary_profile',
            ],
            [
                'id'         => 'change-requests',
                'label'      => 'My Change Requests',
                'href'       => site_url('enrollment/change-requests'),
                'icon'       => 'fa-solid fa-list-check',
                'permission' => 'edit_beneficiary_profile',
            ],
            [
                'id'         => 'claims',
                'label'      => 'My Claims',
                'href'       => site_url('claims'),
                'icon'       => 'fa-solid fa-clipboard-list',
                'permission' => 'view_claims',
            ],
            [
                'id'    => 'view-hospitals',
                'label' => 'View Hospital List',
                'href'  => site_url('hospitals'),
                'icon'  => 'fa-solid fa-hospital',
            ],
            [
                'id'         => 'request-hospital',
                'label'      => 'Request Hospital Addition',
                'href'       => site_url('hospitals/request'),
                'icon'       => 'fa-solid fa-circle-plus',
                'permission' => 'create_hospital_request',
            ],
            [
                'id'    => 'change-password',
                'label' => 'Change Password',
                'href'  => base_url('user/change-password'),
                'icon'  => 'fa-solid fa-key',
            ],
        ];

        foreach ($baseItems as $item) {
            if (isset($item['permission']) && ! $can($item['permission'])) {
                continue;
            }

            $items[] = $item;
        }

        if ($can('manage_users_company') || $can('manage_users_all')) {
            $items[] = [
                'id'    => 'manage-users',
                'label' => 'Manage Users',
                'href'  => site_url('admin/users'),
                'icon'  => 'fa-solid fa-users-gear',
            ];
        }

        if ($can('submit_blog') || $can('edit_blog') || $can('publish_blog')) {
            $items[] = [
                'id'    => 'content-library',
                'label' => 'Stories & Testimonials',
                'href'  => site_url('admin/content'),
                'icon'  => 'fa-solid fa-newspaper',
            ];
        }

        if ($can('search_beneficiaries')) {
            $items[] = [
                'id'    => 'helpdesk-directory',
                'label' => 'Beneficiary Directory',
                'href'  => site_url('helpdesk/beneficiaries'),
                'icon'  => 'fa-solid fa-address-book',
            ];
        }

        if ($can('manage_claims') || $can('process_claims')) {
            $items[] = [
                'id'    => 'admin-claims',
                'label' => 'Claims Registry',
                'href'  => site_url('admin/claims'),
                'icon'  => 'fa-solid fa-file-medical',
            ];
        }

        if ($can('review_profile_update')) {
            $items[] = [
                'id'    => 'manage-change-requests',
                'label' => 'Change Requests',
                'href'  => site_url('admin/change-requests'),
                'icon'  => 'fa-solid fa-clipboard-check',
            ];
        }

        return $items;
    }
}
