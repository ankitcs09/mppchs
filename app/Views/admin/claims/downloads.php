<?php

$filters  = $filters ?? [];
$listing  = $listing ?? ['data' => [], 'summary' => ['channel_breakdown' => []], 'pagination' => []];
$summary  = $listing['summary'] ?? [];
$channels = array_map(static function (array $row): string {
    return $row['channel'] ?? 'unknown';
}, $summary['channel_breakdown'] ?? []);
$channels = array_values(array_unique(array_filter($channels)));
$pagination = $listing['pagination'] ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'perPage' => 25];

function selectedOption(?string $value, ?string $current): string
{
    return $value === $current ? 'selected' : '';
}
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Document downloads</h1>
    <p class="page-heading__subtitle">
      Audit trail of claim document downloads across channels and users.
    </p>
    <?= view('admin/claims/_nav', ['active' => 'downloads']) ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Channel breakdown</h6>
        <?php if (empty($summary['channel_breakdown'])): ?>
          <p class="text-muted small mb-0">No downloads recorded for the selected filters.</p>
        <?php else: ?>
          <div class="vstack gap-3">
            <?php foreach ($summary['channel_breakdown'] as $row): ?>
              <div>
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold text-uppercase"><?= esc($row['channel'] ?? 'unknown') ?></span>
                  <span><?= esc(number_format($row['total'] ?? 0)) ?></span>
                </div>
                <div class="progress mt-1" style="height: 6px;">
                  <?php
                    $total = array_sum(array_map(static fn ($r) => (int) ($r['total'] ?? 0), $summary['channel_breakdown']));
                    $percent = $total > 0 ? (int) round(((int) ($row['total'] ?? 0) / $total) * 100) : 0;
                  ?>
                  <div class="progress-bar" role="progressbar" style="width: <?= esc($percent) ?>%;" aria-valuenow="<?= esc($percent) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Filters</h6>
        <form class="row g-3 align-items-end" method="get" action="<?= site_url('admin/claims/downloads') ?>">
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Channel</label>
            <input type="text" name="channel" class="form-control form-control-sm" value="<?= esc($filters['channel'] ?? '') ?>" placeholder="e.g. beneficiary">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">User type</label>
            <input type="text" name="user_type" class="form-control form-control-sm" value="<?= esc($filters['user_type'] ?? '') ?>" placeholder="staff / beneficiary">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Claim reference</label>
            <input type="text" name="claim_reference" class="form-control form-control-sm" value="<?= esc($filters['claim_reference'] ?? '') ?>" placeholder="Claim #">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Document type</label>
            <input type="text" name="document_type" class="form-control form-control-sm" value="<?= esc($filters['document_type'] ?? '') ?>" placeholder="Type code">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Downloaded between</label>
            <div class="input-group input-group-sm">
              <input type="date" name="from" class="form-control" value="<?= esc($filters['from'] ?? '') ?>">
              <span class="input-group-text">to</span>
              <input type="date" name="to" class="form-control" value="<?= esc($filters['to'] ?? '') ?>">
            </div>
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Company ID</label>
            <input type="number" name="company_id" class="form-control form-control-sm" value="<?= esc($filters['company_id'] ?? '') ?>" placeholder="Optional">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">User ID</label>
            <input type="number" name="user_id" class="form-control form-control-sm" value="<?= esc($filters['user_id'] ?? '') ?>" placeholder="Optional">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Beneficiary ID</label>
            <input type="number" name="beneficiary_id" class="form-control form-control-sm" value="<?= esc($filters['beneficiary_id'] ?? '') ?>" placeholder="Optional">
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= site_url('admin/claims/downloads') ?>" class="btn btn-outline-secondary btn-sm">
              <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-filter me-1"></i>Apply
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th scope="col">Claim</th>
          <th scope="col">Document</th>
          <th scope="col">Channel</th>
          <th scope="col">User</th>
          <th scope="col">Client</th>
          <th scope="col">Downloaded</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($listing['data'])): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              No downloads found for the selected filters.
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($listing['data'] as $row): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= esc($row['claim']['reference'] ?? '-') ?></div>
              <?php if (! empty($row['claim']['company_id'])): ?>
                <div class="text-muted small">Company ID <?= esc($row['claim']['company_id']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= esc($row['document']['title'] ?? '-') ?></div>
              <div class="text-muted small"><?= esc($row['document']['type_label'] ?? $row['document']['type_code'] ?? '-') ?></div>
            </td>
            <td>
              <span class="badge text-bg-secondary text-uppercase"><?= esc($row['channel'] ?? 'unknown') ?></span>
            </td>
            <td>
              <div class="fw-semibold"><?= esc($row['user']['name'] ?? ('User #' . ($row['user']['id'] ?? '-'))) ?></div>
              <div class="text-muted small"><?= esc($row['user']['type'] ?? '-') ?></div>
            </td>
            <td>
              <div><?= esc($row['client']['ip'] ?? '-') ?></div>
              <div class="text-muted small text-truncate" style="max-width: 220px;" title="<?= esc($row['client']['user_agent'] ?? '-') ?>">
                <?= esc($row['client']['user_agent'] ?? '-') ?>
              </div>
            </td>
            <td>
              <div><?= esc($row['downloaded_at'] ?? '-') ?></div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
    $page  = $pagination['page'] ?? 1;
    $pages = $pagination['pages'] ?? 1;
    $query = $_GET;
  ?>
  <?php if ($pages > 1): ?>
    <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        Page <?= esc($page) ?> of <?= esc($pages) ?> | <?= esc($pagination['total'] ?? 0) ?> records
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm<?= $page <= 1 ? ' disabled' : '' ?>"
           href="<?= $page <= 1 ? '#' : esc(site_url('admin/claims/downloads') . '?' . http_build_query($query + ['page' => $page - 1])) ?>">
          <i class="fa-solid fa-chevron-left me-1"></i>Previous
        </a>
        <a class="btn btn-outline-secondary btn-sm<?= $page >= $pages ? ' disabled' : '' ?>"
           href="<?= $page >= $pages ? '#' : esc(site_url('admin/claims/downloads') . '?' . http_build_query($query + ['page' => $page + 1])) ?>">
          Next<i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
