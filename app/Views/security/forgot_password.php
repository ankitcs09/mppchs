<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
  <h1 class="mb-3">Forgot Your Password?</h1>
  <p class="mb-4">
    Don't worry. Confirm your username and registered mobile number and we will send a secure link to reset your password.
  </p>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
  <?php helper('form'); ?>
  <?php
    $session = session();
    $success = $session->getFlashdata('success');
    $error   = $session->getFlashdata('error');
    $errors  = $session->getFlashdata('errors') ?? [];
    $debugLink = $session->getFlashdata('debug_reset_link');
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

  <?= form_open('password/forgot', ['class' => 'needs-validation', 'novalidate' => 'novalidate']); ?>
    <h2 class="mb-4">Request Password Reset</h2>

    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input
        type="text"
        id="username"
        name="username"
        class="form-control form-control-lg"
        placeholder="Enter your username"
        value="<?= esc(old('username')) ?>"
        required
      />
      <div class="invalid-feedback">Please provide your username.</div>
    </div>

    <div class="mb-3">
      <label for="mobile" class="form-label">Registered Mobile Number</label>
      <input
        type="tel"
        id="mobile"
        name="mobile"
        class="form-control form-control-lg"
        placeholder="Enter your 10-digit mobile number"
        value="<?= esc(old('mobile')) ?>"
        inputmode="numeric"
        pattern="[0-9]{8,12}"
        required
      />
      <div class="invalid-feedback">Please provide your registered mobile number.</div>
      <small class="text-muted">For security, the mobile number must match the one stored with your profile.</small>
    </div>

    <button class="btn btn-primary w-100 py-3 mt-2" type="submit">
      Send Reset Link
    </button>
  <?= form_close(); ?>

  <div class="auth-links mt-4 text-center">
    <a href="<?= site_url('login') ?>" class="text-decoration-none">Back to Login</a>
  </div>

  <?php if (! empty($debugLink)): ?>
    <div class="alert alert-warning mt-4">
      <strong>Development mode:</strong>
      <a href="<?= esc($debugLink) ?>">Use this link to reset immediately.</a>
    </div>
  <?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
