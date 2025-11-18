<?php

$entries       = $entries ?? [];
$filters       = array_merge(['type' => null, 'status' => null, 'q' => ''], $filters ?? []);
$typeOptions   = $typeOptions ?? [];
$statusOptions = $statusOptions ?? [];
$statusLabels  = $statusLabels ?? [];
$canEdit       = $canEdit ?? false;
$canPublish    = $canPublish ?? false;
$canApprove    = $canApprove ?? false;
$currentUserId = $currentUserId ?? null;

$statusIcons = [
    'draft'     => 'fa-regular fa-file-lines',
    'review'    => 'fa-solid fa-hourglass-half',
    'approved'  => 'fa-solid fa-circle-check',
    'published' => 'fa-solid fa-bullhorn',
    'archived'  => 'fa-solid fa-box-archive',
];

$statusBadge = static function (?string $status) use ($statusLabels, $statusIcons): string {
    $status = strtolower((string) $status);
    $label  = $statusLabels[$status] ?? ucfirst($status);
    $icon   = $statusIcons[$status] ?? 'fa-regular fa-file-lines';

    $class = match ($status) {
        'published' => 'badge bg-success-subtle text-success',
        'review'    => 'badge bg-warning-subtle text-warning',
        'archived'  => 'badge bg-secondary-subtle text-secondary',
        default     => 'badge bg-light text-muted',
    };

    return '<span class="' . esc($class) . '"><i class="' . esc($icon) . ' me-1"></i>' . esc($label) . '</span>';
};

$typeBadge = static function (string $type) use ($typeOptions): string {
    $label = $typeOptions[$type] ?? ucfirst($type);
    return '<span class="badge bg-primary-subtle text-primary">' . esc($label) . '</span>';
};
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Stories & Testimonials</h1>
    <p class="page-heading__subtitle">Share programme updates and beneficiary voices on the public site.</p>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= site_url('admin/content/create') ?>">
    <i class="fa-solid fa-plus me-1"></i>Create entry
  </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Content library</h2>
    <p class="app-panel__subtitle mb-0">Filter and manage existing stories and testimonials.</p>
  </header>

  <form class="row gy-2 gx-3 align-items-end mb-4" method="get">
    <div class="col-md-3">
      <label class="form-label" for="filterType">Type</label>
      <select class="form-select" id="filterType" name="type">
        <option value="">All types</option>
        <?php foreach ($typeOptions as $value => $label): ?>
          <option value="<?= esc($value) ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>>
            <?= esc($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= esc($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
            <?= esc($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="filterSearch">Search</label>
      <input
        type="text"
        class="form-control"
        id="filterSearch"
        name="q"
        value="<?= esc($filters['q']) ?>"
        placeholder="Title, summary, or author"
      >
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1" type="submit">
        <i class="fa-solid fa-filter me-1"></i>Apply
      </button>
      <a class="btn btn-outline-secondary" href="<?= site_url('admin/content') ?>">
        <i class="fa-solid fa-rotate-left me-1"></i>Reset
      </a>
    </div>
  </form>

  <?php if (empty($entries)): ?>
    <div class="text-center text-muted py-5">
      <p class="mb-1">No content entries found.</p>
      <p class="small mb-0">Use the create button above to add your first story or testimonial.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Status</th>
            <th>Updated</th>
            <th>Published</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
              <?php
                $publishedAt   = $entry['published_at'] ? date('d M Y', strtotime($entry['published_at'])) : '—';
                $updatedAt     = $entry['updated_at'] ? date('d M Y', strtotime($entry['updated_at'])) : '—';
                $isOwner       = $currentUserId !== null && (int) $entry['created_by'] === (int) $currentUserId;
                $entryStatus   = strtolower((string) ($entry['status_slug'] ?? $entry['status']));
                $canEditRow    = $canEdit && ($isOwner || $canApprove || $canPublish);
                $canViewEntry  = $canEditRow || $canApprove || $canPublish;
              ?>
              <tr>
                <td>
                <div class="fw-semibold"><?= esc($entry['title']) ?></div>
                <?php if (! empty($entry['author_name'])): ?>
                  <div class="small text-muted">By <?= esc($entry['author_name']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= $typeBadge($entry['type']) ?></td>
                <td><?= $statusBadge($entryStatus) ?></td>
              <td><?= esc($updatedAt) ?></td>
              <td><?= esc($publishedAt) ?></td>
              <td class="text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                  <?php
                    $entryUrl = site_url('admin/content/' . $entry['id'] . '/edit');
                    if ($entryStatus === 'review' && $canApprove) {
                        echo '<a class="btn btn-sm btn-outline-primary" href="' . esc($entryUrl) . '"><i class="fa-solid fa-clipboard-check me-1"></i>Review</a>';
                    } elseif ($canViewEntry) {
                        $label = $canEditRow ? 'Edit' : 'View';
                        $icon  = $canEditRow ? 'fa-solid fa-pen-to-square' : 'fa-regular fa-eye';
                        echo '<a class="btn btn-sm btn-outline-primary" href="' . esc($entryUrl) . '"><i class="' . esc($icon) . ' me-1"></i>' . esc($label) . '</a>';
                    }
                  ?>

                  <?php if ($entryStatus === 'approved' && $canPublish): ?>
                    <form method="post" action="<?= site_url('admin/content/' . $entry['id'] . '/publish') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-success" type="submit">
                        <i class="fa-solid fa-bullhorn me-1"></i>Publish
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($canEditRow && $entryStatus !== 'archived'): ?>
                    <form method="post" action="<?= site_url('admin/content/' . $entry['id'] . '/archive') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-secondary" type="submit">
                        <i class="fa-solid fa-box-archive me-1"></i>Archive
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>
