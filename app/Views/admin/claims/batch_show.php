<?php

$batch = $batch ?? null;
if (! $batch) {
    echo '<p class="text-danger">Batch details unavailable.</p>';
    return;
}

$items = $batch['items'] ?? [];
$notes = $batch['notes'] ?? [];
$companyIds = $batch['company_ids'] ?? [];
$documentsSummary = $batch['documents_summary'] ?? ['totals' => [], 'matrix' => []];
$docTotals = $documentsSummary['totals'] ?? [];
$docMatrix = $documentsSummary['matrix'] ?? [];
?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Batch <?= esc($batch['batch_reference'] ?? ('#' . $batch['id'])) ?></h1>
    <p class="text-muted mb-0">Processed <?= esc($batch['processed_at'] ?? '-') ?> &middot; <?= esc($batch['claims']['received'] ?? 0) ?> claims received.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('admin/claims/batches') ?>" class="btn btn-outline-secondary btn-sm">Back to batches</a>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Summary</h6>
        <dl class="row mb-0">
          <dt class="col-6">Batch ID</dt>
          <dd class="col-6 text-end"><?= esc($batch['id']) ?></dd>
          <dt class="col-6">Received</dt>
          <dd class="col-6 text-end"><?= esc(number_format($batch['claims']['received'] ?? 0)) ?></dd>
          <dt class="col-6">Success</dt>
          <dd class="col-6 text-end"><?= esc(number_format($batch['claims']['success'] ?? 0)) ?></dd>
          <dt class="col-6">Failed</dt>
          <dd class="col-6 text-end">
            <?php $failed = (int) ($batch['claims']['failed'] ?? 0); ?>
            <?php if ($failed > 0): ?>
              <span class="badge text-bg-danger"><?= esc($failed) ?></span>
            <?php else: ?>
              <?= esc($failed) ?>
            <?php endif; ?>
          </dd>
          <dt class="col-6">Source IP</dt>
          <dd class="col-6 text-end"><?= esc($batch['source']['ip'] ?? '-') ?></dd>
          <dt class="col-6">User agent</dt>
          <dd class="col-6 text-end text-truncate" title="<?= esc($batch['source']['user_agent'] ?? '-') ?>">
            <?= esc($batch['source']['user_agent'] ?? '-') ?>
          </dd>
          <dt class="col-6">Company IDs</dt>
          <dd class="col-6 text-end">
            <?php if (empty($companyIds)): ?>
              <span class="text-muted">Unknown</span>
            <?php else: ?>
              <?= esc(implode(', ', $companyIds)) ?>
            <?php endif; ?>
          </dd>
          <?php $docSummarySuccess = (int) (($docTotals['ingested'] ?? 0) + ($docTotals['updated'] ?? 0)); ?>
          <dt class="col-6">Docs attempted</dt>
          <dd class="col-6 text-end"><?= esc(number_format($docTotals['attempted'] ?? 0)) ?></dd>
          <dt class="col-6">Docs successful</dt>
          <dd class="col-6 text-end"><?= esc(number_format($docSummarySuccess)) ?></dd>
          <dt class="col-6">Docs failed</dt>
          <dd class="col-6 text-end"><?= esc(number_format($docTotals['failed'] ?? 0)) ?></dd>
        </dl>
        <?php $matrixTotal = array_sum($docMatrix ?? []); ?>
        <?php if ($matrixTotal > 0): ?>
          <div class="mt-3">
            <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-2">Record vs document</h6>
            <ul class="list-unstyled mb-0 small">
              <li>Record ok &amp; docs ok: <?= esc($docMatrix['record_ok_doc_ok'] ?? 0) ?></li>
              <li>Record ok &amp; docs failed: <?= esc($docMatrix['record_ok_doc_failed'] ?? 0) ?></li>
              <li>Record ok &amp; docs missing: <?= esc($docMatrix['record_ok_doc_missing'] ?? 0) ?></li>
              <li>Record fail &amp; docs received: <?= esc($docMatrix['record_fail_doc_ok'] ?? 0) ?></li>
              <li>Record fail &amp; docs failed: <?= esc($docMatrix['record_fail_doc_failed'] ?? 0) ?></li>
              <li>Record fail &amp; docs missing: <?= esc($docMatrix['record_fail_doc_missing'] ?? 0) ?></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <?php if (! empty($notes)): ?>
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Failure notes</h6>
          <ul class="mb-0 ps-3">
            <?php foreach ($notes as $note): ?>
              <li><?= esc($note) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="text-uppercase text-muted fw-semibold fs-12 mb-3">Claim results</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Reference</th>
                <th scope="col">Status</th>
                <th scope="col">Message</th>
                <th scope="col" class="text-end">Events</th>
                <th scope="col" class="text-end">Documents</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-3">No per-claim details captured for this batch.</td>
                </tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= esc($item['reference'] ?? '-') ?></div>
                    <?php if (! empty($item['claim_id'])): ?>
                      <div class="text-muted small">Claim ID <?= esc($item['claim_id']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $status = $item['status'] ?? 'unknown'; ?>
                    <?php if ($status === 'success'): ?>
                      <span class="badge text-bg-success">Success</span>
                    <?php else: ?>
                      <span class="badge text-bg-warning text-dark"><?= esc(ucfirst($status)) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-break"><?= esc($item['message'] ?? '-') ?></td>
                  <td class="text-end"><?= esc($item['events_ingested'] ?? 0) ?></td>
                  <?php
                    $doc = $item['documents'] ?? [];
                    $docAttemptedItem = (int) ($doc['attempted'] ?? ($item['documents_expected'] ?? 0));
                    $docIngestedItem = (int) ($doc['ingested'] ?? ($item['documents_ingested'] ?? 0));
                    $docFailedItem = (int) ($doc['failed'] ?? max(0, $docAttemptedItem - $docIngestedItem));
                    $docState = $doc['state'] ?? ($docAttemptedItem === 0 ? 'missing' : ($docFailedItem > 0 ? 'failed' : 'ok'));
                    $docMessages = $doc['messages'] ?? [];
                    $docBadgeClass = match ($docState) {
                        'ok'      => 'text-bg-success',
                        'partial' => 'text-bg-warning text-dark',
                        'failed'  => 'text-bg-danger',
                        default   => 'text-bg-secondary',
                    };
                    $docBadgeLabel = match ($docState) {
                        'ok'      => 'Docs ok',
                        'partial' => 'Docs partial',
                        'failed'  => 'Docs failed',
                        default   => 'No docs',
                    };
                  ?>
                  <td class="text-end">
                    <?php if ($docAttemptedItem === 0): ?>
                      <span class="text-muted">No docs</span>
                    <?php else: ?>
                      <div><?= esc($docIngestedItem) ?> / <?= esc($docAttemptedItem) ?> ingested</div>
                      <?php if ($docFailedItem > 0): ?>
                        <div class="text-muted small">Failed <?= esc($docFailedItem) ?></div>
                      <?php endif; ?>
                      <span class="badge <?= $docBadgeClass ?>"><?= esc($docBadgeLabel) ?></span>
                    <?php endif; ?>
                    <?php foreach (array_slice($docMessages, 0, 2) as $docMessage): ?>
                      <div class="text-muted small"><?= esc($docMessage) ?></div>
                    <?php endforeach; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (count($items) >= 200): ?>
          <p class="small text-muted mt-3 mb-0">Only the first 200 items are shown. Export the raw metadata for the full list.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
