<?php

use CodeIgniter\I18n\Time;

$detail            = $detail ?? [];
$request           = $detail['request'] ?? [];
$beneficiaryBefore = $detail['beneficiary']['before'] ?? [];
$beneficiaryBeforeDisplay = $detail['beneficiary']['before_display'] ?? $beneficiaryBefore;
$beneficiaryAfter  = $detail['beneficiary']['after'] ?? [];
$beneficiaryDiff   = $detail['beneficiary']['diff'] ?? [];
$dependentDiff     = $detail['dependents']['diff'] ?? [];
$summary           = $request['summary'] ?? ['beneficiary_changes' => 0, 'dependent_adds' => 0, 'dependent_updates' => 0, 'dependent_removals' => 0];

$status = strtolower($request['status'] ?? 'draft');

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

$formatField = static function (string $name): string {
    return ucwords(str_replace('_', ' ', $name));
};

?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Change Request Details</h1>
    <p class="text-muted mb-0">Compare the approved/submitted values with your original profile.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('enrollment/change-requests') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-left me-1"></i>Back to History
    </a>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">Request Overview</h5>
        <dl class="row mb-0 small">
          <dt class="col-5 text-uppercase text-muted">Reference</dt>
          <dd class="col-7 fw-semibold"><?= esc($request['reference_number'] ?? ('CR-' . str_pad((string) ($request['id'] ?? 0), 6, '0', STR_PAD_LEFT))) ?></dd>

          <dt class="col-5 text-uppercase text-muted">Status</dt>
          <dd class="col-7">
            <span class="badge <?= $statusBadge[$status] ?? 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
              <?= esc($statusLabels[$status] ?? ucfirst($status)) ?>
            </span>
          </dd>

          <dt class="col-5 text-uppercase text-muted">Submitted On</dt>
          <dd class="col-7"><?= $formatDate($request['requested_at'] ?? $request['created_at'] ?? null) ?></dd>

          <dt class="col-5 text-uppercase text-muted">Last Updated</dt>
          <dd class="col-7"><?= $formatDate($request['reviewed_at'] ?? $request['updated_at'] ?? null) ?></dd>

          <?php if (! empty($request['review_comment'])): ?>
            <dt class="col-5 text-uppercase text-muted">Reviewer Comment</dt>
            <dd class="col-7"><?= esc($request['review_comment']) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">Summary</h5>
        <ul class="list-unstyled mb-0 small">
          <li><strong><?= esc($summary['beneficiary_changes'] ?? 0) ?></strong> beneficiary fields updated</li>
          <li><strong><?= esc($summary['dependent_updates'] ?? 0) ?></strong> dependents modified</li>
          <li><strong><?= esc($summary['dependent_adds'] ?? 0) ?></strong> dependents added</li>
          <li><strong><?= esc($summary['dependent_removals'] ?? 0) ?></strong> dependents removed</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php if (empty($beneficiaryDiff) && empty($dependentDiff)): ?>
  <div class="alert alert-info">
    This change request does not contain any recorded differences. It may have been created before the diff tracking feature was enabled.
  </div>
<?php endif; ?>

<?php if (! empty($beneficiaryDiff)): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">Beneficiary Fields</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Field</th>
              <th style="width: 35%;">Current Value</th>
              <th style="width: 35%;">Submitted Value</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($beneficiaryDiff as $field => $change): ?>
              <?php
                $beforeValue = $change['before'] ?? null;
                $afterValue  = $change['after'] ?? null;
                if ($beforeValue === $afterValue) {
                    continue;
                }
                $displayBefore = $beforeValue;
                if (($displayBefore === null || $displayBefore === '') && isset($beneficiaryBeforeDisplay[$field])) {
                    $displayBefore = $beneficiaryBeforeDisplay[$field];
                }
              ?>
              <tr>
                <td class="text-uppercase text-muted small fw-semibold"><?= esc($formatField($field)) ?></td>
                <td><?= esc($displayBefore !== null && $displayBefore !== '' ? $displayBefore : '-') ?></td>
                <td class="fw-semibold text-success"><?= esc($afterValue !== null && $afterValue !== '' ? $afterValue : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (! empty($dependentDiff)): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">Dependents</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 18%;">Dependent</th>
              <th style="width: 12%;">Action</th>
              <th style="width: 35%;">Current Record</th>
              <th style="width: 35%;">Submitted Record</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dependentDiff as $change): ?>
              <?php
                $before = $change['before'] ?? null;
                $after  = $change['after'] ?? null;
                $name   = $before['first_name'] ?? $after['first_name'] ?? '-';
                $action = strtolower($change['action'] ?? 'update');
              ?>
              <tr>
                <td><?= esc($name) ?></td>
                <td>
                  <?php if ($action === 'add'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Add</span>
                  <?php elseif ($action === 'remove'): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Remove</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Update</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($before): ?>
                    <div class="small text-muted">
                      <div><?= esc($before['relationship'] ?? '-') ?></div>
                      <div>Coverage: <?= esc($before['is_health_dependant'] ?? '-') ?></div>
                      <div>Status: <?= esc($before['is_alive'] ?? '-') ?></div>
                      <div>DOB: <?= esc($before['date_of_birth'] ?? '-') ?></div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($after): ?>
                    <div class="small">
                      <div><?= esc($after['relationship'] ?? '-') ?></div>
                      <div>Coverage: <?= esc($after['is_health_dependant'] ?? '-') ?></div>
                      <div>Status: <?= esc($after['is_alive'] ?? '-') ?></div>
                      <div>DOB: <?= esc($after['date_of_birth'] ?? '-') ?></div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
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

<div class="d-flex justify-content-between mt-4">
  <a href="<?= site_url('enrollment/change-requests') ?>" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i>Back to History
  </a>
  <a href="<?= site_url('enrollment/edit') ?>" class="btn btn-primary">
    <i class="fa-solid fa-pen-to-square me-1"></i>Submit New Request
  </a>
</div>
<?= $this->endSection() ?>
