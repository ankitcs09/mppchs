<?php

$filters  = $filters ?? [];
$listing  = $listing ?? ['data' => [], 'summary' => [], 'pagination' => []];
$summary  = $listing['summary'] ?? [];
$docSummaryTotals = $summary['documents']['totals'] ?? [];
$docSummaryMatrix = $summary['documents']['matrix'] ?? [];
$pagination = $listing['pagination'] ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'perPage' => 20];
$selectedHasFailures = $filters['has_failures'] ?? ($filters['has_failures'] ?? null);

function selectOption(?string $value, ?string $current): string
{
    return $value === $current ? 'selected' : '';
}
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Ingestion batches</h1>
    <p class="page-heading__subtitle">
      Monitor inbound claim batches, their success rate, and failure notes.
    </p>
    <?= view('admin/claims/_nav', ['active' => 'batches']) ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Summary</h6>
        <dl class="row mb-0">
          <dt class="col-7">Batches</dt>
          <dd class="col-5 text-end"><?= esc(number_format($pagination['total'] ?? 0)) ?></dd>
          <dt class="col-7">Claims received</dt>
          <dd class="col-5 text-end"><?= esc(number_format($summary['total_received'] ?? 0)) ?></dd>
          <dt class="col-7">Successful</dt>
          <dd class="col-5 text-end"><?= esc(number_format($summary['total_success'] ?? 0)) ?></dd>
          <dt class="col-7">Failed</dt>
          <dd class="col-5 text-end"><?= esc(number_format($summary['total_failed'] ?? 0)) ?></dd>
          <dt class="col-7">Docs attempted</dt>
          <dd class="col-5 text-end"><?= esc(number_format($docSummaryTotals['attempted'] ?? 0)) ?></dd>
          <dt class="col-7">Docs successful</dt>
          <?php $docSummarySuccess = ($docSummaryTotals['ingested'] ?? 0) + ($docSummaryTotals['updated'] ?? 0); ?>
          <dd class="col-5 text-end"><?= esc(number_format($docSummarySuccess)) ?></dd>
          <dt class="col-7">Docs failed</dt>
          <dd class="col-5 text-end"><?= esc(number_format($docSummaryTotals['failed'] ?? 0)) ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Filters</h6>
        <form class="row g-3 align-items-end" method="get" action="<?= site_url('admin/claims/batches') ?>">
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Batch reference</label>
            <input type="text" name="reference" class="form-control form-control-sm" value="<?= esc($filters['reference'] ?? '') ?>" placeholder="Batch #">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Source IP</label>
            <input type="text" name="source_ip" class="form-control form-control-sm" value="<?= esc($filters['source_ip'] ?? '') ?>" placeholder="e.g. 127.0.0.1">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Processed between</label>
            <div class="input-group input-group-sm">
              <input type="date" name="from" class="form-control" value="<?= esc($filters['from'] ?? '') ?>">
              <span class="input-group-text">to</span>
              <input type="date" name="to" class="form-control" value="<?= esc($filters['to'] ?? '') ?>">
            </div>
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Failures</label>
            <select name="has_failures" class="form-select form-select-sm">
              <option value="" <?= selectOption('', $selectedHasFailures) ?>>All</option>
              <option value="1" <?= selectOption('1', $selectedHasFailures) ?>>Has failures</option>
              <option value="0" <?= selectOption('0', $selectedHasFailures) ?>>No failures</option>
            </select>
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label text-uppercase text-muted fs-12 mb-1">Company ID</label>
            <input type="number" name="company_id" class="form-control form-control-sm" value="<?= esc($filters['company_id'] ?? '') ?>" placeholder="Optional">
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= site_url('admin/claims/batches') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
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
          <th scope="col">Batch</th>
          <th scope="col" class="text-end">Received</th>
          <th scope="col" class="text-end">Success</th>
          <th scope="col" class="text-end">Failed</th>
          <th scope="col">Source</th>
          <th scope="col" class="text-end">Docs</th>
          <th scope="col">Processed</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($listing['data'])): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              No batches found for the selected filters.
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($listing['data'] as $row): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= esc($row['batch_reference'] ?? ('Batch #' . $row['id'])) ?></div>
              <div class="text-muted small">ID <?= esc($row['id']) ?></div>
              <?php if (! empty($row['notes'])): ?>
                <div class="text-muted small mt-1">
                  <?php foreach (array_slice($row['notes'], 0, 2) as $note): ?>
                    <div>&bull; <?= esc($note) ?></div>
                  <?php endforeach; ?>
                  <?php if (count($row['notes']) > 2): ?>
                    <div>&bull; <?= esc(count($row['notes']) - 2) ?> additional notes</div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="text-end"><?= esc(number_format($row['claims']['received'] ?? 0)) ?></td>
            <td class="text-end"><?= esc(number_format($row['claims']['success'] ?? 0)) ?></td>
            <td class="text-end">
              <?php $failed = (int) ($row['claims']['failed'] ?? 0); ?>
              <?php if ($failed > 0): ?>
                <span class="badge text-bg-danger"><?= esc($failed) ?></span>
              <?php else: ?>
                <span class="text-muted"><?= esc($failed) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div><?= esc($row['source']['ip'] ?? '-') ?></div>
              <div class="text-muted small"><?= esc($row['source']['user_agent'] ?? '') ?></div>
            </td>
            <?php
              $docTotals = $row['documents_summary']['totals'] ?? [];
              $docAttempted = (int) ($docTotals['attempted'] ?? 0);
              $docSuccess = (int) (($docTotals['ingested'] ?? 0) + ($docTotals['updated'] ?? 0));
              $docFailed = (int) ($docTotals['failed'] ?? 0);
              $docPartial = (int) ($docTotals['partial'] ?? 0);
              $docMissing = (int) ($docTotals['missing'] ?? 0);
            ?>
            <td class="text-end">
              <?php if ($docAttempted === 0): ?>
                <span class="text-muted">No docs</span>
              <?php else: ?>
                <div><?= esc($docAttempted) ?> attempted</div>
                <div class="text-muted small">
                  OK <?= esc($docSuccess) ?> | Fail <?= esc($docFailed) ?>
                  <?php if ($docPartial > 0): ?>
                    <span class="badge text-bg-warning text-dark ms-1">Partial <?= esc($docPartial) ?></span>
                  <?php endif; ?>
                  <?php if ($docMissing > 0): ?>
                    <span class="badge text-bg-secondary ms-1">Missing <?= esc($docMissing) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <div><?= esc($row['processed_at'] ?? '-') ?></div>
              <?php if (! empty($row['created_at'])): ?>
                <div class="text-muted small">Logged <?= esc($row['created_at']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="<?= site_url('admin/claims/batches/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Inspect
              </a>
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
           href="<?= $page <= 1 ? '#' : esc(site_url('admin/claims/batches') . '?' . http_build_query($query + ['page' => $page - 1])) ?>">
          <i class="fa-solid fa-chevron-left me-1"></i>Previous
        </a>
        <a class="btn btn-outline-secondary btn-sm<?= $page >= $pages ? ' disabled' : '' ?>"
           href="<?= $page >= $pages ? '#' : esc(site_url('admin/claims/batches') . '?' . http_build_query($query + ['page' => $page + 1])) ?>">
          Next<i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>


