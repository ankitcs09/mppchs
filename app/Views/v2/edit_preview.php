<?php

$preview   = $preview ?? [];
$diff      = $preview['diff'] ?? [];
$summary   = $preview['summary'] ?? ['beneficiary_changes' => 0, 'dependent_adds' => 0, 'dependent_updates' => 0, 'dependent_removals' => 0];
$before    = $preview['before'] ?? [];
$after     = $preview['after'] ?? [];
$lookups   = $lookups ?? [];

$beneficiaryDiff = $diff['beneficiary'] ?? [];
$dependentDiff   = $diff['dependents'] ?? [];

$hasChanges = ! empty($beneficiaryDiff) || ! empty($dependentDiff);

$formatDiffValue = static function (string $field, $value) use ($lookups) {
    if ($value === null || $value === '') {
        return '-';
    }

    $map = $lookups[$field] ?? null;
    if (is_array($map)) {
        if (array_key_exists($value, $map)) {
            return $map[$value];
        }
        $stringKey = (string) $value;
        if (array_key_exists($stringKey, $map)) {
            return $map[$stringKey];
        }
    }

    return (string) $value;
};

?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Review Your Changes</h1>
    <p class="text-muted mb-0">Compare the proposed updates with your current profile before submitting.</p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! $hasChanges): ?>
  <div class="alert alert-info">
    No changes were detected compared to your current profile. You may go back and adjust the form if needed.
  </div>
<?php endif; ?>

<?php if ($hasChanges): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h5 class="fw-semibold mb-2">Summary</h5>
          <ul class="list-unstyled small mb-0">
            <li><strong><?= esc($summary['beneficiary_changes'] ?? 0) ?></strong> beneficiary fields updated</li>
            <li><strong><?= esc($summary['dependent_adds'] ?? 0) ?></strong> dependents added</li>
            <li><strong><?= esc($summary['dependent_updates'] ?? 0) ?></strong> dependents modified</li>
            <li><strong><?= esc($summary['dependent_removals'] ?? 0) ?></strong> dependents removed</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($beneficiaryDiff)): ?>
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-body-tertiary">
        <h5 class="mb-0">Beneficiary Details</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Field</th>
                <th>Current Value</th>
                <th>Proposed Value</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($beneficiaryDiff as $field => $change): ?>
                <tr>
                  <td class="text-uppercase small text-muted fw-semibold"><?= esc(str_replace('_', ' ', $field)) ?></td>
                  <td><?= esc($formatDiffValue($field, $change['before'] ?? null)) ?></td>
                  <td class="text-success fw-semibold"><?= esc($formatDiffValue($field, $change['after'] ?? null)) ?></td>
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
                <th style="width: 16%;">Dependent</th>
                <th style="width: 14%;">Action</th>
                <th style="width: 35%;">Current</th>
                <th style="width: 35%;">Proposed</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dependentDiff as $change): ?>
                <tr>
                  <td><?= esc($change['before']['first_name'] ?? $change['after']['first_name'] ?? '-') ?></td>
                  <td>
                    <?php if ($change['action'] === 'add'): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Add</span>
                    <?php elseif ($change['action'] === 'remove'): ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Remove</span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Update</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($change['before']): ?>
                      <div class="small text-muted">
                        <div><?= esc($formatDiffValue('relationship', $change['before']['relationship'] ?? null)) ?></div>
                        <div>Coverage: <?= esc($formatDiffValue('is_health_dependant', $change['before']['is_health_dependant'] ?? null)) ?></div>
                        <div>Status: <?= esc($formatDiffValue('is_alive', $change['before']['is_alive'] ?? null)) ?></div>
                        <div>Gender: <?= esc($formatDiffValue('gender', $change['before']['gender'] ?? null)) ?></div>
                        <div>Blood Group: <?= esc($formatDiffValue('blood_group_id', $change['before']['blood_group_id'] ?? null)) ?></div>
                        <div>DOB: <?= esc($formatDiffValue('date_of_birth', $change['before']['date_of_birth'] ?? null)) ?></div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($change['after']): ?>
                      <div class="small">
                        <div><?= esc($formatDiffValue('relationship', $change['after']['relationship'] ?? null)) ?></div>
                        <div>Coverage: <?= esc($formatDiffValue('is_health_dependant', $change['after']['is_health_dependant'] ?? null)) ?></div>
                        <div>Status: <?= esc($formatDiffValue('is_alive', $change['after']['is_alive'] ?? null)) ?></div>
                        <div>Gender: <?= esc($formatDiffValue('gender', $change['after']['gender'] ?? null)) ?></div>
                        <div>Blood Group: <?= esc($formatDiffValue('blood_group_id', $change['after']['blood_group_id'] ?? null)) ?></div>
                        <div>DOB: <?= esc($formatDiffValue('date_of_birth', $change['after']['date_of_birth'] ?? null)) ?></div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small">-</span>
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
<?php endif; ?>

<form method="post" action="<?= site_url('enrollment/edit/confirm') ?>" class="d-flex gap-2">
  <?= csrf_field() ?>
  <a class="btn btn-outline-secondary" href="<?= site_url('enrollment/edit') ?>">Go Back to Form</a>
  <button type="submit" class="btn btn-primary" <?= $hasChanges ? '' : 'disabled' ?>>Submit Changes</button>
</form>
<?= $this->endSection() ?>
