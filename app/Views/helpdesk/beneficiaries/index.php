<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">
      <i class="fa-solid fa-address-book me-2 text-primary"></i>Beneficiary Directory
    </h1>
    <p class="text-muted mb-0">Search beneficiaries within your assigned organisation.</p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form method="get" class="card shadow-sm border-0 mb-4">
  <div class="card-body row g-3 align-items-end">
    <div class="col-md-9">
      <label for="searchInput" class="form-label">
        <i class="fa-solid fa-magnifying-glass me-2 text-secondary"></i>
        Search by name, reference number, PPO, or mobile
      </label>
      <input
        id="searchInput"
        type="text"
        name="q"
        value="<?= esc($query) ?>"
        class="form-control"
        placeholder="e.g. SAHEBRAO or BEN2025..."
        autofocus
      >
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1" type="submit">
        <i class="fa-solid fa-magnifying-glass me-1"></i>Search
      </button>
      <?php if ($query !== ''): ?>
        <a class="btn btn-outline-secondary" href="<?= site_url('helpdesk/beneficiaries') ?>">
          <i class="fa-solid fa-eraser me-1"></i>Clear
        </a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if ($query === ''): ?>
  <div class="alert alert-info">
    <i class="fa-solid fa-circle-info me-2"></i>Enter a keyword above to look up beneficiaries.
  </div>
<?php elseif (empty($results)): ?>
  <div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation me-2"></i>No beneficiaries matched your search.
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col">Reference</th>
              <th scope="col">Name</th>
              <th scope="col">City</th>
              <th scope="col">Contact</th>
              <th scope="col">Company</th>
              <th scope="col" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $row): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= esc($row['reference_number']) ?></div>
                  <?php if (! empty($row['legacy_reference'])): ?>
                    <small class="text-muted">
                      <i class="fa-regular fa-folder me-1"></i>Legacy: <?= esc($row['legacy_reference']) ?>
                    </small>
                  <?php endif; ?>
                </td>
                <td><?= esc(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td>
                <td><?= esc($row['city'] ?? '-') ?></td>
                <td><?= esc($row['primary_mobile_masked'] ?? '-') ?></td>
                <td><?= esc($row['company_name'] ?? 'N/A') ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="<?= site_url('helpdesk/beneficiaries/' . $row['id']) ?>">
                    <i class="fa-regular fa-eye me-1"></i>View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if (($pagination['pages'] ?? 0) > 1): ?>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div>
          Page <?= esc($pagination['page']) ?> of <?= esc($pagination['pages']) ?>
        </div>
        <div class="btn-group">
          <?php $prev = max(1, $pagination['page'] - 1); ?>
          <?php $next = min($pagination['pages'], $pagination['page'] + 1); ?>
          <a class="btn btn-outline-secondary <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>"
             href="<?= esc(site_url('helpdesk/beneficiaries') . '?' . http_build_query(['q' => $query, 'page' => $prev])) ?>">
            Previous
          </a>
          <a class="btn btn-outline-secondary <?= $pagination['page'] >= $pagination['pages'] ? 'disabled' : '' ?>"
             href="<?= esc(site_url('helpdesk/beneficiaries') . '?' . http_build_query(['q' => $query, 'page' => $next])) ?>">
            Next
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>
