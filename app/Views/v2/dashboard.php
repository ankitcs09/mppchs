<?php
$activeNav = $activeNav ?? 'dashboard-v2';
$snapshot = $snapshot ?? [];

$coveredDependents      = $snapshot['dependentsCovered'] ?? [];
$otherDependents        = $snapshot['dependentsOthers'] ?? [];
$actionCenter           = $snapshot['actionCenter'] ?? ['beneficiary' => [], 'dependents' => []];
$beneficiaryIdentifiers = $snapshot['beneficiaryIdentifiers'] ?? [];
$dependentIdentifiers   = $snapshot['dependentIdentifiers'] ?? [];

$profileCompletion = (int) ($snapshot['profileCompletion'] ?? 0);
$missingMessages   = $snapshot['missingMessages'] ?? [];
$contact           = $snapshot['contact'] ?? [];
$addressLines      = $snapshot['address'] ?? [];
$planOption        = $snapshot['planOption'] ?? null;
$category          = $snapshot['category'] ?? null;
$beneficiary       = $snapshot['beneficiary'] ?? [];
$referenceNumber   = $beneficiary['reference_number'] ?? null;
$legacyReference   = $beneficiary['legacy_reference'] ?? null;

$coveredNames = array_values(array_filter(array_map(static function (array $row): ?string {
    $name     = trim((string) ($row['name'] ?? ''));
    $relation = trim((string) ($row['relationship'] ?? ''));
    if ($name !== '' && $relation !== '') {
        return $name . ' - ' . $relation;
    }
    return $name !== '' ? $name : ($relation !== '' ? $relation : null);
}, $coveredDependents)));

$primaryMobile   = $contact['mobile'] ?? null;
$alternateMobile = $contact['alternate'] ?? null;
$emailAddress    = $contact['email'] ?? null;

$addressDisplay = $addressLines ? implode(', ', array_filter(array_map('trim', $addressLines))) : null;

$beneficiaryActions = $actionCenter['beneficiary'] ?? [];
$dependentActions   = $actionCenter['dependents'] ?? [];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Welcome<?= isset($beneficiary['first_name']) ? ', ' . esc($beneficiary['first_name']) : '' ?></h1>
    <p class="text-muted mb-0">Here is your latest cashless benefit snapshot.</p>
  </div>
  <div class="text-end">
    <?php if ($snapshot['lastUpdatedHuman'] ?? null): ?>
      <div><small class="text-muted">Last updated <?= esc($snapshot['lastUpdatedHuman']) ?></small></div>
    <?php endif; ?>
    <div class="mt-3" style="min-width: 220px;">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted text-uppercase fw-semibold">Profile completion</small>
        <small class="fw-semibold"><?= $profileCompletion ?>%</small>
      </div>
      <div class="progress" style="height: 8px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, max(0, $profileCompletion)) ?>%;"></div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <small class="text-uppercase text-muted fw-semibold">Scheme Tier</small>
        <h3 class="mt-2 mb-1"><?= esc($planOption ?? 'Not selected') ?></h3>
        <p class="text-muted mb-0"><?= esc($category ?? 'Category not specified') ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <small class="text-uppercase text-muted fw-semibold">Contact &amp; Address</small>
        <ul class="list-unstyled mt-2 mb-0 text-muted small">
          <li><strong>Mobile:</strong> <?= esc($primaryMobile ?? 'Not provided') ?></li>
          <li><strong>Alternate:</strong> <?= esc($alternateMobile ?? 'Not provided') ?></li>
          <li><strong>Email:</strong> <?= esc($emailAddress ?? 'Not provided') ?></li>
          <li class="mt-2"><strong>Address:</strong> <?= esc($addressDisplay ?? 'Not provided') ?></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <small class="text-uppercase text-muted fw-semibold d-flex justify-content-between align-items-center">
          Dependents Covered
          <span class="badge bg-primary-subtle text-primary"><?= count($coveredDependents) ?></span>
        </small>
        <?php if (! empty($coveredNames)): ?>
          <ul class="list-unstyled mt-2 mb-0 text-muted small">
            <?php foreach (array_slice($coveredNames, 0, 4) as $label): ?>
              <li><?= esc($label) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if (count($coveredNames) > 4): ?>
            <small class="text-muted d-block mt-1">+<?= count($coveredNames) - 4 ?> more family member(s)</small>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted small mt-2 mb-0">No health dependents recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title mb-3">Action Center</h5>
        <?php if ($beneficiaryActions === [] && $dependentActions === []): ?>
          <p class="text-muted mb-3">All mandatory information is in place. No follow-up required.</p>
        <?php else: ?>
          <?php if (! empty($beneficiaryActions)): ?>
            <h6 class="fw-semibold small text-uppercase text-muted">Beneficiary</h6>
            <ul class="text-muted small">
              <?php foreach ($beneficiaryActions as $item): ?>
                <li><?= esc($item) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if (! empty($dependentActions)): ?>
            <h6 class="fw-semibold small text-uppercase text-muted">Dependents</h6>
            <ul class="list-unstyled text-muted small">
              <?php foreach ($dependentActions as $dependent): ?>
                <li class="mb-2">
                  <strong><?= esc($dependent['label']) ?>:</strong>
                  <ul class="mt-1 mb-0 text-muted small">
                    <?php foreach ($dependent['items'] as $item): ?>
                      <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endif; ?>
        <a class="btn btn-outline-primary btn-sm mt-2" href="<?= site_url('cashless_form_view_readonly_datatable') ?>">View My Submitted Form</a>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title mb-3">Personal Identifiers</h5>
        <?php $beneficiaryFilled = array_filter($beneficiaryIdentifiers, static fn ($value) => trim((string) $value) !== 'Not provided'); ?>
        <?php if (! empty($beneficiaryFilled)): ?>
          <dl class="row mb-0 small">
            <?php foreach ($beneficiaryFilled as $label => $value): ?>
              <dt class="col-sm-5 text-muted text-uppercase"><?= esc($label) ?></dt>
              <dd class="col-sm-7 mb-2"><?= esc($value) ?></dd>
            <?php endforeach; ?>
          </dl>
        <?php else: ?>
          <p class="text-muted small mb-0">No beneficiary identifiers recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title mb-3">Dependent Identifiers</h5>
        <?php if (! empty($dependentIdentifiers)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dependentIdentifiers as $entry): ?>
              <li class="list-group-item px-0">
                <strong><?= esc($entry['label']) ?></strong>
                <ul class="mt-1 mb-0 text-muted small">
                  <?php foreach ($entry['fields'] as $fieldLabel => $fieldValue): ?>
                    <li><span class="fw-semibold"><?= esc($fieldLabel) ?>:</span> <?= esc($fieldValue) ?></li>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted mb-0">No dependent identifiers recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Reference Details</h5>
        <ul class="list-unstyled text-muted small mb-0">
          <li><strong>Reference Number:</strong> <?= esc($referenceNumber ?? 'Not assigned') ?></li>
          <li><strong>Legacy Reference:</strong> <?= esc($legacyReference ?? 'Not available') ?></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title mb-3">Helpful Resources</h5>
        <ul class="mb-3">
          <li><a href="<?= site_url('cashless_form_view_readonly_datatable') ?>">Review submitted cashless form</a></li>
          <li><a href="<?= site_url('dashboard/cashless-form/edit') ?>">Update cashless form</a></li>
          <li><a href="<?= site_url('hospitals/request') ?>">Request hospital addition</a></li>
          <li><a href="<?= site_url('hospitals') ?>">Browse empanelled hospitals</a></li>
        </ul>
        <p class="text-muted small mb-0">Need support? Email <a href="mailto:support@mppgcl.in">support@mppgcl.in</a> or call the helpline.</p>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
