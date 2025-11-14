<?php

$requests = $requests ?? [];
$status   = $status ?? 'pending';
$pager    = $pager ?? null;

$statusLabels = [
    'pending'    => 'Pending',
    'approved'   => 'Approved',
    'rejected'   => 'Rejected',
    'needs_info' => 'Needs Info',
    'draft'      => 'Draft',
];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Profile Change Requests</h1>
    <p class="page-heading__subtitle">
      Review beneficiary-submitted updates and take action to keep records current.
    </p>
  </div>
  <form class="d-flex gap-2 align-items-center" method="get">
    <label class="form-label text-muted mb-0 small text-uppercase">
      <i class="fa-solid fa-filter me-1"></i>Filter
    </label>
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="needs_info" <?= $status === 'needs_info' ? 'selected' : '' ?>>Needs Info</option>
      <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
      <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
    </select>
  </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Requests</h2>
    <p class="app-panel__subtitle">
      Check details, request additional information, or approve the submitted changes.
    </p>
  </header>
  <div class="table-surface table-stack-mobile">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Request #</th>
          <th scope="col">Beneficiary</th>
          <th scope="col">Submitted By</th>
          <th scope="col">Status</th>
          <th scope="col">Submitted</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No change requests found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($requests as $row): ?>
            <tr>
              <td data-label="Request #">#<?= esc($row['id']) ?></td>
              <td data-label="Beneficiary">
                <div class="fw-semibold"><?= esc($row['reference_number'] ?? 'Beneficiary #' . $row['beneficiary_v2_id']) ?></div>
                <div class="text-muted small"><?= esc($row['legacy_reference'] ?? '') ?></div>
              </td>
              <td data-label="Submitted By">
                <div class="fw-semibold"><?= esc($row['display_name'] ?? $row['username'] ?? '-') ?></div>
                <div class="text-muted small">User ID: <?= esc($row['user_id']) ?></div>
              </td>
              <td data-label="Status">
                <?php
                  $badgeClass = match ($row['status']) {
                      'approved'   => 'success',
                      'rejected'   => 'danger',
                      'needs_info' => 'warning text-dark',
                      default      => 'info',
                  };
                ?>
                <span class="badge bg-<?= $badgeClass ?>">
                  <?= esc($statusLabels[$row['status']] ?? ucfirst($row['status'])) ?>
                </span>
              </td>
              <td data-label="Submitted">
                <div><?= esc($row['requested_at'] ? date('d M Y H:i', strtotime($row['requested_at'])) : '-') ?></div>
                <?php if (! empty($row['reviewed_at'])): ?>
                  <div class="text-muted small">Reviewed <?= esc(date('d M Y H:i', strtotime($row['reviewed_at']))) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Actions" class="text-end">
                <a href="<?= site_url('admin/change-requests/' . $row['id']) ?>" class="btn btn-outline-primary btn-sm">
                  <i class="fa-regular fa-eye me-1"></i>View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager): ?>
    <footer class="d-flex justify-content-end mt-3">
      <?= $pager->links() ?>
    </footer>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>
