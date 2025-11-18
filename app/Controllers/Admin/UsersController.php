<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Traits\AuthorizationTrait;
use App\Services\Admin\UserManagementService;
use App\Services\RbacService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class UsersController extends BaseController
{
    use AuthorizationTrait;

    private UserManagementService $userService;
    private RbacService $rbac;

    public function __construct()
    {
        $this->userService = new UserManagementService();
        $this->rbac        = Services::rbac();
    }

    public function index()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $context = $this->rbac->context();

        $userTypes     = $this->userService->userTypeOptions();
        $statusOptions = [
            'active'   => 'Active',
            'locked'   => 'Locked',
            'disabled' => 'Disabled',
        ];
        $companies = $this->userService->accessibleCompanies($context);

        $typeParam    = $this->request->getGet('type');
        $statusParam  = $this->request->getGet('status');
        $companyParam = $this->request->getGet('company');

        $typeFilter = is_string($typeParam) && $typeParam !== '' && array_key_exists($typeParam, $userTypes)
            ? $typeParam
            : null;

        $statusFilter = is_string($statusParam) && $statusParam !== '' && array_key_exists($statusParam, $statusOptions)
            ? $statusParam
            : null;

        if ($companyParam === 'global') {
            $companyFilter = 'global';
        } elseif ($companyParam !== null && $companyParam !== '') {
            $companyFilter = (int) $companyParam;
        } else {
            $companyFilter = null;
        }

        $filters = [
            'type'    => $typeFilter,
            'status'  => $statusFilter,
            'company' => $companyFilter,
        ];

        $result = $this->userService->listUsers($context, $search, $filters);

        return view('admin/users/index', [
            'pageinfo'        => [
                'apptitle'    => 'Manage Users',
                'appdashname' => 'Admin',
            ],
            'users'           => $result['users'],
            'roleAssignments' => $result['roleAssignments'],
            'metrics'         => $result['metrics'] ?? [],
            'search'          => $search,
            'filters'         => $filters,
            'userTypes'       => $userTypes,
            'statusOptions'   => $statusOptions,
            'companies'       => $companies,
            'context'         => $context,
        ]);
    }

    public function create()
    {
        $context    = $this->rbac->context();
        $validation = Services::validation();

        if (session()->has('errors')) {
            foreach ((array) session('errors') as $field => $message) {
                $validation->setError((string) $field, (string) $message);
            }
        }

        $options = $this->userService->formOptions($context);

        return view('admin/users/form', [
            'pageinfo'      => [
                'apptitle'    => 'Create User',
                'appdashname' => 'Admin',
            ],
            'companies'     => $options['companies'],
            'roles'         => $options['roles'],
            'userTypes'     => $options['userTypes'],
            'validation'    => $validation,
            'isEdit'        => false,
            'context'       => $context,
            'user'          => [
                'username'     => '',
                'display_name' => '',
                'email'        => '',
                'mobile'       => '',
                'user_type'    => 'staff',
                'company_id'   => null,
                'status'       => 'active',
            ],
            'assignedRoles' => [],
        ]);
    }

    public function store(): RedirectResponse
    {
        $context    = $this->rbac->context();
        $validation = Services::validation();
        $userTypes  = $this->userService->userTypeOptions();

        $rules = [
            'username'     => 'required|min_length[3]|max_length[100]|is_unique[app_users.username]',
            'display_name' => 'required|min_length[3]|max_length[150]',
            'email'        => 'permit_empty|valid_email|max_length[190]',
            'mobile'       => 'permit_empty|max_length[30]',
            'user_type'    => 'required|in_list[' . implode(',', array_keys($userTypes)) . ']',
            'company_id'   => 'permit_empty|integer',
        ];

        if (! $validation->setRules($rules)->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userType  = (string) $this->request->getPost('user_type');
        $companyId = $this->userService->resolveCompanyId($context, $this->request->getPost('company_id'), $userType);

        if ($this->userService->requiresCompany($userType) && $companyId === null) {
            return redirect()->back()->withInput()->with('errors', ['company_id' => 'Please select a company.']);
        }

        $result = $this->userService->createUser([
            'username'     => trim((string) $this->request->getPost('username')),
            'display_name' => trim((string) $this->request->getPost('display_name')),
            'email'        => trim((string) $this->request->getPost('email')) ?: null,
            'mobile'       => trim((string) $this->request->getPost('mobile')) ?: null,
            'user_type'    => $userType,
            'company_id'   => $companyId,
            'roles'        => (array) $this->request->getPost('roles'),
        ], $context);

        return redirect()->to(site_url('admin/users'))
            ->with('success', 'User created successfully. Temporary password: ' . $result['temporary_password']);
    }

    public function edit(int $id)
    {
        $context    = $this->rbac->context();
        $validation = Services::validation();

        if (session()->has('errors')) {
            foreach ((array) session('errors') as $field => $message) {
                $validation->setError((string) $field, (string) $message);
            }
        }

        $user = $this->userService->findAccessibleUser($id, $context);
        if (! $user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found or access denied.');
        }

        $options  = $this->userService->formOptions($context);
        $assigned = $this->userService->currentRoleSlugs($id);

        return view('admin/users/form', [
            'pageinfo'      => [
                'apptitle'    => 'Edit User',
                'appdashname' => 'Admin',
            ],
            'user'          => $user,
            'companies'     => $options['companies'],
            'roles'         => $options['roles'],
            'userTypes'     => $options['userTypes'],
            'assignedRoles' => $assigned,
            'validation'    => $validation,
            'isEdit'        => true,
            'context'       => $context,
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $context = $this->rbac->context();
        $user    = $this->userService->findAccessibleUser($id, $context);

        if (! $user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found or access denied.');
        }

        $validation = Services::validation();
        $userTypes  = $this->userService->userTypeOptions();

        $rules = [
            'display_name' => 'required|min_length[3]|max_length[150]',
            'email'        => 'permit_empty|valid_email|max_length[190]',
            'mobile'       => 'permit_empty|max_length[30]',
            'user_type'    => 'required|in_list[' . implode(',', array_keys($userTypes)) . ']',
            'status'       => 'required|in_list[active,locked,disabled]',
            'company_id'   => 'permit_empty|integer',
        ];

        if (! $validation->setRules($rules)->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userType  = (string) $this->request->getPost('user_type');
        $companyId = $this->userService->resolveCompanyId($context, $this->request->getPost('company_id'), $userType, $user);

        if ($this->userService->requiresCompany($userType) && $companyId === null) {
            return redirect()->back()->withInput()->with('errors', ['company_id' => 'Please select a company.']);
        }

        $this->userService->updateUser($id, [
            'display_name' => trim((string) $this->request->getPost('display_name')),
            'email'        => trim((string) $this->request->getPost('email')) ?: null,
            'mobile'       => trim((string) $this->request->getPost('mobile')) ?: null,
            'user_type'    => $userType,
            'status'       => (string) $this->request->getPost('status'),
            'company_id'   => $companyId,
            'roles'        => (array) $this->request->getPost('roles'),
        ], $context);

        return redirect()->to(site_url('admin/users'))
            ->with('success', 'User updated successfully.');
    }

    public function forceReset(int $id): RedirectResponse
    {
        $context = $this->rbac->context();
        $user    = $this->userService->findAccessibleUser($id, $context);

        if (! $user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found or access denied.');
        }

        $tempPassword = $this->userService->forcePasswordReset($id);

        return redirect()->to(site_url('admin/users/' . $id . '/edit'))
            ->with('success', 'Temporary password generated: ' . $tempPassword);
    }
}

