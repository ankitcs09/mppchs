<?php

namespace App\Controllers\Traits;

use App\Exceptions\PageForbiddenException;
use App\Services\RbacService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;

/**
 * Shared authorisation helpers for controllers that need RBAC awareness.
 */
trait AuthorizationTrait
{
    private ?RbacService $authRbac = null;

    protected function ensureLoggedIn(): void
    {
        if (! isset($this->session) || ! $this->session->get('isLoggedIn')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    protected function isUserLoggedIn(): bool
    {
        if (! isset($this->session)) {
            return false;
        }

        return (bool) ($this->session->get('isLoggedIn') && $this->session->get('id'));
    }

    protected function currentUserId(): ?int
    {
        if (! $this->isUserLoggedIn()) {
            return null;
        }

        return (int) $this->session->get('id');
    }

    protected function resolveUserTable(): string
    {
        if (! isset($this->session)) {
            return 'app_users';
        }

        $table = $this->session->get('authUserTable');

        return $table ?: 'app_users';
    }

    protected function rbac(): ?RbacService
    {
        if ($this->authRbac !== null) {
            return $this->authRbac;
        }

        try {
            $this->authRbac = Services::rbac();
        } catch (\Throwable $exception) {
            log_message('debug', '[Auth] Unable to resolve RBAC service: {message}', ['message' => $exception->getMessage()]);
            $this->authRbac = null;
        }

        return $this->authRbac;
    }

    protected function enforcePermission(string $permission): void
    {
        $rbac = $this->rbac();
        if ($rbac === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $rbac->enforce($permission);
        } catch (PageForbiddenException $exception) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Ensures the user has at least one permission from the provided list.
     *
     * @param string[] $permissions
     */
    protected function enforceAnyPermission(array $permissions): void
    {
        $rbac = $this->rbac();
        if ($rbac === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        foreach ($permissions as $permission) {
            if ($permission !== '' && $rbac->hasPermission($permission)) {
                return;
            }
        }

        throw PageNotFoundException::forPageNotFound();
    }

    protected function companyScopeIds(): ?array
    {
        $rbac = $this->rbac();
        if ($rbac === null) {
            return null;
        }

        $context = $rbac->context();
        if (! empty($context['has_global_scope'])) {
            return null;
        }

        return $context['company_ids'] ?? [];
    }

    protected function enforceCompanyScope(?int $companyId): void
    {
        $scope = $this->companyScopeIds();
        if ($scope === null) {
            return;
        }

        if ($companyId === null || ! in_array($companyId, $scope, true)) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
