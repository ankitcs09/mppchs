<?php
$activeNav = $activeNav ?? 'view-submitted-form';
$detailSections = $detailSections ?? [];
$coveredDependents = $dependentsCovered ?? [];
$otherDependents = $dependentsNotDependent ?? [];
$message = $message ?? null;
$helpdeskMode = ! empty($helpdesk_mode);
$helpdeskBeneficiaryId = $helpdesk_beneficiary_id ?? null;
$helpdeskReference = $helpdesk_reference ?? null;
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title"><?= esc($pageinfo['frmmsg'] ?? 'Submitted Cashless Form') ?></h1>
    <?php if ($helpdeskMode): ?>
      <p class="page-heading__subtitle">
        Helpdesk view of the submitted cashless form. Reference: <?= esc($helpdeskReference ?? '-') ?>.
      </p>
    <?php else: ?>
      <p class="page-heading__subtitle">
        Review captured information for your cashless benefits and raise corrections before hospital admissions.
      </p>
    <?php endif; ?>
  </div>
  <?php if (! empty($lastUpdatedHuman)): ?>
    <span class="badge-soft">Last updated <?= esc($lastUpdatedHuman) ?></span>
  <?php endif; ?>
</div>
<?php if ($helpdeskMode && $helpdeskBeneficiaryId): ?>
  <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="<?= site_url('helpdesk/beneficiaries') ?>">Back to search</a>
    <a class="btn btn-primary" href="<?= site_url('helpdesk/beneficiaries/' . $helpdeskBeneficiaryId . '/pdf') ?>">
      Download PDF
    </a>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! $helpdeskMode): ?>
  <section class="app-panel mb-4">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Before you proceed</h2>
      <p class="app-panel__subtitle">
        Confirm your profile and dependent details so the healthcare team can validate claims without delays.
      </p>
    </header>
    <ul class="app-panel__list mb-0">
      <li>Verify personal, pension, and dependent details for accuracy.</li>
      <li>The <strong>Scheme Option</strong> reflects your current contribution tier.</li>
      <li>Use <strong>Request Edit</strong> if something needs to change before your next claim.</li>
    </ul>
  </section>
<?php endif; ?>

<?php if (! empty($message) && empty($beneficiary)): ?>
  <div class="alert alert-warning" role="alert">
    <?= esc($message) ?>
  </div>
<?php endif; ?>

<?php if (! empty($beneficiary)): ?>
  <?php if ($helpdeskMode && $helpdeskBeneficiaryId): ?>
    <section class="app-panel mb-4">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Helpdesk Edit Request</h2>
        <p class="app-panel__subtitle">Capture beneficiary feedback and send it to the admin review queue.</p>
      </header>
      <form method="post" action="<?= site_url('helpdesk/beneficiaries/' . $helpdeskBeneficiaryId . '/request-edit') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label for="helpdeskNotes" class="form-label">Notes</label>
          <textarea id="helpdeskNotes" name="notes" class="form-control" rows="3" placeholder="Describe the corrections requested" required><?= esc(old('notes')) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Supporting documents (optional)</label>
          <input type="file" name="attachments[]" class="form-control" multiple>
          <div class="form-text">Attach scanned forms or supporting documents (PDF, JPG, PNG).</div>
        </div>
        <button class="btn btn-primary" type="submit">Submit Edit Request</button>
      </form>
    </section>
  <?php endif; ?>

  <section class="app-panel mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
      <div>
        <h2 class="app-panel__title mb-1">Beneficiary snapshot</h2>
        <p class="app-panel__subtitle mb-0">
          Cross-check demographic, pension, and health identifiers.
        </p>
      </div>
      <?php if (! $helpdeskMode): ?>
        <a class="btn btn-outline-primary" href="<?= site_url('dashboard/cashless-form/edit') ?>">Request Edit</a>
      <?php endif; ?>
    </div>

    <?php if (! empty($detailSections)): ?>
      <div class="row g-4">
        <?php foreach ($detailSections as $section): ?>
          <div class="col-lg-6">
            <section class="app-panel app-panel--compact h-100">
              <header class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-uppercase small text-muted fw-semibold mb-0"><?= esc($section['title']) ?></h3>
              </header>
              <?php $rows = $section['rows'] ?? $section['items'] ?? []; ?>
              <?php if (! empty($rows)): ?>
                <table class="table table-sm table-borderless align-middle mb-0">
                  <tbody>
                    <?php foreach ($rows as $item): ?>
                      <tr>
                        <th scope="row" class="text-muted" style="width: 40%;"><?= esc($item['label']) ?></th>
                        <td><?= esc($item['value']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p class="text-muted small mb-0">No information recorded for this section.</p>
              <?php endif; ?>
            </section>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="app-panel mb-4">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Dependents covered</h2>
      <p class="app-panel__subtitle">
        Family members currently eligible for scheme benefits. Confirm their identifiers and coverage flags.
      </p>
    </header>
    <?php if (! empty($coveredDependents)): ?>
      <div class="table-surface table-stack-mobile">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Relation</th>
              <th scope="col">Status</th>
              <th scope="col">Health Coverage</th>
              <th scope="col">Name</th>
              <th scope="col">Gender</th>
              <th scope="col">Blood Group</th>
              <th scope="col">Date of Birth</th>
              <th scope="col">Aadhaar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($coveredDependents as $dependent): ?>
              <?php
                $healthLabel = $dependent['health_label'] ?? null;
                if ($healthLabel === null && array_key_exists('is_dependent_for_health', $dependent)) {
                    if (! empty($dependent['is_dependent_for_health'])) {
                        $healthLabel = 'Yes';
                    } elseif (strtolower((string) ($dependent['status'] ?? '')) === 'not applicable') {
                        $healthLabel = 'Not Applicable';
                    } elseif (strtolower((string) ($dependent['status'] ?? '')) === 'not provided') {
                        $healthLabel = 'Not Provided';
                    } else {
                        $healthLabel = 'No';
                    }
                }
              ?>
              <tr>
                <td data-label="#"><?= esc($dependent['order']) ?></td>
                <td data-label="Relation"><?= esc($dependent['relation']) ?></td>
                <td data-label="Status"><?= esc($dependent['status']) ?></td>
                <td data-label="Health Coverage"><?= esc($healthLabel ?? 'Not Provided') ?></td>
                <td data-label="Name"><?= esc($dependent['name']) ?></td>
                <td data-label="Gender"><?= esc($dependent['gender'] ?? 'Not Provided') ?></td>
                <td data-label="Blood Group"><?= esc($dependent['blood_group']) ?></td>
                <td data-label="Date of Birth"><?= esc($dependent['date_of_birth']) ?></td>
                <td data-label="Aadhaar"><?= esc($dependent['aadhar_number']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-muted mb-0">No dependents are currently marked as covered.</p>
    <?php endif; ?>
  </section>

  <section class="app-panel">
    <header class="app-panel__header">
      <h2 class="app-panel__title mb-1">Other family members</h2>
      <p class="app-panel__subtitle">
        Family members recorded for reference. Update their coverage if eligibility changes.
      </p>
    </header>
    <?php if (! empty($otherDependents)): ?>
      <div class="table-surface table-stack-mobile">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Relation</th>
              <th scope="col">Status</th>
              <th scope="col">Health Coverage</th>
              <th scope="col">Name</th>
              <th scope="col">Gender</th>
              <th scope="col">Blood Group</th>
              <th scope="col">Date of Birth</th>
              <th scope="col">Aadhaar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($otherDependents as $dependent): ?>
              <?php
                $healthLabel = $dependent['health_label'] ?? null;
                if ($healthLabel === null && array_key_exists('is_dependent_for_health', $dependent)) {
                    if (! empty($dependent['is_dependent_for_health'])) {
                        $healthLabel = 'Yes';
                    } elseif (strtolower((string) ($dependent['status'] ?? '')) === 'not applicable') {
                        $healthLabel = 'Not Applicable';
                    } elseif (strtolower((string) ($dependent['status'] ?? '')) === 'not provided') {
                        $healthLabel = 'Not Provided';
                    } else {
                        $healthLabel = 'No';
                    }
                }
              ?>
              <tr>
                <td data-label="#"><?= esc($dependent['order']) ?></td>
                <td data-label="Relation"><?= esc($dependent['relation']) ?></td>
                <td data-label="Status"><?= esc($dependent['status']) ?></td>
                <td data-label="Health Coverage"><?= esc($healthLabel ?? 'Not Provided') ?></td>
                <td data-label="Name"><?= esc($dependent['name']) ?></td>
                <td data-label="Gender"><?= esc($dependent['gender'] ?? 'Not Provided') ?></td>
                <td data-label="Blood Group"><?= esc($dependent['blood_group']) ?></td>
                <td data-label="Date of Birth"><?= esc($dependent['date_of_birth']) ?></td>
                <td data-label="Aadhaar"><?= esc($dependent['aadhar_number']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-muted mb-0">No additional family members recorded.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?= $this->endSection() ?>
