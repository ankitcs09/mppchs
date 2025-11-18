<?php

$filters   = $filters ?? [];
$listing   = $listing ?? [
    'data' => [],
    'pagination' => ['page' => 1, 'pages' => 1, 'perPage' => 20, 'total' => 0],
    'summary' => [],
];
$statuses  = $statuses ?? [];
$types     = $types ?? [];
$pagerBase = site_url('claims');

$selectedStatus = (array) ($filters['status'] ?? $filters['status_codes'] ?? []);
$selectedType   = (array) ($filters['type'] ?? $filters['type_codes'] ?? []);
$searchTerm     = trim($filters['search'] ?? $filters['search_term'] ?? '');
$fromDate       = $filters['from'] ?? $filters['from_date'] ?? '';
$toDate         = $filters['to'] ?? $filters['to_date'] ?? '';
$claimReference = trim($filters['claim_reference'] ?? '');
$policyNumber   = trim($filters['policy_number'] ?? '');
$tpaReference   = trim($filters['tpa_reference'] ?? '');
$hospitalCode   = trim($filters['hospital_code'] ?? '');
$minAmountInput = trim((string) ($filters['min_amount'] ?? ''));
$maxAmountInput = trim((string) ($filters['max_amount'] ?? ''));
$currencySymbol = "\u{20B9}";

$statusBreakdown = $listing['summary']['status_breakdown'] ?? [];
$statusTotal = array_sum(array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $statusBreakdown));
$exportQuery = $filters ? '?' . http_build_query($filters) : '';

function optionSelected(array $haystack, string $needle): string
{
    return in_array($needle, $haystack, true) ? 'selected' : '';
}

if (! function_exists('claims_format_date_with_relative')) {
    function claims_humanize_days(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $target = new DateTime($date);
            $now    = new DateTime();
        } catch (Throwable $e) {
            return null;
        }

        $diff = $target->diff($now);
        $days = (int) $diff->format('%a');

        if ($days === 0) {
            return 'today';
        }

        if ($days === 1) {
            return '1 day ago';
        }

        return $days . ' days ago';
    }

    function claims_format_date_with_relative(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $relative = claims_humanize_days($date);

        return $relative ? sprintf('%s · %s', $date, $relative) : $date;
    }
}

$formatCurrency = static function (float $value) use ($currencySymbol): string {
    return trim($currencySymbol) . ' ' . number_format($value, 2);
};

$totalClaims          = (int) ($listing['summary']['total_claims'] ?? 0);
$totalClaimed         = (float) ($listing['summary']['total_claimed'] ?? 0);
$totalApproved        = (float) ($listing['summary']['total_approved'] ?? 0);
$totalCashless        = (float) ($listing['summary']['total_cashless'] ?? 0);
$totalCopay           = (float) ($listing['summary']['total_copay'] ?? 0);
$outstandingCashless  = max(0.0, $totalClaimed - $totalCashless - $totalCopay);
$nonCashlessApproved  = max(0.0, $totalApproved - $totalCashless);
$notApprovedAmount    = max(0.0, $totalClaimed - $totalApproved);

$summaryChips = [
    [
        'label' => 'Total claims',
        'value' => number_format($totalClaims),
        'hint'  => 'Across the selected filters',
        'abbr'  => 'TC',
    ],
    [
        'label' => 'Claimed',
        'value' => $formatCurrency($totalClaimed),
        'hint'  => 'Total submitted amount',
        'abbr'  => 'CL',
    ],
    [
        'label' => 'Approved',
        'value' => $formatCurrency($totalApproved),
        'hint'  => $nonCashlessApproved > 0
            ? $formatCurrency($nonCashlessApproved) . ' non-cashless'
            : 'Approved so far',
        'abbr'  => 'AP',
    ],
    [
        'label' => 'Cashless',
        'value' => $formatCurrency($totalCashless),
        'hint'  => 'Settled directly with hospital',
        'abbr'  => 'CS',
    ],
    [
        'label' => 'Co-pay',
        'value' => $formatCurrency($totalCopay),
        'hint'  => 'Beneficiary contribution',
        'abbr'  => 'CP',
    ],
    [
        'label' => 'Outstanding',
        'value' => $formatCurrency($outstandingCashless),
        'hint'  => $notApprovedAmount > 0
            ? $formatCurrency($notApprovedAmount) . ' not approved'
            : 'Remaining to settle',
        'abbr'  => 'OS',
    ],
];

$breakdownSegments = array_filter([
    [
        'label' => 'Cashless settled',
        'value' => $totalCashless,
        'class' => 'summary-bar__segment--cashless',
    ],
    [
        'label' => 'Co-pay',
        'value' => $totalCopay,
        'class' => 'summary-bar__segment--copay',
    ],
    [
        'label' => 'Outstanding',
        'value' => $outstandingCashless,
        'class' => 'summary-bar__segment--outstanding',
    ],
], static fn (array $segment): bool => ($segment['value'] ?? 0) > 0);

$breakdownTotal = array_sum(array_column($breakdownSegments, 'value'));

$topClaims = array_slice(
    (function (array $claims): array {
        usort($claims, static function (array $a, array $b): int {
            $amountA = (float) ($a['amounts']['claimed'] ?? 0);
            $amountB = (float) ($b['amounts']['claimed'] ?? 0);
            return $amountB <=> $amountA;
        });
        return $claims;
    })($listing['data'] ?? []),
    0,
    5
);

$statusClasses = [
    'approved'     => 'status-pill--approved',
    'settled'      => 'status-pill--approved',
    'closed'       => 'status-pill--approved',
    'pending'      => 'status-pill--pending',
    'in_progress'  => 'status-pill--in-progress',
    'processing'   => 'status-pill--in-progress',
    'under_review' => 'status-pill--in-progress',
    'rejected'     => 'status-pill--rejected',
    'denied'       => 'status-pill--rejected',
    'cancelled'    => 'status-pill--rejected',
    'submitted'    => 'status-pill--submitted',
    'new'          => 'status-pill--submitted',
];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">My Claims</h1>
    <p class="page-heading__subtitle">
      Track cashless and reimbursement claims submitted for you and your dependents.
    </p>
  </div>
  <div class="btn-group">
    <button
      type="button"
      class="btn btn-outline-primary btn-sm dropdown-toggle"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      Export
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li>
        <a class="dropdown-item" href="<?= site_url('claims/export' . $exportQuery) ?>">
          Download XLSX
        </a>
      </li>
      <li>
        <a class="dropdown-item" href="<?= site_url('claims/export/pdf' . $exportQuery) ?>">
          Download PDF
        </a>
      </li>
    </ul>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="app-panel mb-4">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Claims summary</h2>
    <p class="app-panel__subtitle">A quick snapshot of totals across the filters applied below.</p>
  </header>
  <div class="summary-strip">
    <?php foreach ($summaryChips as $chip): ?>
      <div class="summary-chip">
        <div class="summary-chip__icon">
          <?= esc($chip['abbr']) ?>
        </div>
        <div class="summary-chip__content">
          <span class="summary-chip__label"><?= esc($chip['label']) ?></span>
          <span class="summary-chip__value"><?= esc($chip['value']) ?></span>
        </div>
        <span class="summary-chip__hint"><?= esc($chip['hint']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($breakdownTotal > 0): ?>
    <div class="summary-breakdown">
      <div class="summary-bar" role="img" aria-label="Breakdown of claimed amount">
        <?php foreach ($breakdownSegments as $segment): ?>
          <?php
            $width = $breakdownTotal > 0 ? ($segment['value'] / $breakdownTotal) * 100 : 0;
          ?>
          <div
            class="summary-bar__segment <?= esc($segment['class']) ?>"
            style="width: <?= esc(number_format($width, 2)) ?>%;"
            title="<?= esc($segment['label']) ?> · <?= strip_tags($formatCurrency($segment['value'])) ?>"
          ></div>
        <?php endforeach; ?>
      </div>
      <ul class="summary-legend">
        <?php foreach ($breakdownSegments as $segment): ?>
          <li>
            <span class="summary-legend__swatch <?= esc($segment['class']) ?>"></span>
            <?= esc($segment['label']) ?> · <?= $formatCurrency($segment['value']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <?php if (! empty($topClaims)): ?>
    <div class="summary-top-claims">
      <div class="summary-top-claims__header">
        <h3>Top claims by amount</h3>
        <span class="text-muted small">Largest 5 claims in the current view</span>
      </div>
      <ul class="summary-top-claims__list">
        <?php foreach ($topClaims as $topClaim): ?>
          <li>
            <div>
              <strong><?= esc($topClaim['claim_reference'] ?? 'CLAIM') ?></strong>
              <?php if (! empty($topClaim['hospital']['name'])): ?>
                <span class="text-muted small d-block"><?= esc($topClaim['hospital']['name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="text-end">
              <div class="summary-top-claims__amount">
                <?= $formatCurrency((float) ($topClaim['amounts']['claimed'] ?? 0)) ?>
              </div>
              <div class="text-muted small">
                Approved <?= $formatCurrency((float) ($topClaim['amounts']['approved'] ?? 0)) ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>

<section class="app-panel mb-4 claims-filter-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Refine results</h2>
    <p class="app-panel__subtitle">
      Combine filters to focus on a specific claim status, amount range, or hospital.
    </p>
    <button class="btn btn-outline-primary btn-sm d-lg-none" type="button" data-filter-sheet-open aria-haspopup="dialog" aria-controls="claimsFilterSheet">
      Filters
    </button>
  </header>
  <?php
    $advancedOpen = $claimReference || $tpaReference || $policyNumber || $hospitalCode || $minAmountInput !== '' || $maxAmountInput !== '';
  ?>
  <form class="claims-filter claims-filter--inline" method="get" action="<?= site_url('claims') ?>">
    <div class="filter-grid filter-grid--primary">
      <div class="filter-grid__item filter-grid__item--search filter-grid__item--wide">
        <label class="filter-grid__label visually-hidden" for="claimsSearch">Search claims</label>
        <div class="filter-search">
          <i class="fa-solid fa-magnifying-glass filter-search__icon" aria-hidden="true"></i>
          <input
            id="claimsSearch"
            type="text"
            name="search"
            class="form-control form-control-sm filter-search__input"
            value="<?= esc($searchTerm) ?>"
            placeholder="Search claim #, hospital, diagnosis"
            autocomplete="off"
          >
          <button
            class="filter-search__clear"
            type="button"
            data-action="clear-search"
            aria-label="Clear search"
            <?= $searchTerm === '' ? 'hidden' : '' ?>
          >
            &times;
          </button>
        </div>
      </div>
      <div class="filter-grid__item filter-grid__item--status">
        <label class="filter-grid__label">Status</label>
        <div class="filter-pills" role="group" aria-label="Filter by status">
          <?php foreach ($statuses as $status): ?>
            <?php
              $statusCode = $status['code'];
              $statusId   = 'status-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($statusCode));
              $isChecked  = in_array($statusCode, $selectedStatus, true);
            ?>
            <div class="filter-pill">
              <input
                type="checkbox"
                class="filter-pill__checkbox"
                id="<?= esc($statusId) ?>"
                name="status[]"
                value="<?= esc($statusCode) ?>"
                <?= $isChecked ? 'checked' : '' ?>
              >
              <label class="filter-pill__label" for="<?= esc($statusId) ?>"><?= esc($status['label']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="filter-grid__item filter-grid__item--date filter-grid__item--date-from">
        <label class="filter-grid__label">Date from</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($fromDate) ?>">
      </div>
      <div class="filter-grid__item filter-grid__item--date filter-grid__item--date-to">
        <label class="filter-grid__label">Date to</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($toDate) ?>">
      </div>
      <div class="filter-grid__item filter-grid__item--type">
        <label class="filter-grid__label">Type</label>
        <div class="filter-pills" role="group" aria-label="Filter by type">
          <?php foreach ($types as $type): ?>
            <?php
              $typeCode = $type['code'];
              $typeId   = 'type-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($typeCode));
              $isCheckedType  = in_array($typeCode, $selectedType, true);
            ?>
            <div class="filter-pill">
              <input
                type="checkbox"
                class="filter-pill__checkbox"
                id="<?= esc($typeId) ?>"
                name="type[]"
                value="<?= esc($typeCode) ?>"
                <?= $isCheckedType ? 'checked' : '' ?>
              >
              <label class="filter-pill__label" for="<?= esc($typeId) ?>"><?= esc($type['label']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="filter-grid__actions">
      <div class="d-flex align-items-center gap-2">
        <button
          class="btn btn-link btn-sm px-0"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#claimsAdvancedFilters"
          aria-expanded="<?= $advancedOpen ? 'true' : 'false' ?>"
          aria-controls="claimsAdvancedFilters"
        >
          <?= $advancedOpen ? 'Hide advanced filters' : 'Show advanced filters' ?>
        </button>
        <span class="text-muted small d-none d-md-inline">Claim, TPA, and policy IDs live under advanced filters.</span>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= site_url('claims') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-rotate-left me-1"></i>Reset
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-filter me-1"></i>Apply
        </button>
      </div>
    </div>
  <div class="collapse claims-filter__advanced<?= $advancedOpen ? ' show' : '' ?>" id="claimsAdvancedFilters">
      <div class="filter-grid filter-grid--advanced">
        <div class="filter-grid__item">
          <label class="filter-grid__label">Claim #</label>
          <input
            type="text"
            name="claim_reference"
            class="form-control form-control-sm"
            value="<?= esc($claimReference) ?>"
          >
        </div>
        <div class="filter-grid__item">
          <label class="filter-grid__label">TPA reference</label>
          <input
            type="text"
            name="tpa_reference"
            class="form-control form-control-sm"
            value="<?= esc($tpaReference) ?>"
          >
        </div>
        <div class="filter-grid__item">
          <label class="filter-grid__label">Policy/card #</label>
          <input
            type="text"
            name="policy_number"
            class="form-control form-control-sm"
            value="<?= esc($policyNumber) ?>"
          >
        </div>
        <div class="filter-grid__item">
          <label class="filter-grid__label">Hospital code</label>
          <input
            type="text"
            name="hospital_code"
            class="form-control form-control-sm"
            value="<?= esc($hospitalCode) ?>"
          >
        </div>
        <div class="filter-grid__item">
          <label class="filter-grid__label">Min claimed</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text">&#8377;</span>
            <input
              type="number"
              min="0"
              step="0.01"
              name="min_amount"
              class="form-control"
              value="<?= esc($minAmountInput) ?>"
            >
          </div>
        </div>
        <div class="filter-grid__item">
          <label class="filter-grid__label">Max claimed</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text">&#8377;</span>
            <input
              type="number"
              min="0"
              step="0.01"
              name="max_amount"
              class="form-control"
              value="<?= esc($maxAmountInput) ?>"
            >
          </div>
        </div>
      </div>
    </div>
  </form>
</section>

<section class="filter-sheet d-lg-none" data-filter-sheet id="claimsFilterSheet" role="dialog" aria-modal="true" aria-labelledby="claimsFilterTitle">
  <div class="filter-sheet__dialog">
    <header class="filter-sheet__header">
      <h2 id="claimsFilterTitle">Refine results</h2>
      <button type="button" class="btn-close" aria-label="Close filters" data-filter-sheet-close></button>
    </header>
    <form class="claims-filter" method="get" action="<?= site_url('claims') ?>">
      <div class="filter-grid filter-grid--primary">
        <div class="filter-grid__item filter-grid__item--search filter-grid__item--wide">
          <label class="filter-grid__label visually-hidden" for="claimsSearchMobile">Search claims</label>
          <div class="filter-search">
            <i class="fa-solid fa-magnifying-glass filter-search__icon" aria-hidden="true"></i>
            <input
              id="claimsSearchMobile"
              type="text"
              name="search"
              class="form-control form-control-sm filter-search__input"
              value="<?= esc($searchTerm) ?>"
              placeholder="Search claim #, hospital, diagnosis"
              autocomplete="off"
            >
            <button
              class="filter-search__clear"
              type="button"
              data-action="clear-search"
              aria-label="Clear search"
              <?= $searchTerm === '' ? 'hidden' : '' ?>
            >
              &times;
            </button>
          </div>
        </div>
        <div class="filter-grid__item filter-grid__item--status">
          <label class="filter-grid__label">Status</label>
          <div class="filter-pills" role="group" aria-label="Filter by status">
            <?php foreach ($statuses as $status): ?>
              <?php
                $statusCode = $status['code'];
                $statusId   = 'sheet-status-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($statusCode));
                $isChecked  = in_array($statusCode, $selectedStatus, true);
              ?>
              <div class="filter-pill">
                <input
                  type="checkbox"
                  class="filter-pill__checkbox"
                  id="<?= esc($statusId) ?>"
                  name="status[]"
                  value="<?= esc($statusCode) ?>"
                  <?= $isChecked ? 'checked' : '' ?>
                >
                <label class="filter-pill__label" for="<?= esc($statusId) ?>"><?= esc($status['label']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="filter-grid__item filter-grid__item--date">
          <label class="filter-grid__label">Date from</label>
          <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($fromDate) ?>">
        </div>
        <div class="filter-grid__item filter-grid__item--date">
          <label class="filter-grid__label">Date to</label>
          <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($toDate) ?>">
        </div>
        <div class="filter-grid__item filter-grid__item--type">
          <label class="filter-grid__label">Type</label>
          <div class="filter-pills" role="group" aria-label="Filter by type">
            <?php foreach ($types as $type): ?>
              <?php
                $typeCode = $type['code'];
                $typeId   = 'sheet-type-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($typeCode));
                $isCheckedType  = in_array($typeCode, $selectedType, true);
              ?>
              <div class="filter-pill">
                <input
                  type="checkbox"
                  class="filter-pill__checkbox"
                  id="<?= esc($typeId) ?>"
                  name="type[]"
                  value="<?= esc($typeCode) ?>"
                  <?= $isCheckedType ? 'checked' : '' ?>
                >
                <label class="filter-pill__label" for="<?= esc($typeId) ?>"><?= esc($type['label']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="collapse claims-filter__advanced<?= $advancedOpen ? ' show' : '' ?>" id="claimsAdvancedFiltersMobile">
        <div class="filter-grid filter-grid--advanced">
          <div class="filter-grid__item">
            <label class="filter-grid__label">Claim #</label>
            <input
              type="text"
              name="claim_reference"
              class="form-control form-control-sm"
              value="<?= esc($claimReference) ?>"
            >
          </div>
          <div class="filter-grid__item">
            <label class="filter-grid__label">TPA reference</label>
            <input
              type="text"
              name="tpa_reference"
              class="form-control form-control-sm"
              value="<?= esc($tpaReference) ?>"
            >
          </div>
          <div class="filter-grid__item">
            <label class="filter-grid__label">Policy/card #</label>
            <input
              type="text"
              name="policy_number"
              class="form-control form-control-sm"
              value="<?= esc($policyNumber) ?>"
            >
          </div>
          <div class="filter-grid__item">
            <label class="filter-grid__label">Hospital code</label>
            <input
              type="text"
              name="hospital_code"
              class="form-control form-control-sm"
              value="<?= esc($hospitalCode) ?>"
            >
          </div>
          <div class="filter-grid__item">
            <label class="filter-grid__label">Min claimed</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><?= esc($currencySymbol) ?></span>
              <input
                type="number"
                min="0"
                step="0.01"
                name="min_amount"
                class="form-control"
                value="<?= esc($minAmountInput) ?>"
              >
            </div>
          </div>
          <div class="filter-grid__item">
            <label class="filter-grid__label">Max claimed</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><?= esc($currencySymbol) ?></span>
              <input
                type="number"
                min="0"
                step="0.01"
                name="max_amount"
                class="form-control"
                value="<?= esc($maxAmountInput) ?>"
              >
            </div>
          </div>
        </div>
      </div>
      <div class="filter-grid__actions">
        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-link btn-sm px-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#claimsAdvancedFiltersMobile"
            aria-expanded="<?= $advancedOpen ? 'true' : 'false' ?>"
            aria-controls="claimsAdvancedFiltersMobile"
          >
            <?= $advancedOpen ? 'Hide advanced filters' : 'Show advanced filters' ?>
          </button>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-filter-sheet-close>
            <i class="fa-solid fa-xmark me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-filter me-1"></i>Apply
          </button>
        </div>
      </div>
    </form>
  </div>
</section>

<?php if (! empty($statusBreakdown)): ?>
  <section class="app-panel mb-4">
    <header class="app-panel__header">
      <div>
        <h2 class="app-panel__title mb-1">Status mix</h2>
        <p class="app-panel__subtitle">
          How your claims are distributed across the current processing stages.
        </p>
      </div>
      <span class="status-total badge-soft">Total: <?= esc($statusTotal) ?></span>
    </header>
    <div class="status-grid<?= count($statusBreakdown) === 1 ? ' status-grid--compact' : '' ?>">
      <?php foreach ($statusBreakdown as $row): ?>
        <?php
          $rowTotal = (int) ($row['total'] ?? 0);
          $percent  = $statusTotal > 0 ? (int) round(($rowTotal / $statusTotal) * 100) : 0;
          $typeSlices = [];
          if (! empty($row['types']) && is_array($row['types'])) {
              foreach ($row['types'] as $typeKey => $typeValue) {
                  $count = (int) $typeValue;
                  if ($count <= 0) {
                      continue;
                  }
                  $label = is_string($typeKey) ? ucwords(str_replace(['_', '-'], ' ', $typeKey)) : 'Other';
                  $typeSlices[] = [
                      'label' => $label,
                      'count' => $count,
                  ];
              }
          }
        ?>
        <article class="status-card">
          <header class="status-card__header">
            <div>
              <div class="status-card__label"><?= esc($row['label']) ?></div>
              <div class="status-card__code"><?= esc(strtoupper($row['code'] ?? '')) ?></div>
            </div>
            <div class="status-card__metrics">
              <span class="status-card__count"><?= esc($rowTotal) ?></span>
              <span class="status-card__percent"><?= esc($percent) ?>%</span>
            </div>
          </header>
          <div
            class="status-card__meter"
            role="progressbar"
            aria-valuenow="<?= esc($percent) ?>"
            aria-valuemin="0"
            aria-valuemax="100"
          >
            <div class="status-card__meter-bar" style="width: <?= esc($percent) ?>%;"></div>
          </div>
          <?php if (! empty($typeSlices)): ?>
            <ul class="status-card__chips">
              <?php foreach ($typeSlices as $slice): ?>
                <li>
                  <span class="status-card__chip-label"><?= esc($slice['label']) ?></span>
                  <span class="status-card__chip-count"><?= esc($slice['count']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if ($statusTotal === 0): ?>
        <div class="status-empty">We’ll show a breakdown as soon as claims start moving through different stages.</div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<section class="app-panel">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Claims list</h2>
    <p class="app-panel__subtitle">
      Review submitted claims, check their latest status, and inspect the timeline.
    </p>
  </header>

  <div class="table-surface table-stack">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th scope="col">Claim #</th>
          <th scope="col">Hospital</th>
          <th scope="col">Status</th>
          <th scope="col">Claimed</th>
          <th scope="col">Approved</th>
          <th scope="col">Updated</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($listing['data'])): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              No claims found for the selected filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($listing['data'] as $row): ?>
            <?php
              $rawCollapseKey = (string) ($row['id'] ?? $row['claim_reference'] ?? uniqid('claim'));
              $collapseId     = 'claimDetails' . preg_replace('/[^A-Za-z0-9]/', '', $rawCollapseKey);

              $beneficiary = is_array($row['beneficiary'] ?? null) ? $row['beneficiary'] : [];
              $hospital    = is_array($row['hospital'] ?? null) ? $row['hospital'] : [];
              $dates       = is_array($row['dates'] ?? null) ? $row['dates'] : [];
              $typeInfo    = is_array($row['type'] ?? null) ? $row['type'] : [];
              $statusInfo  = is_array($row['status'] ?? null) ? $row['status'] : [];

              $claimedAmount    = (float) ($row['amounts']['claimed'] ?? 0);
              $approvedAmount   = (float) ($row['amounts']['approved'] ?? 0);
              $cashlessAmount   = (float) ($row['amounts']['cashless'] ?? 0);
              $copayAmount      = (float) ($row['amounts']['copay'] ?? 0);
              $deductionsAmount = (float) ($row['amounts']['non_payable'] ?? 0);
              $outstandingAmount = max(0.0, $claimedAmount - $cashlessAmount - $copayAmount);
              $nonCashlessApproved = max(0.0, $approvedAmount - $cashlessAmount);

              $statusNotes = trim((string) ($statusInfo['notes'] ?? ''));
              $nextStep    = trim((string) ($statusInfo['next_action'] ?? ''));
              if ($nextStep === '' && $statusNotes !== '') {
                  $nextStep = $statusNotes;
              }
              if ($nextStep === '') {
                  $nextStep = 'Review timeline for latest updates.';
              }

              $claimOpened   = claims_format_date_with_relative($dates['claim'] ?? null);
              $lastUpdated   = claims_format_date_with_relative($dates['updated'] ?? null);
              $claimType     = $typeInfo['label'] ?? ($typeInfo['name'] ?? null);

              $nextStepText = $nextStep;

              $quickFacts = [
                  [
                      'label' => 'Status',
                      'value' => $row['status']['label'] ?? 'Unknown',
                  ],
                  [
                      'label' => 'Claim type',
                      'value' => $claimType,
                  ],
                  [
                      'label' => 'Opened',
                      'value' => $claimOpened,
                  ],
                  [
                      'label' => 'Last update',
                      'value' => $lastUpdated,
                  ],
              ];
              $quickFacts = array_values(array_filter($quickFacts, static fn (array $item): bool => (string) trim((string) ($item['value'] ?? '')) !== ''));

              $financialFacts = [
                  ['label' => 'Claimed total', 'value' => $formatCurrency($claimedAmount)],
                  ['label' => 'Approved total', 'value' => $formatCurrency($approvedAmount)],
                  ['label' => 'Cashless settled', 'value' => $formatCurrency($cashlessAmount), 'group' => 'approved'],
                  ['label' => 'Approved (non-cashless)', 'value' => $formatCurrency($nonCashlessApproved), 'group' => 'approved'],
                  ['label' => 'Co-pay collected', 'value' => $formatCurrency($copayAmount)],
                  ['label' => 'Non-payable / deductions', 'value' => $formatCurrency($deductionsAmount)],
                  ['label' => 'Outstanding to settle', 'value' => $formatCurrency($outstandingAmount), 'highlight' => true],
              ];

              $beneficiaryLabel = trim(($beneficiary['name'] ?? '') . (
                  ! empty($beneficiary['relationship'])
                      ? ' · ' . $beneficiary['relationship']
                      : ''
              ));
              $beneficiaryPhone = $beneficiary['phone'] ?? $beneficiary['mobile'] ?? ($beneficiary['contact'] ?? null);
              $beneficiaryEmail = $beneficiary['email'] ?? null;
              $hospitalPhone    = $hospital['phone'] ?? $hospital['contact'] ?? null;
              $hospitalEmail    = $hospital['email'] ?? null;

              $peopleFacts = array_filter([
                  'Beneficiary'      => $beneficiaryLabel !== '' ? $beneficiaryLabel : null,
                  'Beneficiary phone'=> $beneficiaryPhone,
                  'Beneficiary email'=> $beneficiaryEmail,
                  'Hospital contact' => $hospitalPhone,
                  'Hospital email'   => $hospitalEmail,
                  'TPA reference'    => $row['tpa_reference'] ?? null,
                  'Policy/card #'    => $row['policy_number'] ?? null,
              ], static fn ($value) => (string) trim((string) $value) !== '');

              $timeline    = is_array($row['timeline'] ?? null) ? $row['timeline'] : [];
              $timelinePreview = array_slice($timeline, 0, 3);
            ?>
            <tr>
              <td data-label="Claim #">
                <div class="fw-semibold"><?= esc($row['claim_reference']) ?></div>
                <?php if (! empty($row['external_reference'])): ?>
                  <div class="text-muted small"><?= esc($row['external_reference']) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Hospital">
                <div><?= esc($row['hospital']['name'] ?? '-') ?></div>
                <div class="text-muted small"><?= esc($row['hospital']['code'] ?? '-') ?></div>
              </td>
                            <td data-label="Status">
                <?php
                  $statusCode  = strtolower((string) ($row['status']['code'] ?? ''));
                  $statusStyle = trim('status-pill ' . ($statusClasses[$statusCode] ?? ''));
                ?>
                <span class="<?= esc($statusStyle) ?>">
                  <?= esc($row['status']['label'] ?? 'Unknown') ?>
                </span>
              </td>
              <td data-label="Claimed">
                <div class="fw-semibold">
                  <?= $formatCurrency((float) ($row['amounts']['claimed'] ?? 0)) ?>
                </div>
                <div class="text-muted small">
                  Cashless <?= $formatCurrency((float) ($row['amounts']['cashless'] ?? 0)) ?>
                </div>
              </td>
              <td data-label="Approved">
                <div class="fw-semibold">
                  <?= $formatCurrency((float) ($row['amounts']['approved'] ?? 0)) ?>
                </div>
                <div class="text-muted small">
                  Non-payable <?= $formatCurrency((float) ($row['amounts']['non_payable'] ?? 0)) ?>
                </div>
              </td>
              <td data-label="Updated"><?= esc($row['dates']['updated'] ?? $row['dates']['claim'] ?? '-') ?></td>
              <td data-label="Actions" class="text-end actions-cell">
                <button
                  class="btn btn-link btn-sm px-0"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#<?= esc($collapseId) ?>"
                  aria-expanded="false"
                  aria-controls="<?= esc($collapseId) ?>"
                >
                  <i class="fa-solid fa-circle-info me-1"></i>Details
                </button>
                <a href="<?= site_url('claims/' . $row['id']) ?>" class="btn btn-outline-primary btn-sm">
                  <i class="fa-regular fa-clock me-1"></i>View timeline
                </a>
              </td>
            </tr>
            <tr class="claim-details collapse" id="<?= esc($collapseId) ?>">
              <td colspan="7">
                <div class="claim-details__body">
                  <div class="claim-details__column claim-details__column--notes">
                    <h3 class="claim-details__title">People &amp; timeline</h3>
                    <?php if (! empty($peopleFacts) || ! empty($hospital)): ?>
                      <dl class="claim-details__list claim-details__list--contacts">
                        <?php foreach ($peopleFacts as $label => $value): ?>
                          <div>
                            <dt><?= esc($label) ?></dt>
                            <dd><?= esc($value) ?></dd>
                          </div>
                        <?php endforeach; ?>
                        <?php if (! empty($hospital['name'])): ?>
                          <div>
                            <dt>Hospital</dt>
                            <dd>
                              <span class="d-block fw-semibold"><?= esc($hospital['name']) ?></span>
                              <?php if (! empty($hospital['city']) || ! empty($hospital['state'])): ?>
                                <span class="text-muted small d-block">
                                  <?= esc(trim(($hospital['city'] ?? '') . ', ' . ($hospital['state'] ?? ''), ' ,')) ?>
                                </span>
                              <?php endif; ?>
                            </dd>
                          </div>
                        <?php endif; ?>
                      </dl>
                    <?php endif; ?>
                    <h4 class="claim-details__subtitle">Recent updates</h4>
                    <?php if (! empty($timelinePreview)): ?>
                      <ul class="claim-details__timeline">
                        <?php foreach ($timelinePreview as $event): ?>
                          <?php
                            $eventLabel = $event['label'] ?? $event['status'] ?? null;
                            $eventWhen  = $event['at'] ?? $event['date'] ?? null;
                          ?>
                          <?php if ($eventLabel || $eventWhen): ?>
                            <li>
                              <strong><?= esc($eventLabel ?? 'Update') ?></strong>
                              <?php if ($eventWhen): ?>
                                <span class="text-muted small"><?= esc($eventWhen) ?></span>
                              <?php endif; ?>
                            </li>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <p class="claim-details__note mb-0">
                        Detailed timeline available via the full claim view.
                      </p>
                    <?php endif; ?>
                  </div>
                  <div class="claim-details__column">
                    <h3 class="claim-details__title">Quick facts</h3>
                    <?php if (! empty($quickFacts)): ?>
                      <dl class="claim-details__list claim-details__list--facts">
                        <?php foreach ($quickFacts as $item): ?>
                          <div>
                            <dt><?= esc($item['label']) ?></dt>
                            <dd><?= esc($item['value']) ?></dd>
                          </div>
                        <?php endforeach; ?>
                      </dl>
                    <?php endif; ?>
                  </div>
                  <div class="claim-details__column">
                    <h3 class="claim-details__title">Financial overview</h3>
                    <table class="claim-financial-table">
                      <tbody>
                        <?php foreach ($financialFacts as $rowFact): ?>
                          <tr class="<?= esc($rowFact['group'] ?? '') ?><?= ! empty($rowFact['highlight']) ? ' is-highlight' : '' ?>">
                            <th scope="row"><?= esc($rowFact['label']) ?></th>
                            <td><span class="claim-financial-table__value"><?= esc($rowFact['value']) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                    <?php if (! empty($deductionsAmount) || ! empty($nonCashlessApproved)): ?>
                      <p class="claim-details__note mb-0">
                        Figures reflect the latest approved amounts, including cashless settlements and co-pay collected.
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="claim-details__footer">
                  <?php if (! empty($nextStepText)): ?>
                    <strong>Next step:</strong>
                    <span><?= esc($nextStepText) ?></span>
                  <?php else: ?>
                    <span>Review the timeline for the latest updates.</span>
                  <?php endif; ?>
                </div>
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
        Page <?= esc($page) ?> of <?= esc($pages) ?> &bull; <?= esc($pagination['total'] ?? 0) ?> records
      </div>
      <div class="d-flex gap-2">
        <a
          class="btn btn-outline-secondary btn-sm<?= $page <= 1 ? ' disabled' : '' ?>"
          href="<?= $page <= 1 ? '#' : esc($pagerBase . '?' . http_build_query($query + ['page' => $page - 1])) ?>"
        >
          Previous
        </a>
        <a
          class="btn btn-outline-secondary btn-sm<?= $page >= $pages ? ' disabled' : '' ?>"
          href="<?= $page >= $pages ? '#' : esc($pagerBase . '?' . http_build_query($query + ['page' => $page + 1])) ?>"
        >
          Next
        </a>
      </div>
    </footer>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>





