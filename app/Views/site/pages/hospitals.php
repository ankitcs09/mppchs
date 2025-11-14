<?php
$activeNav   = 'hospitals';
$landingStats = $stats ?? [
    'states'        => 0,
    'cities'        => 0,
    'hospitals'     => 0,
    'beneficiaries' => 0,
    'generatedAt'   => date(DATE_ATOM),
];
$lastUpdated = ! empty($landingStats['generatedAt'])
    ? date('d M Y', strtotime((string) $landingStats['generatedAt']))
    : date('d M Y');
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>MPPCHS Coverage Snapshot</h2>
    <p>Live view of the cashless network across India.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="row text-center gy-4">
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-map-location-dot fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="states"><?= number_format((int) ($landingStats['states'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">States covered</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-city fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="cities"><?= number_format((int) ($landingStats['cities'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Cities reached</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-hospital fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="hospitals"><?= number_format((int) ($landingStats['hospitals'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Empanelled hospitals</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-people-group fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="beneficiaries"><?= number_format((int) ($landingStats['beneficiaries'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Beneficiaries protected</p>
        </div>
      </div>
    </div>
    <p class="text-center text-muted small mt-3">Last refreshed <span data-stat="timestamp"><?= esc($lastUpdated) ?></span></p>
  </div>
</section>

<section id="hospital-search" class="section py-5 bg-light">
  <div class="container section-title" data-aos="fade-up">
    <h2>Find Empanelled Hospitals</h2>
    <p>Select a State and City to view currently available hospitals under the scheme.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="row justify-content-center mb-4">
      <div class="col-md-4 mb-3">
        <label for="state" class="form-label fw-semibold">Select State / राज्य चुनें</label>
        <select id="state" class="form-select">
          <option value="">-- Select State --</option>
        </select>
      </div>
      <div class="col-md-4 mb-3">
        <label for="city" class="form-label fw-semibold">Select City / शहर चुनें</label>
        <select id="city" class="form-select" disabled>
          <option value="">-- Select City --</option>
        </select>
      </div>
    </div>
    <div id="hospital-panel" class="card shadow-sm mt-4 d-none">
      <button class="card-header bg-white border-0 d-flex justify-content-between align-items-center text-start fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hospital-collapse" aria-expanded="false" aria-controls="hospital-collapse">
        <span>Hospitals in <span id="selected-region">selected location</span></span>
        <span class="badge bg-primary rounded-pill" id="hospital-count">0</span>
      </button>
      <div id="hospital-collapse" class="collapse" data-bs-parent="#hospital-panel">
        <div class="card-body">
          <div id="hospital-table-wrapper" class="table-responsive d-none">
            <table class="table table-sm table-striped table-hover align-middle text-center">
              <thead class="table-primary">
                <tr>
                  <th style="width: 6%;">SN</th>
                  <th style="width: 36%;">Hospital Name</th>
                  <th style="width: 20%;">City</th>
                  <th style="width: 20%;">State</th>
                  <th style="width: 18%;">Contact</th>
                </tr>
              </thead>
              <tbody id="hospital-list"></tbody>
            </table>
          </div>
          <div id="hospital-pagination" class="d-flex justify-content-between align-items-center mt-3 d-none">
            <span class="text-muted small" id="hospital-range"></span>
            <div class="btn-group">
              <button type="button" class="btn btn-outline-primary btn-sm" id="hospital-prev">Previous</button>
              <button type="button" class="btn btn-outline-primary btn-sm" id="hospital-next">Next</button>
            </div>
          </div>
          <p class="text-muted small mb-0 text-center" id="hospital-hint">Select a state and city to view empanelled hospitals.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('site/partials/hospital_script') ?>
<?= $this->endSection() ?>
