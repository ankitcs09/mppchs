<?php
$dashboard       = $dashboard ?? [];
$meta            = $dashboard['meta'] ?? [];
$summary         = $dashboard['summary']['cards'] ?? [];
$queues          = $dashboard['actionQueues']['items'] ?? [];
$claims          = $dashboard['claims'] ?? [];
$network         = $dashboard['network'] ?? [];
$beneficiary     = $dashboard['beneficiary']['profile'] ?? [];
$pulse           = $dashboard['pulse'] ?? [];
$pulseMetrics    = $pulse['metrics'] ?? [];
$exceptions      = $dashboard['exceptions']['items'] ?? [];
$utilisation     = $dashboard['utilisation'] ?? [];
$utilStates      = $utilisation['states'] ?? [];
$uploads         = $dashboard['uploads']['items'] ?? [];
$support         = $dashboard['support'] ?? [];
$supportChange   = $support['changeRequests'] ?? [];
$supportHospital = $support['hospitalRequests'] ?? [];
$notes           = $dashboard['notes'] ?? [];

$scopeMeta      = $meta['scope'] ?? [];
$scopeOptions   = $scopeMeta['options'] ?? [];
$selectedScope  = $scopeMeta['selected'] ?? 'all';
$canFilterScope = ! empty($scopeMeta['canFilter']);
$narrative      = $meta['narrative'] ?? null;

$statusLabels = [
    'registered'         => 'Registered',
    'preauth_pending'    => 'Pre-auth Pending',
    'preauth_approved'   => 'Pre-auth Approved',
    'query_raised'       => 'Query Raised',
    'processing'         => 'Processing',
    'approved'           => 'Approved',
    'partially_approved' => 'Partially Approved',
    'settled'            => 'Settled',
    'rejected'           => 'Rejected',
    'closed'             => 'Closed',
];

$nowHuman = isset($meta['generatedAt']) ? date('d M Y, h:i A', strtotime($meta['generatedAt'])) : null;

$formatNumber = static function ($value): string {
    if ($value === null) {
        return '-';
    }
    $value = (float) $value;
    if ($value >= 10000000) {
        return number_format($value / 10000000, 1) . ' Cr';
    }
    if ($value >= 100000) {
        return number_format($value / 100000, 1) . ' L';
    }
    if (floor($value) === $value) {
        return number_format((int) $value);
    }
    return number_format($value, 2);
};
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Operations Dashboard</h1>
    <p class="text-muted mb-0">
      <?= esc($meta['scopeLabel'] ?? 'Company overview') ?>
      <?php if ($nowHuman): ?>
        - <span>Refreshed <?= esc($nowHuman) ?></span>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2">
    <?php if ($canFilterScope): ?>
      <form method="get" class="d-flex align-items-center gap-2" role="search">
        <label class="small text-muted" for="companyScope">Scope</label>
        <select name="company" id="companyScope" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($scopeOptions as $option): ?>
            <option value="<?= esc($option['value']) ?>" <?= ($option['value'] ?? '') === $selectedScope ? 'selected' : '' ?>>
              <?= esc($option['label'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>
    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('admin/reports') ?>">Download MIS</a>
  </div>
</div>
<?php if (! empty($narrative)): ?>
  <div class="alert alert-info small shadow-sm mb-3" role="status">
    <?= esc($narrative) ?>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="dashboard-section">
  <div class="dashboard-band">
    <div class="dashboard-band__primary card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h2 class="h5 mb-0">Live program pulse</h2>
            <p class="text-muted small mb-0"><?= esc($pulse['windowLabel'] ?? 'Last 24 hours') ?></p>
          </div>
        </div>
        <?php if ($pulseMetrics !== []): ?>
          <div class="pulse-metrics">
            <?php foreach ($pulseMetrics as $metric): ?>
              <div class="pulse-metric">
                <p class="pulse-metric__label text-muted small text-uppercase mb-1"><?= esc($metric['label'] ?? '') ?></p>
                <div class="pulse-metric__value"><?= esc($formatNumber($metric['value'] ?? 0)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted small mb-0">No activity recorded in this window.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard-band__secondary">
      <div class="dashboard-surface card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 text-uppercase text-muted mb-0"><?= esc($notes['headline'] ?? 'Leadership notes') ?></h2>
          </div>
          <?php if (! empty($notes['items'])): ?>
            <ul class="leadership-notes list-unstyled mb-0">
              <?php foreach ($notes['items'] as $note): ?>
                <li class="leadership-notes__item">
                  <p class="text-muted text-uppercase small mb-1"><?= esc($note['label'] ?? '') ?></p>
                  <div class="fs-5 fw-semibold"><?= esc($formatNumber($note['value'] ?? 0)) ?></div>
                  <?php if (! empty($note['caption'])): ?>
                    <p class="text-muted small mb-0"><?= esc($note['caption']) ?></p>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted small mb-0">No role-specific updates at the moment.</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="dashboard-surface card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 text-uppercase text-muted mb-0">Bulk upload monitor</h2>
            <span class="text-muted small">Last 5 batches</span>
          </div>
          <?php if ($uploads === []): ?>
            <p class="text-muted small mb-0">No recent batches have been processed.</p>
          <?php else: ?>
            <ul class="bulk-monitor list-unstyled mb-0">
              <?php foreach ($uploads as $batch): ?>
                <li class="bulk-monitor__item">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <p class="fw-semibold mb-0"><?= esc($batch['reference'] ?? 'Batch') ?></p>
                      <?php if (! empty($batch['processedAgo'])): ?>
                        <p class="text-muted small mb-0"><?= esc($batch['processedAgo']) ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-success-subtle text-success-emphasis"><?= esc($formatNumber($batch['success'] ?? 0)) ?></span>
                      <span class="badge bg-danger-subtle text-danger-emphasis"><?= esc($formatNumber($batch['failed'] ?? 0)) ?></span>
                    </div>
                  </div>
                  <p class="text-muted small mb-0">Received <?= esc($formatNumber($batch['received'] ?? 0)) ?> records</p>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h2 class="h5 mb-1">Action centre</h2>
          <p class="text-muted small mb-0">Queues awaiting operational follow-up.</p>
        </div>
      </div>
      <?php if ($queues === []): ?>
        <p class="text-muted small mb-0">All caught up &mdash; no operational queues need attention.</p>
      <?php else: ?>
        <div class="dashboard-actions__grid">
          <?php foreach ($queues as $item): ?>
            <?php
              $priority      = strtolower((string) ($item['priority'] ?? 'normal'));
              $priorityClass = 'dashboard-action--' . preg_replace('/[^a-z]/', '', $priority);
              if ($priorityClass === 'dashboard-action--') {
                  $priorityClass = 'dashboard-action--normal';
              }
              $label     = trim((string) ($item['label'] ?? ''));
              $initial   = $label !== '' ? strtoupper(mb_substr($label, 0, 1)) : '?';
              $count     = $item['count'] ?? 0;
              $isLink    = ! empty($item['href']);
              $countText = $formatNumber($count);
            ?>
            <?php if ($isLink): ?>
              <a href="<?= esc($item['href']) ?>" class="dashboard-action <?= esc($priorityClass) ?>">
            <?php else: ?>
              <div class="dashboard-action <?= esc($priorityClass) ?>">
            <?php endif; ?>
                <span class="dashboard-action__icon" aria-hidden="true"><?= esc($initial) ?></span>
                <div class="dashboard-action__content">
                  <p class="dashboard-action__title mb-1"><?= esc($label) ?></p>
                  <?php if (! empty($item['description'])): ?>
                    <p class="dashboard-action__description text-muted small mb-0"><?= esc($item['description']) ?></p>
                  <?php endif; ?>
                </div>
                <div class="dashboard-action__meta text-end">
                  <span class="dashboard-action__count"><?= esc($countText) ?></span>
                  <span class="dashboard-action__chevron" aria-hidden="true">&rsaquo;</span>
                </div>
            <?php if ($isLink): ?>
              </a>
            <?php else: ?>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="dashboard-grid dashboard-grid--three">
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Claims pipeline</h2>
        <div class="claims-matrix">
          <?php foreach ($claims['status'] ?? [] as $code => $count): ?>
            <div class="claims-matrix__item">
              <p class="claims-matrix__label text-muted small text-uppercase mb-1"><?= esc($statusLabels[$code] ?? ucfirst(str_replace('_', ' ', $code))) ?></p>
              <div class="claims-matrix__value"><?= esc($formatNumber($count)) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (! empty($claims['trend'])): ?>
          <hr class="my-3" />
          <h3 class="h6 text-uppercase text-muted fw-semibold mb-2">Weekly trend</h3>
          <ul class="list-unstyled mb-0 small text-muted">
            <?php foreach ($claims['trend'] as $row): ?>
              <li class="d-flex justify-content-between">
                <span><?= esc($row['label'] ?? 'Week') ?></span>
                <span><?= esc($formatNumber($row['claims'] ?? 0)) ?> claims · Rs <?= esc($formatNumber($row['amount'] ?? 0)) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Financial snapshot</h2>
        <?php $financials = $claims['financials'] ?? []; ?>
        <dl class="row mb-0 gy-2 small text-muted">
          <dt class="col-7 text-uppercase">Total claimed</dt>
          <dd class="col-5 fw-semibold text-end">Rs <?= esc($formatNumber($financials['claimed'] ?? 0.0)) ?></dd>
          <dt class="col-7 text-uppercase">Approved</dt>
          <dd class="col-5 fw-semibold text-end">Rs <?= esc($formatNumber($financials['approved'] ?? 0.0)) ?></dd>
          <dt class="col-7 text-uppercase">Cashless</dt>
          <dd class="col-5 fw-semibold text-end">Rs <?= esc($formatNumber($financials['cashless'] ?? 0.0)) ?></dd>
          <dt class="col-7 text-uppercase">Co-pay collected</dt>
          <dd class="col-5 fw-semibold text-end">Rs <?= esc($formatNumber($financials['copay'] ?? 0.0)) ?></dd>
          <dt class="col-7 text-uppercase">Non-payable</dt>
          <dd class="col-5 fw-semibold text-end">Rs <?= esc($formatNumber($financials['nonPayable'] ?? 0.0)) ?></dd>
        </dl>
      </div>
    </div>
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">SLA watch (next 48 hrs)</h2>
        <?php if (! empty($claims['nearBreach'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($claims['nearBreach'] as $row): ?>
              <li class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <a href="<?= esc($row['url'] ?? '#') ?>" class="fw-semibold text-decoration-none"><?= esc($row['reference'] ?? '-') ?></a>
                    <p class="text-muted small mb-0"><?= esc($row['hospital'] ?? '-') ?></p>
                    <p class="text-muted small mb-0">Rs <?= esc($formatNumber($row['claimed'] ?? 0.0)) ?> · <?= esc($row['status'] ?? 'In progress') ?></p>
                  </div>
                  <span class="badge bg-warning-subtle text-warning-emphasis"><?= esc($formatNumber($row['ageDays'] ?? 0)) ?> d</span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted small mb-0">No claims nearing SLA thresholds.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="dashboard-grid dashboard-grid--three">
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Exception feed</h2>
        <?php if ($exceptions === []): ?>
          <p class="text-muted small mb-0">No ageing notices or escalations right now.</p>
        <?php else: ?>
          <ul class="exception-feed list-unstyled mb-0">
            <?php foreach ($exceptions as $item): ?>
              <li class="exception-feed__item">
                <div>
                  <p class="fw-semibold mb-1"><?= esc($item['title'] ?? '') ?></p>
                  <p class="text-muted small mb-0">
                    <?= esc($item['reference'] ?? '-') ?>
                    <?php if (! empty($item['context'])): ?>
                      &middot; <?= esc($item['context']) ?>
                    <?php endif; ?>
                  </p>
                </div>
                <div class="exception-feed__meta text-end">
                  <?php if (! empty($item['badge'])): ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis"><?= esc($item['badge']) ?></span>
                  <?php endif; ?>
                  <?php if (! empty($item['url'])): ?>
                    <a class="small text-decoration-none" href="<?= esc($item['url']) ?>">Open</a>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Utilisation snapshot</h2>
        <p class="text-muted small mb-3"><?= esc($utilisation['rangeLabel'] ?? 'Last 30 days') ?></p>
        <?php if ($utilStates === []): ?>
          <p class="text-muted small mb-0">No recent claims captured in this window.</p>
        <?php else: ?>
          <ul class="utilisation-list list-unstyled mb-0">
            <?php foreach ($utilStates as $row): ?>
              <li class="utilisation-list__item">
                <div>
                  <p class="fw-semibold mb-0"><?= esc($row['label'] ?? 'Unassigned') ?></p>
                  <p class="text-muted small mb-0"><?= esc($formatNumber($row['claims'] ?? 0)) ?> claims</p>
                </div>
                <div class="text-end">
                  <p class="text-muted small mb-0">Rs <?= esc($formatNumber($row['amount'] ?? 0.0)) ?></p>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Support signals</h2>
        <div class="support-kpis">
          <div class="support-kpi">
            <p class="support-kpi__label text-muted small text-uppercase mb-1">Change requests pending</p>
            <div class="support-kpi__value"><?= esc($formatNumber($supportChange['pending'] ?? 0)) ?></div>
            <p class="text-muted small mb-0">
              Awaiting clarifications: <?= esc($formatNumber($supportChange['needsInfo'] ?? 0)) ?>
              <?php if (($supportChange['oldestAge'] ?? null) !== null): ?>
                &middot; Oldest <?= esc($formatNumber($supportChange['oldestAge'])) ?> d
              <?php endif; ?>
            </p>
          </div>
          <div class="support-kpi">
            <p class="support-kpi__label text-muted small text-uppercase mb-1">Hospital requests in review</p>
            <div class="support-kpi__value"><?= esc($formatNumber($supportHospital['pending'] ?? 0)) ?></div>
            <p class="text-muted small mb-0">
              Approved 24h: <?= esc($formatNumber($supportHospital['approved24h'] ?? 0)) ?>
              <?php if (($supportHospital['oldestAge'] ?? null) !== null): ?>
                &middot; Oldest <?= esc($formatNumber($supportHospital['oldestAge'])) ?> d
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="dashboard-grid dashboard-grid--two">
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Hospital network watch</h2>
        <?php $pending = $network['pendingRequests']['items'] ?? []; ?>
        <?php if ($pending === []): ?>
          <p class="text-muted small mb-0">No pending empanelment requests, you are up to date.</p>
        <?php else: ?>
          <div class="network-watch">
            <?php foreach ($pending as $request): ?>
              <article class="network-watch__item">
                <div class="network-watch__body">
                  <p class="network-watch__title mb-1"><?= esc($request['hospital'] ?? '-') ?></p>
                  <?php if (! empty($request['location'])): ?>
                    <p class="text-muted small mb-1"><?= esc($request['location']) ?></p>
                  <?php endif; ?>
                  <p class="text-muted small mb-0">
                    Submitted <?= esc($formatNumber($request['ageDays'] ?? 0)) ?> day(s) ago &middot;
                    Ref <?= esc($request['reference'] ?? '-') ?>
                  </p>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis"><?= esc($request['status'] ?? 'Pending') ?></span>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard-surface card shadow-sm border-0">
      <div class="card-body">
        <h2 class="h5 mb-3">Beneficiary pulse</h2>
        <div class="row g-3">
          <div class="col-12">
            <div class="pulse-card pulse-card--accent h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="text-muted text-uppercase small mb-0">Awaiting operations review</p>
                <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= esc($formatNumber($beneficiary['pendingReviewer'] ?? 0)) ?></span>
              </div>
              <p class="text-muted small mb-0">Beneficiaries flagged for manual verification.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="pulse-card h-100">
              <p class="text-muted text-uppercase small mb-1">OTP pending</p>
              <div class="fs-4 fw-semibold"><?= esc($formatNumber($beneficiary['otpPending'] ?? 0)) ?></div>
              <p class="text-muted small mb-0">Members yet to verify contact details.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="pulse-card h-100">
              <p class="text-muted text-uppercase small mb-1">Policy card missing</p>
              <div class="fs-4 fw-semibold"><?= esc($formatNumber($beneficiary['noPolicyCard'] ?? 0)) ?></div>
              <p class="text-muted small mb-0">Beneficiaries without mapped policy cards.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
          <h2 class="h5 mb-1">High-value claims in progress</h2>
          <p class="text-muted small mb-0">Top tickets by claimed amount that are yet to close.</p>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Claim ref.</th>
              <th scope="col">Beneficiary</th>
              <th scope="col" class="text-end">Claimed</th>
              <th scope="col" class="text-end">Approved</th>
              <th scope="col">Hospital</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Age (days)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (! empty($claims['highlights'])): ?>
              <?php foreach ($claims['highlights'] as $row): ?>
                <tr>
                  <td class="fw-semibold">
                    <?php if (! empty($row['url'])): ?>
                      <a href="<?= esc($row['url']) ?>" class="text-decoration-none"><?= esc($row['reference'] ?? '-') ?></a>
                    <?php else: ?>
                      <?= esc($row['reference'] ?? '-') ?>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($row['beneficiary'] ?? '-') ?></td>
                  <td class="text-end">Rs <?= esc($formatNumber($row['claimed'] ?? 0.0)) ?></td>
                  <td class="text-end">Rs <?= esc($formatNumber($row['approved'] ?? 0.0)) ?></td>
                  <td><?= esc($row['hospital'] ?? '-') ?></td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary"><?= esc($row['status'] ?? 'In progress') ?></span>
                  </td>
                  <td class="text-end"><?= esc($formatNumber($row['ageDays'] ?? 0)) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No active claims found for the current scope.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
