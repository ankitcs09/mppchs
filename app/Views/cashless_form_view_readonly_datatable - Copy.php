<?php
/*
 * Read‑only dashboard view for the cashless health scheme.
 *
 * This view displays the beneficiary’s details in a searchable
 * DataTable and lists all dependents in a separate table.  A
 * “Request Edit” button allows the logged‑in user to navigate to
 * the edit form.  Static instructions and a declaration are shown
 * above the tables.  If no form data is found for the user, a
 * message is displayed instead of the tables.
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashless Scheme Form</title>
  <!-- DataTables CSS & jQuery from CDN -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <style>
    body { font-family: system-ui, Arial, sans-serif; margin: 20px; }
    .section { margin-top: 24px; }
    .note { color: #666; }
    table.dataTable thead th { background: #f6f6f8; }
    .actions { margin: 16px 0; }
  </style>
</head>
<body>

<h2>Cashless Health Scheme – Your Form</h2>

<?php if (! empty($message)): ?>
  <div class="note"><?= esc($message) ?></div>
<?php endif; ?>

<!-- Instructions & Declaration (static content) -->
<section class="section">
  <h3>Instructions</h3>
  <ul>
    <li>Please verify all personal, pension and dependent details carefully.</li>
    <li>The “Scheme Option” indicates your selected contribution tier.</li>
    <li>If you notice any mistakes, click the <strong>Request Edit</strong> button below.</li>
  </ul>
  <h3>Declaration</h3>
  <p class="note">I hereby declare that the details submitted are true and correct to the best of my knowledge.</p>
</section>

<?php if (! empty($beneficiary)): ?>
  <div class="actions">
    <a href="<?= site_url('dashboard/cashless-form/request-edit') ?>" class="btn btn-warning">Request Edit</a>
  </div>

  <!-- Beneficiary Details Table -->
  <section class="section">
    <h3>Beneficiary Details</h3>
    <table id="kvtable" class="display" style="width:100%">
      <thead><tr><th>Field</th><th>Value</th></tr></thead>
      <tbody>
        <?php
          // Map of display labels to beneficiary keys
          $map = [
            'Unique Reference' => 'unique_ref_number',
            'Category'         => 'category',
            'Scheme Option'    => 'scheme_option',
            'First Name'       => 'first_name',
            'Middle Name'      => 'middle_name',
            'Last Name'        => 'last_name',
            'Gender'           => 'gender',
            'Date of Birth'    => 'date_of_birth',
            'Blood Group'      => 'blood_group',
            'Samagra ID'       => 'samagra_id',
            'Retirement Date'  => 'retirement_date',
            'RAO'              => 'rao',
            'Office @ Retirement' => 'office_at_retirement',
            'Designation'      => 'designation',
            'Address Line 1'   => 'address_line1',
            'Address Line 2'   => 'address_line2',
            'City'             => 'city',
            'State'            => 'state',
            'Postal Code'      => 'postal_code',
            'Mobile'           => 'mobile_number',
            'Alternate Mobile' => 'alternate_mobile',
            'Email'            => 'email',
            'PPO Number'       => 'ppo_number',
            'GPF Number'       => 'gpf_number',
            'PRAN Number'      => 'pran_number',
            'Bank Name'        => 'bank_name',
            'Bank Account'     => 'bank_account_number',
            'Aadhaar'          => 'aadhar_number',
            'PAN'              => 'pan_number',
            'Spouse Status'    => 'spouse_status',
            'Spouse Dependent' => 'spouse_dependent',
            'Spouse Name'      => 'spouse_name',
            'Spouse Gender'    => 'spouse_gender',
            'Spouse Blood Group' => 'spouse_blood_group',
            'Spouse DOB'       => 'spouse_dob',
            'Spouse Aadhaar'   => 'spouse_aadhar',
            'Father Name'      => 'father_name',
            'Father Dependent' => 'father_dependent_for_health',
            'Mother Name'      => 'mother_name',
            'Mother Dependent' => 'mother_dependent_for_health',
          ];
          foreach ($map as $label => $key):
            $val = $beneficiary[$key] ?? '';
            // Convert boolean values to Yes/No for clarity
            if (in_array($key, ['spouse_dependent','father_dependent_for_health','mother_dependent_for_health'])) {
                if ($val === null || $val === '') {
                    $val = '';
                } elseif ((int) $val === 1) {
                    $val = 'Yes';
                } else {
                    $val = 'No';
                }
            }
        ?>
        <tr><td><?= esc($label) ?></td><td><?= esc((string) $val) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Dependents DataTable -->
  <section class="section">
    <h3>Dependents</h3>
    <table id="deptable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>#</th><th>Relation</th><th>Status</th><th>Dependent for Health</th>
          <th>Name</th><th>Gender</th><th>Blood Group</th><th>DOB</th><th>Aadhaar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dependents as $d): ?>
          <tr>
            <td><?= esc($d['dependent_order'] ?? '') ?></td>
            <td><?= esc($d['relation'] ?? '') ?></td>
            <td><?= esc($d['status'] ?? '') ?></td>
            <td><?= (! empty($d['is_dependent_for_health']) ? 'Yes' : 'No') ?></td>
            <td><?= esc($d['name'] ?? '') ?></td>
            <td><?= esc($d['gender'] ?? '') ?></td>
            <td><?= esc($d['blood_group'] ?? '') ?></td>
            <td><?= esc($d['date_of_birth'] ?? '') ?></td>
            <td><?= esc($d['aadhar_number'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

<?php endif; ?>

<script>
$(function() {
  $('#kvtable').DataTable({
    paging: false,
    info: false,
    searching: true
    ordering: false // keep original order of attributes
  });
  $('#deptable').DataTable({
    paging: true,
    pageLength: 5,
    searching: true
  });
});
</script>

</body>
</html>