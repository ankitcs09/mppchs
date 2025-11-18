<?php $title = 'Mobile OTP Login'; ?>

<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
  <h1 class="mb-3">Quick Access with OTP</h1>
  <p class="mb-4">
    Enter your registered mobile number to receive a one-time password. Perfect when you’re on the move or have
    misplaced your password.
  </p>
  <ul class="list-unstyled text-muted">
    <li class="mb-2">
      <i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>
      OTPs are valid for five minutes.
    </li>
    <li class="mb-2">
      <i class="fa-solid fa-shield-check text-primary me-2"></i>
      Smart resend limits protect your account.
    </li>
    <li class="mb-0">
      <i class="fa-solid fa-circle-check text-primary me-2"></i>
      Unlock the full dashboard and cashless services instantly.
    </li>
  </ul>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
  <?php helper('form'); ?>
  <?= form_open(site_url('login/otp'), ['class' => 'needs-validation', 'novalidate' => 'novalidate']); ?>
    <h2 class="mb-4">Login with Mobile OTP</h2>

    <div class="mb-3">
      <label for="mobileInput" class="form-label">Registered Mobile Number</label>
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-light text-muted">+91</span>
        <input
          type="tel"
          id="mobileInput"
          name="mobile"
          class="form-control"
          placeholder="Enter mobile number"
          value="<?= esc(old('mobile')) ?>"
          maxlength="10"
          required
        />
      </div>
      <div class="invalid-feedback">Please enter your registered mobile number.</div>
    </div>

    <button class="btn btn-primary w-100 py-3 mt-2" type="submit">
      Send OTP
    </button>
  <?= form_close(); ?>

  <div class="auth-links mt-4 text-center">
    <a class="btn btn-outline-primary w-100 py-3" href="<?= site_url('login') ?>">
      Back to Password Login
    </a>
  </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
