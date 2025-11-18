<!doctype html>
<html lang="en" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="MPPGCL" />
    <title><?= esc($pageinfo['apptitle'] ?? 'Beneficiary') ?></title>
    <link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/vendor/flatpickr/flatpickr.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/vendor/flatpickr/material_blue.css') ?>" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;600&family=Roboto+Mono:wght@500&family=Noto+Sans+Devanagari:wght@400;600&display=swap" rel="stylesheet" />
    <link href="<?= base_url('assets/css/datatables.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" />
    <?= $this->renderSection('styles') ?>
  </head>
  <body data-theme="wellcare-classic">
    <?php
      $flashMappings = [
          'success' => 'success',
          'error'   => 'danger',
          'warning' => 'warning',
          'info'    => 'info',
      ];
      foreach ($flashMappings as $flashKey => $feedbackType):
        if ($flashMessage = session()->getFlashdata($flashKey)):
    ?>
      <span class="d-none" data-feedback-message="<?= esc($flashMessage) ?>" data-feedback-type="<?= esc($feedbackType) ?>"></span>
    <?php
        endif;
      endforeach;
    ?>
    <header class="app-navbar navbar sticky-top p-0">
      <div class="container-fluid d-flex align-items-center gap-3 px-3">
        <button
          class="btn btn-outline-secondary d-md-none"
          type="button"
          data-role="sidebar-toggle"
          aria-label="Toggle navigation"
          aria-controls="appSidebar"
          aria-expanded="false"
        >
          <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <a class="navbar-brand" href="<?= base_url(); ?>">
          <?= esc(session()->get('bname') ?? session()->get('username') ?? $pageinfo['appdashname'] ?? 'Dashboard') ?>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
          <div class="d-none d-md-block">
            <input
              type="search"
              class="form-control form-control-sm rounded-pill"
              placeholder="Search"
              aria-label="Search"
              data-role="global-search"
            />
          </div>
          <div class="theme-switcher">
            <label class="visually-hidden" for="themeSelectDesktop">Theme selection</label>
            <select
              id="themeSelectDesktop"
              class="form-select form-select-sm theme-select"
              data-role="theme-select"
              aria-label="Select colour theme"
            >
              <option value="wellcare-classic">WellCare Classic</option>
              <option value="wellcare-warm">WellCare Warm</option>
              <option value="freshcare-modern">FreshCare Modern</option>
            </select>
            <div class="form-check form-switch mb-0">
              <input
                class="form-check-input"
                type="checkbox"
                role="switch"
                id="themeModeSwitchDesktop"
                data-role="mode-toggle"
                aria-label="Toggle dark mode"
              />
              <label class="form-check-label" for="themeModeSwitchDesktop">Dark</label>
            </div>
          </div>
          <?php
            $localeOptions = [
                'en' => 'English',
                'hi' => 'हिन्दी',
            ];
            $currentLocale = service('request')->getLocale();
            $query = service('request')->getGet();
            $currentUrl = current_url();
          ?>
          <div class="language-switch">
            <?php foreach ($localeOptions as $code => $label): ?>
              <?php
                $query['lang'] = $code;
                $link = $currentUrl . '?' . http_build_query($query);
              ?>
              <a
                  class="text-decoration-none <?= $currentLocale === $code ? 'fw-semibold' : 'text-muted' ?>"
                  href="<?= esc($link) ?>"
              >
                <?= esc($label) ?>
              </a>
              <?php if ($code !== array_key_last($localeOptions)): ?>
                <span class="text-muted mx-1">|</span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </header>

    <?php
      $activeNav = $activeNav ?? '';
      $navItems  = navigation_items(isset($navItems) ? $navItems : null);
    ?>

    <div class="container-fluid app-shell">
      <div class="row flex-nowrap">
        <aside id="appSidebar" class="app-sidebar col-md-3 col-lg-2" data-role="sidebar">
          <div class="d-flex flex-column h-100 gap-4">
            <div class="d-md-none">
              <div class="theme-switcher flex-column align-items-stretch gap-2">
                <label class="form-label mb-1" for="themeSelectMobile">Theme</label>
                <select
                  id="themeSelectMobile"
                  class="form-select theme-select"
                  data-role="theme-select"
                  aria-label="Select colour theme"
                >
                  <option value="wellcare-classic">WellCare Classic</option>
                  <option value="wellcare-warm">WellCare Warm</option>
                  <option value="freshcare-modern">FreshCare Modern</option>
                </select>
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="themeModeSwitchMobile"
                    data-role="mode-toggle"
                  />
                  <label class="form-check-label" for="themeModeSwitchMobile">Dark mode</label>
                </div>
              </div>
            </div>
            <nav class="nav flex-column gap-1">
              <a class="nav-link d-flex align-items-center gap-2" href="<?= site_url('/') ?>">
                <i class="fa-solid fa-house fa-fw" aria-hidden="true"></i>
                Home
              </a>
            </nav>
            <nav class="nav flex-column gap-1">
              <?php foreach ($navItems as $item): ?>
                <a
                  class="nav-link d-flex align-items-center gap-2 <?= $item['id'] === $activeNav ? 'active' : '' ?>"
                  aria-current="<?= $item['id'] === $activeNav ? 'page' : 'false' ?>"
                  href="<?= esc($item['href']) ?>"
                >
                  <i class="<?= esc($item['icon']) ?> fa-fw" aria-hidden="true"></i>
                  <?= esc($item['label']) ?>
                </a>
              <?php endforeach; ?>
            </nav>
            <div class="mt-auto pt-3 border-top">
              <a class="nav-link d-flex align-items-center gap-2" href="<?= base_url('logout') ?>">
                <i class="fa-solid fa-right-from-bracket fa-fw" aria-hidden="true"></i>
                Sign out
              </a>
            </div>
          </div>
        </aside>
        <div class="app-sidebar-backdrop" data-role="sidebar-backdrop" hidden></div>
        <main class="app-main col-md-9 ms-sm-auto col-lg-10 px-md-4">
          <div class="app-main-surface">
            <?= $this->renderSection('header') ?>
            <?= $this->renderSection('content') ?>
          </div>
        </main>
      </div>
    </div>

    <?php if (session()->get('isLoggedIn')): ?>
      <div
        class="idle-timeout-modal"
        data-idle-modal
        data-warning-ms="1200000"
        data-timeout-ms="1800000"
        data-logout-url="<?= esc(site_url('logout')) ?>"
        hidden
      >
        <div class="idle-timeout-modal__backdrop" data-idle-backdrop></div>
        <div class="idle-timeout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="idleTimeoutTitle">
          <h2 class="idle-timeout-modal__title" id="idleTimeoutTitle">Still there?</h2>
          <p class="idle-timeout-modal__text">
            You've been inactive for a bit. We will sign you out soon to keep your account secure.
          </p>
          <div class="d-flex flex-column flex-sm-row gap-2">
            <button class="btn btn-primary w-100" type="button" data-idle-stay>Stay signed in</button>
            <button class="btn btn-outline-secondary w-100" type="button" data-idle-logout>Sign out now</button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
    <script type="module" src="<?= base_url('assets/js/app.js') ?>"></script>
  </body>
</html>
