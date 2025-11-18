<?php

namespace App\Filters;

use App\Services\RbacService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Route filter that enforces RBAC permissions.
 *
 * Usage in routes:
 *     $routes->get('admin/dashboard', 'Admin::index', ['filter' => 'permission:view_dashboard_company']);
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $requested = $this->normalizeRequestedPermissions($arguments);
        if ($requested === []) {
            return null;
        }

        /** @var RbacService $rbac */
        $rbac = Services::rbac();
        foreach ($requested as $permissionKey) {
            if ($this->permissionSatisfied($rbac, $permissionKey)) {
                return null;
            }
        }

        log_message('debug', '[RBAC] Permission denied', [
            'requested'    => $requested,
            'context'      => $rbac->context(),
        ]);

        $response = Services::response();
        $response->setStatusCode(403);

        $accept = strtolower($request->getHeaderLine('Accept'));
        if ($request->isAJAX() || str_contains($accept, 'application/json')) {
            return $response->setJSON([
                'status'  => 403,
                'error'   => 'forbidden',
                'message' => 'You do not have permission to perform this action.',
            ]);
        }

        $message = 'You do not have permission to access this resource.';
        $locator = Services::locator();
        if ($locator->locateFile('errors/html/error_403', 'Views') !== null) {
            return $response->setBody(view('errors/html/error_403', ['message' => $message]));
        }

        return $response->setBody($message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }

    private function permissionSatisfied(RbacService $rbac, string $permissionKey): bool
    {
        if ($rbac->hasPermission($permissionKey)) {
            return true;
        }

        $alternates = $this->alternatePermissions($permissionKey);
        foreach ($alternates as $key) {
            if ($rbac->hasPermission($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns alternative permission keys that should satisfy the request.
     *
     * Example: requesting manage_users_company will also accept manage_users_all.
     */
    private function alternatePermissions(string $permissionKey): array
    {
        $alternates = [];

        if (str_ends_with($permissionKey, '_company')) {
            $alternates[] = substr($permissionKey, 0, -strlen('_company')) . '_all';
        } elseif (str_ends_with($permissionKey, '_all')) {
            $alternates[] = substr($permissionKey, 0, -strlen('_all')) . '_company';
        }

        return $alternates;
    }

    /**
     * @param array|null $arguments
     *
     * @return string[]
     */
    private function normalizeRequestedPermissions(?array $arguments): array
    {
        if ($arguments === null || $arguments === []) {
            return [];
        }

        $requested = [];
        foreach ($arguments as $index => $raw) {
            if ($index > 0) {
                $requested[] = $raw;
                continue;
            }

            foreach (explode('|', (string) $raw) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $requested[] = $part;
                }
            }
        }

        return array_values(array_unique($requested));
    }
}
