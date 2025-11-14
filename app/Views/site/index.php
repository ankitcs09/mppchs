<?php
$landingStats = $stats ?? [
    'states'        => 0,
    'cities'        => 0,
    'hospitals'     => 0,
    'beneficiaries' => 0,
    'generatedAt'   => date(DATE_ATOM),
];
$stories       = $stories ?? [];
$testimonials  = $testimonials ?? [];
$faqCategories = $faqCategories ?? [];
$lastUpdated   = ! empty($landingStats['generatedAt'])
    ? date('d M Y', strtotime((string) $landingStats['generatedAt']))
    : date('d M Y');

// Override layout navigation to use in-page anchors on the landing page.
$navLinks = [
    ['id' => 'home', 'label' => 'Home', 'href' => '#hero'],
    ['id' => 'benefits', 'label' => 'Benefits', 'href' => '#benefits'],
    ['id' => 'coverage', 'label' => 'Coverage', 'href' => '#coverage'],
    ['id' => 'hospitals', 'label' => 'Hospitals', 'href' => '#hospital-search'],
    ['id' => 'contribution', 'label' => 'Contribution', 'href' => '#pricing'],
    ['id' => 'voices', 'label' => 'Voices', 'href' => site_url('testimonials')],
    ['id' => 'insights', 'label' => 'Leadership Insights', 'href' => site_url('stories')],
    ['id' => 'faq', 'label' => 'FAQ', 'href' => '#faq'],
    ['id' => 'contact', 'label' => 'Contact', 'href' => '#contact'],
];
$bodyClass = 'inner-page index-page';
$activeNav = 'home';
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section id="hero" class="hero landing-hero py-5">
  <div class="container">
    <div class="row align-items-center gy-4">
      <div class="col-lg-6" data-aos="fade-up">
        <p class="text-uppercase fw-semibold text-muted mb-2">MPPCHS</p>
        <h1 class="fw-bold mb-3">Cashless healthcare for every MPPGCL family</h1>
        <p class="lead mb-3">Employees, pensioners, and dependents can access immediate treatment across a fast-growing network of empanelled hospitals.</p>
        <p class="text-muted mb-4">कर्मचारी, पेंशनर एवं परिवार अब देशभर के मान्यता प्राप्त अस्पतालों में बिना भुगतान का उपचार पा सकते हैं।</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="#benefits" class="btn btn-primary">योजना के लाभ</a>
          <a href="#hospital-search" class="btn btn-outline-primary">नेटवर्क देखें</a>
        </div>
      </div>
      <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
        <div class="metrics-card">
          <h6 class="text-uppercase text-muted mb-3">Current reach</h6>
          <div class="row">
            <div class="col-6 stat">
              <span><?= number_format((int) ($landingStats['states'] ?? 0)) ?></span>
              <small>States supported</small>
            </div>
            <div class="col-6 stat">
              <span><?= number_format((int) ($landingStats['cities'] ?? 0)) ?></span>
              <small>Cities with hospitals</small>
            </div>
            <div class="col-6 stat">
              <span><?= number_format((int) ($landingStats['hospitals'] ?? 0)) ?></span>
              <small>Empanelled hospitals</small>
            </div>
            <div class="col-6 stat">
              <span><?= number_format((int) ($landingStats['beneficiaries'] ?? 0)) ?></span>
              <small>Beneficiaries onboard</small>
            </div>
          </div>
          <div class="text-muted small">Last updated <?= esc($lastUpdated) ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section py-5" id="leadership">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Leadership · नेतृत्व</h2>
      <p>Guiding vision behind the Contributory Cashless Health Scheme.</p>
    </div>
    <div class="row gy-4" data-aos="fade-up" data-aos-delay="50">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="leadership-card h-100">
          <img src="<?= base_url('assets/img/HCM.png') ?>" alt="Hon'ble Chief Minister" class="leadership-photo">
          <h5 class="mb-1">Dr Mohan Yadav</h5>
          <p class="mb-0">Hon'ble Chief Minister</p>
          <small class="text-muted">Government of Madhya Pradesh</small>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="leadership-card h-100">
          <img src="<?= base_url('assets/img/HEM.png') ?>" alt="Hon'ble Energy Minister" class="leadership-photo">
          <h5 class="mb-1">Shri Pradyumn Singh Tomar</h5>
          <p class="mb-0">Hon'ble Energy Minister</p>
          <small class="text-muted">Government of Madhya Pradesh</small>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="leadership-card h-100">
          <img src="<?= base_url('assets/img/chairman_Sir.png') ?>" alt="Chairman MPPGCL" class="leadership-photo">
          <h5 class="mb-1">Shri Neeraj Mandloi, IAS</h5>
          <p class="mb-0">Chairman, MPPGCL</p>
          <small class="text-muted">Additional Chief Secretary (Energy)</small>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="leadership-card h-100">
          <img src="<?= base_url('assets/img/MD_MPPGCL.png') ?>" alt="Managing Director" class="leadership-photo">
          <h5 class="mb-1">Shri Manjeet Singh</h5>
          <p class="mb-0">Managing Director</p>
          <small class="text-muted">M. P. Power Generating Co. Ltd.</small>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="about" class="section py-5 bg-light">
  <div class="container">
    <div class="row gy-4 align-items-center">
      <div class="col-lg-6" data-aos="fade-up">
        <img src="<?= base_url('assets/img/about.jpg') ?>" class="img-fluid rounded" alt="MPPCHS overview">
      </div>
      <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
        <h3 class="fw-bold mb-3">About MPPCHS</h3>
        <p>The scheme provides comprehensive cashless healthcare support through Implementation Support Agencies, empanelled hospitals, and a streamlined claims process.</p>
        <ul class="list-unstyled mt-3">
          <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i><strong>Contributory model:</strong> Shared contribution by beneficiaries and the company.</li>
          <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i><strong>Network strength:</strong> ISA-managed empanelment and authorisations.</li>
          <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i><strong>Cashless assurance:</strong> Direct admission without upfront payments.</li>
        </ul>
        <p class="text-muted mb-0">योगदान आधारित योजना जो परिवार को किसी भी कठिन घड़ी में तत्काल इलाज का भरोसा देती है।</p>
      </div>
    </div>
  </div>
</section>

<section id="stats" class="section py-5">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>MPPCHS Coverage Snapshot</h2>
      <p>Live view of the cashless network across India.</p>
    </div>
    <div class="row text-center gy-4" data-aos="fade-up" data-aos-delay="50">
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-map-location-dot fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="states"><?= number_format((int) ($landingStats['states'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">States covered</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-city fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="cities"><?= number_format((int) ($landingStats['cities'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Cities reached</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-hospital fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="hospitals"><?= number_format((int) ($landingStats['hospitals'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Empanelled hospitals</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-4 rounded stories-card">
          <i class="fa-solid fa-people-group fs-2 text-primary mb-3"></i>
          <h3 class="fw-bold mb-1" data-stat="beneficiaries"><?= number_format((int) ($landingStats['beneficiaries'] ?? 0)) ?></h3>
          <p class="text-muted mb-0">Beneficiaries protected</p>
        </div>
      </div>
    </div>
    <p class="text-center text-muted small mt-3">Last refreshed <span data-stat="timestamp"><?= esc($lastUpdated) ?></span></p>
  </div>
</section>

<section id="hospital-search" class="section py-5 bg-light">
  <div class="container section-title" data-aos="fade-up">
    <h2>Find Empanelled Hospitals</h2>
    <p>Select a State and City to view the current network under the scheme.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="50">
    <div class="row justify-content-center mb-4">
      <div class="col-md-4 mb-3">
        <label for="state" class="form-label fw-semibold">Select State / राज्य चुनें</label>
        <select id="state" class="form-select">
          <option value="">-- Select State --</option>
        </select>
      </div>
      <div class="col-md-4 mb-3">
        <label for="city" class="form-label fw-semibold">Select City / शहर चुनें</label>
        <select id="city" class="form-select" disabled>
          <option value="">-- Select City --</option>
        </select>
      </div>
    </div>
    <div id="hospital-panel" class="card shadow-sm mt-4 d-none">
      <button class="card-header bg-white border-0 d-flex justify-content-between align-items-center text-start fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hospital-collapse" aria-expanded="false" aria-controls="hospital-collapse">
        <span>Hospitals in <span id="selected-region">selected location</span></span>
        <span class="badge bg-primary rounded-pill" id="hospital-count">0</span>
      </button>
      <div id="hospital-collapse" class="collapse" data-bs-parent="#hospital-panel">
        <div class="card-body">
          <div id="hospital-table-wrapper" class="table-responsive d-none">
            <table class="table table-sm table-striped table-hover align-middle text-center">
              <thead class="table-primary">
                <tr>
                  <th style="width: 6%;">SN</th>
                  <th style="width: 36%;">Hospital Name</th>
                  <th style="width: 20%;">City</th>
                  <th style="width: 20%;">State</th>
                  <th style="width: 18%;">Contact</th>
                </tr>
              </thead>
              <tbody id="hospital-list"></tbody>
            </table>
          </div>
          <div id="hospital-pagination" class="d-flex justify-content-between align-items-center mt-3 d-none">
            <span class="text-muted small" id="hospital-range"></span>
            <div class="btn-group">
              <button type="button" class="btn btn-outline-primary btn-sm" id="hospital-prev">Previous</button>
              <button type="button" class="btn btn-outline-primary btn-sm" id="hospital-next">Next</button>
            </div>
          </div>
          <p class="text-muted small mb-0 text-center" id="hospital-hint">Select a state and city to view empanelled hospitals.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="benefits" class="section py-5">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Key Benefits / प्रमुख लाभ</h2>
      <p>Cashless access, transparent tracking, and strong support for every beneficiary.</p>
    </div>
    <div class="row gy-4" data-aos="fade-up" data-aos-delay="50">
      <div class="col-lg-4">
        <div class="service-item h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-shield-heart text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Cashless Hospitalisation</h4>
          <p>Instant admission and discharge at network hospitals with ISA coordination.</p>
          <p class="text-muted small mb-0">नेटवर्क अस्पतालों में बिना नकद भर्ती और छुट्टी।</p>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="service-item h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-people-roof text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Dependent Coverage</h4>
          <p>Spouses, children, and eligible parents are covered under one contribution.</p>
          <p class="text-muted small mb-0">एक ही योगदान में परिवार के सभी सदस्यों का संरक्षण।</p>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="service-item h-100 p-4">
          <div class="icon mb-3"><i class="fa-solid fa-chart-line text-primary fs-2"></i></div>
          <h4 class="fw-semibold mb-2">Transparent Tracking</h4>
          <p>Authorisations and claims tracked digitally with SMS/email updates.</p>
          <p class="text-muted small mb-0">सभी अनुमोदन और क्लेम की जानकारी SMS/ईमेल से।</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="coverage" class="section py-5 bg-light">
  <div class="container section-title" data-aos="fade-up">
    <h2>Coverage Highlights</h2>
    <p>Comprehensive protection across inpatient, daycare, maternity and preventive care.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="50">
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
        <div class="tab-content p-4 bg-white rounded shadow-sm">
          <div class="tab-pane active show" id="cov1">
            <h3>Inpatient Care</h3>
            <p>Covers room rent, medical procedures, medicines, and nursing for admissible hospitalisations.</p>
            <p class="text-muted mb-0">मान्य भर्ती के दौरान कमरे, दवाई एवं नर्सिंग का संपूर्ण खर्च।</p>
          </div>
          <div class="tab-pane" id="cov2">
            <h3>Day Care Procedures</h3>
            <p>Treatments requiring less than 24 hours stay are covered as per ISA guidelines.</p>
            <p class="text-muted mb-0">छोटे उपचार जिनमें 24 घंटे से कम समय लगे शामिल हैं।</p>
          </div>
          <div class="tab-pane" id="cov3">
            <h3>Maternity Benefits</h3>
            <p>Normal and caesarean deliveries covered within annual entitlement and limits.</p>
            <p class="text-muted mb-0">सामान्य एवं सीज़ेरियन डिलीवरी का वहन सीमाओं के अनुसार।</p>
          </div>
          <div class="tab-pane" id="cov4">
            <h3>Pre &amp; Post Hospitalisation</h3>
            <p>Diagnostics and medicines before admission and after discharge covered within defined windows.</p>
            <p class="text-muted mb-0">भर्ती से पहले और डिस्चार्ज के बाद की जाँच व दवाएं।</p>
          </div>
          <div class="tab-pane" id="cov5">
            <h3>Key Exclusions</h3>
            <p>Cosmetic procedures, non-medical items and notified exclusions remain outside cover.</p>
            <p class="text-muted mb-0">कॉस्मेटिक या गैर-चिकित्सा मदें योजना से बाहर रहती हैं।</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="stories" class="section py-5">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Voices &amp; Stories</h2>
      <p>Real beneficiary journeys, testimonials, and programme highlights.</p>
    </div>
    <?php if (empty($stories)): ?>
      <div class="text-center text-muted py-5" data-aos="fade-up" data-aos-delay="50">
        Fresh stories will appear here soon.
      </div>
    <?php else: ?>
      <div class="stories-grid">
        <?php foreach ($stories as $index => $story): ?>
          <?php
            $storyUrl    = ! empty($story['slug']) ? site_url('stories/' . rawurlencode((string) $story['slug'])) : site_url('stories');
            $publishedAt = ! empty($story['published_at']) ? date('d M Y', strtotime((string) $story['published_at'])) : null;
            $excerpt     = $story['excerpt'] ?? ($story['summary'] ?? '');
            $delay       = min($index, 5) * 40;
          ?>
          <article class="story-card" data-aos="fade-up" data-aos-delay="<?= esc($delay) ?>">
                <div class="story-meta mb-2">
                  <span class="badge-soft"><i class="fa-solid fa-newspaper me-1"></i>Voice</span>
              <?php if ($publishedAt): ?>
                <span><i class="fa-regular fa-calendar me-1"></i><?= esc($publishedAt) ?></span>
              <?php endif; ?>
            </div>
            <h3 class="h6 fw-semibold mb-1"><?= esc($story['title'] ?? 'Story') ?></h3>
            <p class="text-muted small flex-grow-1"><?= esc($excerpt) ?></p>
            <a class="btn btn-link px-0 mt-2" href="<?= esc($storyUrl) ?>">
              Read full story <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="voices" class="section py-5 bg-light">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Testimonials</h2>
      <p>Experiences shared by pensioners and their families.</p>
    </div>
    <?php if (empty($testimonials)): ?>
      <div class="text-center text-muted py-5" data-aos="fade-up" data-aos-delay="50">
        Testimonials will appear here as they are published.
      </div>
    <?php else: ?>
      <div class="voices-grid stories-grid">
        <?php foreach ($testimonials as $voiceIndex => $voice): ?>
          <?php
            $quote = $voice['quote'] ?? $voice['excerpt'] ?? $voice['summary'] ?? '';
            $delay = min($voiceIndex, 5) * 40;
          ?>
          <article class="voice-card" data-aos="fade-up" data-aos-delay="<?= esc($delay) ?>">
            <div class="voice-quote">
              <i class="fa-solid fa-quote-left me-2 text-primary"></i><?= esc($quote) ?>
            </div>
            <div class="voice-author">
              <strong><?= esc($voice['author_name'] ?? 'Beneficiary') ?></strong>
              <?php if (! empty($voice['author_title'])): ?>
                <span><?= esc($voice['author_title']) ?></span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="pricing" class="section py-5">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Plans &amp; Contributions / योजनाएं एवं योगदान</h2>
      <p>Compare plan options and understand the annual protection levels.</p>
    </div>
    <div class="table-responsive" data-aos="fade-up" data-aos-delay="50">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Particulars</th>
            <th>Option 1</th>
            <th>Option 2</th>
            <th>Option 3</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><strong>Beneficiary Contribution (₹ / month / family)</strong></td>
            <td>₹ 1,500</td>
            <td>₹ 1,800</td>
            <td>₹ 2,200</td>
          </tr>
          <tr>
            <td>2</td>
            <td><strong>Annual Sum Insured (₹ lakh)</strong></td>
            <td>₹ 5 lakh</td>
            <td>₹ 7.5 lakh</td>
            <td>₹ 10 lakh</td>
          </tr>
          <tr>
            <td>3</td>
            <td><strong>Room Rent Eligibility</strong></td>
            <td>₹ 3,000 per day</td>
            <td>₹ 5,000 per day</td>
            <td>₹ 6,500 per day</td>
          </tr>
          <tr>
            <td>4</td>
            <td><strong>Preventive Health Check-up</strong></td>
            <td>Once a year</td>
            <td>Once a year</td>
            <td>Twice a year</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="text-center mt-4">
      <a href="#contact" class="btn btn-primary px-4">Contact HR Cell</a>
    </div>
  </div>
</section>

<section id="faq" class="section py-5 bg-light">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Frequently Asked Questions / अक्सर पूछे जाने वाले प्रश्न</h2>
      <p class="text-muted mb-0">Search or filter by topic, then expand a question to view the answer in English or Hindi.</p>
    </div>
    <div class="faq-toolbar" data-aos="fade-up" data-aos-delay="50">
      <input type="search" id="faq-search" class="form-control" placeholder="Search questions e.g. reimbursement, hospital, ISA">
      <div class="faq-chips">
        <button type="button" class="faq-chip active" data-filter="all">All</button>
        <?php foreach ($faqCategories as $category): ?>
          <button type="button" class="faq-chip" data-filter="<?= esc($category['id']) ?>">
            <i class="fa-solid <?= esc($category['icon'] ?? 'fa-circle-question') ?> me-1"></i><?= esc($category['title']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="accordion" id="faqCategories" data-aos="fade-up" data-aos-delay="100">
      <?php foreach ($faqCategories as $category): ?>
        <?php $categoryCollapse = 'faq-cat-' . $category['id']; ?>
        <div class="accordion-item faq-category" data-category-id="<?= esc($category['id']) ?>">
          <h2 class="accordion-header" id="heading-<?= esc($category['id']) ?>">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= esc($category['id']) ?>" aria-expanded="false" aria-controls="collapse-<?= esc($category['id']) ?>">
              <span class="me-2 text-primary"><i class="fa-solid <?= esc($category['icon'] ?? 'fa-circle-question') ?>"></i></span>
              <div>
                <strong><?= esc($category['title']) ?></strong>
                <?php if (! empty($category['description'])): ?>
                  <div class="small text-muted"><?= esc($category['description']) ?></div>
                <?php endif; ?>
              </div>
            </button>
          </h2>
          <div id="collapse-<?= esc($category['id']) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= esc($category['id']) ?>" data-bs-parent="#faqCategories">
            <div class="accordion-body">
              <div class="accordion accordion-flush" id="<?= esc($categoryCollapse) ?>">
                <?php foreach ($category['questions'] as $question): ?>
                  <?php
                    $questionId = $category['id'] . '-' . $question['slug'];
                    $searchText = mb_strtolower($question['question'] . ' ' . $question['answer'] . ' ' . ($question['hindi'] ?? ''), 'UTF-8');
                  ?>
                  <div class="accordion-item faq-item" data-category="<?= esc($category['id']) ?>" data-search="<?= esc($searchText, 'attr') ?>">
                    <h2 class="accordion-header" id="heading-<?= esc($questionId) ?>">
                      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= esc($questionId) ?>" aria-expanded="false" aria-controls="collapse-<?= esc($questionId) ?>">
                        <?= esc($question['question']) ?>
                      </button>
                    </h2>
                    <div id="collapse-<?= esc($questionId) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= esc($questionId) ?>" data-bs-parent="#<?= esc($categoryCollapse) ?>">
                      <div class="accordion-body">
                        <p class="mb-2"><?= esc($question['answer']) ?></p>
                        <?php if (! empty($question['hindi'])): ?>
                          <button class="btn btn-sm btn-link px-0 faq-hindi-toggle" type="button" data-target="#hindi-<?= esc($questionId) ?>">हिंदी देखें</button>
                          <p class="faq-hindi d-none" id="hindi-<?= esc($questionId) ?>"><?= esc($question['hindi']) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div id="faq-empty" class="mt-4 d-none">
      <i class="fa-solid fa-magnifying-glass me-2"></i>No questions found for your search. Try another keyword or reset the filters.
    </div>
  </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('site/partials/hospital_script') ?>
<script>
  window.addEventListener('load', () => {
    const searchInput = document.getElementById('faq-search');
    const chips = Array.from(document.querySelectorAll('.faq-chip'));
    const faqItems = Array.from(document.querySelectorAll('.faq-item'));
    const categories = Array.from(document.querySelectorAll('.faq-category'));
    const emptyState = document.getElementById('faq-empty');
    const hindiToggles = Array.from(document.querySelectorAll('.faq-hindi-toggle'));

    if (!faqItems.length) {
      return;
    }

    let activeCategory = 'all';

    const showCollapse = (element) => {
      if (!element) {
        return;
      }
      if (typeof bootstrap !== 'undefined') {
        bootstrap.Collapse.getOrCreateInstance(element, { toggle: false }).show();
      } else {
        element.classList.add('show');
        element.style.height = 'auto';
      }
    };

    const hideCollapse = (element) => {
      if (!element) {
        return;
      }
      if (typeof bootstrap !== 'undefined') {
        bootstrap.Collapse.getOrCreateInstance(element, { toggle: false }).hide();
      } else {
        element.classList.remove('show');
        element.style.height = '';
      }
    };

    const applyFilters = () => {
      const term = (searchInput?.value || '').trim().toLowerCase();
      let visibleCount = 0;
      const categoryMatches = {};

      faqItems.forEach((item) => {
        const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
        const matchesSearch = !term || (item.dataset.search || '').includes(term);
        const shouldShow = matchesCategory && matchesSearch;
        item.classList.toggle('d-none', !shouldShow);
        if (shouldShow) {
          visibleCount++;
          const cat = item.dataset.category;
          categoryMatches[cat] = (categoryMatches[cat] || 0) + 1;
        }
      });

      categories.forEach((category) => {
        const catId = category.dataset.categoryId;
        const hasMatch = !!categoryMatches[catId];
        category.classList.toggle('d-none', !hasMatch);
        const collapseEl = category.querySelector('.accordion-collapse');
        if (!hasMatch) {
          hideCollapse(collapseEl);
        }
      });

      if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount !== 0);
      }

      if (term) {
        const firstVisibleCategory = categories.find((cat) => !cat.classList.contains('d-none'));
        if (firstVisibleCategory) {
          showCollapse(firstVisibleCategory.querySelector('.accordion-collapse'));
        }
      }
    };

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        chips.forEach((btn) => btn.classList.remove('active'));
        chip.classList.add('active');
        activeCategory = chip.dataset.filter || 'all';
        applyFilters();
      });
    });

    searchInput?.addEventListener('input', () => applyFilters());

    hindiToggles.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = document.querySelector(btn.dataset.target);
        if (!target) {
          return;
        }
        target.classList.toggle('d-none');
        btn.textContent = target.classList.contains('d-none') ? 'हिंदी देखें' : 'Hide Hindi';
      });
    });

    applyFilters();
  });
</script>
<?= $this->endSection() ?>
