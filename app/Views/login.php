<?php $title = lang('Auth.signInTitle'); ?>

<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
  <h1 class="mb-3"><?= lang('Auth.heroTitle') ?></h1>
  <p class="mb-4">
    <?= lang('Auth.heroBody') ?>
  </p>
  <div class="d-flex gap-3 flex-wrap">
    <div>
      <i class="fa-solid fa-shield-halved text-primary fs-4"></i>
      <span class="ms-2 text-muted"><?= lang('Auth.loginOptions') ?></span>
    </div>
    <div>
      <i class="fa-solid fa-hospital text-primary fs-4"></i>
      <span class="ms-2 text-muted"><?= lang('Auth.hospitalInfo') ?></span>
    </div>
  </div>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
  <?php helper('form'); ?>
  <?= form_open('login', ['class' => 'needs-validation', 'novalidate' => 'novalidate']); ?>
    <h2 class="mb-4"><?= lang('Auth.signInTitle') ?></h2>
    <div class="mb-3">
      <label for="username" class="form-label"><?= lang('Auth.username') ?></label>
      <input
        type="text"
        id="username"
        name="username"
        class="form-control form-control-lg"
        placeholder="<?= lang('Auth.username') ?>"
        value="<?= esc(old('username')) ?>"
        required
      />
      <div class="invalid-feedback"><?= lang('Auth.username') ?></div>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label"><?= lang('Auth.password') ?></label>
      <input
        type="password"
        id="password"
        name="password"
        class="form-control form-control-lg"
        placeholder="<?= lang('Auth.password') ?>"
        required
      />
      <div class="invalid-feedback"><?= lang('Auth.password') ?></div>
    </div>

    <div class="form-check mb-3">
      <input
        class="form-check-input"
        type="checkbox"
        value="1"
        id="remember"
        name="remember"
        <?= old('remember') ? 'checked' : '' ?>
      />
      <label class="form-check-label" for="remember">
        <?= lang('Auth.rememberMeLabel') ?>
      </label>
      <div class="form-text"><?= lang('Auth.rememberMeHelp') ?></div>
    </div>

    <button class="btn btn-primary w-100 py-3 mt-2" type="submit">
      <?= lang('Auth.loginButton') ?>
    </button>
  <?= form_close(); ?>

  <div class="auth-links mt-4 text-center">
    <a href="<?= site_url('password/forgot') ?>" class="text-decoration-none d-block mb-3"><?= lang('Auth.forgotPassword') ?></a>
    <p class="mb-2 text-muted"><?= lang('Auth.otpPrompt') ?></p>
    <a class="btn btn-outline-primary w-100 py-3" href="<?= site_url('login/otp') ?>">
      <?= lang('Auth.otpCta') ?>
    </a>
  </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
