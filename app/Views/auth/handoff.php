<?php

$displayName = $user['display_name'] ?? $user['username'] ?? 'your account';
$lastSeen    = $lastSeen ?? null;
$methodLabel = 'current session';

?>

<?= $this->extend('layouts/auth') ?>

<?= $this->section('hero') ?>
<div class="text-center">
  <h2 class="mb-3">Secure Login</h2>
  <p class="text-muted mb-0">
    We detected an active session for <?= esc($displayName) ?> on another device.
    You can continue here and sign the other device out.
  </p>
</div>
<?= $this->endSection() ?>

<?= $this->section('form') ?>
<div class="text-center">
  <h1 class="h4 mb-3">Continue on this device?</h1>
  <p class="text-muted">
    Continuing here will sign out your earlier session.
    <?php if ($lastSeen): ?>
      <br />Last activity on the other device: <strong><?= esc($lastSeen) ?></strong>
    <?php endif; ?>
  </p>
  <form method="post" action="<?= site_url('login/handoff/confirm') ?>" class="d-flex flex-column gap-2 mb-3">
    <?= csrf_field() ?>
    <button class="btn btn-primary btn-lg w-100" type="submit">Continue here</button>
  </form>
  <form method="post" action="<?= site_url('login/handoff/cancel') ?>" class="d-flex flex-column gap-2">
    <?= csrf_field() ?>
    <button class="btn btn-outline-secondary w-100" type="submit">Cancel</button>
  </form>
</div>
<?= $this->endSection() ?>
