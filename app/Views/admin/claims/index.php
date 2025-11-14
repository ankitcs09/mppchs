<?php

$filters  = $filters ?? [];
$listing  = $listing ?? [
    'data' => [],
    'pagination' => ['page' => 1, 'pages' => 1, 'perPage' => 25, 'total' => 0],
    'summary' => ['totals' => [], 'status_breakdown' => []],
];
$statuses = $statuses ?? [];
$types    = $types ?? [];

$statusParam      = (array) ($filters['status'] ?? $filters['status_codes'] ?? []);
$typeParam        = (array) ($filters['type'] ?? $filters['type_codes'] ?? []);
$companyParam     = $filters['company_id'] ?? '';
$searchTerm       = trim($filters['search'] ?? $filters['search_term'] ?? '');
$beneficiaryTerm  = trim($filters['beneficiary'] ?? $filters['beneficiary_term'] ?? '');
$fromDate         = $filters['from'] ?? $filters['from_date'] ?? '';
$toDate           = $filters['to'] ?? $filters['to_date'] ?? '';
$claimReference   = trim($filters['claim_reference'] ?? '');
$policyNumber     = trim($filters['policy_number'] ?? '');
$tpaReference     = trim($filters['tpa_reference'] ?? '');
$hospitalCode     = trim($filters['hospital_code'] ?? '');
$minAmountInput   = trim((string) ($filters['min_amount'] ?? ''));
$maxAmountInput   = trim((string) ($filters['max_amount'] ?? ''));
$currencySymbol   = '&#8377;&nbsp;';

function isSelected(array $haystack, string $needle): string
{
    return in_array($needle, $haystack, true) ? 'selected' : '';
}
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Claims Registry</h1>
    <p class="page-heading__subtitle">
      Centralised visibility across cashless and reimbursement claims with filtering, exports, and ingestion logs.
    </p>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <?= view('admin/claims/_nav', ['active' => 'registry']) ?>
      <?php $exportQuery = $filters ? '?' . http_build_query($filters) : ''; ?>
      <div class="btn-group">
        <button
          type="button"
          class="btn btn-primary btn-sm dropdown-toggle"
          data-bs-toggle="dropdown"
          aria-expanded="false"
        >
          <i class="fa-solid fa-file-export me-1"></i>Export
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="<?= site_url('admin/claims/export' . $exportQuery) ?>">
              <i class="fa-regular fa-file-excel me-2 text-success"></i>Download XLSX
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="<?= site_url('admin/claims/export/pdf' . $exportQuery) ?>">
              <i class="fa-regular fa-file-pdf me-2 text-danger"></i>Download PDF
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <section class="app-panel app-panel--compact h-100">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Aggregate overview</h2>
        <p class="app-panel__subtitle small">
          Quick totals across the selected filters including claim counts and financial headroom.
        </p>
      </header>
      <dl class="row gy-2 mb-0">
        <dt class="col-7">Total claims</dt>
        <dd class="col-5 text-end"><?= esc(number_format($listing['summary']['totals']['total_claims'] ?? 0)) ?></dd>
        <dt class="col-7">Claimed amount</dt>
        <dd class="col-5 text-end"><?= $currencySymbol ?><?= esc(number_format($listing['summary']['totals']['total_claimed'] ?? 0, 2)) ?></dd>
        <dt class="col-7">Approved</dt>
        <dd class="col-5 text-end"><?= $currencySymbol ?><?= esc(number_format($listing['summary']['totals']['total_approved'] ?? 0, 2)) ?></dd>
        <dt class="col-7">Cashless</dt>
        <dd class="col-5 text-end"><?= $currencySymbol ?><?= esc(number_format($listing['summary']['totals']['total_cashless'] ?? 0, 2)) ?></dd>
        <dt class="col-7">Co-pay</dt>
        <dd class="col-5 text-end"><?= $currencySymbol ?><?= esc(number_format($listing['summary']['totals']['total_copay'] ?? 0, 2)) ?></dd>
        <dt class="col-7">Non-payable</dt>
        <dd class="col-5 text-end"><?= $currencySymbol ?><?= esc(number_format($listing['summary']['totals']['total_non_payable'] ?? 0, 2)) ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-lg-8">
    <section class="app-panel">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Refine results</h2>
        <p class="app-panel__subtitle">
          Combine status, beneficiary, financial, and time-frame filters to isolate the records that matter.
        </p>
      </header>
      <form class="row g-3 align-items-end" method="get" action="<?= site_url('admin/claims') ?>">
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Status</label>
          <select name="status[]" class="form-select form-select-sm" multiple size="5">
            <?php foreach ($statuses as $status): ?>
              <option value="<?= esc($status['code']) ?>" <?= isSelected($statusParam, $status['code']) ?>>
                <?= esc($status['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Type</label>
          <select name="type[]" class="form-select form-select-sm" multiple size="5">
            <?php foreach ($types as $type): ?>
              <option value="<?= esc($type['code']) ?>" <?= isSelected($typeParam, $type['code']) ?>>
                <?= esc($type['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Company</label>
          <input type="text" name="company_id" class="form-control form-control-sm" value="<?= esc($companyParam) ?>" placeholder="Company code">
        </div>
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Beneficiary</label>
          <input type="text" name="beneficiary" class="form-control form-control-sm" value="<?= esc($beneficiaryTerm) ?>" placeholder="Name / reference">
        </div>
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Date from</label>
          <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($fromDate) ?>">
        </div>
        <div class="col-sm-6 col-md-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Date to</label>
          <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($toDate) ?>">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" value="<?= esc($searchTerm) ?>" placeholder="Claim #, hospital, diagnosis">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Hospital code</label>
          <input type="text" name="hospital_code" class="form-control form-control-sm" value="<?= esc($hospitalCode) ?>">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Claim #</label>
          <input type="text" name="claim_reference" class="form-control form-control-sm" value="<?= esc($claimReference) ?>" placeholder="Internal reference">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">TPA reference</label>
          <input type="text" name="tpa_reference" class="form-control form-control-sm" value="<?= esc($tpaReference) ?>" placeholder="External reference">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Policy/card #</label>
          <input type="text" name="policy_number" class="form-control form-control-sm" value="<?= esc($policyNumber) ?>" placeholder="Policy ID">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Min claimed</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text">&#8377;</span>
            <input type="number" min="0" step="0.01" name="min_amount" class="form-control" value="<?= esc($minAmountInput) ?>">
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label text-uppercase text-muted fs-12 mb-1">Max claimed</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text">&#8377;</span>
            <input type="number" min="0" step="0.01" name="max_amount" class="form-control" value="<?= esc($maxAmountInput) ?>">
          </div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="<?= site_url('admin/claims') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
          <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
      </form>
    </section>
  </div>
</div>

<?php if (! empty($listing['summary']['status_breakdown'])): ?>
  <section class="app-panel mb-4">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Status breakdown</h2>
      <p class="app-panel__subtitle">
        Distribution of records by current processing state, helpful for identifying workflow bottlenecks.
      </p>
    </header>
    <div class="row g-3">
      <?php foreach ($listing['summary']['status_breakdown'] as $row): ?>
        <div class="col-sm-6 col-md-3">
          <div class="app-panel app-panel--compact h-100">
            <div class="fw-semibold"><?= esc($row['label']) ?></div>
            <div class="text-muted small mb-2"><?= esc(strtoupper($row['code'])) ?></div>
            <div class="display-6"><?= esc(number_format($row['total'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Claims list</h2>
    <p class="app-panel__subtitle">
      Each record summarises beneficiary, company, monetary components, and quick links to the detailed timeline.
    </p>
  </header>
  <div class="table-surface table-stack-mobile claims-table">
    <table class="table table-striped table-sm align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Claim #</th>
          <th scope="col">Beneficiary</th>
          <th scope="col">Company</th>
          <th scope="col">Status</th>
          <th scope="col">Claimed</th>
          <th scope="col">Approved</th>
          <th scope="col">Claim Date</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($listing['data'])): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              No claims found for the selected filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($listing['data'] as $row): ?>
            <tr>
              <td data-label="Claim #">
                <div class="fw-semibold d-flex flex-wrap align-items-center gap-2">
                  <?= esc($row['claim_reference']) ?>
                  <?php if (! empty($row['external_reference'])): ?>
                    <span class="claims-table__tag"><?= esc($row['external_reference']) ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td data-label="Beneficiary">
                <div class="fw-semibold mb-1"><?= esc($row['beneficiary']['name'] ?? '-') ?></div>
                <?php if (! empty($row['beneficiary']['reference'])): ?>
                  <span class="claims-table__subtext"><?= esc($row['beneficiary']['reference']) ?></span>
                <?php else: ?>
                  <span class="claims-table__subtext text-muted">&mdash;</span>
                <?php endif; ?>
              </td>
              <td data-label="Company">
                <div class="fw-semibold mb-1"><?= esc($row['company']['name'] ?? '-') ?></div>
                <?php if (! empty($row['company']['code'])): ?>
                  <span class="claims-table__subtext"><?= esc($row['company']['code']) ?></span>
                <?php endif; ?>
              </td>
              <td data-label="Status">
                <span class="badge bg-secondary-subtle text-body">
                  <?= esc($row['status']['label'] ?? 'Unknown') ?>
                </span>
              </td>
              <td data-label="Claimed">
                <div class="fw-semibold"><?= $currencySymbol ?><?= esc(number_format($row['amounts']['claimed'] ?? 0, 2)) ?></div>
                <?php
                  $claimedParts = [];
                  $cashless     = (float) ($row['amounts']['cashless'] ?? 0);
                  $copay        = (float) ($row['amounts']['copay'] ?? 0);
                  if ($cashless > 0) {
                      $claimedParts[] = ['label' => 'Cashless', 'amount' => $cashless];
                  }
                  if ($copay > 0) {
                      $claimedParts[] = ['label' => 'Co-pay', 'amount' => $copay];
                  }
                ?>
                <?php if ($claimedParts !== []): ?>
                  <div class="claims-table__subtext">
                    <?php foreach ($claimedParts as $index => $part): ?>
                      <?php if ($index > 0): ?>&nbsp;&middot;&nbsp;<?php endif; ?>
                      <?= esc($part['label']) ?> <?= $currencySymbol ?><?= esc(number_format($part['amount'], 2)) ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Approved">
                <div class="fw-semibold"><?= $currencySymbol ?><?= esc(number_format($row['amounts']['approved'] ?? 0, 2)) ?></div>
                <?php $nonPayable = (float) ($row['amounts']['non_payable'] ?? 0); ?>
                <?php if ($nonPayable > 0): ?>
                  <div class="claims-table__subtext">
                    Non-payable <?= $currencySymbol ?><?= esc(number_format($nonPayable, 2)) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Claim Date"><?= esc($row['dates']['claim'] ?? '-') ?></td>
              <td data-label="Actions" class="text-end">
                <a href="<?= site_url('admin/claims/' . $row['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                  <i class="fa-solid fa-magnifying-glass me-1"></i>Inspect
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php
    $pagination = $listing['pagination'];
    $page       = $pagination['page'] ?? 1;
    $pages      = $pagination['pages'] ?? 1;
    $query      = $_GET;
  ?>
  <?php if ($pages > 1): ?>
    <footer class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
      <div class="text-muted small">
        Page <?= esc($page) ?> of <?= esc($pages) ?> • <?= esc($pagination['total'] ?? 0) ?> records
      </div>
      <div class="d-flex gap-2">
        <a
          class="btn btn-outline-secondary btn-sm<?= $page <= 1 ? ' disabled' : '' ?>"
          href="<?= $page <= 1 ? '#' : esc(site_url('admin/claims') . '?' . http_build_query($query + ['page' => $page - 1])) ?>"
        >
          <i class="fa-solid fa-chevron-left me-1"></i>Previous
        </a>
        <a
          class="btn btn-outline-secondary btn-sm<?= $page >= $pages ? ' disabled' : '' ?>"
          href="<?= $page >= $pages ? '#' : esc(site_url('admin/claims') . '?' . http_build_query($query + ['page' => $page + 1])) ?>"
        >
          Next<i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
      </div>
    </footer>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>





