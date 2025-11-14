<?php
$activeNav = 'request-hospital';
$requests  = $requests ?? [];
$stats     = $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Hospital Addition Requests</h1>
    <p class="text-muted mb-0">Submit a new hospital for review and track your previous submissions.</p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div
  data-module="hospital-request"
  data-states-endpoint="<?= site_url('hospitals/states') ?>"
  data-cities-endpoint="<?= site_url('hospitals/request-cities') ?>"
  data-duplicate-endpoint="<?= site_url('hospitals/check-duplicate') ?>"
  data-submit-endpoint="<?= site_url('hospitals/request') ?>"
  data-default-state="Madhya Pradesh"
>
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase fw-semibold">Total Requests</small>
        <h3 class="mt-2 mb-0"><?= number_format((int) ($stats['total'] ?? 0)) ?></h3>
        <p class="text-muted small mb-0">Submitted so far</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase fw-semibold">Pending Review</small>
        <h3 class="mt-2 mb-0"><?= number_format((int) ($stats['pending'] ?? 0)) ?></h3>
        <p class="text-muted small mb-0">Awaiting action</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase fw-semibold">Approved</small>
        <h3 class="mt-2 mb-0 text-success"><?= number_format((int) ($stats['approved'] ?? 0)) ?></h3>
        <p class="text-muted small mb-0">Added to the network</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <small class="text-muted text-uppercase fw-semibold">Rejected</small>
        <h3 class="mt-2 mb-0 text-danger"><?= number_format((int) ($stats['rejected'] ?? 0)) ?></h3>
        <p class="text-muted small mb-0">Need follow-up</p>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm bg-light mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3">Disclaimer / अस्वीकरण</h5>
    <p class="mb-2 small text-muted">
      <span class="fw-semibold">Disclaimer:</span>
      The request received from the pensioner for addition of a hospital shall be evaluated by MPPGCL. If found suitable and feasible, the suggested hospital may be considered for empanelment. However, the decision to empanel any hospital shall rest solely at the discretion of MPPGCL.
    </p>
    <p class="mb-0 small text-muted">
      <span class="fw-semibold">अस्वीकरण:</span>
      पेंशनर द्वारा अस्पताल जोड़े जाने के लिए दी गई जानकारी का मूल्यांकन म.प्र. पावर जनरेटिंग कंपनी लिमिटेड (MPPGCL) द्वारा किया जाएगा। यदि उपयुक्त एवं व्यवहार्य पाया गया, तो सुझाए गए अस्पताल को पैनल में शामिल करने पर विचार किया जा सकता है। तथापि, किसी भी अस्पताल को पैनल में शामिल करने का निर्णय पूर्णतः MPPGCL के विवेकाधिकार पर निर्भर करेगा।
    </p>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div id="requestAlert" class="alert d-none" role="alert"></div>

        <h4 class="mb-3">Submit a New Request</h4>
        <p class="text-muted small mb-4">We check for duplicates automatically. Fill in as much detail as possible for faster approval.</p>

        <form id="hospitalRequestForm" novalidate>
          <?= csrf_field() ?>

          <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-3">Location</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="requestState" class="form-label">State *</label>
                <select id="requestState" name="state_id" class="form-select" required>
                  <option value="">Select state</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="requestCity" class="form-label">City *</label>
                <select id="requestCity" name="city_id" class="form-select" disabled required>
                  <option value="">Select city</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-3">Hospital Details</h6>
            <div class="row g-3">
              <div class="col-md-8">
                <label for="hospitalName" class="form-label">Hospital Name *</label>
                <input type="text" id="hospitalName" name="hospital_name" class="form-control" required>
                <div id="duplicateHint" class="form-text d-none"></div>
              </div>
              <div class="col-md-4">
                <label for="contactPerson" class="form-label">Contact Person</label>
                <input type="text" id="contactPerson" name="contact_person" class="form-control">
              </div>
              <div class="col-md-6">
                <label for="contactPhone" class="form-label">Contact Phone</label>
                <input type="text" id="contactPhone" name="contact_phone" class="form-control">
              </div>
              <div class="col-md-6">
                <label for="contactEmail" class="form-label">Contact Email</label>
                <input type="email" id="contactEmail" name="contact_email" class="form-control">
              </div>
              <div class="col-12">
                <label for="hospitalAddress" class="form-label">Address</label>
                <input type="text" id="hospitalAddress" name="address" class="form-control" placeholder="Street, locality, landmarks">
              </div>
              <div class="col-12">
                <label for="requestNotes" class="form-label">Notes</label>
                <textarea id="requestNotes" name="notes" class="form-control" rows="3" placeholder="Any additional information for the review team"></textarea>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">
              <i class="fa-solid fa-paper-plane me-1"></i>Submit request
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">How the review works</h5>
        <ol class="text-muted small mb-4">
          <li>We confirm the hospital is not already empanelled.</li>
          <li>Your request is verified by the concerned teams.</li>
          <li>After tie up is successful between our ISA and the hospital for empanelment with MP Power Companies the hospital is added in the empanelled list</li>
        </ol>
        <div class="bg-light rounded p-3">
          <h6 class="text-muted text-uppercase small fw-semibold mb-2">Tips</h6>
          <ul class="text-muted small mb-0">
            <li>Before submitting a requets for addition please peform the duplication check</li>
            <li>Include contact information so the team can verify quickly.</li>
            <li>You can track the status of each request below.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h5 class="mb-0">My Requests</h5>
      <span class="text-muted small ms-2">Statuses update as soon as the review team takes action.</span>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0" id="userRequestsTable">
        <thead class="table-light">
          <tr>
            <th scope="col" style="min-width: 160px;">Reference</th>
            <th scope="col" style="min-width: 180px;">Hospital</th>
            <th scope="col">State</th>
            <th scope="col">City</th>
            <th scope="col">Status</th>
            <th scope="col">Requested On</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! empty($requests)) : ?>
            <?php foreach ($requests as $item) : ?>
              <?php
                $statusRaw   = strtolower((string) ($item['status'] ?? 'pending'));
                $statusLabel = ucfirst($statusRaw);
                $badgeClass  = 'bg-secondary';
                if ($statusRaw === 'approved') {
                    $badgeClass = 'bg-success';
                } elseif ($statusRaw === 'rejected') {
                    $badgeClass = 'bg-danger';
                } elseif ($statusRaw === 'in_review') {
                    $badgeClass = 'bg-warning text-dark';
                    $statusLabel = 'In Review';
                } elseif ($statusRaw === 'pending') {
                    $badgeClass = 'bg-info text-dark';
                }
              ?>
              <tr>
                <td><?= esc($item['reference_number'] ?? '-') ?></td>
                <td><?= esc($item['hospital_name'] ?? '-') ?></td>
                <td><?= esc($item['state_name'] ?? '-') ?></td>
                <td><?= esc($item['city_name'] ?? '-') ?></td>
                <td><span class="badge <?= esc($badgeClass) ?>"><?= esc($statusLabel) ?></span></td>
                <td><?= esc($item['created_at_display'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else : ?>
            <tr id="noRequestsRow">
              <td colspan="6" class="text-center text-muted py-4">
                No requests submitted yet. Use the form above to raise one.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?= $this->endSection() ?>
