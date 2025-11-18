<?php $activeNav = 'benefits'; ?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>Key Benefits</h2>
    <p>Cashless access, transparent tracking, and strong support for every beneficiary.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="row gy-4">
      <div class="col-lg-4 col-md-6">
        <div class="service-item position-relative shadow-sm h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-shield-heart text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Cashless Hospitalisation</h4>
          <p>Instant admission and discharge at network hospitals with ISA co-ordination.</p>
          <p class="text-muted small mb-0">नेटवर्क अस्पतालों में बिना अग्रिम भुगतान के उपचार।</p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="service-item position-relative shadow-sm h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-people-line text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Dependent Coverage</h4>
          <p>Spouses, children, and eligible parents are covered under a single family contribution.</p>
          <p class="text-muted small mb-0">एक ही पारिवारिक योगदान में सभी आश्रितों का कवरेज।</p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="service-item position-relative shadow-sm h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-chart-line text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Transparent Tracking</h4>
          <p>Authorisations and claims tracked digitally with SMS/email updates.</p>
          <p class="text-muted small mb-0">अधिकृत अनुमोदन और दावे डिजिटल रूप से ट्रैक।</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
