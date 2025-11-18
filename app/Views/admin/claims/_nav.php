<?php

$active = $active ?? 'registry';

$tabs = [
    'registry' => [
        'label' => 'Claims registry',
        'href'  => site_url('admin/claims'),
        'icon'  => 'fa-solid fa-list-check',
    ],
    'batches' => [
        'label' => 'Ingestion batches',
        'href'  => site_url('admin/claims/batches'),
        'icon'  => 'fa-solid fa-cloud-arrow-down',
    ],
    'downloads' => [
        'label' => 'Document downloads',
        'href'  => site_url('admin/claims/downloads'),
        'icon'  => 'fa-solid fa-file-arrow-down',
    ],
];
?>

<ul class="nav nav-pills flex-wrap gap-2 mb-3 claims-tabs">
  <?php foreach ($tabs as $key => $tab): ?>
    <li class="nav-item">
      <a
        class="nav-link<?= $key === $active ? ' active' : '' ?>"
        href="<?= esc($tab['href']) ?>"
        aria-current="<?= $key === $active ? 'page' : 'false' ?>"
      >
        <?php if (! empty($tab['icon'])): ?>
          <i class="<?= esc($tab['icon']) ?> me-1"></i>
        <?php endif; ?>
        <?= esc($tab['label']) ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
