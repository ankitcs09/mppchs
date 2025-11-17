<?php
$navLinks  = $navLinks ?? site_nav_links();
$session      = session();
$isLoggedIn   = (bool) ($session->get('isLoggedIn') ?? false);
$dashboardUrl = site_url('dashboard');
$loginUrl     = site_url('login');
$logoutUrl    = site_url('logout');
?>
<a class="skip-link" href="#main">Skip to main content</a>

<header id="header" class="sticky-top">
  <div class="access-bar py-2">
    <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-3 small">
        <span>English</span>
        <span>|</span>
        <span>हिन्दी</span>
      </div>
      <div class="toolbar d-flex align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-1">
          <span class="small text-white-50">Text:</span>
          <button type="button" class="btn btn-sm btn-outline-light" data-font-scale="sm" aria-pressed="false">A-</button>
          <button type="button" class="btn btn-sm btn-outline-light active" data-font-scale="md" aria-pressed="true">A</button>
          <button type="button" class="btn btn-sm btn-outline-light" data-font-scale="lg" aria-pressed="false">A+</button>
        </div>
      </div>
    </div>
  </div>
  <div class="brand-bar">
    <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <img src="<?= base_url('assets/img/cashless-logo.png') ?>" alt="MPPCHS" height="56" class="rounded-circle border border-light shadow-sm">
        <div>
          <div class="brand-title">MPPGCL · Contributory Cashless Health Scheme</div>
          <div class="brand-subtitle">Cashless healthcare for employees, pensioners & dependent families.</div>
        </div>
      </div>
      <div class="text-muted small"><i class="fa-regular fa-calendar me-2"></i>Serving beneficiaries across India</div>
    </div>
  </div>
  <div class="nav-bar py-2">
    <div class="container d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <nav id="navmenu" class="navmenu">
        <ul>
          <?php foreach ($navLinks as $link): ?>
            <?php $isActive = ($link['id'] ?? null) === ($activeNav ?? null); ?>
            <li>
              <a class="<?= $isActive ? 'active' : '' ?>" href="<?= esc($link['href']) ?>">
                <?= esc($link['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="cta-group d-none d-lg-flex gap-2">
        <?php if ($isLoggedIn): ?>
          <a class="btn btn-primary" href="<?= esc($dashboardUrl) ?>"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
          <a class="btn btn-outline-secondary" href="<?= esc($logoutUrl) ?>">Sign out</a>
        <?php else: ?>
          <a class="btn btn-primary" href="<?= esc($loginUrl) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</a>
        <?php endif; ?>
      </div>
      <button class="mobile-nav-toggle d-lg-none" type="button" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
