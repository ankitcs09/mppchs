<?php $activeNav = 'contribution'; ?>
<?= $this->extend('site/layouts/public') ?>

<?= $this->section('content') ?>
<section class="section py-5">
  <div class="container section-title" data-aos="fade-up">
    <h2>Plans &amp; Contributions</h2>
    <p>Compare available plan options and understand the annual protection levels.</p>
  </div>
  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-primary">
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Particulars / विवरण</th>
            <th style="width: 20%;">Option 1</th>
            <th style="width: 20%;">Option 2</th>
            <th style="width: 20%;">Option 3</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><strong>Beneficiary Contribution (₹/month/family)</strong><br>लाभार्थी योगदान (रु./माह/परिवार)</td>
            <td>₹1,500</td>
            <td>₹1,800</td>
            <td>₹2,200</td>
          </tr>
          <tr>
            <td>2</td>
            <td><strong>Annual Sum Insured (₹ lakh)</strong><br>वार्षिक बीमित राशि (रु. लाख)</td>
            <td>₹5 lakh</td>
            <td>₹7.5 lakh</td>
            <td>₹10 lakh</td>
          </tr>
          <tr>
            <td>3</td>
            <td><strong>Room Rent Eligibility</strong><br>कक्ष किराया पात्रता</td>
            <td>₹3,000 per day</td>
            <td>₹5,000 per day</td>
            <td>₹6,500 per day</td>
          </tr>
          <tr>
            <td>4</td>
            <td><strong>Preventive Health Check-up</strong><br>वार्षिक स्वास्थ्य जांच</td>
            <td>Available (once a year)</td>
            <td>Available (once a year)</td>
            <td>Available (twice a year)</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="text-center mt-4">
      <a href="<?= site_url('contact') ?>" class="btn btn-primary px-4">Connect with HR Cell</a>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
