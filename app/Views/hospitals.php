<?php $activeNav = 'view-hospitals'; ?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title">
      <?= isset($pageinfo['appdashname']) ? esc($pageinfo['appdashname']) : 'Empanelled Hospitals' ?>
    </h1>
    <p class="page-heading__subtitle">
      Search the authorised network by geography and review category guidelines before initiating requests.
    </p>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section
  class="app-panel hospitals-panel"
  data-module="hospitals-index"
  data-states-endpoint="<?= site_url('hospitals/states') ?>"
  data-cities-endpoint="<?= site_url('hospitals/cities') ?>"
  data-list-endpoint="<?= site_url('hospitals/list') ?>"
  data-default-state="Madhya Pradesh"
>
  <div class="app-panel__header">
    <div>
      <h2 class="app-panel__title mb-1">Filter the network</h2>
      <p class="app-panel__subtitle mb-0">
        Narrow down the empanelled hospitals by geography, then drill into category definitions and contact options.
      </p>
    </div>
    <div class="app-panel__meta">
      <div class="metric-chip">
        <span class="metric-chip__label">Active filters</span>
        <span class="metric-chip__value" data-role="filter-count">0</span>
      </div>
      <button class="btn btn-outline-secondary btn-sm" type="button" data-action="reset-filters">
        <i class="fa-solid fa-rotate-left me-1"></i>
        Reset
      </button>
    </div>
  </div>

  <div class="row g-4 align-items-end">
    <div class="col-md-4">
      <label for="stateFilter" class="form-label text-muted text-uppercase small">State</label>
      <div class="form-floating">
        <select id="stateFilter" class="form-select" data-role="state-filter">
          <option value="">All States</option>
        </select>
        <label for="stateFilter">Choose a state</label>
      </div>
    </div>
    <div class="col-md-4">
      <label for="cityFilter" class="form-label text-muted text-uppercase small">City</label>
      <div class="form-floating">
        <select id="cityFilter" class="form-select" data-role="city-filter" disabled>
          <option value="">All Cities</option>
        </select>
        <label for="cityFilter">Refine by city</label>
      </div>
    </div>
    <div class="col-md-4">
      <label for="hospitalSearch" class="form-label text-muted text-uppercase small">Search</label>
      <div class="input-group hospital-search">
        <span class="input-group-text" id="hospitalSearchIcon">
          <i class="fa-solid fa-magnifying-glass"></i>
        </span>
        <input
          type="search"
          id="hospitalSearch"
          class="form-control"
          placeholder="Search by hospital, phone, or email"
          aria-label="Search hospital records"
          data-role="table-search"
        />
        <button
          class="btn btn-outline-secondary"
          type="button"
          aria-label="Clear search"
          data-action="clear-search"
          hidden
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>
  </div>

  <div class="hospital-filter-pills mt-4" data-role="filter-pills" hidden></div>

  <div class="hospital-legend card card-border mt-4">
    <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
      <div class="legend-icon rounded-circle flex-shrink-0">
        <i class="fa-solid fa-hospital"></i>
      </div>
      <div class="flex-grow-1">
        <h3 class="h6 mb-1">Category primer</h3>
        <p class="text-muted mb-0">
          Tap on a category pill inside the table to view its coverage notes, rate slabs, and co-pay rules.
          Keep the cursor over the pill to preview the description.
        </p>
      </div>
      <div class="text-md-end">
        <p class="mb-1 text-muted small">Need an offline copy?</p>
        <a
          class="btn btn-outline-primary btn-sm"
          href="<?= site_url('hospitals/list') ?>?format=csv"
          target="_blank"
          rel="noopener"
        >
          <i class="fa-solid fa-download me-1"></i> Export CSV
        </a>
      </div>
    </div>
  </div>

  <div class="table-surface table-stack-mobile mt-4">
    <div class="table-toolbar d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between">
      <div class="table-toolbar__meta small text-muted">
        <span data-role="result-count">Loading...</span>
      </div>
      <div class="table-toolbar__controls d-flex flex-column flex-md-row gap-3 align-items-md-center">
        <div class="d-flex align-items-center gap-2">
          <label for="pageLength" class="small text-muted text-uppercase">Rows</label>
          <select id="pageLength" class="form-select form-select-sm" data-role="page-length">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary btn-sm" type="button" data-action="refresh-table">
            <i class="fa-solid fa-rotate-right me-1"></i>
            Refresh
          </button>
        </div>
      </div>
    </div>
    <div class="table-responsive mt-3">
      <table id="hospitalTable" class="table table-hover align-middle mb-0" data-role="hospital-table">
        <thead>
          <tr>
            <th scope="col">Hospital</th>
            <th scope="col">Category</th>
            <th scope="col">State</th>
            <th scope="col">City</th>
            <th scope="col">Phone</th>
            <th scope="col">Email</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div class="table-loading" data-role="table-loading" hidden>
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <p class="text-muted small mb-0">Fetching hospitals...</p>
      </div>
    </div>
    <div class="empty-state text-center py-5" data-role="empty-state" hidden>
      <div class="empty-state__icon rounded-circle mx-auto mb-3">
        <i class="fa-solid fa-location-dot"></i>
      </div>
      <h3 class="h5 mb-2">No hospitals match the filters</h3>
      <p class="text-muted mb-3">Try clearing one of the filters or search with a different keyword.</p>
      <button class="btn btn-primary" type="button" data-action="reset-filters">
        Clear Filters
      </button>
    </div>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/datatables.min.js') ?>"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<?= $this->endSection() ?>
