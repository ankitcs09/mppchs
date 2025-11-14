<?php

$index    = $index ?? 0;
$dependent = $dependent ?? [];
$masters  = $masters ?? [];
$errors   = $errors ?? [];

$relationships = $masters['dependentRelationships'] ?? [];
$statuses      = $masters['dependentStatuses'] ?? [];
$healthOptions = $masters['healthCoverageOptions'] ?? [];
$genderOptions = $masters['genders'] ?? [];
$bloodGroupOptions = $masters['bloodGroups'] ?? [];

$isDeleted = ! empty($dependent['is_deleted']);

?>
<tr data-row-index="<?= esc($index) ?>" class="<?= $isDeleted ? 'table-warning text-decoration-line-through' : '' ?>">
  <td>
    <input type="hidden" class="dependent-is-deleted" name="dependents[<?= esc($index) ?>][is_deleted]" value="<?= $isDeleted ? 1 : 0 ?>">
    <input type="hidden" name="dependents[<?= esc($index) ?>][id]" value="<?= esc($dependent['id'] ?? '') ?>">
    <input type="hidden" name="dependents[<?= esc($index) ?>][temp_id]" value="<?= esc($dependent['temp_id'] ?? '') ?>">
    <input type="text" name="dependents[<?= esc($index) ?>][first_name]" class="form-control form-control-sm <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= esc($dependent['first_name'] ?? '') ?>" placeholder="Full name">
    <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback d-block"><?= esc($errors['first_name']) ?></div><?php endif; ?>
  </td>
  <td>
    <select name="dependents[<?= esc($index) ?>][relationship]" class="form-select form-select-sm <?= isset($errors['relationship']) ? 'is-invalid' : '' ?>">
      <option value="">Select</option>
      <?php foreach ($relationships as $option): ?>
        <option value="<?= esc($option['code'] ?? $option['id'] ?? '') ?>" <?= (string) ($dependent['relationship'] ?? '') === (string) ($option['code'] ?? $option['id'] ?? '') ? 'selected' : '' ?>>
          <?= esc($option['label'] ?? $option['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['relationship'])): ?><div class="invalid-feedback d-block"><?= esc($errors['relationship']) ?></div><?php endif; ?>
  </td>
  <td>
    <input type="number" min="1" name="dependents[<?= esc($index) ?>][dependant_order]" class="form-control form-control-sm <?= isset($errors['dependant_order']) ? 'is-invalid' : '' ?>" value="<?= esc($dependent['dependant_order'] ?? '') ?>">
    <?php if (isset($errors['dependant_order'])): ?><div class="invalid-feedback d-block"><?= esc($errors['dependant_order']) ?></div><?php endif; ?>
  </td>
  <td>
    <select name="dependents[<?= esc($index) ?>][is_alive]" class="form-select form-select-sm <?= isset($errors['is_alive']) ? 'is-invalid' : '' ?>">
      <option value="">Select</option>
      <?php foreach ($statuses as $option): ?>
        <option value="<?= esc($option['code'] ?? $option['id'] ?? '') ?>" <?= (string) ($dependent['is_alive'] ?? '') === (string) ($option['code'] ?? $option['id'] ?? '') ? 'selected' : '' ?>>
          <?= esc($option['label'] ?? $option['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['is_alive'])): ?><div class="invalid-feedback d-block"><?= esc($errors['is_alive']) ?></div><?php endif; ?>
  </td>
  <td>
    <select name="dependents[<?= esc($index) ?>][is_health_dependant]" class="form-select form-select-sm">
      <option value="">Select</option>
      <?php foreach ($healthOptions as $option): ?>
        <option value="<?= esc($option['code'] ?? $option['id'] ?? '') ?>" <?= (string) ($dependent['is_health_dependant'] ?? '') === (string) ($option['code'] ?? $option['id'] ?? '') ? 'selected' : '' ?>>
          <?= esc($option['label'] ?? $option['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </td>
  <td>
    <select name="dependents[<?= esc($index) ?>][gender]" class="form-select form-select-sm <?= isset($errors['gender']) ? 'is-invalid' : '' ?>">
      <option value="">Select</option>
      <?php foreach ($genderOptions as $option): ?>
        <option value="<?= esc($option['code'] ?? $option['id'] ?? '') ?>" <?= (string) ($dependent['gender'] ?? '') === (string) ($option['code'] ?? $option['id'] ?? '') ? 'selected' : '' ?>>
          <?= esc($option['label'] ?? $option['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['gender'])): ?><div class="invalid-feedback d-block"><?= esc($errors['gender']) ?></div><?php endif; ?>
  </td>
  <td>
    <input
      type="text"
      name="dependents[<?= esc($index) ?>][date_of_birth]"
      class="form-control form-control-sm <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>"
      value="<?= esc($dependent['date_of_birth'] ?? '') ?>"
      placeholder="DD/MM/YYYY"
      data-date-picker
      data-alt-format="d/m/Y"
      data-max-date="today"
      data-date-placeholder="DD/MM/YYYY"
      autocomplete="off"
      inputmode="numeric"
    >
    <?php if (isset($errors['date_of_birth'])): ?><div class="invalid-feedback d-block"><?= esc($errors['date_of_birth']) ?></div><?php endif; ?>
  </td>
  <td>
    <?php $bloodGroupClass = 'form-select form-select-sm' . (isset($errors['blood_group_id']) ? ' is-invalid' : ''); ?>
    <select name="dependents[<?= esc($index) ?>][blood_group_id]" class="<?= esc($bloodGroupClass) ?>">
      <option value="">Select</option>
      <?php foreach ($bloodGroupOptions as $option): ?>
        <option value="<?= esc($option['id'] ?? '') ?>" <?= (string) ($dependent['blood_group_id'] ?? '') === (string) ($option['id'] ?? '') ? 'selected' : '' ?>>
          <?= esc($option['label'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['blood_group_id'])): ?><div class="invalid-feedback d-block"><?= esc($errors['blood_group_id']) ?></div><?php endif; ?>
  </td>
  <td>
    <input
      type="text"
      name="dependents[<?= esc($index) ?>][aadhaar]"
      class="form-control form-control-sm <?= isset($errors['aadhaar']) ? 'is-invalid' : '' ?>"
      value="<?= esc($dependent['aadhaar'] ?? '') ?>"
      placeholder="Current: <?= esc($dependent['aadhaar_masked'] ?? '-') ?>"
    >
    <?php if (isset($errors['aadhaar'])): ?><div class="invalid-feedback d-block"><?= esc($errors['aadhaar']) ?></div><?php endif; ?>
  </td>
  <td class="text-end">
    <button type="button" class="btn btn-sm <?= $isDeleted ? 'btn-outline-secondary' : 'btn-outline-danger' ?> remove-dependent-btn">
      <i class="<?= $isDeleted ? 'fa-solid fa-rotate-left' : 'fa-solid fa-trash' ?>"></i>
    </button>
  </td>
</tr>
