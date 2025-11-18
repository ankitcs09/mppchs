<?php $activeNav = 'coverage'; ?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>Coverage Highlights</h2>
    <p>Comprehensive protection across inpatient, daycare, maternity and preventive care.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="row">
      <div class="col-lg-3">
        <ul class="nav nav-tabs flex-column">
          <li class="nav-item"><a class="nav-link active show" data-bs-toggle="tab" href="#cov1">Inpatient Care</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cov2">Day Care</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cov3">Maternity</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cov4">Pre/Post Hospitalisation</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cov5">Exclusions</a></li>
        </ul>
      </div>
      <div class="col-lg-9 mt-4 mt-lg-0">
        <div class="tab-content">
          <div class="tab-pane active show" id="cov1">
            <h3>Inpatient Care</h3>
            <p>Covers room rent, medical procedures, medicines and nursing for admissible hospitalisations.</p>
            <p class="text-muted mb-0">स्वीकृत भरती पर कक्ष, उपचार और दवाओं का पूर्ण कवरेज।</p>
          </div>
          <div class="tab-pane" id="cov2">
            <h3>Day Care Procedures</h3>
            <p>Authorised treatments requiring less than 24 hours stay are covered as per ISA guidelines.</p>
            <p class="text-muted mb-0">24 घंटे से कम ठहराव वाले अनुमोदित उपचार शामिल।</p>
          </div>
          <div class="tab-pane" id="cov3">
            <h3>Maternity Benefits</h3>
            <p>Normal and caesarean deliveries covered within annual entitlement and hospital limits.</p>
            <p class="text-muted mb-0">प्रसव लाभ योजना की सीमा के अंतर्गत।</p>
          </div>
          <div class="tab-pane" id="cov4">
            <h3>Pre &amp; Post Hospitalisation</h3>
            <p>Diagnostics and medicines before admission and after discharge covered within specified windows.</p>
            <p class="text-muted mb-0">भर्ती से पूर्व व डिस्चार्ज के बाद की जाँच/दवाएँ शामिल।</p>
          </div>
          <div class="tab-pane" id="cov5">
            <h3>Key Exclusions</h3>
            <p>Cosmetic procedures, non-medical items and notified exclusions remain outside cashless cover.</p>
            <p class="text-muted mb-0">कॉस्मेटिक उपचार एवं सूचित अपवर्जन योजना से बाहर।</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
