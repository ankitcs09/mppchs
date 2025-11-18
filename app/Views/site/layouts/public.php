<?php
$activeNav  = $activeNav ?? 'home';
$navLinks   = $navLinks ?? site_nav_links();
$bodyClass  = trim((string) ($bodyClass ?? 'inner-page'));
$bodyClass  = $bodyClass === '' ? 'inner-page' : $bodyClass;
$session      = session();
$isLoggedIn   = (bool) ($session->get('isLoggedIn') ?? false);
$dashboardUrl = site_url('dashboard');
$loginUrl     = site_url('login');
$logoutUrl    = site_url('logout');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= esc($pageTitle ?? 'MPPGCL Cashless') ?></title>
  <meta name="description" content="<?= esc($pageDescription ?? 'MPPCHS updates') ?>">
  <meta name="csrf-token" content="<?= esc(csrf_hash()) ?>">

  <link href="<?= base_url('assets/img/favicon.png') ?>" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/aos/aos.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/glightbox/css/glightbox.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/swiper/swiper-bundle.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/main.css') ?>" rel="stylesheet">
  <?= $this->renderSection('styles') ?>
</head>

<body class="<?= esc($bodyClass) ?>">
  <?= view('site/partials/header', compact('navLinks', 'activeNav')) ?>

  <main id="main" class="py-5">
    <?= $this->renderSection('content') ?>
  </main>

    <?= view('site/partials/footer') ?>

  <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/aos/aos.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/glightbox/js/glightbox.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/swiper/swiper-bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/main.js') ?>"></script>
  <script src="<?= base_url('assets/js/accessibility.js') ?>"></script>
  <script>
    (() => {
      const body = document.body;
      const navToggle = document.querySelector('.mobile-nav-toggle');
      const nav = document.getElementById('navmenu');

      navToggle?.addEventListener('click', () => {
        body.classList.toggle('mobile-nav-active');
        const icon = navToggle.querySelector('i');
        if (icon) {
          icon.classList.toggle('fa-bars');
          icon.classList.toggle('fa-xmark');
        }
      });

      nav?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
          if (body.classList.contains('mobile-nav-active')) {
            body.classList.remove('mobile-nav-active');
            const icon = navToggle?.querySelector('i');
            if (icon && icon.classList.contains('fa-xmark')) {
              icon.classList.replace('fa-xmark', 'fa-bars');
            }
          }
        });
      });
    })();
  </script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>


