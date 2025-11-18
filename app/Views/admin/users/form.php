<?php

$userTypes = $userTypes ?? [];
$isEdit   = $isEdit ?? false;
$user     = $user ?? [];
$roles    = $roles ?? [];
$companies = $companies ?? [];
$assignedRoles = $assignedRoles ?? [];
$validation = $validation ?? \Config\Services::validation();
$context = $context ?? [];

$pageTitle = $isEdit ? 'Edit User' : 'Create User';
$pageSubtitle = $isEdit
    ? 'Update account details and permissions.'
    : 'Provide user details and assign appropriate roles.';

$currentUserId  = $context['user_id'] ?? null;
$isSelf         = $isEdit && $currentUserId === ($user['id'] ?? null);
$submittedRoles = $isSelf ? $assignedRoles : (array) old('roles', $assignedRoles);

$roleIndex = [];
foreach ($roles as $roleItem) {
    $roleIndex[$roleItem['slug']] = $roleItem;
}

$defaultRolePresets = [
    [
        'key'         => 'helpdesk_support',
        'label'       => 'Helpdesk support',
        'description' => 'Search beneficiaries, download PDFs, raise edit requests.',
        'roles'       => ['helpdesk_user'],
    ],
    [
        'key'         => 'content_author',
        'label'       => 'Content author',
        'description' => 'Create and edit stories/testimonials.',
        'roles'       => ['blog_editor'],
    ],
    [
        'key'         => 'content_reviewer',
        'label'       => 'Content reviewer',
        'description' => 'Approve and publish public updates.',
        'roles'       => ['content_reviewer'],
    ],
    [
        'key'         => 'company_admin',
        'label'       => 'Company admin',
        'description' => 'Manage company level dashboards, users, and approvals.',
        'roles'       => ['company_admin'],
    ],
    [
        'key'         => 'super_admin',
        'label'       => 'Super admin',
        'description' => 'Full platform access across all companies.',
        'roles'       => ['super_admin'],
    ],
    [
        'key'         => 'isa_operations',
        'label'       => 'ISA operations',
        'description' => 'ISA hospital onboarding and operational tools.',
        'roles'       => ['isa_ops'],
    ],
];

$rolePresets = $rolePresets ?? $defaultRolePresets;
$rolePresets = array_values(array_filter(array_map(static function (array $preset) use ($roleIndex) {
    $preset['roles'] = array_values(array_filter($preset['roles'], static fn ($slug) => isset($roleIndex[$slug])));

    return empty($preset['roles']) ? null : $preset;
}, $rolePresets)));

$selectedRoleDetails = array_values(array_filter(array_map(static function ($slug) use ($roleIndex) {
    return $roleIndex[$slug] ?? null;
}, $submittedRoles)));
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title"><?= esc($pageTitle) ?></h1>
    <p class="page-heading__subtitle"><?= esc($pageSubtitle) ?></p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('admin/users') ?>">Back to list</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->has('success')): ?>
  <div class="alert alert-success"><?= esc(session('success')) ?></div>
<?php elseif (session()->has('error')): ?>
  <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if ($validation->getErrors()): ?>
  <div class="alert alert-danger">
    <strong>Please fix the following:</strong>
    <ul class="mb-0">
      <?php foreach ($validation->getErrors() as $message): ?>
        <li><?= esc($message) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<section class="app-panel">
  <form method="post" action="<?= $isEdit ? site_url('admin/users/' . $user['id'] . '/update') : site_url('admin/users') ?>">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="_method" value="POST">
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
        <input
          type="text"
          class="form-control"
          id="username"
          name="username"
          value="<?= esc(old('username', $user['username'])) ?>"
          <?= $isEdit ? 'readonly disabled' : '' ?>
          required
        >
        <div class="form-text">Usernames are unique and cannot be changed later.</div>
      </div>
      <div class="col-md-4">
        <label for="display_name" class="form-label">Display name <span class="text-danger">*</span></label>
        <input
          type="text"
          class="form-control"
          id="display_name"
          name="display_name"
          value="<?= esc(old('display_name', $user['display_name'])) ?>"
          required
        >
      </div>
      <div class="col-md-4">
        <label for="user_type" class="form-label">User type <span class="text-danger">*</span></label>
        <select class="form-select" id="user_type" name="user_type" required>
          <?php foreach ($userTypes as $value => $label): ?>
            <option value="<?= esc($value) ?>" <?= old('user_type', $user['user_type']) === $value ? 'selected' : '' ?>>
              <?= esc($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="email" class="form-label">Email</label>
        <input
          type="email"
          class="form-control"
          id="email"
          name="email"
          value="<?= esc(old('email', $user['email'] ?? '')) ?>"
        >
      </div>
      <div class="col-md-4">
        <label for="mobile" class="form-label">Mobile</label>
        <input
          type="text"
          class="form-control"
          id="mobile"
          name="mobile"
          value="<?= esc(old('mobile', $user['mobile'] ?? '')) ?>"
        >
      </div>
      <div class="col-md-4">
        <label for="company_id" class="form-label">Company</label>
        <select class="form-select" id="company_id" name="company_id">
          <option value="">-- None / Global --</option>
          <?php foreach ($companies as $company): ?>
            <option
              value="<?= (int) $company['id'] ?>"
              <?= (string) old('company_id', $user['company_id']) === (string) $company['id'] ? 'selected' : '' ?>
            >
              <?= esc($company['name']) ?><?= $company['code'] ? ' (' . esc($company['code']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Required for staff and ISA accounts.</div>
      </div>

      <?php if ($isEdit): ?>
        <div class="col-md-4">
          <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
          <select class="form-select" id="status" name="status" required>
            <?php foreach (['active' => 'Active', 'locked' => 'Locked', 'disabled' => 'Disabled'] as $value => $label): ?>
              <option value="<?= $value ?>" <?= old('status', $user['status']) === $value ? 'selected' : '' ?>>
                <?= esc($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="status" value="active">
      <?php endif; ?>
    </div>

    <div class="mb-4" data-role="role-manager" data-role-self="<?= $isSelf ? '1' : '0' ?>">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <h2 class="h5 mb-1">Role assignments</h2>
          <p class="text-muted small mb-0">
            Assign one or more roles. Non-global roles inherit the user&rsquo;s company scope.
            <?php if ($isSelf): ?>
              <br><strong>Note:</strong> You cannot modify your own role assignments.
            <?php endif; ?>
          </p>
        </div>
        <?php if (! empty($rolePresets) && ! $isSelf): ?>
          <div class="text-end">
            <label for="rolePreset" class="form-label mb-1">Quick presets</label>
            <select class="form-select form-select-sm" id="rolePreset" data-role="role-preset">
              <option value="">Select preset</option>
              <?php foreach ($rolePresets as $preset): ?>
                <option
                  value="<?= esc($preset['key']) ?>"
                  data-roles='<?= esc(json_encode($preset['roles']), 'attr') ?>'
                >
                  <?= esc($preset['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Apply a preset to pre-select recommended roles.</div>
          </div>
        <?php endif; ?>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-lg-8">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 mb-0">Available roles</h3>
            <?php if (! $isSelf): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-role="role-clear">Clear all</button>
            <?php endif; ?>
          </div>
          <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach ($roles as $role): ?>
              <?php $checked = in_array($role['slug'], $submittedRoles, true); ?>
              <div class="col">
                <section
                  class="app-panel app-panel--compact role-card h-100<?= $checked ? ' border-primary border-2 shadow-sm' : '' ?>"
                  data-role="role-card"
                >
                  <div class="d-flex align-items-start gap-2">
                    <div class="form-check me-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        name="roles[]"
                        id="role_<?= (int) $role['id'] ?>"
                        value="<?= esc($role['slug']) ?>"
                        data-role="role-checkbox"
                        data-role-name="<?= esc($role['name']) ?>"
                        data-role-description="<?= esc($role['description'] ?? '') ?>"
                        <?= $checked ? 'checked' : '' ?>
                        <?= $isSelf ? 'disabled' : '' ?>
                      >
                    </div>
                    <div class="flex-grow-1">
                      <label class="fw-semibold d-block" for="role_<?= (int) $role['id'] ?>">
                        <?= esc($role['name']) ?>
                      </label>
                      <?php if (! empty($role['description'])): ?>
                        <p class="small text-muted mb-2"><?= esc($role['description']) ?></p>
                      <?php else: ?>
                        <p class="small text-muted mb-2">Grants related feature access.</p>
                      <?php endif; ?>
                      <div class="d-flex flex-wrap gap-2">
                        <?php if ((int) $role['is_global'] === 1): ?>
                          <span class="badge bg-info-subtle text-info-emphasis">Global</span>
                        <?php else: ?>
                          <span class="badge bg-light text-muted">Company scoped</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </section>
              </div>
            <?php endforeach; ?>

            <?php if (empty($roles)): ?>
              <div class="col">
                <div class="alert alert-warning mb-0">
                  No assignable roles are available. Contact the administrator.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <section class="app-panel app-panel--compact h-100">
            <h3 class="h6 mb-2">Selected roles</h3>
            <p class="text-muted small">A quick summary of what this user will be able to access.</p>
            <div
              class="text-muted small<?= empty($selectedRoleDetails) ? '' : ' d-none' ?>"
              data-role="role-summary-empty"
            >
              No roles selected yet.
            </div>
            <ul
              class="list-unstyled small<?= empty($selectedRoleDetails) ? ' d-none' : '' ?>"
              data-role="role-summary-list"
            >
              <?php foreach ($selectedRoleDetails as $detail): ?>
                <li class="mb-2">
                  <div class="fw-semibold"><?= esc($detail['name']) ?></div>
                  <div class="text-muted"><?= esc($detail['description'] ?? 'Grants access to relevant modules.') ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        </div>
      </div>

      <?php if ($isSelf && ! empty($assignedRoles)): ?>
        <?php foreach ($assignedRoles as $roleSlug): ?>
          <input type="hidden" name="roles[]" value="<?= esc($roleSlug) ?>">
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save changes' : 'Create user' ?></button>
      <?php if ($isEdit): ?>
        <button
          class="btn btn-outline-warning"
          type="submit"
          formaction="<?= site_url('admin/users/' . $user['id'] . '/force-reset') ?>"
          formmethod="post"
          formnovalidate
        >
          Generate temporary password
        </button>
      <?php endif; ?>
    </div>
  </form>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= base_url('assets/js/admin-user-form.js') ?>"></script>
<?= $this->endSection() ?>
