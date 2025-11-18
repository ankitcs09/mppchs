<?php
$activeNav = 'voices';
$voices    = $voices ?? [];
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section pt-5">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <h2>Beneficiary Voices</h2>
      <p>Experiences shared by pensioners and their families across the MPPCHS network.</p>
    </div>
    <?php if (empty($voices)): ?>
      <div class="text-center text-muted py-5" data-aos="fade-up" data-aos-delay="50">
        Testimonials will appear here as they are published.
      </div>
    <?php else: ?>
      <div class="row gy-4" data-aos="fade-up" data-aos-delay="50">
        <?php foreach ($voices as $index => $voice): ?>
          <?php
            $quote = $voice['quote'] ?? $voice['excerpt'] ?? $voice['summary'] ?? '';
            $delay = min($index, 5) * 40;
          ?>
          <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= esc($delay) ?>">
            <article class="voice-card h-100">
              <div class="voice-quote mb-3">
                <i class="fa-solid fa-quote-left me-2 text-primary"></i><?= esc($quote) ?>
              </div>
              <div class="voice-author">
                <strong><?= esc($voice['author_name'] ?? 'Beneficiary') ?></strong>
                <?php if (! empty($voice['author_title'])): ?>
                  <span><?= esc($voice['author_title']) ?></span>
                <?php endif; ?>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>
