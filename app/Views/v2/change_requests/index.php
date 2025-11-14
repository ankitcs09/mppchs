<?php

use CodeIgniter\I18n\Time;

$requests = $requests ?? [];

$statusLabels = [
    'pending'    => 'Pending Review',
    'approved'   => 'Approved',
    'rejected'   => 'Rejected',
    'needs_info' => 'More Information Required',
    'draft'      => 'Draft',
];

$statusBadge = [
    'pending'    => 'bg-warning-subtle text-warning border border-warning-subtle',
    'approved'   => 'bg-success-subtle text-success border border-success-subtle',
    'rejected'   => 'bg-danger-subtle text-danger border border-danger-subtle',
    'needs_info' => 'bg-info-subtle text-info border border-info-subtle',
    'draft'      => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
];

$formatDate = static function (?string $value): string {
    if (empty($value)) {
        return '—';
    }

    try {
        return Time::parse($value)->toLocalizedString('dd MMM yyyy HH:mm');
    } catch (\Throwable $exception) {
        return esc($value);
    }
};

?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">My Change Requests</h1>
    <p class="text-muted mb-0">Track the status of profile update submissions, reviewer comments, and outcomes.</p>
  </div>
  <div>
    <a href="<?= site_url('enrollment/edit') ?>" class="btn btn-sm btn-primary">
      <i class="fa-solid fa-pen-to-square me-1"></i>Start New Change Request
    </a>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (empty($requests)): ?>
  <div class="alert alert-info">
    No change requests submitted yet. Use <strong>Edit My Details</strong> to submit updates to your profile.
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" style="width: 18%;">Reference</th>
              <th scope="col" style="width: 14%;">Status</th>
              <th scope="col" style="width: 18%;">Submitted</th>
              <th scope="col" style="width: 18%;">Last Updated</th>
              <th scope="col" style="width: 17%;">Summary</th>
              <th scope="col" style="width: 15%;">Reviewer Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $request): ?>
              <?php
                $status = strtolower($request['status'] ?? 'draft');
                $summary = $request['summary'] ?? [];
                $reference = $request['reference_number'] ?? ('CR-' . str_pad((string) $request['id'], 6, '0', STR_PAD_LEFT));
              ?>
              <tr>
                <td>
                  <a href="<?= site_url('enrollment/change-requests/' . $request['id']) ?>" class="fw-semibold text-decoration-none">
                    <?= esc($reference) ?>
                  </a>
                  <?php if (! empty($request['submission_no'])): ?>
                    <div class="text-muted small">Submission #<?= esc($request['submission_no']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $statusBadge[$status] ?? 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                    <?= esc($statusLabels[$status] ?? ucfirst($status)) ?>
                  </span>
                </td>
                <td><?= $formatDate($request['requested_at'] ?? $request['created_at'] ?? null) ?></td>
                <td><?= $formatDate($request['reviewed_at'] ?? $request['updated_at'] ?? null) ?></td>
                <td class="small text-muted">
                  <div><?= esc($summary['beneficiary_changes'] ?? 0) ?> field updates</div>
                  <div><?= esc($summary['dependent_updates'] ?? 0) ?> dependents updated</div>
                  <div><?= esc($summary['dependent_adds'] ?? 0) ?> added / <?= esc($summary['dependent_removals'] ?? 0) ?> removed</div>
                </td>
                <td class="small">
                  <?php if (! empty($request['review_comment'])): ?>
                    <span class="text-muted"><?= esc($request['review_comment']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>
