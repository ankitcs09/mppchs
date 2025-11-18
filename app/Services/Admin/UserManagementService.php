<?php

namespace App\Services\Admin;

use App\Models\AppUserModel;
use App\Models\CompanyModel;
use App\Models\RoleModel;
use App\Models\UserRoleModel;
use App\Services\RbacService;
use Config\Services;

class UserManagementService
{
    private AppUserModel $users;
    private RoleModel $roles;
    private UserRoleModel $userRoles;
    private CompanyModel $companies;
    private RbacService $rbac;

    /**
     * Role slugs that only global administrators may alter.
     *
     * @var string[]
     */
    private array $protectedRoleSlugs = ['super_admin', 'company_admin'];

    /** @var array<int,bool> */
    private array $protectedUserCache = [];

    public function __construct(
        ?AppUserModel $users = null,
        ?RoleModel $roles = null,
        ?UserRoleModel $userRoles = null,
        ?CompanyModel $companies = null,
        ?RbacService $rbac = null
    ) {
        $this->users     = $users ?? new AppUserModel();
        $this->roles     = $roles ?? new RoleModel();
        $this->userRoles = $userRoles ?? new UserRoleModel();
        $this->companies = $companies ?? new CompanyModel();
        $this->rbac      = $rbac ?? Services::rbac();
    }

    /**
     * Returns the user listing and role assignments filtered by RBAC scope.
     *
     * @param array<string,mixed> $filters
     *
     * @return array{users:array<int,array>, roleAssignments:array<int,array>, metrics:array<string,mixed>}
     */
    public function listUsers(array $context, ?string $search, array $filters = []): array
    {
        $typeFilter    = $filters['type'] ?? null;
        $statusFilter  = $filters['status'] ?? null;
        $companyFilter = $filters['company'] ?? null;

        log_message(
            'debug',
            '[UserManagement] listUsers search="{search}" scope={scope} filters={filters}',
            [
                'search'  => $search,
                'scope'   => json_encode($context['company_ids'] ?? []),
                'filters' => json_encode($filters),
            ]
        );

        $companyScope = $this->allowedCompanyIds($context);

        $builder = $this->users->builder('app_users u')
            ->select('u.*, c.name AS company_name, c.code AS company_code')
            ->join('companies c', 'c.id = u.company_id', 'left')
            ->orderBy('u.created_at', 'DESC');

        if ($companyScope !== null) {
            if ($companyScope === []) {
                $builder->where('1 = 0');
            } else {
                $builder->whereIn('u.company_id', $companyScope);
            }
        }

        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('u.display_name', $search)
                ->orLike('u.email', $search)
                ->orLike('u.mobile', $search)
                ->groupEnd();
        }

        if (is_string($typeFilter) && $typeFilter !== '') {
            $builder->where('u.user_type', $typeFilter);
        }

        if (is_string($statusFilter) && $statusFilter !== '') {
            $builder->where('u.status', $statusFilter);
        }

        if ($companyFilter === 'global') {
            $builder->where('u.company_id IS NULL', null, false);
        } elseif (is_int($companyFilter)) {
            $builder->where('u.company_id', $companyFilter);
        }

        $users = $builder->get()->getResultArray();
        $roleAssignments = $this->loadRolesForUsers(array_column($users, 'id'));

        foreach ($users as &$user) {
            $user['can_edit'] = $this->canManageUserRecord($user, $context, $roleAssignments);
        }
        unset($user);

        $statusCounts = [
            'active'   => 0,
            'locked'   => 0,
            'disabled' => 0,
        ];
        $typeCounts = [];

        foreach ($users as $data) {
            $status = strtolower((string) ($data['status'] ?? 'unknown'));
            if (! array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;

            $type = strtolower((string) ($data['user_type'] ?? 'unknown'));
            if (! array_key_exists($type, $typeCounts)) {
                $typeCounts[$type] = 0;
            }
            $typeCounts[$type]++;
        }

        $metrics = [
            'total'  => count($users),
            'status' => $statusCounts,
            'type'   => $typeCounts,
        ];

        return [
            'users'           => $users,
            'roleAssignments' => $roleAssignments,
            'metrics'         => $metrics,
        ];
    }

    /**
     * Convenience wrapper returning the dropdown data required by the form.
     *
     * @return array{companies:array, roles:array, userTypes:array}
     */
    public function formOptions(array $context): array
    {
        return [
            'companies' => $this->accessibleCompanies($context),
            'roles'     => $this->availableRoles($context),
            'userTypes' => $this->userTypeOptions(),
        ];
    }

    /**
     * Persists a new user and assigns the requested roles.
     *
     * @return array{user_id:int, temporary_password:string}
     */
    public function createUser(array $data, array $context): array
    {
        $tempPassword = $this->generateTemporaryPassword();

        log_message(
            'debug',
            '[UserManagement] createUser username="{username}" type="{type}" company={company}',
            [
                'username' => $data['username'],
                'type'     => $data['user_type'],
                'company'  => $data['company_id'] ?? null,
            ]
        );

        $userId = $this->users->insert([
            'username'             => $data['username'],
            'display_name'         => $data['display_name'],
            'bname'                => $data['display_name'],
            'email'                => $data['email'] ?? null,
            'mobile'               => $data['mobile'] ?? null,
            'password'             => password_hash($tempPassword, PASSWORD_DEFAULT),
            'user_type'            => $data['user_type'],
            'company_id'           => $data['company_id'],
            'status'               => $data['status'] ?? 'active',
            'force_password_reset' => 1,
            'password_changed_at'  => null,
            'last_login_at'        => null,
            'session_version'      => 1,
        ]);

        $this->syncRoles((int) $userId, $data['roles'] ?? [], $data['company_id'] ?? null, $context);
        $this->rbac->clearContextCache();

        return [
            'user_id'            => (int) $userId,
            'temporary_password' => $tempPassword,
        ];
    }

    /**
     * Updates the supplied user and synchronises role assignments.
     */
    public function updateUser(int $id, array $data, array $context): void
    {
        $roleSlugs = $data['roles'] ?? [];

        log_message(
            'debug',
            '[UserManagement] updateUser id={id} type="{type}" company={company}',
            [
                'id'      => $id,
                'type'    => $data['user_type'],
                'company' => $data['company_id'] ?? null,
            ]
        );

        // Users cannot modify their own roles.
        if (($context['user_id'] ?? 0) === $id) {
            $roleSlugs = $this->currentRoleSlugs($id);
        }

        $this->users->update($id, [
            'display_name' => $data['display_name'],
            'bname'        => $data['display_name'],
            'email'        => $data['email'] ?? null,
            'mobile'       => $data['mobile'] ?? null,
            'user_type'    => $data['user_type'],
            'company_id'   => $data['company_id'],
            'status'       => $data['status'],
        ]);

        $this->syncRoles($id, $roleSlugs, $data['company_id'] ?? null, $context);
        $this->rbac->clearContextCache();
    }

    /**
     * Generates a temporary password for the supplied user.
     */
    public function forcePasswordReset(int $id): string
    {
        $tempPassword = $this->generateTemporaryPassword();

        log_message('debug', '[UserManagement] forcePasswordReset id={id}', ['id' => $id]);

        $current = $this->users->select('session_version')->find($id);
        $nextVersion = (int) ($current['session_version'] ?? 1) + 1;

        $this->users->update($id, [
            'password'             => password_hash($tempPassword, PASSWORD_DEFAULT),
            'force_password_reset' => 1,
            'password_changed_at'  => null,
            'session_version'      => $nextVersion,
        ]);

        $this->rbac->clearContextCache();

        return $tempPassword;
    }

    /**
     * Attempts to load a user the actor is permitted to view/manage.
     */
    public function findAccessibleUser(int $id, array $context): ?array
    {
        $user = $this->users->find($id);
        if (! $user) {
            return null;
        }

        if ($this->rbac->hasPermission('manage_users_all')) {
            return $user;
        }

        if ($this->userHasProtectedRole($id)) {
            return null;
        }

        $allowed = $this->allowedCompanyIds($context);
        $userCompany = $user['company_id'] ?? null;

        if ($allowed === null) {
            return $user;
        }

        if ($userCompany === null) {
            return null;
        }

        return in_array((int) $userCompany, $allowed, true) ? $user : null;
    }

    public function currentRoleSlugs(int $userId): array
    {
        $rows = $this->userRoles->builder()
            ->select('r.slug')
            ->from('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $userId)
            ->where('ur.revoked_at', null)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_column($rows, 'slug')));
    }

    public function userTypeOptions(): array
    {
        return [
            'staff'       => 'Staff / Administrator',
            'isa'         => 'Implementation Support Agency',
            'content'     => 'Content / Branding',
            'beneficiary' => 'Beneficiary',
        ];
    }

    public function availableRoles(array $context): array
    {
        $builder = $this->roles->builder()
            ->select('id, slug, name, description, is_global')
            ->where('is_assignable', 1)
            ->orderBy('priority', 'ASC');

        if (! $this->rbac->hasPermission('manage_users_all')) {
            $builder->where('is_global', 0);
        }

        return $builder->get()->getResultArray();
    }

    public function accessibleCompanies(array $context): array
    {
        $companies = $this->companies
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();

        $allowed = $this->allowedCompanyIds($context);
        if ($allowed === null) {
            return $companies;
        }

        return array_values(array_filter($companies, static fn ($company) => in_array((int) $company['id'], $allowed, true)));
    }

    public function resolveCompanyId(array $context, $companyInput, ?string $userType = null, ?array $existingUser = null): ?int
    {
        $companyId = $companyInput !== null && $companyInput !== '' ? (int) $companyInput : ($existingUser['company_id'] ?? null);

        if (! $this->rbac->hasPermission('manage_users_all')) {
            $allowed = $this->allowedCompanyIds($context) ?? [];
            if ($companyId !== null && ! in_array($companyId, $allowed, true)) {
                $companyId = null;
            }
        }

        $finalType = $userType ?? ($existingUser['user_type'] ?? 'staff');
        if ($this->requiresCompany($finalType) && $companyId === null) {
            return null;
        }

        return $companyId;
    }

    public function requiresCompany(string $userType): bool
    {
        return in_array($userType, ['staff', 'isa', 'content'], true);
    }

    /**
     * Determines which companies the actor is allowed to interact with.
     */
    private function allowedCompanyIds(array $context): ?array
    {
        if (! empty($context['has_global_scope'])) {
            return null;
        }

        return $context['company_ids'] ?? [];
    }

    private function loadRolesForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = $this->userRoles->builder()
            ->select('ur.user_id, r.name, r.slug')
            ->from('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->whereIn('ur.user_id', $userIds)
            ->where('ur.revoked_at', null)
            ->orderBy('r.priority', 'ASC')
            ->get()
            ->getResultArray();

        $assignment = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $assignment[$userId]['names'][] = $row['name'];
            $assignment[$userId]['slugs'][] = $row['slug'];
        }

        foreach ($assignment as &$data) {
            $data['names'] = array_values(array_unique($data['names'] ?? []));
            $data['slugs'] = array_values(array_unique($data['slugs'] ?? []));
        }
        unset($data);

        return $assignment;
    }

    private function canManageUserRecord(array $user, array $context, array $roleAssignments): bool
    {
        $actorId = (int) ($context['user_id'] ?? 0);
        $targetId = (int) ($user['id'] ?? 0);

        if ($this->rbac->hasPermission('manage_users_all')) {
            return true;
        }

        if ($this->userHasProtectedRole($targetId)) {
            return false;
        }

        if ($actorId === $targetId) {
            return false;
        }

        $assignment = $roleAssignments[$targetId]['slugs'] ?? [];
        if (! empty(array_intersect($assignment, $this->protectedRoleSlugs))) {
            return false;
        }

        $allowed = $this->allowedCompanyIds($context);
        if ($allowed === null) {
            return true;
        }

        $companyId = $user['company_id'] ?? null;
        if ($companyId === null) {
            return false;
        }

        return in_array((int) $companyId, $allowed, true);
    }

    private function userHasProtectedRole(int $userId): bool
    {
        if (! array_key_exists($userId, $this->protectedUserCache)) {
            $slugs = $this->currentRoleSlugs($userId);
            $this->protectedUserCache[$userId] = ! empty(array_intersect($slugs, $this->protectedRoleSlugs));
        }

        return $this->protectedUserCache[$userId];
    }

    private function syncRoles(int $userId, array $requestedSlugs, ?int $companyId, array $context): void
    {
        $requestedSlugs = array_values(array_unique(array_filter(array_map('trim', $requestedSlugs))));

        $existing = $this->currentRoleSlugs($userId);

        $toAdd    = array_diff($requestedSlugs, $existing);
        $toRemove = array_diff($existing, $requestedSlugs);

        $actorHasManageAll = $this->rbac->hasPermission('manage_users_all');
        $actorId           = (int) ($context['user_id'] ?? 0);
        $protected         = $this->protectedRoleSlugs;

        if (! empty($toAdd) && (! $actorHasManageAll || $actorId === $userId)) {
            $toAdd = array_diff($toAdd, $protected);
        }

        if (! empty($toRemove)) {
            $protectedToRemove = array_intersect($toRemove, $protected);
            if (! $actorHasManageAll || $actorId === $userId) {
                $toRemove = array_diff($toRemove, $protectedToRemove);
            }
        }

        if (! empty($toRemove)) {
            $roleIdsToRemove = $this->roles->whereIn('slug', $toRemove)->findColumn('id');
            if (! empty($roleIdsToRemove)) {
                $this->userRoles->builder()
                    ->set('revoked_at', utc_now())
                    ->where('user_id', $userId)
                    ->whereIn('role_id', $roleIdsToRemove)
                    ->where('revoked_at', null)
                    ->update();
            }
        }

        if (empty($toAdd)) {
            return;
        }

        $roleRows = $this->roles->builder()
            ->select('id, slug, is_global')
            ->whereIn('slug', $toAdd)
            ->get()
            ->getResultArray();

        foreach ($roleRows as $roleRow) {
            $assignmentCompany = $roleRow['is_global'] ? null : $companyId;

            if ($assignmentCompany === null && ! $roleRow['is_global']) {
                continue;
            }

            $this->userRoles->assign(
                $userId,
                (int) $roleRow['id'],
                $assignmentCompany,
                $actorId ?: null
            );
        }

        unset($this->protectedUserCache[$userId]);
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#%&';
        $password = '';
        $maxIndex = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }
}

