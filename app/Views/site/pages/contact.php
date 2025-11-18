<?php $activeNav = 'contact'; ?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>Contact Us</h2>
    <p>Share your issue with the MPPCHS helpdesk team and we'll get back shortly.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="50">
    <?php if ($errors = session('errors')): ?>
      <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
          <?php foreach ((array) $errors as $message): ?>
            <li><?= esc($message) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php elseif ($success = session()->getFlashdata('success')): ?>
      <div class="alert alert-success" role="alert">
        <?= esc($success) ?>
      </div>
    <?php endif; ?>
    <div class="row gy-4">
      <div class="col-lg-5">
        <div class="stories-card p-4 h-100">
          <h3 class="h5">Need assistance?</h3>
          <p class="text-muted">Use the form to submit your request directly to the helpdesk team. Please include accurate contact details so we can respond quickly.</p>
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><i class="fa-solid fa-phone-volume me-2 text-primary"></i>0761-2702225</li>
            <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-primary"></i>cashless@mppgcl.in</li>
          </ul>
        </div>
      </div>
      <div class="col-lg-7">
        <form action="<?= site_url('contact/request') ?>" method="post" class="stories-card p-4">
          <?= csrf_field() ?>
          <div class="row gy-3">
            <div class="col-md-6">
              <label for="contact-name" class="form-label">Full Name</label>
              <input type="text" name="name" id="contact-name" class="form-control" value="<?= old('name') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="contact-email" class="form-label">Email</label>
              <input type="email" name="email" id="contact-email" class="form-control" value="<?= old('email') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="contact-phone" class="form-label">Phone (optional)</label>
              <input type="text" name="phone" id="contact-phone" class="form-control" value="<?= old('phone') ?>">
            </div>
            <div class="col-md-6">
              <label for="contact-subject" class="form-label">Subject</label>
              <input type="text" name="subject" id="contact-subject" class="form-control" value="<?= old('subject') ?>" required>
            </div>
            <div class="col-12">
              <label for="contact-message" class="form-label">Request Details</label>
              <textarea name="message" id="contact-message" rows="5" class="form-control" required><?= old('message') ?></textarea>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary px-4">Submit Request</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
