<?php
$activeNav = $activeNav ?? 'insights';
$stories   = $stories ?? [];
$pager     = $pager ?? null;
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section pt-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>Leadership Insights</h2>
    <p>Reflections and updates shared by scheme leadership.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="50">
    <?php if (empty($stories)): ?>
      <div class="text-center text-muted py-5">
        Fresh stories will appear here soon. Stay tuned!
      </div>
    <?php else: ?>
      <div class="row gy-4">
        <?php foreach ($stories as $story): ?>
          <div class="col-md-6 col-lg-4">
            <article class="card h-100 shadow-sm border-0">
              <?php if (! empty($story['featured_image'])): ?>
                <img src="<?= esc($story['featured_image']) ?>" class="card-img-top" alt="<?= esc($story['title']) ?>">
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <p class="text-uppercase text-muted small mb-1">Story</p>
                <h3 class="h5"><?= esc($story['title']) ?></h3>
                <p class="text-muted small flex-grow-1"><?= esc($story['excerpt'] ?? '') ?></p>
                <?php $storyUrl = ! empty($story['slug']) ? site_url('stories/' . rawurlencode((string) $story['slug'])) : '#'; ?>
                <a class="stretched-link mt-2" href="<?= esc($storyUrl) ?>">
                  Read full story
                </a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($pager): ?>
        <div class="mt-4 d-flex justify-content-center">
          <?= $pager->links('stories', 'default_full') ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>




