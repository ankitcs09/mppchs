<?php
$session      = session();
$isLoggedIn   = (bool) ($session->get('isLoggedIn') ?? false);
$dashboardUrl = site_url('dashboard');
$loginUrl     = site_url('login');
$logoutUrl    = site_url('logout');
$activeNav    = $activeNav ?? null;
$navLinks     = $navLinks ?? site_nav_links();
$heroContent = trim($this->renderSection('hero'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= esc($title ?? 'MPPGCL :: Secure Access') ?></title>
  <meta name="csrf-token" content="<?= esc(csrf_hash()) ?>">
  <link href="<?= base_url('assets/img/favicon.png') ?>" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/main.css') ?>" rel="stylesheet">
  <?= $this->renderSection('styles') ?>
</head>

<body class="inner-page auth-page">
  <?= view('site/partials/header', compact('navLinks', 'activeNav')) ?>

  <main id="main" class="auth-main py-5">
    <div class="container" id="auth-main">
      <div class="row align-items-center gy-5">
        <?php if ($heroContent !== ''): ?>
          <div class="col-lg-6 auth-hero">
            <?= $heroContent ?>
          </div>
          <div class="col-lg-6">
            <div class="auth-card">
              <?= $this->renderSection('form') ?>
            </div>
          </div>
        <?php else: ?>
          <div class="col-lg-7 col-xl-6 mx-auto">
            <div class="auth-card">
              <?= $this->renderSection('form') ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

    <?= view('site/partials/footer') ?>

  <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/accessibility.js') ?>"></script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>


