<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">
      <i class="fa-solid fa-id-card-clip me-2 text-primary"></i>Beneficiary Profile
    </h1>
    <p class="text-muted mb-0">Reference <?= esc($snapshot['reference_number'] ?? '') ?></p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= site_url('helpdesk/beneficiaries') ?>">
      <i class="fa-solid fa-arrow-left-long me-1"></i>Back to search
    </a>
    <a class="btn btn-primary" href="<?= site_url('helpdesk/beneficiaries/' . $snapshot['id'] . '/pdf') ?>">
      <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
    </a>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">
          <i class="fa-solid fa-user me-2 text-primary"></i>Personal Details
        </h5>
        <dl class="row mb-0">
          <dt class="col-sm-4">Name</dt>
          <dd class="col-sm-8"><?= esc(trim(($snapshot['first_name'] ?? '') . ' ' . ($snapshot['last_name'] ?? ''))) ?></dd>

          <dt class="col-sm-4">Category</dt>
          <dd class="col-sm-8"><?= esc($snapshot['category_label'] ?? '-') ?></dd>

          <dt class="col-sm-4">Scheme</dt>
          <dd class="col-sm-8"><?= esc($snapshot['plan_option_label'] ?? '-') ?></dd>

          <dt class="col-sm-4">Date of Birth</dt>
          <dd class="col-sm-8"><?= esc(format_display_time($snapshot['date_of_birth'] ?? null, 'dd MMM yyyy') ?? '-') ?></dd>

          <dt class="col-sm-4">RAO</dt>
          <dd class="col-sm-8"><?= esc($snapshot['rao_label'] ?? $snapshot['rao_other'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">
          <i class="fa-solid fa-address-card me-2 text-primary"></i>Contact
        </h5>
        <dl class="row mb-0">
          <dt class="col-sm-4">Address</dt>
          <dd class="col-sm-8"><?= esc($snapshot['correspondence_address'] ?? '-') ?></dd>

          <dt class="col-sm-4">City</dt>
          <dd class="col-sm-8"><?= esc($snapshot['city'] ?? '-') ?></dd>

          <dt class="col-sm-4">State</dt>
          <dd class="col-sm-8"><?= esc($snapshot['state_name'] ?? '-') ?></dd>

          <dt class="col-sm-4">Primary Mobile</dt>
          <dd class="col-sm-8"><?= esc($snapshot['primary_mobile_masked'] ?? '-') ?></dd>

          <dt class="col-sm-4">Email</dt>
          <dd class="col-sm-8"><?= esc($snapshot['email'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-body-tertiary">
    <h5 class="mb-0">
      <i class="fa-solid fa-people-roof me-2 text-primary"></i>Dependents
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Relationship</th>
            <th>Status</th>
            <th>Health Coverage</th>
            <th>Date of Birth</th>
            <th>Aadhaar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($snapshot['dependents'] ?? [] as $dependent): ?>
            <tr>
              <td><?= esc($dependent['first_name'] ?? '-') ?></td>
              <td><?= esc($dependent['relationship'] ?? '-') ?></td>
              <td><?= esc($dependent['is_alive'] ?? '-') ?></td>
              <td><?= esc($dependent['is_health_dependant'] ?? '-') ?></td>
              <td><?= esc($dependent['date_of_birth'] ?? '-') ?></td>
              <td><?= esc($dependent['aadhaar_masked'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($snapshot['dependents'])): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-3">No dependents recorded.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
