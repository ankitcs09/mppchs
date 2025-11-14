<?php

$request = $request ?? [];
$before  = $before ?? [];
$after   = $after ?? [];
$summary = $summary ?? [];
$diff    = $diff ?? [];

$beneficiaryDiff = $diff['beneficiary'] ?? [];
$dependentDiff   = $diff['dependents'] ?? [];

$statusLabels = [
    'pending'    => lang('ChangeRequests.filtersPending'),
    'approved'   => lang('ChangeRequests.filtersApproved'),
    'rejected'   => lang('ChangeRequests.filtersRejected'),
    'needs_info' => lang('ChangeRequests.filtersNeedsInfo'),
    'draft'      => 'Draft',
];

$status        = $request['status'] ?? 'pending';
$statusLabel   = $statusLabels[$status] ?? ucfirst($status);
$statusBadge   = match ($status) {
    'approved'   => 'success',
    'rejected'   => 'danger',
    'needs_info' => 'warning text-dark',
    default      => 'info',
};

$summaryCards = [
    [
        'label' => lang('ChangeRequests.summaryBeneficiary'),
        'value' => $summary['beneficiary_changes'] ?? 0,
    ],
    [
        'label' => lang('ChangeRequests.summaryDependentAdds'),
        'value' => $summary['dependent_adds'] ?? 0,
    ],
    [
        'label' => lang('ChangeRequests.summaryDependentUpdates'),
        'value' => $summary['dependent_updates'] ?? 0,
    ],
    [
        'label' => lang('ChangeRequests.summaryDependentRemovals'),
        'value' => $summary['dependent_removals'] ?? 0,
    ],
];

$items = $items ?? [];
$defaultItemCounts = [
    'total'      => count($items),
    'pending'    => 0,
    'approved'   => 0,
    'rejected'   => 0,
    'needs_info' => 0,
];
$itemCounts = array_merge($defaultItemCounts, $itemCounts ?? []);

$itemStatusLabels = [
    'pending'    => lang('ChangeRequests.filtersPending'),
    'approved'   => lang('ChangeRequests.filtersApproved'),
    'rejected'   => lang('ChangeRequests.filtersRejected'),
    'needs_info' => lang('ChangeRequests.filtersNeedsInfo'),
];

$itemStatusClasses = [
    'pending'    => 'secondary',
    'approved'   => 'success',
    'rejected'   => 'danger',
    'needs_info' => 'warning text-dark',
];

$formatItemValue = static function ($value): string {
    if ($value === null || $value === '') {
        return '<span class="text-muted">—</span>';
    }

    $decoded = json_decode((string) $value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $chunks = [];
        foreach ($decoded as $key => $val) {
            $chunks[] = '<div><span class="text-muted small me-1">' . esc((string) $key) . ':</span>' . esc(is_scalar($val) ? (string) $val : json_encode($val)) . '</div>';
        }
        return implode('', $chunks);
    }

    return nl2br(esc((string) $value));
};
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">
      <?= sprintf(lang('ChangeRequests.heading'), esc($request['id'] ?? '-')) ?>
      <span class="badge bg-<?= $statusBadge ?> ms-2"><?= esc($statusLabel) ?></span>
    </h1>
    <p class="page-heading__subtitle">
      <?= sprintf(
          lang('ChangeRequests.submittedMeta'),
          esc($request['reference_number'] ?? '-'),
          esc($request['requested_at'] ?? '-')
      ) ?>
    </p>
  </div>
  <a href="<?= site_url('admin/change-requests') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-arrow-left-long me-1"></i>Back to list
  </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
  <div class="col-lg-4 col-md-6">
    <section class="app-panel app-panel--compact h-100">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Submission</h2>
        <p class="app-panel__subtitle">Key metadata for this change request.</p>
      </header>
      <dl class="row gy-2 mb-0 small text-muted">
        <dt class="col-5">Submitted</dt>
        <dd class="col-7 text-body"><?= esc($request['requested_at'] ?? '-') ?></dd>
        <dt class="col-5">Submitted by</dt>
        <dd class="col-7 text-body"><?= esc($request['display_name'] ?? $request['username'] ?? '-') ?> (ID <?= esc($request['user_id'] ?? '-') ?>)</dd>
        <dt class="col-5">Reviewed</dt>
        <dd class="col-7 text-body"><?= esc($request['reviewed_at'] ?? 'Awaiting review') ?></dd>
        <dt class="col-5">Reviewer</dt>
        <dd class="col-7 text-body"><?= esc($request['reviewed_by'] ?? '—') ?></dd>
        <?php if (! empty($request['review_comment'])): ?>
          <dt class="col-5">Comment</dt>
          <dd class="col-7 text-body"><?= esc($request['review_comment']) ?></dd>
        <?php endif; ?>
      </dl>
    </section>
  </div>
  <div class="col-lg-8 col-md-6">
    <section class="app-panel app-panel--compact h-100">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1"><?= lang('ChangeRequests.summaryTitle') ?></h2>
        <p class="app-panel__subtitle"><?= lang('ChangeRequests.summarySubtitle') ?></p>
      </header>
      <div class="row g-3">
        <?php foreach ($summaryCards as $card): ?>
          <div class="col-sm-6 col-xl-3">
            <div class="dashboard-card h-100">
              <span class="kpi-label"><?= esc($card['label']) ?></span>
              <span class="kpi-value"><?= esc($card['value']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<section class="app-panel mb-3" data-module="admin-change-request">
  <header class="app-panel__header flex-wrap gap-3">
    <div>
      <h2 class="app-panel__title mb-1"><?= lang('ChangeRequests.reviewChecklist') ?></h2>
      <p class="app-panel__subtitle mb-0"><?= lang('ChangeRequests.reviewChecklistHelp') ?></p>
    </div>
    <div class="change-filter-pills ms-auto">
      <?php foreach (['pending', 'approved', 'rejected', 'needs_info'] as $filter): ?>
        <?php $isActive = $filter === 'pending'; ?>
        <button
          type="button"
          class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>"
          data-filter-status="<?= esc($filter) ?>"
          <?= $isActive ? 'data-filter-active="true"' : '' ?>
        >
          <?php
            $iconMap = [
                'pending'    => 'fa-regular fa-clock',
                'approved'   => 'fa-solid fa-circle-check',
                'rejected'   => 'fa-solid fa-circle-xmark',
                'needs_info' => 'fa-solid fa-circle-question',
            ];
            $icon = $iconMap[$filter] ?? 'fa-regular fa-circle';
          ?>
          <i class="<?= esc($icon) ?> me-1"></i><?= esc($itemStatusLabels[$filter]) ?>
          <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($itemCounts[$filter] ?? 0) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </header>

  <?php if (empty($items)): ?>
    <div class="empty-state text-center py-5">
      <div class="empty-state__icon rounded-circle mx-auto mb-3">
        <i class="fa-solid fa-square-check"></i>
      </div>
      <h3 class="h5 mb-2">Nothing to review</h3>
      <p class="text-muted mb-0">No granular changes were captured for this submission.</p>
    </div>
  <?php else: ?>
    <div class="vstack gap-3" data-role="item-list">
      <?php foreach ($items as $item): ?>
        <?php
          $itemStatus = $item['status'] ?? 'pending';
          $statusBadge = $itemStatusClasses[$itemStatus] ?? 'secondary';
          $entityLabel = ($item['entity_type'] ?? 'beneficiary') === 'beneficiary'
            ? 'Beneficiary'
            : 'Dependent';
          $fieldLabel = $item['field_label'] ?? $item['field_key'];
          $notePresent = ! empty($item['review_note']);
        ?>
        <article
          class="change-item-card"
          data-item-status="<?= esc($itemStatus) ?>"
        >
          <div class="change-item-card__header">
            <div class="change-item-card__labels">
              <span class="badge text-bg-light text-dark"><?= esc($entityLabel) ?></span>
              <?php if (! empty($item['entity_identifier']) && $entityLabel === 'Dependent'): ?>
                <span class="badge text-bg-info-subtle text-dark">ID <?= esc($item['entity_identifier']) ?></span>
              <?php endif; ?>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
              <?php if (! empty($item['reviewed_by'])): ?>
                <span class="text-muted small">
                  <?php if (! empty($item['reviewed_at'])): ?>
                    <?= esc(date('d M, H:i', strtotime($item['reviewed_at']))) ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
              <span class="badge text-bg-<?= esc($statusBadge) ?>"><?= esc($itemStatusLabels[$itemStatus] ?? ucfirst($itemStatus)) ?></span>
            </div>
          </div>

          <div class="change-item-card__body">
            <p class="change-item-card__field mb-3">
              <strong><?= esc($fieldLabel) ?></strong>
            </p>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="change-item-card__value text-muted">Current value</div>
                <div class="change-item-card__value-body">
                  <?= $formatItemValue($item['old_value'] ?? null) ?>
                </div>
              </div>
              <div class="col-md-6">
                <div class="change-item-card__value text-muted">Proposed value</div>
                <div class="change-item-card__value-body">
                  <?= $formatItemValue($item['new_value'] ?? null) ?>
                </div>
              </div>
            </div>
          </div>

          <footer class="change-item-card__footer">
            <form
              method="post"
              action="<?= site_url('admin/change-requests/' . $request['id'] . '/items/' . $item['id']) ?>"
              class="change-item-card__form"
            >
              <?= csrf_field() ?>
              <div class="input-group input-group-sm flex-grow-1">
                <span class="input-group-text">Note</span>
                <input
                  type="text"
                  name="note"
                  class="form-control"
                  placeholder="Optional note"
                  value="<?= esc($item['review_note'] ?? '') ?>"
                />
              </div>
              <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Review actions">
                <button type="submit" name="status" value="approved" class="btn btn-outline-success">
                  <i class="fa-solid fa-circle-check me-1"></i>Approve
                </button>
                <button type="submit" name="status" value="rejected" class="btn btn-outline-danger">
                  <i class="fa-solid fa-circle-xmark me-1"></i>Reject
                </button>
                <button type="submit" name="status" value="needs_info" class="btn btn-outline-warning text-dark">
                  <i class="fa-solid fa-circle-question me-1"></i>Needs info
                </button>
                <button type="submit" name="status" value="pending" class="btn btn-outline-secondary">
                  <i class="fa-solid fa-rotate-left me-1"></i>Reset
                </button>
              </div>
            </form>
            <?php if ($notePresent): ?>
              <p class="small text-muted mb-0 mt-2">
                <i class="fa-solid fa-comments me-1"></i>
                <?= esc($item['review_note']) ?>
              </p>
            <?php endif; ?>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (! empty($beneficiaryDiff)): ?>
  <section class="app-panel mb-3">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Beneficiary fields</h2>
      <p class="app-panel__subtitle">Side-by-side comparison of existing data and the proposed change.</p>
    </header>
    <div class="table-surface table-stack-mobile">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Field</th>
            <th scope="col">Current value</th>
            <th scope="col">Proposed value</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($beneficiaryDiff as $field => $change): ?>
            <tr>
              <td data-label="Field" class="text-uppercase small text-muted fw-semibold"><?= esc(str_replace('_', ' ', $field)) ?></td>
              <td data-label="Current"><?= esc($change['before'] ?? '-') ?></td>
              <td data-label="Proposed" class="text-success fw-semibold"><?= esc($change['after'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php if (! empty($dependentDiff)): ?>
  <section class="app-panel mb-3">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Dependents</h2>
      <p class="app-panel__subtitle">Proposed additions, updates, or removals for dependents.</p>
    </header>
    <div class="table-surface table-stack-mobile">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Dependent</th>
            <th scope="col">Action</th>
            <th scope="col">Current</th>
            <th scope="col">Proposed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dependentDiff as $change): ?>
            <?php
              $actionBadge = match ($change['action']) {
                  'add'    => 'success',
                  'remove' => 'danger',
                  default  => 'warning text-dark',
              };
            ?>
            <tr>
              <td data-label="Dependent"><?= esc($change['before']['first_name'] ?? $change['after']['first_name'] ?? '-') ?></td>
              <td data-label="Action">
                <span class="badge bg-<?= $actionBadge ?>"><?= ucfirst($change['action']) ?></span>
              </td>
              <td data-label="Current">
                <?php if ($change['before']): ?>
                  <div class="small text-muted">
                    <div><?= esc($change['before']['relationship'] ?? '-') ?></div>
                    <div>Coverage: <?= esc($change['before']['is_health_dependant'] ?? '-') ?></div>
                    <div>Status: <?= esc($change['before']['is_alive'] ?? '-') ?></div>
                    <div>DOB: <?= esc($change['before']['date_of_birth'] ?? '-') ?></div>
                  </div>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td data-label="Proposed">
                <?php if ($change['after']): ?>
                  <div class="small">
                    <div><?= esc($change['after']['relationship'] ?? '-') ?></div>
                    <div>Coverage: <?= esc($change['after']['is_health_dependant'] ?? '-') ?></div>
                    <div>Status: <?= esc($change['after']['is_alive'] ?? '-') ?></div>
                    <div>DOB: <?= esc($change['after']['date_of_birth'] ?? '-') ?></div>
                  </div>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php if ($status === 'pending' || $status === 'needs_info'): ?>
  <section class="app-panel">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Reviewer actions</h2>
      <p class="app-panel__subtitle">Approve, reject, or ask for more details. Comments will be visible to the submitter.</p>
    </header>
    <div class="vstack gap-3">
      <form method="post" action="<?= site_url('admin/change-requests/' . $request['id'] . '/approve') ?>" class="row g-2 align-items-center">
        <?= csrf_field() ?>
        <div class="col-sm">
          <input type="text" name="comment" class="form-control form-control-sm" placeholder="Reviewer comment (optional)">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-success btn-sm">
            <i class="fa-solid fa-circle-check me-1"></i>Approve
          </button>
        </div>
      </form>

      <form method="post" action="<?= site_url('admin/change-requests/' . $request['id'] . '/reject') ?>" class="row g-2 align-items-center">
        <?= csrf_field() ?>
        <div class="col-sm">
          <input type="text" name="comment" class="form-control form-control-sm" placeholder="Reason for rejection" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-circle-xmark me-1"></i>Reject
          </button>
        </div>
      </form>

      <form method="post" action="<?= site_url('admin/change-requests/' . $request['id'] . '/needs-info') ?>" class="row g-2 align-items-center">
        <?= csrf_field() ?>
        <div class="col-sm">
          <input type="text" name="comment" class="form-control form-control-sm" placeholder="Describe the additional information required" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-warning btn-sm text-dark">
            <i class="fa-solid fa-circle-question me-1"></i>Request info
          </button>
        </div>
      </form>
    </div>
  </section>
<?php endif; ?>
<?= $this->endSection() ?>
