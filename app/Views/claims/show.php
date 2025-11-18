<?php

$claim = $claim ?? null;
if (! $claim) {
    echo '<p class="text-danger">Claim not found.</p>';
    return;
}

function renderAmount(?float $value): string
{
    if ($value === null) {
        return '-';
    }
    return '&#8377;&nbsp;' . number_format($value, 2);
}

$events = $claim['events'] ?? [];
$documents = $claim['documents'] ?? [];
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Claim <?= esc($claim['claim_reference']) ?></h1>
    <p class="page-heading__subtitle">Detailed status, amounts, timeline, and supporting documents.</p>
  </div>
  <a href="<?= site_url('claims') ?>" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
  <div class="col-lg-8">
    <section class="app-panel mb-3">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Overview</h2>
        <p class="app-panel__subtitle">Key attributes for this claim.</p>
      </header>
      <dl class="row gy-2 mb-0">
        <dt class="col-sm-4 text-muted">Status</dt>
        <dd class="col-sm-8">
          <span class="badge bg-secondary-subtle text-body">
            <?= esc($claim['status']['label'] ?? 'Unknown') ?>
          </span>
        </dd>
        <dt class="col-sm-4 text-muted">Type</dt>
        <dd class="col-sm-8"><?= esc($claim['type']['label'] ?? '-') ?></dd>
        <dt class="col-sm-4 text-muted">Claim date</dt>
        <dd class="col-sm-8"><?= esc($claim['dates']['claim'] ?? '-') ?></dd>
        <dt class="col-sm-4 text-muted">Hospital</dt>
        <dd class="col-sm-8">
          <div><?= esc($claim['hospital']['name'] ?? '-') ?></div>
          <div class="text-muted small"><?= esc($claim['hospital']['city'] ?? '') ?></div>
        </dd>
        <dt class="col-sm-4 text-muted">Diagnosis</dt>
        <dd class="col-sm-8"><?= esc($claim['diagnosis'] ?? '-') ?></dd>
        <dt class="col-sm-4 text-muted">Remarks</dt>
        <dd class="col-sm-8"><?= esc($claim['remarks'] ?? '-') ?></dd>
      </dl>
    </section>

    <section class="app-panel mb-3">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Financial summary</h2>
        <p class="app-panel__subtitle">Amounts recorded during claim processing.</p>
      </header>
      <div class="row g-3">
        <?php
          $amountCards = [
              'Claimed'     => $claim['amounts']['claimed'] ?? null,
              'Approved'    => $claim['amounts']['approved'] ?? null,
              'Cashless'    => $claim['amounts']['cashless'] ?? null,
              'Co-pay'      => $claim['amounts']['copay'] ?? null,
              'Non-payable' => $claim['amounts']['non_payable'] ?? null,
              'Reimbursed'  => $claim['amounts']['reimbursed'] ?? null,
          ];
        ?>
        <?php foreach ($amountCards as $label => $value): ?>
          <div class="col-md-4 col-sm-6">
            <div class="dashboard-card h-100">
              <span class="kpi-label"><?= esc($label) ?></span>
              <span class="kpi-value"><?= renderAmount($value) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="app-panel">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Timeline</h2>
        <p class="app-panel__subtitle">Sequential updates captured for this claim.</p>
      </header>
      <?php if (empty($events)): ?>
        <p class="text-muted mb-0">No timeline events captured yet.</p>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($events as $event): ?>
            <div class="timeline-step">
              <span class="timeline-dot"></span>
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                  <div class="fw-semibold"><?= esc($event['event_label'] ?? $event['event_code'] ?? 'Status update') ?></div>
                  <span class="badge bg-secondary-subtle text-body">
                    <?= esc($event['status']['label'] ?? '-') ?>
                  </span>
                </div>
                <div class="text-muted small">
                  <?= esc(isset($event['event_time']) ? date('d M Y H:i', strtotime($event['event_time'])) : '-') ?>
                </div>
              </div>
              <?php if (! empty($event['description'])): ?>
                <p class="text-muted small mb-0 mt-2"><?= esc($event['description']) ?></p>
              <?php endif; ?>
              <?php if (! empty($event['source'])): ?>
                <p class="text-muted small mb-0 mt-1">Source: <?= esc($event['source']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-4">
    <section class="app-panel app-panel--compact mb-3">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Beneficiary</h2>
        <p class="app-panel__subtitle">Who this claim is raised for.</p>
      </header>
      <dl class="row gy-2 mb-0">
        <dt class="col-5 text-muted">Name</dt>
        <dd class="col-7"><?= esc($claim['beneficiary']['name'] ?? '-') ?></dd>
        <dt class="col-5 text-muted">Reference</dt>
        <dd class="col-7"><?= esc($claim['beneficiary']['reference'] ?? '-') ?></dd>
        <dt class="col-5 text-muted">Mobile</dt>
        <dd class="col-7"><?= esc($claim['beneficiary']['mobile_masked'] ?? '-') ?></dd>
        <dt class="col-5 text-muted">Dependent</dt>
        <dd class="col-7"><?= esc($claim['dependent']['name'] ?? 'Primary Beneficiary') ?></dd>
      </dl>
    </section>

    <section class="app-panel app-panel--compact">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Documents</h2>
        <p class="app-panel__subtitle">Uploaded proofs and approvals.</p>
      </header>
      <?php if (empty($documents)): ?>
        <p class="text-muted mb-0">No documents linked yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($documents as $doc): ?>
            <li class="mb-3">
              <div class="fw-semibold"><?= esc($doc['title']) ?></div>
              <div class="text-muted small mb-2">
                <?= esc($doc['type']['label'] ?? 'Document') ?>
                <?php if (! empty($doc['uploaded_at'])): ?>
                  • <?= esc($doc['uploaded_at']) ?>
                <?php endif; ?>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <?php if (! empty($doc['download_url'])): ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?= esc($doc['download_url']) ?>">
                    Download
                  </a>
                <?php endif; ?>
                <?php if (! empty($doc['view_url'])): ?>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= esc($doc['view_url']) ?>" target="_blank">
                    View
                  </a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
