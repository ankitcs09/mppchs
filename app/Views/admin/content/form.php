<?php

$entry         = $entry ?? null;
$typeOptions   = $typeOptions ?? [];
$statusOptions = $statusOptions ?? [];
$statusLabels  = $statusLabels ?? [];
$canPublish    = $canPublish ?? false;
$canEdit       = $canEdit ?? false;
$canApprove    = $canApprove ?? false;
$reviews       = $reviews ?? [];
$validation    = \Config\Services::validation();

$isEdit = $entry !== null;
$pageTitle = $isEdit ? 'Edit entry' : 'Create entry';
$actionUrl = $isEdit
    ? site_url('admin/content/' . $entry['id'] . '/update')
    : site_url('admin/content');

$old = static function (string $field, $default = null) use ($entry) {
    return old($field, $entry[$field] ?? $default);
};
?>
<?= $this->extend('layouts/default') ?>

<?= $this->section('header') ?>
<div class="page-heading">
  <div>
    <h1 class="page-heading__title"><?= esc($pageTitle) ?></h1>
    <p class="page-heading__subtitle mb-0">
      <?= $isEdit ? 'Update the selected story or testimonial.' : 'Share a new story or beneficiary voice for the public site.' ?>
    </p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('admin/content') ?>">Back to content</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $entryStatus   = $isEdit ? strtolower((string) ($entry['status'] ?? 'draft')) : 'draft';
    $showPublish   = $canPublish && in_array($entryStatus, ['approved', 'published'], true);
    $showApprove   = $canApprove && $entryStatus === 'review';
    $isAuthor      = $isAuthor ?? false;
    $isLocked      = $isLockedForAuthor ?? false;
    $canWithdraw   = $isAuthor && $entryStatus === 'review';
    $isAuthorOnly  = $isAuthor && ! $canPublish && ! $canApprove;
?>

<section class="app-panel">
  <?php if ($validation->getErrors()): ?>
    <div class="alert alert-danger">
      <strong>Please check the highlighted fields below.</strong>
    </div>
  <?php endif; ?>

  <?php if ($isLocked): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <strong>Submitted for review.</strong>
        Withdraw the submission to make further edits.
      </div>
      <?php if ($canWithdraw): ?>
        <form method="post" action="<?= site_url('admin/content/' . $entry['id'] . '/withdraw') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-secondary" type="submit">
            <i class="fa-solid fa-rotate-left me-1"></i>Withdraw
          </button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= esc($actionUrl) ?>">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="_method" value="POST">
    <?php endif; ?>

    <?php if ($isLocked): ?>
      <fieldset disabled>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
        <input
          type="text"
          class="form-control <?= $validation->hasError('title') ? 'is-invalid' : '' ?>"
          id="title"
          name="title"
          value="<?= esc($old('title', '')) ?>"
          required
        >
        <div class="invalid-feedback"><?= esc($validation->getError('title')) ?></div>
      </div>
      <div class="col-lg-3">
        <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
        <select
          class="form-select <?= $validation->hasError('type') ? 'is-invalid' : '' ?>"
          id="type"
          name="type"
          required
        >
          <?php foreach ($typeOptions as $value => $label): ?>
            <option value="<?= esc($value) ?>" <?= $old('type') === $value ? 'selected' : '' ?>>
              <?= esc($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= esc($validation->getError('type')) ?></div>
      </div>
      <div class="col-lg-3">
        <div class="d-flex flex-column gap-2">
          <div>
            <label class="form-label" for="status">Status</label>
            <select
              class="form-select <?= $validation->hasError('status') ? 'is-invalid' : '' ?>"
              id="status"
              name="status"
            >
              <?php foreach ($statusOptions as $value => $label): ?>
                <option value="<?= esc($value) ?>" <?= $old('status', $entry['status'] ?? 'draft') === $value ? 'selected' : '' ?>>
                  <?= esc($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= esc($validation->getError('status')) ?></div>
            <?php if ($isAuthorOnly): ?>
              <div class="form-text">Use the quick actions to save a draft or submit for review.</div>
            <?php elseif (! $canPublish): ?>
              <div class="form-text">Publish requires additional approval.</div>
            <?php endif; ?>
          </div>
          <?php if ($isAuthorOnly && ! $isLocked): ?>
            <div class="d-flex flex-column gap-2">
              <button class="btn btn-outline-secondary" type="submit" name="workflow_action" value="save_draft">
                <i class="fa-regular fa-floppy-disk me-1"></i>Save draft
              </button>
              <button class="btn btn-outline-primary" type="submit" name="workflow_action" value="submit_review">
                <i class="fa-solid fa-paper-plane me-1"></i>Submit for review
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <label class="form-label" for="slug">Slug</label>
        <input
          type="text"
          class="form-control <?= $validation->hasError('slug') ? 'is-invalid' : '' ?>"
          id="slug"
          name="slug"
          value="<?= esc($old('slug', '')) ?>"
          placeholder="auto-generated-when-empty"
        >
        <div class="invalid-feedback"><?= esc($validation->getError('slug')) ?></div>
        <div class="form-text">Used for story URLs. Leave blank to auto-generate.</div>
      </div>
      <div class="col-lg-3">
        <label class="form-label" for="display_order">Display order</label>
        <input
          type="number"
          class="form-control"
          id="display_order"
          name="display_order"
          value="<?= esc($old('display_order')) ?>"
          min="0"
        >
        <div class="form-text">Lower numbers appear first in testimonials.</div>
      </div>
      <div class="col-lg-3 d-flex align-items-center">
        <div class="form-check mt-4 pt-2">
          <input
            class="form-check-input"
            type="checkbox"
            id="is_featured"
            name="is_featured"
            value="1"
            <?= $old('is_featured', $entry['is_featured'] ?? 0) ? 'checked' : '' ?>
          >
          <label class="form-check-label" for="is_featured">Feature on homepage</label>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label" for="summary">Summary</label>
      <textarea
        class="form-control <?= $validation->hasError('summary') ? 'is-invalid' : '' ?>"
        id="summary"
        name="summary"
        rows="3"
      ><?= esc($old('summary', '')) ?></textarea>
      <div class="invalid-feedback"><?= esc($validation->getError('summary')) ?></div>
      <div class="form-text">Shown on cards and previews (optional).</div>
    </div>

    <div class="mb-4">
      <label class="form-label" for="body">Body (stories)</label>
      <textarea
        class="form-control <?= $validation->hasError('body') ? 'is-invalid' : '' ?>"
        id="body"
        name="body"
        rows="8"
      ><?= esc($old('body', '')) ?></textarea>
      <div class="invalid-feedback"><?= esc($validation->getError('body')) ?></div>
      <div class="form-text">Use HTML or plain text for full stories. Testimonials may leave this blank.</div>
    </div>

    <div class="mb-4">
      <label class="form-label" for="quote">Quote (testimonials)</label>
      <textarea
        class="form-control <?= $validation->hasError('quote') ? 'is-invalid' : '' ?>"
        id="quote"
        name="quote"
        rows="4"
      ><?= esc($old('quote', '')) ?></textarea>
      <div class="invalid-feedback"><?= esc($validation->getError('quote')) ?></div>
      <div class="form-text">Displayed prominently on testimonial cards.</div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label" for="author_name">Author / beneficiary name</label>
        <input
          type="text"
          class="form-control <?= $validation->hasError('author_name') ? 'is-invalid' : '' ?>"
          id="author_name"
          name="author_name"
          value="<?= esc($old('author_name', '')) ?>"
        >
        <div class="invalid-feedback"><?= esc($validation->getError('author_name')) ?></div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="author_title">Designation / relation</label>
        <input
          type="text"
          class="form-control <?= $validation->hasError('author_title') ? 'is-invalid' : '' ?>"
          id="author_title"
          name="author_title"
          value="<?= esc($old('author_title', '')) ?>"
        >
        <div class="invalid-feedback"><?= esc($validation->getError('author_title')) ?></div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="featured_image">Featured image URL</label>
        <input
          type="text"
          class="form-control <?= $validation->hasError('featured_image') ? 'is-invalid' : '' ?>"
          id="featured_image"
          name="featured_image"
          value="<?= esc($old('featured_image', '')) ?>"
          placeholder="https://example.com/image.jpg"
        >
        <div class="invalid-feedback"><?= esc($validation->getError('featured_image')) ?></div>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label" for="tags">Tags</label>
      <input
        type="text"
        class="form-control <?= $validation->hasError('tags') ? 'is-invalid' : '' ?>"
        id="tags"
        name="tags"
        value="<?= esc($old('tags', '')) ?>"
        placeholder="healthcare, benefits"
      >
      <div class="invalid-feedback"><?= esc($validation->getError('tags')) ?></div>
      <div class="form-text">Comma-separated keywords (optional).</div>
    </div>

    <?php if ($isLocked): ?>
      </fieldset>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2">
      <?php if (! $isLocked): ?>
        <button class="btn btn-primary" type="submit">
          <i class="fa-solid fa-floppy-disk me-1"></i><?= $isEdit ? 'Save changes' : 'Create entry' ?>
        </button>
      <?php endif; ?>
      <a class="btn btn-outline-secondary" href="<?= site_url('admin/content') ?>">Cancel</a>
      <?php if ($isEdit && $showPublish): ?>
        <form method="post" action="<?= site_url('admin/content/' . $entry['id'] . '/publish') ?>" class="d-inline">
          <?= csrf_field() ?>
          <button class="btn btn-success" type="submit">
            <i class="fa-solid fa-bullhorn me-1"></i>Publish now
          </button>
        </form>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($isEdit && ! empty($reviews)): ?>
    <hr class="my-4">
    <h3 class="h6 mb-3">Review history</h3>
    <div class="timeline">
      <?php foreach ($reviews as $review): ?>
        <?php
          $reviewedAt = $review['created_at'] ? date('d M Y, H:i', strtotime($review['created_at'])) : '';
          $reviewer   = $review['display_name']
            ?? $review['username']
            ?? ('Reviewer #' . $review['reviewer_id']);
          switch ($review['action']) {
              case 'approve':
                  $actionLabel = 'Approved';
                  $badgeClass  = 'bg-success-subtle text-success';
                  break;
              case 'withdraw':
                  $actionLabel = 'Withdrawn';
                  $badgeClass  = 'bg-secondary-subtle text-secondary';
                  break;
              default:
                  $actionLabel = 'Requested changes';
                  $badgeClass  = 'bg-warning-subtle text-warning';
          }
        ?>
        <div class="mb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="badge <?= esc($badgeClass) ?>"><?= esc($actionLabel) ?></span>
            <span class="small text-muted"><?= esc($reviewedAt) ?></span>
          </div>
          <div class="small text-muted">By <?= esc($reviewer) ?></div>
          <?php if (! empty($review['note'])): ?>
            <p class="mb-0 mt-1"><?= esc($review['note']) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($isEdit && $showApprove): ?>
    <hr class="my-4">
    <h3 class="h6 mb-3">Review decision</h3>
    <form method="post" action="<?= site_url('admin/content/' . $entry['id'] . '/review') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Action</label>
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="action" id="reviewApprove" value="approve" checked>
            <label class="form-check-label" for="reviewApprove">Approve</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="action" id="reviewChanges" value="changes">
            <label class="form-check-label" for="reviewChanges">Needs changes</label>
          </div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="reviewNote">Reviewer note</label>
        <textarea class="form-control" id="reviewNote" name="note" rows="3" placeholder="Share feedback for the author"></textarea>
        <div class="form-text">Notes are optional when approving, but required when requesting changes.</div>
      </div>
      <button class="btn btn-outline-primary" type="submit">
        <i class="fa-solid fa-clipboard-check me-1"></i>Submit review
      </button>
    </form>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>
