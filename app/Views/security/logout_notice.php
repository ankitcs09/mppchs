<?php

$logoutUrl = $logoutUrl ?? site_url('logout');
$delayMs   = $delayMs ?? 3000;

?>

<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<section class="app-panel text-center py-5">
  <div class="mb-4">
    <h1 class="h3 mb-2">Password Updated</h1>
    <p class="text-muted">
      For your security we’ll sign you out and ask you to sign in again.
    </p>
  </div>
  <div class="d-flex flex-column gap-2 align-items-center">
    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
    <p class="text-muted small mb-0">
      Redirecting you in a moment&hellip;
      <a href="<?= esc($logoutUrl) ?>">Sign out now</a>
    </p>
  </div>
</section>

<?= $this->endSection() ?>

<div
  class="logout-redirect-trigger d-none"
  data-logout-url="<?= esc($logoutUrl) ?>"
  data-delay-ms="<?= (int) $delayMs ?>"
></div>
