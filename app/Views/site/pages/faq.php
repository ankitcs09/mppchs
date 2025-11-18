<?php
$activeNav = 'faq';
$faqCategories = $faqCategories ?? [];
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5 faq-page">
  <div class="container section-title" data-aos="fade-up">
    <h2>Frequently Asked Questions</h2>
    <p>MPPCHS onboarding, authorisation and claims workflow explained in simple steps.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="50">
    <div class="row gy-3 align-items-center mb-4">
      <div class="col-12">
        <input type="search" class="form-control" id="faq-search" placeholder="Search questions e.g. reimbursement, hospital, ISA">
      </div>
      <div class="col-12">
        <div class="faq-chips">
          <button class="faq-chip active" data-filter="all">All</button>
          <?php foreach ($faqCategories as $category): ?>
            <button class="faq-chip" data-filter="<?= esc($category['id']) ?>">
              <i class="fa-solid <?= esc($category['icon'] ?? 'fa-circle-question') ?> me-1"></i>
              <?= esc($category['title']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="accordion" id="faqCategories" data-aos="fade-up" data-aos-delay="100">
      <?php foreach ($faqCategories as $category): ?>
        <div class="accordion-item faq-category" data-category-id="<?= esc($category['id']) ?>">
          <h2 class="accordion-header" id="heading-<?= esc($category['id']) ?>">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= esc($category['id']) ?>" aria-expanded="false" aria-controls="collapse-<?= esc($category['id']) ?>">
              <i class="fa-solid <?= esc($category['icon'] ?? 'fa-circle-question') ?> me-2 text-primary"></i>
              <?= esc($category['title']) ?>
            </button>
          </h2>
          <div id="collapse-<?= esc($category['id']) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= esc($category['id']) ?>" data-bs-parent="#faqCategories">
            <div class="accordion-body">
              <?php foreach ($category['questions'] ?? [] as $faq): ?>
                <div class="faq-item mb-3" data-category="<?= esc($category['id']) ?>" data-search="<?= esc(strtolower(($faq['question'] ?? '') . ' ' . ($faq['answer'] ?? '') . ' ' . ($faq['hindi'] ?? ''))) ?>">
                  <div class="d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-1"><?= esc($faq['question'] ?? 'Question') ?></h3>
                    <button class="btn btn-sm btn-outline-secondary faq-hindi-toggle" type="button" data-target="#hindi-<?= esc($faq['slug'] ?? uniqid()) ?>">
                      हिन्दी देखें
                    </button>
                  </div>
                  <p class="mb-2 text-muted"><?= esc($faq['answer'] ?? '') ?></p>
                  <?php if (! empty($faq['hindi'])): ?>
                    <div class="faq-hindi collapse" id="hindi-<?= esc($faq['slug'] ?? uniqid()) ?>">
                      <p class="mb-0"><?= esc($faq['hindi']) ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div id="faq-empty" class="text-center text-muted py-5 d-none">
      <i class="fa-solid fa-circle-question fa-2x mb-3"></i>
      <p class="mb-0">No questions matched your search. Try a different keyword or category.</p>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
