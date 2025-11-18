<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: #f8fafc;
      color: #0f172a;
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
      align-items: center;
      justify-content: center;
    }
    .wrapper {
      text-align: center;
      padding: 3rem 2rem;
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
      max-width: 420px;
      width: 100%;
    }
    .code {
      font-size: 4rem;
      font-weight: 700;
      margin: 0 0 0.5rem;
      color: #dc2626;
    }
    .message {
      font-size: 1.125rem;
      margin-bottom: 1.5rem;
    }
    .details {
      font-size: 0.95rem;
      color: #475569;
      margin-bottom: 2rem;
    }
    .actions a {
      display: inline-block;
      margin: 0 0.25rem;
      padding: 0.65rem 1.2rem;
      border-radius: 0.75rem;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s ease-in-out;
    }
    .actions a.primary {
      background: #2563eb;
      color: #fff;
    }
    .actions a.primary:hover {
      background: #1d4ed8;
    }
    .actions a.secondary {
      background: #e2e8f0;
      color: #1e293b;
    }
    .actions a.secondary:hover {
      background: #cbd5f5;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="code">403</div>
    <div class="message">You do not have permission to access this resource.</div>
    <?php if (isset($message) && $message !== ''): ?>
      <div class="details"><?= esc($message) ?></div>
    <?php else: ?>
      <div class="details">
        This action is restricted based on your role or company scope. If you believe this is an error,
        please contact your system administrator.
      </div>
    <?php endif; ?>
    <div class="actions">
      <a class="primary" href="<?= site_url('/') ?>">Go to Dashboard</a>
      <a class="secondary" href="javascript:history.back()">Go Back</a>
    </div>
  </div>
</body>
</html>
