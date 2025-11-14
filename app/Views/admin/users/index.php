<?php

$search          = $search ?? '';
$users           = $users ?? [];
$roleAssignments = $roleAssignments ?? [];
$userTypes       = $userTypes ?? [];
$statusOptions   = $statusOptions ?? [
    'active'   => 'Active',
    'locked'   => 'Locked',
    'disabled' => 'Disabled',
];
$companies       = $companies ?? [];
$filters         = array_merge(['type' => null, 'status' => null, 'company' => null], $filters ?? []);
$metrics         = $metrics ?? [];

$statusTotals = array_merge(['active' => 0, 'locked' => 0, 'disabled' => 0], $metrics['status'] ?? []);
$typeTotals   = $metrics['type'] ?? [];
$totalCount   = (int) ($metrics['total'] ?? count($users));

$formatCount = static function (int $value): string {
    return number_format($value);
};

$typeLabels = $userTypes;
foreach ($typeTotals as $typeKey => $count) {
    if (! isset($typeLabels[$typeKey])) {
        $typeLabels[$typeKey] = ucwords(str_replace('_', ' ', (string) $typeKey));
    }
}

$selectedCompany = $filters['company'];

$buildTypeClass = static function (string $typeKey): string {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $typeKey));
    return 'user-type-tag user-type-tag--' . ($slug !== '' ? $slug : 'default');
};
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">User Management</h1>
    <p class="page-heading__subtitle mb-0">
      Create, edit, and monitor accounts across the cashless portal.
      <span class="badge rounded-pill bg-primary-subtle text-primary ms-2">
        <?= esc($formatCount($totalCount)) ?> users
      </span>
    </p>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= site_url('admin/users/create') ?>">
    <i class="fa-solid fa-user-plus me-1"></i>Create user
  </a>
</div>
<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Accounts</h2>
    <p class="app-panel__subtitle">Current users with contact, scope, and access details.</p>
  </header>
  <?php if ($users === []): ?>
    <div class="text-center text-muted py-5">No accounts found.</div>
  <?php else: ?>
    <?php $accordionId = 'userAccordion'; ?>
    <div class="user-accordion" id="<?= esc($accordionId, 'attr') ?>">
      <?php foreach ($users as $user): ?>
        <?php
          $assignment       = $roleAssignments[$user['id']] ?? ['names' => [], 'slugs' => []];
          $roleNames        = $assignment['names'] ?? [];
          $roleSlugs        = $assignment['slugs'] ?? [];
          $canEdit          = $user['can_edit'] ?? false;
          $typeKey          = strtolower((string) ($user['user_type'] ?? 'unknown'));
          $typeLabel        = $typeLabels[$typeKey] ?? ucwords(str_replace('_', ' ', $typeKey));
          $typeClass        = $buildTypeClass($typeKey);
          $statusKey        = strtolower((string) ($user['status'] ?? 'active'));
          $statusLabel      = ucfirst($statusKey);
          $statusClass      = match ($statusKey) {
              'active'   => 'user-status-pill user-status-pill--active',
              'locked'   => 'user-status-pill user-status-pill--warning',
              'disabled' => 'user-status-pill user-status-pill--danger',
              default    => 'user-status-pill user-status-pill--neutral',
          };
          $companyId        = $user['company_id'] ?? null;
          $companyName      = trim((string) ($user['company_name'] ?? ''));
          $companyCode      = trim((string) ($user['company_code'] ?? ''));
          if ($companyName === '' && $companyCode !== '') {
              $companyName = $companyCode;
              $companyCode = '';
          }
          $scopeLabel       = $companyId === null ? 'All companies' : ($companyName !== '' ? $companyName : '--');
          $scopeMeta        = $companyId === null ? 'Global role' : ($companyCode !== '' ? $companyCode : null);
          $lastLogin        = $user['last_login_at'] ?? null;
          $formattedLogin   = $lastLogin ? date('d M Y, H:i', strtotime($lastLogin)) : null;
          $visibleRoles     = array_slice($roleNames, 0, 3);
          $visibleSlugs     = array_slice($roleSlugs, 0, 3);
          $roleOverflow     = max(0, count($roleNames) - count($visibleRoles));
          $overflowSlugs    = array_slice($roleSlugs, count($visibleSlugs));
          $collapseId       = 'user-collapse-' . $user['id'];
          $headingId        = 'user-summary-' . $user['id'];
        ?>
        <article class="user-accordion__item">
          <div class="user-accordion__header" id="<?= esc($headingId, 'attr') ?>">
            <button
              class="user-accordion__toggle"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#<?= esc($collapseId, 'attr') ?>"
              aria-expanded="false"
              aria-controls="<?= esc($collapseId, 'attr') ?>"
            >
              <div class="user-accordion__summary">
                <div class="user-accordion__identity">
                  <span class="user-accordion__name"><?= esc($user['display_name'] ?? $user['username']) ?></span>
                  <span class="<?= esc($typeClass, 'attr') ?>"><?= esc($typeLabel) ?></span>
                  <?php if (! empty($user['email'])): ?>
                    <span class="user-accordion__meta"><?= esc($user['email']) ?></span>
                  <?php endif; ?>
                  <?php if (! empty($user['mobile'])): ?>
                    <span class="user-accordion__meta"><?= esc($user['mobile']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="user-accordion__scope">
                  <span class="user-accordion__scope-label"><?= esc($scopeLabel) ?></span>
                  <?php if ($scopeMeta): ?>
                    <span class="user-accordion__meta"><?= esc($scopeMeta) ?></span>
                  <?php endif; ?>
                </div>
                <div class="user-accordion__status">
                  <span class="<?= esc($statusClass, 'attr') ?>"><?= esc($statusLabel) ?></span>
                  <span class="user-accordion__meta">
                    <?= esc($formattedLogin ?? 'Never logged in') ?>
                  </span>
                </div>
                <span class="user-accordion__chevron" aria-hidden="true"></span>
              </div>
            </button>
          </div>
          <div
            id="<?= esc($collapseId, 'attr') ?>"
            class="collapse user-accordion__body"
            aria-labelledby="<?= esc($headingId, 'attr') ?>"
            data-bs-parent="#<?= esc($accordionId, 'attr') ?>"
          >
            <div class="user-accordion__body-inner">
              <div class="row g-4">
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Account</h3>
                    <dl>
                      <dt>Username</dt>
                      <dd><?= esc($user['username']) ?></dd>
                      <dt>Email</dt>
                      <dd><?= esc($user['email'] ?: '—') ?></dd>
                      <dt>Mobile</dt>
                      <dd><?= esc($user['mobile'] ?: '—') ?></dd>
                    </dl>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Scope &amp; Type</h3>
                    <dl>
                      <dt>Type</dt>
                      <dd><?= esc($typeLabel) ?></dd>
                      <dt>Scope</dt>
                      <dd>
                        <?= esc($scopeLabel) ?>
                        <?php if ($scopeMeta): ?>
                          <span class="user-info-muted"><?= esc($scopeMeta) ?></span>
                        <?php endif; ?>
                      </dd>
                      <dt>Status</dt>
                      <dd><span class="<?= esc($statusClass, 'attr') ?>"><?= esc($statusLabel) ?></span></dd>
                    </dl>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Roles assigned</h3>
                    <?php if ($visibleRoles === []): ?>
                      <p class="text-muted small mb-0">No roles assigned.</p>
                    <?php else: ?>
                      <div class="user-role-badges">
                        <?php foreach ($visibleRoles as $index => $roleName): ?>
                          <?php $slug = $visibleSlugs[$index] ?? $roleName; ?>
                          <span class="user-role-badge" title="<?= esc($slug) ?>"><?= esc($roleName) ?></span>
                        <?php endforeach; ?>
                        <?php if ($roleOverflow > 0): ?>
                          <span class="user-role-badge user-role-badge--muted" title="<?= esc(implode(', ', $overflowSlugs)) ?>">
                            +<?= esc($roleOverflow) ?> more
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="user-accordion__footer">
                <div class="user-accordion__footer-meta">
                  <span class="user-info-muted">Last login</span>
                  <strong><?= esc($formattedLogin ?? 'Never logged in') ?></strong>
                </div>
                <div class="user-accordion__actions">
                  <?php if ($canEdit): ?>
                    <a class="btn btn-primary btn-sm" href="<?= site_url('admin/users/' . $user['id'] . '/edit') ?>">
                      <i class="fa-solid fa-user-pen me-1"></i>Edit user
                    </a>
                  <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" type="button" disabled title="You do not have rights to modify this user">
                      <i class="fa-solid fa-lock me-1"></i>Edit user
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->has('success')): ?>
  <div class="alert alert-success"><?= esc(session('success')) ?></div>
<?php elseif (session()->has('error')): ?>
  <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>

<section class="app-panel app-panel--compact mb-3 user-filter-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Filter users</h2>
    <p class="app-panel__subtitle">
      Combine search, type, status, and scope filters to focus on the cohort you need to manage.
    </p>
  </header>
  <form method="get" class="row g-2 g-md-3 align-items-end user-filters">
    <div class="col-sm-6 col-lg-4">
      <label for="search" class="form-label small text-muted">Search</label>
      <input
        type="text"
        name="q"
        id="search"
        value="<?= esc($search) ?>"
        class="form-control form-control-sm"
        placeholder="Username, name, email, mobile"
      >
    </div>
    <div class="col-sm-6 col-md-4 col-lg-2">
      <label for="filter-type" class="form-label small text-muted">Type</label>
      <select name="type" id="filter-type" class="form-select form-select-sm">
        <option value=""<?= $filters['type'] === null ? ' selected' : '' ?>>All user types</option>
        <?php foreach ($userTypes as $value => $label): ?>
          <option value="<?= esc($value) ?>"<?= $filters['type'] === $value ? ' selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
        <?php foreach ($typeTotals as $typeKey => $_count): ?>
          <?php if (isset($userTypes[$typeKey])) { continue; } ?>
          <option value="<?= esc($typeKey) ?>"<?= $filters['type'] === $typeKey ? ' selected' : '' ?>><?= esc($typeLabels[$typeKey]) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6 col-md-4 col-lg-2">
      <label for="filter-status" class="form-label small text-muted">Status</label>
      <select name="status" id="filter-status" class="form-select form-select-sm">
        <option value=""<?= $filters['status'] === null ? ' selected' : '' ?>>Any status</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= esc($value) ?>"<?= $filters['status'] === $value ? ' selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6 col-md-4 col-lg-3">
      <label for="filter-company" class="form-label small text-muted">Scope</label>
      <select name="company" id="filter-company" class="form-select form-select-sm">
        <option value=""<?= $selectedCompany === null ? ' selected' : '' ?>>All accessible companies</option>
        <option value="global"<?= $selectedCompany === 'global' ? ' selected' : '' ?>>Global / multi-company roles</option>
        <?php foreach ($companies as $company): ?>
          <?php $companyLabel = trim(($company['name'] ?? '') . (! empty($company['code']) ? ' (' . $company['code'] . ')' : '')); ?>
          <option value="<?= esc((string) ($company['id'] ?? '')) ?>"<?= is_int($selectedCompany) && $selectedCompany === (int) $company['id'] ? ' selected' : '' ?>>
            <?= esc($companyLabel !== '' ? $companyLabel : 'Company #' . (string) ($company['id'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto d-flex gap-2">
      <button class="btn btn-primary btn-sm" type="submit">
        <i class="fa-solid fa-filter me-1"></i>Apply filters
      </button>
      <?php if ($search !== '' || $filters['type'] !== null || $filters['status'] !== null || $selectedCompany !== null): ?>
        <a class="btn btn-link btn-sm" href="<?= site_url('admin/users') ?>">
          <i class="fa-solid fa-rotate-left me-1"></i>Reset
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="row g-3 mb-3">
  <div class="col-12 col-xl-4">
    <div class="user-metric-card h-100">
      <p class="user-metric-card__title">Total accounts</p>
      <div class="user-metric-card__value"><?= esc($formatCount($totalCount)) ?></div>
      <p class="text-muted small mb-0">Matching the current filters.</p>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <span class="user-status-chip user-status-chip--active">Active <?= esc($formatCount((int) ($statusTotals['active'] ?? 0))) ?></span>
        <span class="user-status-chip user-status-chip--warning">Locked <?= esc($formatCount((int) ($statusTotals['locked'] ?? 0))) ?></span>
        <span class="user-status-chip user-status-chip--danger">Disabled <?= esc($formatCount((int) ($statusTotals['disabled'] ?? 0))) ?></span>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-8">
    <div class="user-metric-card h-100">
      <p class="user-metric-card__title">User type distribution</p>
      <?php if ($typeTotals === []): ?>
        <p class="text-muted small mb-0">No users found for the selected filters.</p>
      <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($typeTotals as $typeKey => $count): ?>
            <span class="user-type-chip" title="<?= esc($typeKey) ?>">
              <span><?= esc($typeLabels[$typeKey] ?? ucwords(str_replace('_', ' ', (string) $typeKey))) ?></span>
              <span class="user-type-chip__count"><?= esc($formatCount((int) $count)) ?></span>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Accounts</h2>
    <p class="app-panel__subtitle">Current users with contact, scope, and access details.</p>
  </header>
  <?php if ($users === []): ?>
    <div class="text-center text-muted py-5">No accounts found.</div>
  <?php else: ?>
    <?php $accordionId = 'userAccordion'; ?>
    <div class="user-accordion" id="<?= esc($accordionId, 'attr') ?>">
      <?php foreach ($users as $user): ?>
        <?php
          $assignment       = $roleAssignments[$user['id']] ?? ['names' => [], 'slugs' => []];
          $roleNames        = $assignment['names'] ?? [];
          $roleSlugs        = $assignment['slugs'] ?? [];
          $canEdit          = $user['can_edit'] ?? false;
          $typeKey          = strtolower((string) ($user['user_type'] ?? 'unknown'));
          $typeLabel        = $typeLabels[$typeKey] ?? ucwords(str_replace('_', ' ', $typeKey));
          $typeClass        = $buildTypeClass($typeKey);
          $statusKey        = strtolower((string) ($user['status'] ?? 'active'));
          $statusLabel      = ucfirst($statusKey);
          $statusClass      = match ($statusKey) {
              'active'   => 'user-status-pill user-status-pill--active',
              'locked'   => 'user-status-pill user-status-pill--warning',
              'disabled' => 'user-status-pill user-status-pill--danger',
              default    => 'user-status-pill user-status-pill--neutral',
          };
          $companyId        = $user['company_id'] ?? null;
          $companyName      = trim((string) ($user['company_name'] ?? ''));
          $companyCode      = trim((string) ($user['company_code'] ?? ''));
          if ($companyName === '' && $companyCode !== '') {
              $companyName = $companyCode;
              $companyCode = '';
          }
          $scopeLabel       = $companyId === null ? 'All companies' : ($companyName !== '' ? $companyName : '--');
          $scopeMeta        = $companyId === null ? 'Global role' : ($companyCode !== '' ? $companyCode : null);
          $lastLogin        = $user['last_login_at'] ?? null;
          $formattedLogin   = $lastLogin ? date('d M Y, H:i', strtotime($lastLogin)) : null;
          $visibleRoles     = array_slice($roleNames, 0, 3);
          $visibleSlugs     = array_slice($roleSlugs, 0, 3);
          $roleOverflow     = max(0, count($roleNames) - count($visibleRoles));
          $overflowSlugs    = array_slice($roleSlugs, count($visibleSlugs));
          $collapseId       = 'user-collapse-' . $user['id'];
          $headingId        = 'user-summary-' . $user['id'];
        ?>
        <article class="user-accordion__item">
          <div class="user-accordion__header" id="<?= esc($headingId, 'attr') ?>">
            <button
              class="user-accordion__toggle"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#<?= esc($collapseId, 'attr') ?>"
              aria-expanded="false"
              aria-controls="<?= esc($collapseId, 'attr') ?>"
            >
              <div class="user-accordion__summary">
                <div class="user-accordion__identity">
                  <span class="user-accordion__name"><?= esc($user['display_name'] ?? $user['username']) ?></span>
                  <span class="<?= esc($typeClass, 'attr') ?>"><?= esc($typeLabel) ?></span>
                  <?php if (! empty($user['email'])): ?>
                    <span class="user-accordion__meta"><?= esc($user['email']) ?></span>
                  <?php endif; ?>
                  <?php if (! empty($user['mobile'])): ?>
                    <span class="user-accordion__meta"><?= esc($user['mobile']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="user-accordion__scope">
                  <span class="user-accordion__scope-label"><?= esc($scopeLabel) ?></span>
                  <?php if ($scopeMeta): ?>
                    <span class="user-accordion__meta"><?= esc($scopeMeta) ?></span>
                  <?php endif; ?>
                </div>
                <div class="user-accordion__status">
                  <span class="<?= esc($statusClass, 'attr') ?>"><?= esc($statusLabel) ?></span>
                  <span class="user-accordion__meta">
                    <?= esc($formattedLogin ?? 'Never logged in') ?>
                  </span>
                </div>
                <span class="user-accordion__chevron" aria-hidden="true"></span>
              </div>
            </button>
          </div>
          <div
            id="<?= esc($collapseId, 'attr') ?>"
            class="collapse user-accordion__body"
            aria-labelledby="<?= esc($headingId, 'attr') ?>"
            data-bs-parent="#<?= esc($accordionId, 'attr') ?>"
          >
            <div class="user-accordion__body-inner">
              <div class="row g-4">
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Account</h3>
                    <dl>
                      <dt>Username</dt>
                      <dd><?= esc($user['username']) ?></dd>
                      <dt>Email</dt>
                      <dd><?= esc($user['email'] ?: '—') ?></dd>
                      <dt>Mobile</dt>
                      <dd><?= esc($user['mobile'] ?: '—') ?></dd>
                    </dl>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Scope &amp; Type</h3>
                    <dl>
                      <dt>Type</dt>
                      <dd><?= esc($typeLabel) ?></dd>
                      <dt>Scope</dt>
                      <dd>
                        <?= esc($scopeLabel) ?>
                        <?php if ($scopeMeta): ?>
                          <span class="user-info-muted"><?= esc($scopeMeta) ?></span>
                        <?php endif; ?>
                      </dd>
                      <dt>Status</dt>
                      <dd><span class="<?= esc($statusClass, 'attr') ?>"><?= esc($statusLabel) ?></span></dd>
                    </dl>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="user-info-block">
                    <h3>Roles assigned</h3>
                    <?php if ($visibleRoles === []): ?>
                      <p class="text-muted small mb-0">No roles assigned.</p>
                    <?php else: ?>
                      <div class="user-role-badges">
                        <?php foreach ($visibleRoles as $index => $roleName): ?>
                          <?php $slug = $visibleSlugs[$index] ?? $roleName; ?>
                          <span class="user-role-badge" title="<?= esc($slug) ?>"><?= esc($roleName) ?></span>
                        <?php endforeach; ?>
                        <?php if ($roleOverflow > 0): ?>
                          <span class="user-role-badge user-role-badge--muted" title="<?= esc(implode(', ', $overflowSlugs)) ?>">
                            +<?= esc($roleOverflow) ?> more
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="user-accordion__footer">
                <div class="user-accordion__footer-meta">
                  <span class="user-info-muted">Last login</span>
                  <strong><?= esc($formattedLogin ?? 'Never logged in') ?></strong>
                </div>
                <div class="user-accordion__actions">
                  <?php if ($canEdit): ?>
                    <a class="btn btn-primary btn-sm" href="<?= site_url('admin/users/' . $user['id'] . '/edit') ?>">
                      <i class="fa-solid fa-user-pen me-1"></i>Edit user
                    </a>
                  <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" type="button" disabled title="You do not have rights to modify this user">
                      <i class="fa-solid fa-lock me-1"></i>Edit user
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>







