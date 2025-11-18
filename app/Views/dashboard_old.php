<?php
$activeNav = $activeNav ?? 'dashboard';
$name      = $name ?? session()->get('bname') ?? session()->get('username');
$today     = new DateTime();

$summaryChips = [
    [
        'label' => 'Active Requests',
        'value' => number_format((int) ($stats['activeRequests'] ?? 0)),
        'hint'  => 'Awaiting hospital or admin action',
    ],
    [
        'label' => 'Approved Claims (12 mo)',
        'value' => '₹ ' . number_format((float) ($stats['approvedAmountYear'] ?? 0), 2),
        'hint'  => 'Cashless amount settled',
    ],
    [
        'label' => 'Dependents Covered',
        'value' => number_format((int) ($stats['dependentsCovered'] ?? 0)),
        'hint'  => 'Family members with active coverage',
    ],
];

$quickLinks = [
    [
        'href' => site_url('beneficiary'),
        'icon' => 'fa-solid fa-id-card',
        'label' => 'Beneficiary Profile',
        'description' => 'Review identifiers, scheme details, and contact information.',
    ],
    [
        'href' => site_url('dependent'),
        'icon' => 'fa-solid fa-people-group',
        'label' => 'Dependent Coverage',
        'description' => 'Check coverage status for each dependent and update documentation.',
    ],
    [
        'href' => site_url('history'),
        'icon' => 'fa-solid fa-clock-rotate-left',
        'label' => 'Request History',
        'description' => 'See recently submitted requests, updates, and actions required.',
    ],
    [
        'href' => site_url('claims'),
        'icon' => 'fa-solid fa-clipboard-list',
        'label' => 'My Claims',
        'description' => 'Track claim status, cashless settlements, and approvals.',
    ],
];
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">Welcome<?= $name ? ', ' . esc($name) : '' ?></h1>
    <p class="page-heading__subtitle">Here&rsquo;s a snapshot of your cashless health journey and the actions you can take today.</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('logout') ?>">
    Sign out
  </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="app-panel dashboard-overview">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Today&rsquo;s snapshot</h2>
    <p class="app-panel__subtitle">Stay on top of requests, claims, and coverage at a glance.</p>
  </header>
  <div class="dashboard-overview__chips">
    <?php foreach ($summaryChips as $chip): ?>
      <div class="summary-chip dashboard-overview__chip">
        <div class="summary-chip__content">
          <span class="summary-chip__label"><?= esc($chip['label']) ?></span>
          <span class="summary-chip__value"><?= esc($chip['value']) ?></span>
        </div>
        <span class="summary-chip__hint"><?= esc($chip['hint']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="app-panel dashboard-actions">
  <header class="app-panel__header">
    <h2 class="app-panel__title mb-1">Quick actions</h2>
    <p class="app-panel__subtitle">Choose what you&rsquo;d like to do next.</p>
  </header>
  <div class="dashboard-actions__grid">
    <?php foreach ($quickLinks as $link): ?>
      <a class="dashboard-action" href="<?= esc($link['href']) ?>">
        <span class="dashboard-action__icon">
          <i class="<?= esc($link['icon']) ?>" aria-hidden="true"></i>
        </span>
        <div class="dashboard-action__body">
          <h3 class="dashboard-action__title"><?= esc($link['label']) ?></h3>
          <p class="dashboard-action__description text-muted"><?= esc($link['description']) ?></p>
        </div>
        <span class="dashboard-action__chevron fa-solid fa-chevron-right" aria-hidden="true"></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="app-panel dashboard-help">
  <div class="dashboard-help__content">
    <div>
      <h2 class="dashboard-help__title">Need assistance?</h2>
      <p class="text-muted mb-0">
        Reach our cashless help desk between 10:00 AM and 6:30 PM at <a href="tel:07552610452" class="link-secondary">0755-2610452</a>,
        or email <a href="mailto:cashless@mppgcl.in" class="link-secondary">cashless@mppgcl.in</a>. We&rsquo;re happy to help.
      </p>
    </div>
    <a class="btn btn-outline-primary" href="<?= site_url('support/faqs') ?>">
      Browse FAQs
    </a>
  </div>
</section>
<?= $this->endSection() ?>
