<?php $title = 'Verify OTP'; ?>

<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
  <h1 class="mb-3">Verify the One-Time Password</h1>
  <p class="mb-4">
    Enter the six-digit code we sent to your registered mobile number to complete the sign-in. The code expires in
    five minutes for your security.
  </p>
  <ul class="list-unstyled text-muted">
    <li class="mb-2">
      <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>
      OTPs stay valid for five minutes.
    </li>
    <li class="mb-2">
      <i class="fa-solid fa-rotate text-primary me-2"></i>
      Controlled resends keep the account secure.
    </li>
    <li class="mb-0">
      <i class="fa-solid fa-headset text-primary me-2"></i>
      Reach support if the code doesn’t arrive.
    </li>
  </ul>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
  <div
    data-module="auth-otp-verify"
    data-is-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"
    data-dashboard-url="<?= esc(site_url('dashboard'), 'attr') ?>"
    data-otp-url="<?= esc(site_url('login/otp'), 'attr') ?>"
  >
    <?php helper('form'); ?>
    <?= form_open(site_url('login/otp/verify'), ['class' => 'needs-validation', 'novalidate' => 'novalidate']); ?>
      <h2 class="mb-3">Enter the OTP</h2>
      <p class="text-muted mb-4">
        We sent a six-digit code to <strong><?= esc($maskedMobile ?? 'your mobile') ?></strong>.
      </p>

      <div class="mb-3">
        <label for="otpInput" class="form-label">Enter your OTP</label>
        <div class="input-group input-group-lg">
          <input
            type="password"
            name="otp"
            id="otpInput"
            inputmode="numeric"
            pattern="\d{6}"
            maxlength="6"
            minlength="6"
            class="form-control text-center fs-4"
            placeholder="******"
            required
            autofocus
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
          />
          <button
            type="button"
            class="btn btn-outline-secondary"
            id="otp-visibility-toggle"
            aria-label="Toggle OTP visibility"
          >
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
        <div class="invalid-feedback">
          Enter the six-digit code that was sent to your phone.
        </div>
      </div>

      <button class="btn btn-primary w-100 py-3 mt-2" type="submit">
        Verify &amp; Continue
      </button>
    <?= form_close(); ?>

    <div class="auth-links mt-4">
      <?php
        $resendsRemaining = $resendsRemaining ?? null;
        $cooldownRemaining = $cooldownRemaining ?? null;
      ?>

      <?php if ($resendsRemaining === null || $resendsRemaining > 0): ?>
        <?= form_open(site_url('login/otp/resend'), ['id' => 'otp-resend-form']); ?>
          <button
            id="otp-resend-button"
            class="btn btn-outline-primary w-100 py-3 mb-3"
            type="submit"
            <?= !empty($cooldownRemaining) ? 'disabled' : '' ?>
            data-cooldown="<?= esc((string) (int) ($cooldownRemaining ?? 0)) ?>"
            data-label="Resend OTP<?= $resendsRemaining !== null ? ' (' . esc((string) $resendsRemaining) . ' left)' : '' ?>"
          >
            <?php if (!empty($cooldownRemaining)): ?>
              Resend available in <?= esc((string) ceil($cooldownRemaining)) ?>s
            <?php else: ?>
              Resend OTP<?= $resendsRemaining !== null ? ' (' . esc((string) $resendsRemaining) . ' left)' : '' ?>
            <?php endif; ?>
          </button>
        <?= form_close(); ?>
      <?php else: ?>
        <div class="alert alert-warning text-center mb-3" role="alert">
          You have reached the maximum number of resends. Please restart the login to request a new code.
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-between">
        <a class="link-secondary" href="<?= site_url('login/otp?reset=1') ?>">Use another mobile</a>
        <a class="link-secondary" href="<?= site_url('login') ?>">Use password instead</a>
      </div>
    </div>
  </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (() => {
    const otpInput = document.getElementById('otpInput');
    const toggle = document.getElementById('otp-visibility-toggle');
    toggle?.addEventListener('click', () => {
      if (!otpInput) {
        return;
      }
      const isMasked = otpInput.type === 'password';
      otpInput.type = isMasked ? 'text' : 'password';
      toggle.innerHTML = isMasked
        ? '<i class="fa-solid fa-eye-slash"></i>'
        : '<i class="fa-solid fa-eye"></i>';
      toggle.setAttribute('aria-pressed', isMasked ? 'true' : 'false');
    });
  })();
</script>
<?= $this->endSection() ?>
