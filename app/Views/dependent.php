<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dependents - MPPGCL Cashless</title>
  <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5 text-center">
    <h2>👨‍👩‍👧 Dependents</h2>
    <p>Below are the registered dependents linked with your account.</p>

    <table class="table table-bordered mt-4 bg-white shadow-sm">
      <thead class="table-primary">
        <tr>
          <th>Sr. No.</th>
          <th>Name</th>
          <th>Relation</th>
          <th>Age</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>1</td><td>Mrs. Sunita Solanki</td><td>Spouse</td><td>45</td></tr>
        <tr><td>2</td><td>Rohit Solanki</td><td>Son</td><td>18</td></tr>
        <tr><td>3</td><td>Sneha Solanki</td><td>Daughter</td><td>15</td></tr>
      </tbody>
    </table>

    <div class="mt-4">
      <a href="<?= site_url('dashboard') ?>" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
