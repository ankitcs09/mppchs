<?php

namespace App\Services;

use App\Exceptions\PageForbiddenException;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * Centralised runtime helper for RBAC context.
 *
 * Loads the authenticated user's roles, permissions, and scoping rules
 * from the database and keeps them cached in the session. This service
 * is intentionally lightweight so controllers, filters, and views can
 * query permissions without repeating SQL.
 */
class RbacService
{
    private ConnectionInterface $db;
    private $session;

    /**
     * Local cache keyed by user id.
     *
     * @var array<int,array>
     */
    private array $contextCache = [];
    /**
     * Cache of role slug to id lookups.
     *
     * @var array<string,int>
     */
    private array $roleIdCache = [];

    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->db = $connection ?? Database::connect();
        $this->session = Services::session();
    }

    /**
     * Returns the current authenticated user's RBAC context.
     *
     * @param bool $refresh When true forces a reload from the database.
     *
     * @return array{user_id:int,roles:array,role_slugs:string[],permissions:string[],permission_set:array<string,bool>,company_map:array<int,array>,company_ids:int[],active_company_id:?int,has_global_scope:bool}
     */
    public function context(bool $refresh = false): array
    {
        $userId = (int) ($this->session->get('id') ?? 0);

        if ($userId <= 0) {
            return $this->emptyContext();
        }

        if (! $refresh && isset($this->contextCache[$userId])) {
            return $this->contextCache[$userId];
        }

        $sessionKey = 'rbac.context';
        if (! $refresh && $this->session->has($sessionKey)) {
            $context = $this->session->get($sessionKey);
            if (! empty($context['roles'])) {
                $this->contextCache[$userId] = $context;
                return $context;
            }
        }

        $context = $this->buildContextForUser($userId);
        $this->contextCache[$userId] = $context;
        $this->session->set($sessionKey, $context);

        return $context;
    }

    /**
     * Clears cached RBAC data for the active user.
     */
    public function clearContextCache(): void
    {
        $userId = (int) ($this->session->get('id') ?? 0);
        unset($this->contextCache[$userId]);
        $this->session->remove('rbac.context');
    }

    /**
     * Returns true if the user currently holds the provided permission key.
     */
    public function hasPermission(string $permissionKey): bool
    {
        $permissionKey = trim($permissionKey);
        if ($permissionKey === '') {
            return false;
        }

        $context = $this->context();
        return isset($context['permission_set'][$permissionKey]);
    }

    /**
     * Throws an HTTP 403 error when the permission check fails.
     *
     * @throws PageForbiddenException
     */
    public function enforce(string $permissionKey): void
    {
        if (! $this->hasPermission($permissionKey)) {
            throw PageForbiddenException::forPageForbidden('Forbidden: missing required permission.');
        }
    }

    /**
     * Returns the list of role slugs assigned to the user.
     *
     * @return string[]
     */
    public function roleSlugs(): array
    {
        $context = $this->context();
        return $context['role_slugs'];
    }

    public function hasRole(string $roleSlug): bool
    {
        return in_array($roleSlug, $this->roleSlugs(), true);
    }

    /**
     * Returns the active company scope for the session, or null when the user
     * is operating globally.
     */
    public function getActiveCompanyId(): ?int
    {
        $context = $this->context();
        return $context['active_company_id'];
    }

    /**
     * Updates the active company scope. Users with global scope may clear the
     * company (set to null); others must pick from their allowed list.
     */
    public function setActiveCompanyId(?int $companyId): void
    {
        $context = $this->context();

        if ($companyId === null) {
            if (! $context['has_global_scope']) {
                throw new RuntimeException('This account cannot clear company scope.');
            }

            $context['active_company_id'] = null;
            $this->storeContext($context);
            return;
        }

        if (! in_array($companyId, $context['company_ids'], true) && ! $context['has_global_scope']) {
            throw new RuntimeException('Company not available for this account.');
        }

        $context['active_company_id'] = $companyId;
        $this->storeContext($context);
    }

    /**
     * Returns information about companies accessible to the user.
     *
     * @return array<int,array{id:int,name:string,code:string,is_nodal:bool}>
     */
    public function companies(): array
    {
        $context = $this->context();
        return $context['company_map'];
    }

    private function storeContext(array $context): void
    {
        $userId = $context['user_id'] ?? 0;
        if ($userId) {
            $this->contextCache[$userId] = $context;
        }
        $this->session->set('rbac.context', $context);
        $this->session->set('rbac.active_company_id', $context['active_company_id']);
    }

    /**
     * Builds the RBAC data structure for the supplied user id.
     */
    private function buildContextForUser(int $userId): array
    {
        $roleRows = $this->fetchRoleRows($userId);

        if (empty($roleRows)) {
            if (! $this->maybeAssignDefaultBeneficiaryRole($userId)) {
                return $this->emptyContext($userId);
            }

            $roleRows = $this->fetchRoleRows($userId);
            if (empty($roleRows)) {
                return $this->emptyContext($userId);
            }
        }

        $roleIds = array_unique(array_column($roleRows, 'role_id'));

        $permissionRows = [];
        if (! empty($roleIds)) {
            $permissionRows = $this->db->table('role_permissions AS rp')
                ->select(['rp.role_id', 'p.key'])
                ->join('permissions AS p', 'p.id = rp.permission_id', 'inner')
                ->whereIn('rp.role_id', $roleIds)
                ->get()
                ->getResultArray();
        }

        $permissionSet = [];
        foreach ($permissionRows as $row) {
            $key = (string) $row['key'];
            if ($key !== '') {
                $permissionSet[$key] = true;
            }
        }

        $hasGlobalScope = false;
        $companyIds = [];
        foreach ($roleRows as $row) {
            if ((int) $row['is_global'] === 1 || $row['company_id'] === null) {
                $hasGlobalScope = true;
            }
            if ($row['company_id'] !== null) {
                $companyIds[] = (int) $row['company_id'];
            }
        }
        $companyIds = array_values(array_unique($companyIds));

        $companyBuilder = $this->db->table('companies')
            ->select(['id', 'name', 'code', 'is_nodal']);

        if (! $hasGlobalScope && ! empty($companyIds)) {
            $companyBuilder->whereIn('id', $companyIds);
        }

        $companyRows = $companyBuilder->get()->getResultArray();
        $companyMap = [];
        foreach ($companyRows as $company) {
            $companyMap[(int) $company['id']] = [
                'id'       => (int) $company['id'],
                'name'     => $company['name'],
                'code'     => $company['code'],
                'is_nodal' => (bool) $company['is_nodal'],
            ];
        }

        // Determine active company preference.
        $activeCompanyId = $this->session->get('rbac.active_company_id');

        if ($activeCompanyId !== null && ! is_numeric($activeCompanyId)) {
            $activeCompanyId = null;
        }

        if ($activeCompanyId !== null) {
            $activeCompanyId = (int) $activeCompanyId;
            if (! isset($companyMap[$activeCompanyId]) && ! $hasGlobalScope) {
                $activeCompanyId = null;
            }
        }

        if ($activeCompanyId === null && ! empty($companyMap) && ! $hasGlobalScope) {
            $activeCompanyId = array_key_first($companyMap);
        }

        $roleSlugs = array_values(array_unique(array_map(static fn ($row) => $row['slug'], $roleRows)));

        $context = [
            'user_id'           => $userId,
            'roles'             => $roleRows,
            'role_slugs'        => $roleSlugs,
            'permissions'       => array_keys($permissionSet),
            'permission_set'    => $permissionSet,
            'company_map'       => $companyMap,
            'company_ids'       => array_keys($companyMap),
            'active_company_id' => $activeCompanyId,
            'has_global_scope'  => $hasGlobalScope,
        ];

        return $context;
    }

    /**
     * Default structure used when no RBAC information is available.
     *
     * @return array{user_id:int,roles:array,role_slugs:string[],permissions:string[],permission_set:array<string,bool>,company_map:array<int,array>,company_ids:int[],active_company_id:?int,has_global_scope:bool}
     */
    private function emptyContext(int $userId = 0): array
    {
        return [
            'user_id'           => $userId,
            'roles'             => [],
            'role_slugs'        => [],
            'permissions'       => [],
            'permission_set'    => [],
            'company_map'       => [],
            'company_ids'       => [],
            'active_company_id' => null,
            'has_global_scope'  => false,
        ];
    }

    /**
     * Ensures the provided user has the given role assignment.
     */
    public function ensureRoleAssignment(int $userId, string $roleSlug, ?int $companyId = null): void
    {
        $userId = (int) $userId;
        if ($userId <= 0 || trim($roleSlug) === '') {
            return;
        }

        $roleId = $this->resolveRoleId($roleSlug);
        if ($roleId === null) {
            log_message('error', '[RBAC] Unknown role slug {slug} when ensuring assignment for user {user}', [
                'slug' => $roleSlug,
                'user' => $userId,
            ]);
            return;
        }

        $builder = $this->db->table('user_roles');
        $builder->where('user_id', $userId);
        $builder->where('role_id', $roleId);
        $builder->where('revoked_at', null);

        if ($companyId === null) {
            $builder->where('company_id', null);
        } else {
            $builder->where('company_id', (int) $companyId);
        }

        if ($builder->countAllResults() > 0) {
            return;
        }

        $this->db->table('user_roles')->insert([
            'user_id'     => $userId,
            'role_id'     => $roleId,
            'company_id'  => $companyId,
            'region_id'   => null,
            'assigned_by' => null,
            'assigned_at' => utc_now(),
            'metadata'    => null,
        ]);
    }

    private function fetchRoleRows(int $userId): array
    {
        return $this->db->table('user_roles AS ur')
            ->select([
                'ur.role_id',
                'ur.company_id',
                'ur.region_id',
                'ur.assigned_at',
                'r.slug',
                'r.name',
                'r.is_global',
                'r.priority',
                'r.default_redirect',
            ])
            ->join('roles AS r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $userId)
            ->where('ur.revoked_at IS NULL')
            ->orderBy('r.priority', 'ASC')
            ->orderBy('ur.assigned_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function resolveRoleId(string $roleSlug): ?int
    {
        $roleSlug = strtolower(trim($roleSlug));
        if ($roleSlug === '') {
            return null;
        }

        if (isset($this->roleIdCache[$roleSlug])) {
            return $this->roleIdCache[$roleSlug];
        }

        $row = $this->db->table('roles')
            ->select('id')
            ->where('slug', $roleSlug)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $this->roleIdCache[$roleSlug] = (int) $row['id'];

        return $this->roleIdCache[$roleSlug];
    }

    private function maybeAssignDefaultBeneficiaryRole(int $userId): bool
    {
        $user = $this->db->table('app_users')
            ->select(['id', 'user_type', 'beneficiary_v2_id', 'company_id'])
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (! $user) {
            return false;
        }

        $userType = strtolower((string) ($user['user_type'] ?? ''));
        $isBeneficiary = $userType === 'beneficiary' || ! empty($user['beneficiary_v2_id']);

        if (! $isBeneficiary) {
            return false;
        }

        try {
            $companyId = isset($user['company_id']) ? (int) $user['company_id'] : null;
            $this->ensureRoleAssignment($userId, 'pensioner', $companyId);
            return true;
        } catch (Throwable $exception) {
            log_message('error', '[RBAC] Failed to auto-assign pensioner role to user {user}: {message}', [
                'user'    => $userId,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
