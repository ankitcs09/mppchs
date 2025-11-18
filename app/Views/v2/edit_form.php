<?php

$form    = $form ?? [];
$masters = $masters ?? [];
$errors  = $errors ?? session('errors') ?? [];

$beneficiaryForm = $form['beneficiary'] ?? $form;

$value = static function (string $key) use ($beneficiaryForm) {
    $old = old($key);
    if ($old !== null) {
        return $old;
    }

    return $beneficiaryForm[$key] ?? '';
};

$selectValue = static function (string $key) use ($beneficiaryForm) {
    $old = old($key);
    if ($old !== null && $old !== '') {
        return $old;
    }

    return $beneficiaryForm[$key] ?? '';
};

$dependentsInput = old('dependents');
if ($dependentsInput === null) {
    $dependentsInput = $form['dependents'] ?? [];
}
if (is_string($dependentsInput) && $dependentsInput !== '') {
    $dependentsInput = json_decode($dependentsInput, true, 512, JSON_THROW_ON_ERROR);
}
if (! is_array($dependentsInput)) {
    $dependentsInput = [];
}

$snapshot = $snapshot ?? [];

$errorFor = static function (string $key) use ($errors) {
    return $errors[$key] ?? null;
};

$invalidClass = static function (string $key) use ($errors) {
    return isset($errors[$key]) ? ' is-invalid' : '';
};

?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-end pt-3 pb-2 mb-3 border-bottom">
  <div>
    <h1 class="h2 mb-1">Update Beneficiary Details</h1>
    <p class="text-muted mb-0">Review each section and submit for confirmation.</p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! empty($errors)): ?>
  <div class="alert alert-danger">
    <strong>Kindly resolve the highlighted fields below.</strong>
  </div>
<?php endif; ?>
<div class="alert alert-danger d-none" data-form-error-summary role="alert"></div>
<form method="post" action="<?= site_url('enrollment/edit/preview') ?>" class="needs-validation" novalidate id="beneficiary-edit-form">
  <?= csrf_field() ?>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">1. Scheme &amp; Category</h5>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Scheme Option *</label>
        <select name="plan_option_id" class="form-select <?= isset($errors['plan_option_id']) ? 'is-invalid' : '' ?>">
          <option value="">Select Option</option>
          <?php foreach ($masters['planOptions'] ?? [] as $option): ?>
            <option value="<?= esc($option['id']) ?>" <?= (string) $selectValue('plan_option_id') === (string) $option['id'] ? 'selected' : '' ?>>
              <?= esc($option['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['plan_option_id'])): ?><div class="invalid-feedback d-block"><?= esc($errors['plan_option_id']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Beneficiary Category *</label>
        <select name="category_id" class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>">
          <option value="">Select Category</option>
          <?php foreach ($masters['categories'] ?? [] as $option): ?>
            <option value="<?= esc($option['id']) ?>" <?= (string) $selectValue('category_id') === (string) $option['id'] ? 'selected' : '' ?>>
              <?= esc($option['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback d-block"><?= esc($errors['category_id']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="alert alert-secondary d-flex align-items-start gap-3 mb-4">
    <?php $undertakingError = $errorFor('undertaking_confirmed'); ?>
    <div class="form-check mt-1">
      <input
        class="form-check-input<?= $invalidClass('undertaking_confirmed') ?>"
        type="checkbox"
        name="undertaking_confirmed"
        id="undertaking_confirmed"
        value="yes"
        <?= old('undertaking_confirmed') === 'yes' ? 'checked' : '' ?>
        required
      >
    </div>
    <div>
      <label class="form-check-label fw-semibold" for="undertaking_confirmed">
        I confirm that the above details are accurate to the best of my knowledge and agree to be responsible for any discrepancies. I authorize the portal team to use this information for further processing.
      </label>
      <div class="invalid-feedback d-block" data-field-error="undertaking_confirmed" data-server-message="<?= esc($undertakingError ?? '') ?>">
        <?= esc($undertakingError ?? '') ?>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">2. Personal Information</h5>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">First Name *</label>
        <?php $firstNameError = $errorFor('first_name'); ?>
        <input type="text" name="first_name" class="form-control<?= $invalidClass('first_name') ?>" value="<?= esc($value('first_name')) ?>">
        <div class="invalid-feedback" data-field-error="first_name" data-server-message="<?= esc($firstNameError ?? '') ?>">
          <?= esc($firstNameError ?? '') ?>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Middle Name</label>
        <?php $middleNameError = $errorFor('middle_name'); ?>
        <input type="text" name="middle_name" class="form-control<?= $invalidClass('middle_name') ?>" value="<?= esc($value('middle_name')) ?>">
        <div class="invalid-feedback" data-field-error="middle_name" data-server-message="<?= esc($middleNameError ?? '') ?>">
          <?= esc($middleNameError ?? '') ?>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Last Name *</label>
        <?php $lastNameError = $errorFor('last_name'); ?>
        <input type="text" name="last_name" class="form-control<?= $invalidClass('last_name') ?>" value="<?= esc($value('last_name')) ?>">
        <div class="invalid-feedback" data-field-error="last_name" data-server-message="<?= esc($lastNameError ?? '') ?>">
          <?= esc($lastNameError ?? '') ?>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Gender *</label>
        <select name="gender" class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>">
          <option value="">Select Gender</option>
          <?php foreach ($masters['genders'] ?? [] as $gender): ?>
            <option value="<?= esc($gender['code']) ?>" <?= (string) $selectValue('gender') === (string) $gender['code'] ? 'selected' : '' ?>>
              <?= esc($gender['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['gender'])): ?><div class="invalid-feedback d-block"><?= esc($errors['gender']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Blood Group</label>
        <select name="blood_group_id" class="form-select">
          <option value="">Select Blood Group</option>
          <?php foreach ($masters['bloodGroups'] ?? [] as $group): ?>
            <option value="<?= esc($group['id']) ?>" <?= (string) $selectValue('blood_group_id') === (string) $group['id'] ? 'selected' : '' ?>>
              <?= esc($group['label'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth *</label>
        <input
          type="text"
          name="date_of_birth"
          class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>"
          value="<?= esc($value('date_of_birth')) ?>"
          placeholder="DD/MM/YYYY"
          data-date-picker
          data-alt-format="d/m/Y"
          data-max-date="today"
          data-date-placeholder="DD/MM/YYYY"
          autocomplete="off"
          inputmode="numeric"
        >
        <?php if (isset($errors['date_of_birth'])): ?><div class="invalid-feedback"><?= esc($errors['date_of_birth']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Retirement / Death Date</label>
        <input
          type="text"
          name="retirement_or_death_date"
          class="form-control <?= isset($errors['retirement_or_death_date']) ? 'is-invalid' : '' ?>"
          value="<?= esc($value('retirement_or_death_date')) ?>"
          placeholder="DD/MM/YYYY"
          data-date-picker
          data-alt-format="d/m/Y"
          data-max-date="today"
          data-date-placeholder="DD/MM/YYYY"
          autocomplete="off"
          inputmode="numeric"
        >
        <?php if (isset($errors['retirement_or_death_date'])): ?><div class="invalid-feedback"><?= esc($errors['retirement_or_death_date']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Deceased Employee Name (if applicable)</label>
        <input type="text" name="deceased_employee_name" class="form-control" value="<?= esc($value('deceased_employee_name')) ?>">
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">3. Service &amp; Office</h5>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Regional Account Office</label>
        <select name="rao_id" class="form-select">
          <option value="">Select RAO</option>
          <?php foreach ($masters['raos'] ?? [] as $option): ?>
            <option value="<?= esc($option['id']) ?>" <?= (string) $selectValue('rao_id') === (string) $option['id'] ? 'selected' : '' ?>><?= esc($option['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="mt-2 <?= (string) $selectValue('rao_id') === '900' ? '' : 'd-none' ?>" data-other-wrapper="rao">
          <label class="form-label mb-1">If Other, specify</label>
          <input
            type="text"
            name="rao_other"
            class="form-control <?= (string) $selectValue('rao_id') === '900' ? '' : 'bg-light' ?>"
            value="<?= esc($value('rao_other')) ?>"
            <?= (string) $selectValue('rao_id') === '900' ? '' : 'disabled' ?>
          >
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Office at Retirement</label>
        <select name="retirement_office_id" class="form-select">
          <option value="">Select Office</option>
          <?php foreach ($masters['retirementOffices'] ?? [] as $office): ?>
            <option value="<?= esc($office['id']) ?>" <?= (string) $selectValue('retirement_office_id') === (string) $office['id'] ? 'selected' : '' ?>><?= esc($office['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="mt-2 <?= (string) $selectValue('retirement_office_id') === '900' ? '' : 'd-none' ?>" data-other-wrapper="retirement">
          <label class="form-label mb-1">If Other, specify</label>
          <input
            type="text"
            name="retirement_office_other"
            class="form-control <?= (string) $selectValue('retirement_office_id') === '900' ? '' : 'bg-light' ?>"
            value="<?= esc($value('retirement_office_other')) ?>"
            <?= (string) $selectValue('retirement_office_id') === '900' ? '' : 'disabled' ?>
          >
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Designation at Retirement</label>
        <select name="designation_id" class="form-select">
          <option value="">Select Designation</option>
          <?php foreach ($masters['designations'] ?? [] as $designation): ?>
            <option value="<?= esc($designation['id']) ?>" <?= (string) $selectValue('designation_id') === (string) $designation['id'] ? 'selected' : '' ?>><?= esc($designation['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="mt-2 <?= (string) $selectValue('designation_id') === '900' ? '' : 'd-none' ?>" data-other-wrapper="designation">
          <label class="form-label mb-1">If Other, specify</label>
          <input
            type="text"
            name="designation_other"
            class="form-control <?= (string) $selectValue('designation_id') === '900' ? '' : 'bg-light' ?>"
            value="<?= esc($value('designation_other')) ?>"
            <?= (string) $selectValue('designation_id') === '900' ? '' : 'disabled' ?>
          >
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">4. Contact &amp; Address</h5>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-12">
        <label class="form-label">Correspondence Address</label>
        <textarea name="correspondence_address" rows="3" class="form-control"><?= esc($value('correspondence_address')) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="<?= esc($value('city')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">State *</label>
        <select name="state_id" class="form-select <?= isset($errors['state_id']) ? 'is-invalid' : '' ?>">
          <option value="">Select State</option>
          <?php foreach ($masters['states'] ?? [] as $state): ?>
            <option value="<?= esc($state['id']) ?>" <?= (string) $selectValue('state_id') === (string) $state['id'] ? 'selected' : '' ?>><?= esc($state['label'] ?? $state['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['state_id'])): ?><div class="invalid-feedback d-block"><?= esc($errors['state_id']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Postal Code *</label>
        <input type="text" name="postal_code" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" value="<?= esc($value('postal_code')) ?>">
        <?php if (isset($errors['postal_code'])): ?><div class="invalid-feedback"><?= esc($errors['postal_code']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Primary Mobile</label>
        <input type="hidden" name="primary_mobile" value="<?= esc($snapshot['primary_mobile_masked'] ?? '') ?>">
        <input type="text" class="form-control" value="<?= esc($snapshot['primary_mobile_masked'] ?? '-') ?>" disabled>
        <div class="form-text text-muted">To update your registered mobile number, please contact the scheme office.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Alternate Mobile</label>
        <input type="hidden" name="alternate_mobile" value="<?= esc($snapshot['alternate_mobile_masked'] ?? '') ?>">
        <input type="text" class="form-control" value="<?= esc($snapshot['alternate_mobile_masked'] ?? '-') ?>" disabled>
      </div>
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <?php $emailError = $errorFor('email'); ?>
        <input type="email" name="email" class="form-control<?= $invalidClass('email') ?>" value="<?= esc($value('email')) ?>">
        <div class="invalid-feedback" data-field-error="email" data-server-message="<?= esc($emailError ?? '') ?>">
          <?= esc($emailError ?? '') ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary">
      <h5 class="mb-0">5. Bank &amp; Identifiers</h5>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Bank Source</label>
        <select name="bank_source_id" class="form-select">
          <option value="">Select Bank</option>
          <?php foreach ($masters['bankSources'] ?? [] as $bank): ?>
            <option value="<?= esc($bank['id']) ?>" <?= (string) $selectValue('bank_source_id') === (string) $bank['id'] ? 'selected' : '' ?>>
              <?= esc($bank['label'] ?? $bank['name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Current: <?= esc($snapshot['bank_source_label'] ?? '-') ?></div>
      </div>
      <div class="col-md-6">
        <label class="form-label">If Other, specify</label>
        <input type="text" name="bank_source_other" class="form-control" value="<?= esc(old('bank_source_other', $value('bank_source_other'))) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Servicing Branch</label>
        <select name="bank_servicing_id" class="form-select">
          <option value="">Select Branch</option>
          <?php foreach ($masters['bankServicing'] ?? [] as $branch): ?>
            <option value="<?= esc($branch['id']) ?>" <?= (string) $selectValue('bank_servicing_id') === (string) $branch['id'] ? 'selected' : '' ?>>
              <?= esc($branch['label'] ?? $branch['name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Current: <?= esc($snapshot['bank_servicing_label'] ?? '-') ?></div>
      </div>
      <div class="col-md-6">
        <label class="form-label">If Other, specify</label>
        <input type="text" name="bank_servicing_other" class="form-control" value="<?= esc(old('bank_servicing_other', $value('bank_servicing_other'))) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Bank Account Number</label>
        <?php $bankAccountError = $errorFor('bank_account'); ?>
        <input type="text" name="bank_account" class="form-control<?= $invalidClass('bank_account') ?>" value="<?= esc(old('bank_account')) ?>" placeholder="Current: <?= esc($snapshot['bank_account_masked'] ?? '-') ?>">
        <div class="invalid-feedback" data-field-error="bank_account" data-server-message="<?= esc($bankAccountError ?? '') ?>">
          <?= esc($bankAccountError ?? '') ?>
        </div>
        <div class="form-text">Enter the full account number to update. Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">PPO Number</label>
        <?php $ppoError = $errorFor('ppo_number'); ?>
        <textarea name="ppo_number" class="form-control<?= $invalidClass('ppo_number') ?>" rows="2" placeholder="Current: <?= esc($snapshot['ppo_number_masked'] ?? '-') ?>"><?= esc(old('ppo_number')) ?></textarea>
        <div class="invalid-feedback" data-field-error="ppo_number" data-server-message="<?= esc($ppoError ?? '') ?>">
          <?= esc($ppoError ?? '') ?>
        </div>
        <div class="form-text">Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">PRAN Number</label>
        <input type="text" name="pran_number" class="form-control" value="<?= esc(old('pran_number')) ?>" placeholder="Current: <?= esc($snapshot['pran_number_masked'] ?? '-') ?>">
        <div class="form-text">Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">GPF Number</label>
        <?php $gpfError = $errorFor('gpf_number'); ?>
        <input type="text" name="gpf_number" class="form-control<?= $invalidClass('gpf_number') ?>" value="<?= esc(old('gpf_number')) ?>" placeholder="Current: <?= esc($snapshot['gpf_number_masked'] ?? '-') ?>">
        <div class="invalid-feedback" data-field-error="gpf_number" data-server-message="<?= esc($gpfError ?? '') ?>">
          <?= esc($gpfError ?? '') ?>
        </div>
        <div class="form-text">Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Aadhaar Number</label>
        <?php $aadhaarError = $errorFor('aadhaar'); ?>
        <input type="text" name="aadhaar" class="form-control<?= $invalidClass('aadhaar') ?>" value="<?= esc(old('aadhaar')) ?>" placeholder="Current: <?= esc($snapshot['aadhaar_masked'] ?? '-') ?>">
        <div class="invalid-feedback" data-field-error="aadhaar" data-server-message="<?= esc($aadhaarError ?? '') ?>">
          <?= esc($aadhaarError ?? '') ?>
        </div>
        <div class="form-text">Enter the full number to update. Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">PAN Number</label>
        <?php $panError = $errorFor('pan'); ?>
        <input type="text" name="pan" class="form-control<?= $invalidClass('pan') ?>" value="<?= esc(old('pan')) ?>" placeholder="Current: <?= esc($snapshot['pan_masked'] ?? '-') ?>">
        <div class="invalid-feedback" data-field-error="pan" data-server-message="<?= esc($panError ?? '') ?>">
          <?= esc($panError ?? '') ?>
        </div>
        <div class="form-text">Enter the full number to update. Leave blank to keep the current value.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Samagra ID</label>
        <?php $samagraError = $errorFor('samagra'); ?>
        <input type="text" name="samagra" class="form-control<?= $invalidClass('samagra') ?>" value="<?= esc(old('samagra')) ?>" placeholder="Current: <?= esc($snapshot['samagra_masked'] ?? '-') ?>">
        <div class="invalid-feedback" data-field-error="samagra" data-server-message="<?= esc($samagraError ?? '') ?>">
          <?= esc($samagraError ?? '') ?>
        </div>
        <div class="form-text">Enter the full number to update. Leave blank to keep the current value.</div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
      <h5 class="mb-0">6. Dependents</h5>
      <button type="button" class="btn btn-sm btn-outline-primary" id="add-dependent-btn">
        <i class="fa-solid fa-circle-plus me-1"></i>Add Dependent
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" id="dependents-table">
          <thead class="table-light">
            <tr>
              <th style="width: 16%;">Name</th>
              <th style="width: 15%;">Relationship</th>
              <th style="width: 9%;">Order</th>
              <th style="width: 11%;">Alive</th>
              <th style="width: 11%;">Health Coverage</th>
              <th style="width: 9%;">Gender</th>
              <th style="width: 11%;">Date of Birth</th>
              <th style="width: 11%;">Blood Group</th>
              <th style="width: 11%;">Aadhaar</th>
              <th style="width: 5%;" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dependentsInput as $index => $dependent): ?>
              <?= view('v2/partials/dependent_row', [
                  'index'     => $index,
                  'dependent' => $dependent,
                  'masters'   => $masters,
                  'errors'    => $errors['dependents'][$index] ?? [],
              ]) ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if (! empty($errors['dependents'])): ?>
        <div class="alert alert-danger m-3">
          One or more dependent entries need attention. Kindly review the highlighted rows.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex justify-content-between mb-5">
    <a href="<?= site_url('dashboard/v2') ?>" class="btn btn-outline-secondary">
      <i class="fa-solid fa-arrow-left"></i> Cancel
    </a>
    <button type="submit" class="btn btn-primary">
      Review Changes <i class="fa-solid fa-arrow-right"></i>
    </button>
  </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (function () {
    const dependentsTable = document.querySelector('#dependents-table tbody');
    const addDependentBtn = document.querySelector('#add-dependent-btn');
    let dependentRowIndex = <?= count($dependentsInput) ?>;
    const randomUUID = (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function')
      ? () => crypto.randomUUID()
      : () => 'tmp-' + Date.now().toString(36) + Math.random().toString(36).slice(2);

    const editForm = document.getElementById('beneficiary-edit-form');
    const errorSummary = document.querySelector('[data-form-error-summary]');
    const relationshipOptions = <?= json_encode($masters['dependentRelationships'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const aliveOptions = <?= json_encode($masters['dependentStatuses'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const healthOptions = <?= json_encode($masters['healthCoverageOptions'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const genderOptions = <?= json_encode($masters['genders'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const bloodGroupOptions = <?= json_encode($masters['bloodGroups'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const raoOfficeMap = <?= json_encode($masters['raoOfficeMap'] ?? [], JSON_THROW_ON_ERROR) ?>;

    function hasAllSameDigits(value) {
      return /^(\d)\1+$/.test(value);
    }

    function escapeAttributeSelector(value) {
      if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
      }
      return value.replace(/([ #;&,.+*~':"!^$[\]()=>|/@])/g, '\\$1');
    }

    function findFieldFeedback(name) {
      if (!editForm) {
        return null;
      }
      const selector = `.invalid-feedback[data-field-error="${escapeAttributeSelector(name)}"]`;
      return editForm.querySelector(selector);
    }

    function clearClientFieldError(name) {
      if (!editForm) {
        return;
      }
      const field = editForm.elements[name];
      if (field && field.dataset.clientInvalid === '1') {
        field.classList.remove('is-invalid');
        field.removeAttribute('data-client-invalid');
      }
      const feedback = findFieldFeedback(name);
      if (feedback && feedback.dataset.clientFeedback === '1') {
        feedback.textContent = feedback.dataset.serverMessage ?? '';
        feedback.removeAttribute('data-client-feedback');
      }
    }

    function setClientFieldError(name, message) {
      if (!editForm) {
        return;
      }
      const field = editForm.elements[name];
      if (field) {
        field.classList.add('is-invalid');
        field.dataset.clientInvalid = '1';
      }
      const feedback = findFieldFeedback(name);
      if (feedback) {
        feedback.textContent = message;
        feedback.dataset.clientFeedback = '1';
      }
    }

    function resetClientValidation() {
      if (!editForm) {
        return;
      }
      editForm.querySelectorAll('[data-client-invalid="1"]').forEach((input) => {
        input.classList.remove('is-invalid');
        input.removeAttribute('data-client-invalid');
      });
      editForm.querySelectorAll('.invalid-feedback[data-client-feedback="1"]').forEach((feedback) => {
        feedback.textContent = feedback.dataset.serverMessage ?? '';
        feedback.removeAttribute('data-client-feedback');
      });
      if (errorSummary) {
        errorSummary.classList.add('d-none');
        errorSummary.innerHTML = '';
      }
    }

    function showErrorSummary(errors) {
      if (!errorSummary) {
        return;
      }
      const uniqueLabels = [];
      const seen = new Set();
      errors.forEach((error) => {
        if (!seen.has(error.label)) {
          seen.add(error.label);
          uniqueLabels.push(error.label);
        }
      });
      errorSummary.innerHTML = `<strong>Please fix the highlighted fields:</strong><ul class="mb-0">${uniqueLabels
        .map((label) => `<li>${label}</li>`)
        .join('')}</ul>`;
      errorSummary.classList.remove('d-none');
    }

    const clientValidators = [
      {
        name: 'first_name',
        label: 'First Name',
        required: true,
        minLength: 2,
        maxLength: 120,
        pattern: /^[A-Za-z]+$/,
        message: 'First Name should contain alphabets only (no spaces).',
      },
      {
        name: 'middle_name',
        label: 'Middle Name',
        required: false,
        maxLength: 120,
        pattern: /^[A-Za-z]+$/,
        message: 'Middle Name should contain alphabets only (no spaces).',
      },
      {
        name: 'last_name',
        label: 'Last Name',
        required: true,
        minLength: 2,
        maxLength: 120,
        pattern: /^[A-Za-z]+$/,
        message: 'Last Name should contain alphabets only (no spaces).',
      },
      {
        name: 'email',
        label: 'Email',
        required: false,
        pattern: /^[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*@[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)+$/,
        message: 'Enter a valid email address without spaces.',
      },
      {
        name: 'bank_account',
        label: 'Bank Account Number',
        required: false,
        custom: (value) => /^\d{9,18}$/.test(value) && !hasAllSameDigits(value),
        message: 'Bank account number must be 9-18 digits and not all identical.',
      },
      {
        name: 'ppo_number',
        label: 'PPO Number',
        required: false,
        maxLength: 100,
        pattern: /^[A-Za-z0-9@&/\-\s]+$/,
        message: 'PPO number allows letters, numbers, spaces and @, &, /, - characters only.',
      },
      {
        name: 'gpf_number',
        label: 'GPF Number',
        required: false,
        pattern: /^\d{8}$/,
        custom: (value) => !hasAllSameDigits(value),
        message: 'GPF number must be exactly 8 digits and not all identical.',
      },
      {
        name: 'aadhaar',
        label: 'Aadhaar Number',
        required: false,
        pattern: /^\d{12}$/,
        custom: (value) => !hasAllSameDigits(value),
        message: 'Aadhaar must be 12 digits without spaces and not all identical.',
      },
      {
        name: 'pan',
        label: 'PAN Number',
        required: false,
        pattern: /^[A-Z]{5}[0-9]{4}[A-Z]$/,
        normalize: (value) => value.toUpperCase(),
        message: 'PAN must follow the format AAAAA9999A.',
      },
      {
        name: 'samagra',
        label: 'Samagra ID',
        required: false,
        pattern: /^\d{8,9}$/,
        custom: (value) => !hasAllSameDigits(value),
        message: 'Samagra ID must be 8 or 9 digits and not all identical.',
      },
      {
        name: 'undertaking_confirmed',
        label: 'Undertaking',
        required: true,
        message: 'Please accept the undertaking before submitting.',
        custom: (value) => value === 'yes',
      },
    ];

    function runClientValidation() {
      const issues = [];
      if (!editForm) {
        return issues;
      }

      clientValidators.forEach((rule) => {
        const field = editForm.elements[rule.name];
        if (!field) {
          return;
        }

        const rawValue = (field.value || '').trim();
        field.value = rawValue;
        clearClientFieldError(rule.name);

        if (!rawValue) {
          if (rule.required) {
            const message = `${rule.label} is required.`;
            issues.push({ name: rule.name, label: rule.label, message });
            setClientFieldError(rule.name, message);
          }
          return;
        }

        if (rule.minLength && rawValue.length < rule.minLength) {
          const message = `${rule.label} must be at least ${rule.minLength} characters.`;
          issues.push({ name: rule.name, label: rule.label, message });
          setClientFieldError(rule.name, message);
          return;
        }

        if (rule.maxLength && rawValue.length > rule.maxLength) {
          const message = `${rule.label} must be ${rule.maxLength} characters or fewer.`;
          issues.push({ name: rule.name, label: rule.label, message });
          setClientFieldError(rule.name, message);
          return;
        }

        let valueToTest = rawValue;
        if (typeof rule.normalize === 'function') {
          valueToTest = rule.normalize(rawValue);
          field.value = valueToTest;
        }

        if (rule.pattern && !rule.pattern.test(valueToTest)) {
          issues.push({ name: rule.name, label: rule.label, message: rule.message });
          setClientFieldError(rule.name, rule.message);
          return;
        }

        if (typeof rule.custom === 'function' && !rule.custom(valueToTest)) {
          issues.push({ name: rule.name, label: rule.label, message: rule.message });
          setClientFieldError(rule.name, rule.message);
        }
      });

      return issues;
    }

    const DEPENDENT_STATUS = {
      ALIVE: 'ALIVE',
      NOT_ALIVE: 'NOT_ALIVE',
      NOT_APPLICABLE: 'NOT_APPLICABLE',
    };
    const COVERAGE_STATUS = {
      YES: 'YES',
      NO: 'NO',
      NOT_APPLICABLE: 'NOT_APPLICABLE',
    };

    function applyDependentFieldState(row) {
      if (!row) {
        return;
      }

      const aliveSelect = row.querySelector('select[name$="[is_alive]"]');
      const coverageSelect = row.querySelector('select[name$="[is_health_dependant]"]');

      const aliveValue = aliveSelect ? String(aliveSelect.value || '') : '';
      const coverageValue = coverageSelect ? String(coverageSelect.value || '') : '';

      const lockForAlive =
        aliveValue === DEPENDENT_STATUS.NOT_ALIVE ||
        aliveValue === DEPENDENT_STATUS.NOT_APPLICABLE;
      const lockForCoverage =
        aliveValue === DEPENDENT_STATUS.ALIVE &&
        (coverageValue === COVERAGE_STATUS.NO || coverageValue === COVERAGE_STATUS.NOT_APPLICABLE);

      const shouldLock = lockForAlive || lockForCoverage;

      const controls = row.querySelectorAll('input, select, textarea');
      controls.forEach((control) => {
        if (!control || control.type === 'hidden') {
          return;
        }

        const fieldName = control.name || '';
        const isStatusField =
          fieldName.endsWith('[is_alive]') || fieldName.endsWith('[is_health_dependant]');

        if (isStatusField) {
          control.disabled = false;
          control.classList.remove('bg-light');
          control.removeAttribute('data-dependent-locked');
          return;
        }

        if (shouldLock) {
          control.setAttribute('data-dependent-locked', '1');
          control.disabled = true;
          control.classList.add('bg-light');
        } else if (control.getAttribute('data-dependent-locked') === '1') {
          control.disabled = false;
          control.classList.remove('bg-light');
          control.removeAttribute('data-dependent-locked');
        }
      });
    }

    const raoSelect = document.querySelector('select[name="rao_id"]');
    const raoOtherInput = document.querySelector('input[name="rao_other"]');
    const raoOtherWrapper = document.querySelector('[data-other-wrapper="rao"]');
    const retirementSelect = document.querySelector('select[name="retirement_office_id"]');
    const retirementOtherInput = document.querySelector('input[name="retirement_office_other"]');
    const retirementOtherWrapper = document.querySelector('[data-other-wrapper="retirement"]');
    const designationSelect = document.querySelector('select[name="designation_id"]');
    const designationOtherInput = document.querySelector('input[name="designation_other"]');
    const designationOtherWrapper = document.querySelector('[data-other-wrapper="designation"]');
    const retirementAllOptions = retirementSelect
      ? Array.from(retirementSelect.options).map((option) => ({
          value: option.value,
          text: option.textContent,
        }))
      : [];

    function buildSelectOptions(options, selected) {
      return options.map((option) => {
        const value = option.code ?? option.id ?? '';
        const label = option.label ?? option.name ?? '';
        const isSelected = String(selected ?? '') === String(value);
        return `<option value="${value}" ${isSelected ? 'selected' : ''}>${label}</option>`;
      }).join('');
    }

    function buildRowTemplate(index, data = {}) {
      const jsonData = {
        id: data.id ?? '',
        temp_id: data.temp_id ?? randomUUID(),
        is_deleted: data.is_deleted ? 1 : 0,
        relationship: data.relationship ?? '',
        dependant_order: data.dependant_order ?? '',
        twin_group: data.twin_group ?? '',
        is_alive: data.is_alive ?? '',
        is_health_dependant: data.is_health_dependant ?? '',
        first_name: data.first_name ?? '',
        gender: data.gender ?? '',
        blood_group_id: data.blood_group_id ?? '',
        date_of_birth: data.date_of_birth ?? '',
        aadhaar: data.aadhaar ?? '',
        aadhaar_masked: data.aadhaar_masked ?? '',
      };

      return `
        <tr data-row-index="${index}" class="${jsonData.is_deleted ? 'table-warning text-decoration-line-through' : ''}">
          <td>
            <input type="hidden" class="dependent-is-deleted" name="dependents[${index}][is_deleted]" value="${jsonData.is_deleted}">
            <input type="hidden" name="dependents[${index}][id]" value="${jsonData.id}">
            <input type="hidden" name="dependents[${index}][temp_id]" value="${jsonData.temp_id}">
            <input type="text" name="dependents[${index}][first_name]" class="form-control form-control-sm" value="${jsonData.first_name}" placeholder="Full name">
          </td>
          <td>
            <select name="dependents[${index}][relationship]" class="form-select form-select-sm">
              <option value="">Select</option>
              ${buildSelectOptions(relationshipOptions, jsonData.relationship)}
            </select>
          </td>
          <td>
            <input type="number" name="dependents[${index}][dependant_order]" class="form-control form-control-sm" value="${jsonData.dependant_order}" min="1">
          </td>
          <td>
            <select name="dependents[${index}][is_alive]" class="form-select form-select-sm">
              <option value="">Select</option>
              ${buildSelectOptions(aliveOptions, jsonData.is_alive)}
            </select>
          </td>
          <td>
            <select name="dependents[${index}][is_health_dependant]" class="form-select form-select-sm">
              <option value="">Select</option>
              ${buildSelectOptions(healthOptions, jsonData.is_health_dependant)}
            </select>
          </td>
          <td>
            <select name="dependents[${index}][gender]" class="form-select form-select-sm">
              <option value="">Select</option>
              ${buildSelectOptions(genderOptions, jsonData.gender)}
            </select>
          </td>
         <td>
           <input
             type="text"
             name="dependents[${index}][date_of_birth]"
             class="form-control form-control-sm"
             value="${jsonData.date_of_birth}"
             placeholder="DD/MM/YYYY"
             data-date-picker
             data-alt-format="d/m/Y"
             data-max-date="today"
             data-date-placeholder="DD/MM/YYYY"
             autocomplete="off"
             inputmode="numeric"
           >
         </td>
          <td>
            <select name="dependents[${index}][blood_group_id]" class="form-select form-select-sm">
              <option value="">Select</option>
              ${buildSelectOptions(bloodGroupOptions, jsonData.blood_group_id)}
            </select>
          </td>
          <td>
            <input
              type="text"
              name="dependents[${index}][aadhaar]"
              class="form-control form-control-sm"
              value="${jsonData.aadhaar}"
              placeholder="Current: ${jsonData.aadhaar_masked || '-'}"
            >
          </td>
          <td class="text-end">
            <button type="button" class="btn btn-sm ${jsonData.is_deleted ? 'btn-outline-secondary' : 'btn-outline-danger'} remove-dependent-btn">
              <i class="${jsonData.is_deleted ? 'fa-solid fa-rotate-left' : 'fa-solid fa-trash'}"></i>
            </button>
          </td>
        </tr>
      `;
    }

    function addRow(data = {}) {
      dependentsTable.insertAdjacentHTML('beforeend', buildRowTemplate(dependentRowIndex, data));
      const newRow = dependentsTable.lastElementChild;
      if (newRow) {
        newRow.dataset.rowIndex = dependentRowIndex;
        applyDependentFieldState(newRow);
      }
      dependentRowIndex++;
    }

    addDependentBtn.addEventListener('click', () => addRow());

    // Seed existing rows (if any) to ensure temp IDs exist
    if (dependentsTable.children.length === 0) {
      addRow();
    } else {
      Array.from(dependentsTable.children).forEach((row, idx) => {
        const tempInput = row.querySelector('input[name^="dependents"][name$="[temp_id]"]');
        if (tempInput && ! tempInput.value) {
          tempInput.value = randomUUID();
        }
        row.dataset.rowIndex = idx;
        applyDependentFieldState(row);
      });
    }

    dependentsTable.addEventListener('click', (event) => {
      const button = event.target.closest('.remove-dependent-btn');
      if (! button) {
        return;
      }

      const row = button.closest('tr');
      const isDeletedInput = row.querySelector('.dependent-is-deleted');
      if (! isDeletedInput) {
        return;
      }

      if (isDeletedInput.value === '0') {
        isDeletedInput.value = '1';
        row.classList.add('table-warning', 'text-decoration-line-through');
        button.classList.remove('btn-outline-danger');
        button.classList.add('btn-outline-secondary');
        button.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
      } else {
        isDeletedInput.value = '0';
        row.classList.remove('table-warning', 'text-decoration-line-through');
        button.classList.add('btn-outline-danger');
        button.classList.remove('btn-outline-secondary');
        button.innerHTML = '<i class="fa-solid fa-trash"></i>';
      }
    });

    dependentsTable.addEventListener('change', (event) => {
      const target = event.target;
      if (!target || target.tagName !== 'SELECT') {
        return;
      }

      const name = target.name || '';
      if (!name.endsWith('[is_alive]') && !name.endsWith('[is_health_dependant]')) {
        return;
      }

      const row = target.closest('tr');
      applyDependentFieldState(row);
    });

    function allowedOfficesForRao(raoId) {
      if (!raoId || !raoOfficeMap) {
        return null;
      }
      const list = raoOfficeMap[String(raoId)];
      if (!Array.isArray(list) || list.length === 0) {
        return null;
      }
      return list.map(String);
    }

    function rebuildRetirementOptions(allowed) {
      if (!retirementSelect) {
        return;
      }

      const currentValue = retirementSelect.value;
      retirementSelect.innerHTML = '';

      retirementAllOptions.forEach((option) => {
        if (
          option.value === '' ||
          !Array.isArray(allowed) ||
          allowed.includes(String(option.value))
        ) {
          const optionEl = document.createElement('option');
          optionEl.value = option.value;
          optionEl.textContent = option.text;
          retirementSelect.appendChild(optionEl);
        }
      });

      if (!retirementSelect.querySelector(`option[value="${currentValue}"]`)) {
        retirementSelect.value = '';
      } else {
        retirementSelect.value = currentValue;
      }
    }

    function toggleOtherInput(selectEl, inputEl, otherValue, wrapperEl) {
      if (!selectEl || !inputEl) {
        return;
      }
      const shouldEnable = String(selectEl.value) === String(otherValue);
      inputEl.disabled = !shouldEnable;
      inputEl.classList.toggle('bg-light', !shouldEnable);
      if (wrapperEl) {
        wrapperEl.classList.toggle('d-none', !shouldEnable);
      }
    }

    function handleRaoChange() {
      const allowed = allowedOfficesForRao(raoSelect ? raoSelect.value : null);
      rebuildRetirementOptions(allowed);
      toggleOtherInput(raoSelect, raoOtherInput, '900', raoOtherWrapper);
      toggleOtherInput(retirementSelect, retirementOtherInput, '900', retirementOtherWrapper);
    }

    if (raoSelect) {
      raoSelect.addEventListener('change', handleRaoChange);
      handleRaoChange();
    }

    if (retirementSelect) {
      retirementSelect.addEventListener('change', () => {
        toggleOtherInput(retirementSelect, retirementOtherInput, '900', retirementOtherWrapper);
      });
      toggleOtherInput(retirementSelect, retirementOtherInput, '900', retirementOtherWrapper);
    }

    if (designationSelect) {
      const designationHandler = () => toggleOtherInput(designationSelect, designationOtherInput, '900', designationOtherWrapper);
      designationSelect.addEventListener('change', designationHandler);
      designationHandler();
    }

    toggleOtherInput(raoSelect, raoOtherInput, '900', raoOtherWrapper);

    if (editForm) {
      editForm.addEventListener('submit', (event) => {
        resetClientValidation();
        const issues = runClientValidation();
        if (issues.length > 0) {
          event.preventDefault();
          showErrorSummary(issues);
        }
      });

      editForm.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
          return;
        }
        const lockedFields = editForm.querySelectorAll('[data-dependent-locked="1"]');
        lockedFields.forEach((field) => {
          field.disabled = false;
        });
      });
    }
  })();
</script>
<?= $this->endSection() ?>
