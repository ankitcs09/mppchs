<?php
$activeNav   = $activeNav ?? 'insights';
$publishedAt = format_display_time($story['published_at'] ?? null, 'dd MMM yyyy');
?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section pt-5">
  <div class="container" data-aos="fade-up">
    <p class="text-uppercase text-muted small mb-2">Leadership Insight</p>
    <h1 class="mb-3"><?= esc($story['title'] ?? '') ?></h1>
    <div class="d-flex flex-wrap gap-3 text-muted small mb-4">
      <?php if (! empty($story['author_name'])): ?>
        <span><i class="fa-regular fa-user me-1"></i><?= esc($story['author_name']) ?></span>
      <?php endif; ?>
      <?php if ($publishedAt): ?>
        <span><i class="fa-regular fa-calendar me-1"></i><?= esc($publishedAt) ?></span>
      <?php endif; ?>
    </div>
    <?php if (! empty($story['featured_image'])): ?>
      <div class="mb-4">
        <img src="<?= esc($story['featured_image']) ?>" class="img-fluid rounded" alt="<?= esc($story['title'] ?? '') ?>">
      </div>
    <?php endif; ?>
    <article class="prose">
      <?= $story['body'] ?? '<p>' . esc($story['summary'] ?? '') . '</p>' ?>
    </article>
  </div>
</section>

<?php if (! empty($moreStories)): ?>
  <section class="section pt-0 pb-5">
    <div class="container" data-aos="fade-up" data-aos-delay="50">
      <h2 class="h4 mb-3">More insights</h2>
      <div class="row gy-4">
        <?php foreach ($moreStories as $next): ?>
          <?php $storyUrl = ! empty($next['slug']) ? site_url('stories/' . rawurlencode((string) $next['slug'])) : '#'; ?>
          <div class="col-md-6 col-lg-3">
            <article class="card h-100 shadow-sm border-0">
              <div class="card-body">
                <h3 class="h6"><?= esc($next['title'] ?? '') ?></h3>
                <p class="text-muted small mb-3"><?= esc($next['excerpt'] ?? '') ?></p>
                <a class="stretched-link" href="<?= esc($storyUrl) ?>">Read</a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
<?= $this->endSection() ?>
