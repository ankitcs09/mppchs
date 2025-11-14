<?php
$activeNav = $activeNav ?? 'dashboard-v2';
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">Dashboard</h1>
  <p class="text-muted mb-0">We could not find your enrollment data yet.</p>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="alert alert-warning">
  <h5 class="alert-heading">No Enrollment Record</h5>
  <p class="mb-2">Your account does not have a linked enrollment (v2) record at the moment. If you recently submitted the form, please allow a little time for the import to finish.</p>
  <p class="mb-0">If this message persists, contact the support desk with your reference number or raise a ticket so that we can link your profile.</p>
</div>
<?= $this->endSection() ?>
