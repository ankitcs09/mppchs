<?php

$claim = $claim ?? null;
if (! $claim) {
    echo '<p class="text-danger">Claim not found.</p>';
    return;
}

function adminAmountCell(?float $value): string
{
    if ($value === null) {
        return '-';
    }
    return '&#8377;&nbsp;' . number_format($value, 2);
}
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
  .timeline {
    position: relative;
  }
  .timeline-step {
    position: relative;
    padding-bottom: 1.25rem;
  }
  .timeline-step:last-child {
    padding-bottom: 0;
  }
  .timeline-dot {
    position: absolute;
    left: -1.35rem;
    top: .4rem;
    width: .65rem;
    height: .65rem;
    border-radius: 50%;
    background-color: var(--bs-primary);
    box-shadow: 0 0 0 .15rem var(--bs-primary-bg-subtle);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Claim <?= esc($claim['claim_reference']) ?></h1>
    <p class="text-muted mb-0">Administrative view with beneficiary, policy and document context.</p>
  </div>
  <div>
    <a href="<?= site_url('admin/claims') ?>" class="btn btn-outline-secondary btn-sm">Back to registry</a>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="mb-1"><?= esc($claim['hospital']['name'] ?? '-') ?></h5>
            <div class="text-muted small">
              <?= esc($claim['hospital']['city'] ?? '-') ?>, <?= esc($claim['hospital']['state'] ?? '-') ?>
            </div>
          </div>
          <span class="badge bg-secondary-subtle text-body fs-6">
            <?= esc($claim['status']['label'] ?? 'Unknown') ?>
          </span>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Claim type</small>
            <div class="fw-semibold"><?= esc($claim['type']['label'] ?? '-') ?></div>
          </div>
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Claim date</small>
            <div class="fw-semibold"><?= esc($claim['dates']['claim'] ?? '-') ?></div>
          </div>
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Company</small>
            <div class="fw-semibold"><?= esc($claim['company']['name'] ?? '-') ?></div>
            <div class="text-muted small"><?= esc($claim['company']['code'] ?? '-') ?></div>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Admission</small>
            <div><?= esc($claim['dates']['admission'] ?? '-') ?></div>
          </div>
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Discharge</small>
            <div><?= esc($claim['dates']['discharge'] ?? '-') ?></div>
          </div>
          <div class="col-md-4">
            <small class="text-muted text-uppercase d-block mb-1">Source</small>
            <div><?= esc($claim['source']['channel'] ?? '-') ?></div>
            <div class="text-muted small"><?= esc($claim['source']['reference'] ?? '-') ?></div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-muted text-uppercase d-block mb-1">Diagnosis</small>
          <p class="mb-0"><?= esc($claim['diagnosis'] ?? '-') ?></p>
        </div>
        <?php if (! empty($claim['remarks'])): ?>
          <div class="mt-2 alert alert-info bg-info-subtle border-0">
            <strong>TPA remarks:</strong> <?= esc($claim['remarks']) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Financials</h6>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded-3 p-3 bg-body-secondary-subtle">
              <small class="text-muted text-uppercase d-block mb-1">Claimed</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['claimed']) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded-3 p-3">
              <small class="text-muted text-uppercase d-block mb-1">Approved</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['approved']) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded-3 p-3">
              <small class="text-muted text-uppercase d-block mb-1">Cashless</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['cashless']) ?></div>
            </div>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <div class="border rounded-3 p-3">
              <small class="text-muted text-uppercase d-block mb-1">Co-pay</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['copay']) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded-3 p-3">
              <small class="text-muted text-uppercase d-block mb-1">Non-payable</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['non_payable']) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded-3 p-3">
              <small class="text-muted text-uppercase d-block mb-1">Reimbursed</small>
              <div class="fw-semibold fs-5"><?= adminAmountCell($claim['amounts']['reimbursed']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Timeline</h6>
        <?php $events = $claim['events'] ?? []; ?>
        <?php if (empty($events)): ?>
          <p class="text-muted small mb-0">No timeline events recorded.</p>
        <?php else: ?>
          <div class="timeline border-start border-2 border-primary-subtle ps-4">
            <?php foreach ($events as $event): ?>
              <div class="timeline-step">
                <span class="timeline-dot"></span>
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold"><?= esc($event['event_label'] ?? $event['event_code'] ?? 'Status update') ?></div>
                    <span class="badge bg-secondary-subtle text-body">
                      <?= esc($event['status']['label'] ?? '-') ?>
                    </span>
                  </div>
                  <div class="text-muted small ms-3">
                    <?= esc(isset($event['event_time']) ? date('d M Y H:i', strtotime($event['event_time'])) : '-') ?>
                  </div>
                </div>
                <?php if (! empty($event['description'])): ?>
                  <div class="text-muted small mt-1"><?= esc($event['description']) ?></div>
                <?php endif; ?>
                <div class="text-muted small mt-1">
                  Source: <?= esc($event['source'] ?? 'not specified') ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Beneficiary context</h6>
        <dl class="row mb-0">
          <dt class="col-5">Name</dt>
          <dd class="col-7"><?= esc($claim['beneficiary']['name'] ?? '-') ?></dd>
          <dt class="col-5">Reference #</dt>
          <dd class="col-7"><?= esc($claim['beneficiary']['reference'] ?? '-') ?></dd>
          <dt class="col-5">Mobile</dt>
          <dd class="col-7"><?= esc($claim['beneficiary']['mobile_masked'] ?? '-') ?></dd>
          <dt class="col-5">Dependent</dt>
          <dd class="col-7"><?= esc($claim['dependent']['name'] ?? 'Primary Beneficiary') ?></dd>
        </dl>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Policy</h6>
        <dl class="row mb-0">
          <dt class="col-5">Policy #</dt>
          <dd class="col-7"><?= esc($claim['policy']['policy_number'] ?? '-') ?></dd>
          <dt class="col-5">Card #</dt>
          <dd class="col-7"><?= esc($claim['policy']['card_number'] ?? '-') ?></dd>
          <dt class="col-5">Program</dt>
          <dd class="col-7"><?= esc($claim['policy']['program'] ?? '-') ?></dd>
          <dt class="col-5">Provider</dt>
          <dd class="col-7"><?= esc($claim['policy']['provider'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Documents</h6>
        <ul class="list-group list-group-flush">
          <?php if (empty($claim['documents'])): ?>
            <li class="list-group-item text-muted small">No documents mapped to this claim.</li>
          <?php endif; ?>
          <?php foreach ($claim['documents'] as $doc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold"><?= esc($doc['title']) ?></div>
                <div class="text-muted small">
                  <?= esc($doc['type']['label'] ?? 'Document') ?>
                  <?php if (! empty($doc['uploaded_at'])): ?>
                    | <?= esc($doc['uploaded_at']) ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (! empty($doc['storage']['is_supported'])): ?>
                <a href="<?= site_url('admin/claims/' . $claim['id'] . '/documents/' . $doc['id']) ?>" class="btn btn-sm btn-outline-primary">
                  Download
                </a>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-body">Offline</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="small text-muted mt-2 mb-0">Secure download links will be activated once the document streamer is wired.</p>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
