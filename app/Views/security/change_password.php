<?php

$errors = session()->getFlashdata('errors') ?? [];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Change Password</h1>
    <p class="page-heading__subtitle">Keep your account secure by choosing a strong, unique password.</p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <?php if (! empty($errors)): ?>
      <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0">
          <?php foreach ($errors as $message): ?>
            <li><?= esc($message) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="app-panel app-panel--compact">
      <header class="app-panel__header">
        <h2 class="app-panel__title mb-1">Update password</h2>
        <p class="app-panel__subtitle">
          Use at least 10 characters and include a mix of uppercase, lowercase, numbers, and symbols.
        </p>
      </header>
      <?= form_open('user/change-password', ['method' => 'post', 'autocomplete' => 'off', 'class' => 'vstack gap-3']) ?>
        <?= csrf_field() ?>
        <div>
          <label for="current_password" class="form-label">Current password</label>
          <input
            type="password"
            name="current_password"
            id="current_password"
            class="form-control"
            required
          >
        </div>
        <div>
          <label for="new_password" class="form-label">New password</label>
          <input
            type="password"
            name="new_password"
            id="new_password"
            class="form-control"
            minlength="10"
            required
          >
        </div>
        <div>
          <label for="confirm_password" class="form-label">Confirm new password</label>
          <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            class="form-control"
            minlength="10"
            required
          >
        </div>
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Save password</button>
        </div>
      <?= form_close() ?>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
