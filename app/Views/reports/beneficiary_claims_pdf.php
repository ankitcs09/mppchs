<?php
$title       = $title ?? 'My Claims Summary';
$generatedAt = $generatedAt ?? date('Y-m-d H:i');
$rows        = $rows ?? [];
$totals      = $totals ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?></title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    .meta { font-size: 10px; margin-bottom: 12px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 4px; text-align: left; }
    th { background: #f4f4f4; font-weight: bold; }
    td.text-end { text-align: right; }
    .totals { margin-top: 10px; width: 100%; }
    .totals td { border: none; padding: 2px 0; }
    .totals .label { font-weight: bold; }
  </style>
</head>
<body>
  <h1><?= esc($title) ?></h1>
  <div class="meta">Generated on <?= esc($generatedAt) ?></div>
  <table>
    <thead>
      <tr>
        <th>Claim #</th>
        <th>Status</th>
        <th>Type</th>
        <th>Hospital</th>
        <th>Claimed</th>
        <th>Approved</th>
        <th>Claim Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="7">No claims found.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= esc($row['claim_reference'] ?? '-') ?></td>
            <td><?= esc($row['status'] ?? '-') ?></td>
            <td><?= esc($row['type'] ?? '-') ?></td>
            <td><?= esc($row['hospital'] ?? '-') ?></td>
            <td class="text-end"><?= esc($row['claimed'] ?? '0.00') ?></td>
            <td class="text-end"><?= esc($row['approved'] ?? '0.00') ?></td>
            <td><?= esc($row['claim_date'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="totals">
    <tbody>
      <tr>
        <td class="label">Total claimed:</td>
        <td class="text-end">₹<?= esc($totals['claimed'] ?? '0.00') ?></td>
      </tr>
      <tr>
        <td class="label">Total approved:</td>
        <td class="text-end">₹<?= esc($totals['approved'] ?? '0.00') ?></td>
      </tr>
      <tr>
        <td class="label">Total cashless:</td>
        <td class="text-end">₹<?= esc($totals['cashless'] ?? '0.00') ?></td>
      </tr>
      <tr>
        <td class="label">Total co-pay:</td>
        <td class="text-end">₹<?= esc($totals['copay'] ?? '0.00') ?></td>
      </tr>
    </tbody>
  </table>
</body>
</html>

