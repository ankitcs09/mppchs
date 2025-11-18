<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
  <h1 class="mb-3">Set a New Password</h1>
  <p class="mb-4">
    Create a strong password that you haven't used recently. You will use this to sign in going forward.
  </p>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
  <?php helper('form'); ?>
  <?php
    $session = session();
    $errors = $session->getFlashdata('errors') ?? [];
    $success = $session->getFlashdata('success');
    $error   = $session->getFlashdata('error');
  ?>

  <?php if (! empty($success)): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
  <?php endif; ?>

  <?php if (! empty($error)): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
  <?php endif; ?>

  <?php if (! empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $message): ?>
          <li><?= esc($message) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?= form_open('password/reset/' . esc($selector) . '/' . esc($token), ['class' => 'needs-validation', 'novalidate' => 'novalidate']); ?>
    <h2 class="mb-4">Create Password</h2>

    <div class="mb-3">
      <label for="new_password" class="form-label">New Password</label>
      <input
        type="password"
        id="new_password"
        name="new_password"
        class="form-control form-control-lg"
        placeholder="Enter your new password"
        minlength="10"
        required
      />
      <div class="invalid-feedback">
        Password must be at least 10 characters and include a mix of uppercase, lowercase, numbers, and symbols.
      </div>
      <small class="text-muted">Avoid reusing old passwords or obvious patterns.</small>
    </div>

    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm New Password</label>
      <input
        type="password"
        id="confirm_password"
        name="confirm_password"
        class="form-control form-control-lg"
        placeholder="Re-enter your new password"
        minlength="10"
        required
      />
      <div class="invalid-feedback">Passwords must match.</div>
    </div>

    <button class="btn btn-primary w-100 py-3 mt-2" type="submit">
      Update Password
    </button>
  <?= form_close(); ?>

  <div class="auth-links mt-4 text-center">
    <a href="<?= site_url('login') ?>" class="text-decoration-none">Back to Login</a>
  </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
