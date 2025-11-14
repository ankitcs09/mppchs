<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Traits\AuthorizationTrait;
use App\Models\ContentEntryModel;
use App\Models\ContentEntryReviewModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ContentController extends BaseController
{
    use AuthorizationTrait;

    private ContentEntryModel $entries;
    private ContentEntryReviewModel $reviews;

    private array $typeOptions = [
        'blog'        => 'Stories & Updates',
        'testimonial' => 'Beneficiary Voices',
    ];

    private array $statusLabels = [
        'draft'     => 'Draft',
        'review'    => 'Pending review',
        'approved'  => 'Approved',
        'published' => 'Published',
        'archived'  => 'Archived',
    ];

    public function __construct()
    {
        $this->entries = new ContentEntryModel();
        $this->reviews = new ContentEntryReviewModel();
        helper('text');
    }

    public function index()
    {
        $this->ensureLoggedIn();
        $this->enforceAnyPermission(['submit_blog', 'edit_blog', 'approve_blog', 'publish_blog']);

        $filters = [
            'type'   => $this->request->getGet('type'),
            'status' => $this->request->getGet('status'),
            'q'      => trim((string) $this->request->getGet('q')),
        ];

        $builder = $this->entries->builder();
        $builder->select('*')->where('deleted_at', null);

        if ($filters['type'] && isset($this->typeOptions[$filters['type']])) {
            $builder->where('type', $filters['type']);
        }

        if ($filters['status'] && isset($this->statusLabels[$filters['status']])) {
            $builder->where('status', $filters['status']);
        }

        if ($filters['q'] !== '') {
            $builder->groupStart()
                ->like('title', $filters['q'])
                ->orLike('summary', $filters['q'])
                ->orLike('author_name', $filters['q'])
                ->groupEnd();
        }

        $context = $this->rbac()->context();
        $currentUserId = $context['user_id'] ?? 0;

        $this->applyRoleAwareVisibility($builder, $filters['status'], $currentUserId);

        $builder->orderBy('is_featured', 'DESC')
            ->orderBy('display_order', 'ASC')
            ->orderBy('updated_at', 'DESC');

        $entries = $builder->get()->getResultArray();
        $entries = array_map(fn ($entry) => $this->withNormalizedStatus($entry), $entries);

        $canEdit     = $this->can('edit_blog');
        $canPublish  = $this->can('publish_blog');
        $canApprove  = $this->can('approve_blog');
        $statusOptions = $this->statusOptionsForUser($canApprove, $canPublish, $canEdit);

        return view('admin/content/index', [
            'entries'        => $entries,
            'filters'        => $filters,
            'typeOptions'    => $this->typeOptions,
            'statusOptions'  => $statusOptions,
            'statusLabels'   => $this->statusLabels,
            'canEdit'        => $canEdit,
            'canPublish'     => $canPublish,
            'canApprove'     => $canApprove,
            'currentUserId'  => $currentUserId,
        ]);
    }

    public function create()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('submit_blog');

        return view('admin/content/form', $this->formData());
    }

    public function store()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('submit_blog');

        return $this->persist();
    }

    public function edit(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforceAnyPermission(['edit_blog', 'approve_blog', 'publish_blog']);

        $entry = $this->entries->find($id);
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('admin/content/form', $this->formData($entry));
    }

    public function update(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforceAnyPermission(['edit_blog', 'approve_blog', 'publish_blog']);

        return $this->persist($id);
    }

    public function archive(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('edit_blog');

        $entry = $this->entries->find($id);
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->entries->update($id, [
            'status'      => 'archived',
            'updated_by'  => $this->currentUserId(),
            'published_at'=> $entry['published_at'],
        ]);

        return redirect()->back()->with('success', 'Entry archived.');
    }

    public function publish(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('publish_blog');

        $entry = $this->entries->find($id);
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! in_array($entry['status'], ['approved', 'published'], true)) {
            return redirect()->back()->with('error', 'Entry must be approved before publishing.');
        }

        $payload = [
            'status'       => 'published',
            'updated_by'   => $this->currentUserId(),
            'reviewed_by'  => $entry['reviewed_by'] ?? $this->currentUserId(),
            'published_at' => $entry['published_at'] ?: utc_now(),
        ];

        $this->entries->update($id, $payload);

        return redirect()->back()->with('success', 'Entry published.');
    }

    public function review(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('approve_blog');

        $entry = $this->entries->find($id);
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }

        $action = $this->request->getPost('action');
        $note   = trim((string) $this->request->getPost('note'));

        if (! in_array($action, ['approve', 'changes'], true)) {
            return redirect()->back()->with('error', 'Invalid review action selected.');
        }

        if ($action === 'changes' && $note === '') {
            return redirect()->back()->with('error', 'Please provide feedback when requesting changes.');
        }

        $reviewerId = $this->currentUserId();
        $this->reviews->insert([
            'entry_id'    => $id,
            'reviewer_id' => $reviewerId,
            'action'      => $action,
            'note'        => $note === '' ? null : $note,
            'created_at'  => utc_now(),
        ]);

        $status = $action === 'approve' ? 'approved' : 'draft';
        $payload = [
            'status'      => $status,
            'reviewed_by' => $reviewerId,
            'review_note' => $note === '' ? null : $note,
            'updated_by'  => $reviewerId,
        ];

        $this->entries->update($id, $payload);

        $message = $action === 'approve'
            ? 'Entry approved and ready for publishing.'
            : 'Entry sent back to the author with your feedback.';

        return redirect()->back()->with('success', $message);
    }

    public function withdraw(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('edit_blog');

        $entry = $this->entries->find($id);
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }

        $currentUser = $this->currentUserId();
        if ((int) ($entry['created_by'] ?? 0) !== (int) $currentUser) {
            return redirect()->back()->with('error', 'Only the original author can withdraw a submission.');
        }

        if (strtolower((string) $entry['status']) !== 'review') {
            return redirect()->back()->with('warning', 'Entry is not awaiting review.');
        }

        $this->entries->update($id, [
            'status'      => 'draft',
            'reviewed_by' => null,
            'review_note' => null,
            'updated_by'  => $currentUser,
        ]);

        $this->reviews->insert([
            'entry_id'    => $id,
            'reviewer_id' => $currentUser,
            'action'      => 'withdraw',
            'note'        => null,
            'created_at'  => utc_now(),
        ]);

        return redirect()->back()->with('success', 'Submission withdrawn. You can continue editing.');
    }

    private function persist(?int $id = null)
    {
        $workflowAction = $this->request->getPost('workflow_action');

        $input = $this->request->getPost([
            'type',
            'title',
            'slug',
            'summary',
            'body',
            'quote',
            'author_name',
            'author_title',
            'featured_image',
            'tags',
            'status',
            'display_order',
        ]);
        $input['is_featured'] = (bool) $this->request->getPost('is_featured');

        if ($workflowAction === 'save_draft') {
            $input['status'] = 'draft';
        } elseif ($workflowAction === 'submit_review') {
            $input['status'] = 'review';
        }

        $rules = [
            'title'          => 'required|max_length[190]',
            'type'           => 'required|in_list[' . implode(',', array_keys($this->typeOptions)) . ']',
            'slug'           => 'permit_empty|max_length[190]',
            'summary'        => 'permit_empty|string',
            'body'           => 'permit_empty|string',
            'quote'          => 'permit_empty|string',
            'author_name'    => 'permit_empty|max_length[190]',
            'author_title'   => 'permit_empty|max_length[190]',
            'featured_image' => 'permit_empty|max_length[255]',
            'tags'           => 'permit_empty|max_length[500]',
            'status'         => 'required|in_list[' . implode(',', array_keys($this->statusLabels)) . ']',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please review the highlighted fields.');
        }

        $existing = null;
        if ($id !== null) {
            $existing = $this->entries->find($id);
            if (! $existing) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        $canApprove = $this->can('approve_blog');
        $canPublish = $this->can('publish_blog');
        $canEdit    = $this->can('edit_blog');

        $type   = $input['type'];
        $title  = trim((string) $input['title']);
        $slug   = trim((string) $input['slug']);
        $status = $this->sanitizeStatus($input['status']);

        if ($type === 'blog') {
            if ($slug === '') {
                $slug = $this->uniqueSlug($title, $id);
            } else {
                $slug = $this->uniqueSlug($slug, $id);
            }

            if (trim((string) $input['body']) === '') {
                return redirect()->back()->withInput()->with('error', 'Stories require a body content block.');
            }
        } else {
            $slug = $slug !== '' ? $this->uniqueSlug($slug, $id) : null;
            if (trim((string) $input['quote']) === '') {
                return redirect()->back()->withInput()->with('error', 'Testimonials require a highlighted quote.');
            }
        }

        if ($status === 'published' && ! $canPublish) {
            $status = $canApprove ? 'approved' : 'review';
        }

        if ($status === 'approved' && ! $canApprove) {
            $status = 'review';
        }

        if ($status === 'archived' && ! ($canEdit || $canPublish)) {
            $status = 'draft';
        }

        $payload = [
            'type'           => $type,
            'title'          => $title,
            'slug'           => $slug,
            'summary'        => trim((string) $input['summary']) ?: null,
            'body'           => $input['body'] ?? null,
            'quote'          => $input['quote'] ?? null,
            'author_name'    => trim((string) $input['author_name']) ?: null,
            'author_title'   => trim((string) $input['author_title']) ?: null,
            'featured_image' => trim((string) $input['featured_image']) ?: null,
            'tags'           => $this->normalizeTags($input['tags'] ?? ''),
            'status'         => $status,
            'is_featured'    => $input['is_featured'] ? 1 : 0,
            'display_order'  => $this->normalizeOrder($input['display_order'] ?? null),
            'review_note'    => $existing['review_note'] ?? null,
        ];

        $payload['published_at'] = $this->resolvePublishedAt($status, $existing);
        if (in_array($status, ['draft', 'review'], true)) {
            $payload['reviewed_by'] = null;
            $payload['review_note'] = null;
        } elseif ($status === 'approved' || $status === 'published') {
            $payload['reviewed_by'] = $existing['reviewed_by'] ?? $this->currentUserId();
        }

        if ($existing === null) {
            $payload['created_by'] = $this->currentUserId();

            $this->entries->insert($payload);
            $entryId = $this->entries->getInsertID();

            return redirect()->to(site_url('admin/content/' . $entryId . '/edit'))
                ->with('success', 'Entry created successfully.');
        }

        $payload['updated_by'] = $this->currentUserId();
        $this->entries->update($existing['id'], $payload);

        return redirect()->to(site_url('admin/content/' . $existing['id'] . '/edit'))
            ->with('success', 'Entry updated successfully.');
    }

    private function formData(?array $entry = null): array
    {
        $canEdit    = $this->can('edit_blog') || $this->can('submit_blog');
        $canPublish = $this->can('publish_blog');
        $canApprove = $this->can('approve_blog');
        $statuses   = $this->statusOptionsForUser($canApprove, $canPublish, $canEdit);
        $currentUserId = $this->currentUserId();

        $reviews = [];
        if ($entry) {
            $entry = $this->withNormalizedStatus($entry);
            $reviews = $this->reviewTimeline((int) $entry['id']);
        }

        $isAuthor = $entry && $currentUserId && (int) ($entry['created_by'] ?? 0) === $currentUserId;
        $isLockedForAuthor = $entry
            && strtolower((string) $entry['status']) === 'review'
            && $isAuthor
            && ! $canApprove
            && ! $canPublish;

        return [
            'entry'             => $entry,
            'typeOptions'       => $this->typeOptions,
            'statusOptions'     => $statuses,
            'statusLabels'      => $this->statusLabels,
            'canPublish'        => $canPublish,
            'canEdit'           => $canEdit,
            'canApprove'        => $canApprove,
            'reviews'           => $reviews,
            'currentUserId'     => $currentUserId,
            'isAuthor'          => $isAuthor,
            'isLockedForAuthor' => $isLockedForAuthor,
        ];
    }

    private function normalizeTags(string $tags): ?string
    {
        $parts = array_filter(array_map(static fn ($tag) => trim($tag), explode(',', $tags)));
        return empty($parts) ? null : implode(', ', $parts);
    }

    private function normalizeOrder($order): ?int
    {
        if ($order === null || $order === '') {
            return null;
        }

        return (int) $order;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = strtolower(url_title($value, '-', true));
        if ($base === '') {
            $base = uniqid('entry-', false);
        }

        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . (++$suffix);
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $builder = $this->entries->builder();
        $builder->select('id')
            ->where('slug', $slug)
            ->where('deleted_at', null);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->get()->getFirstRow() !== null;
    }

    private function statusOptionsForUser(bool $canApprove, bool $canPublish, bool $canEdit): array
    {
        $options = [
            'draft'  => $this->statusLabels['draft'],
            'review' => $this->statusLabels['review'],
        ];

        if ($canApprove) {
            $options['approved'] = $this->statusLabels['approved'];
        }

        if ($canPublish) {
            $options['published'] = $this->statusLabels['published'];
        }

        if ($canEdit || $canPublish) {
            $options['archived'] = $this->statusLabels['archived'];
        }

        return $options;
    }

    private function sanitizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === '') {
            return 'draft';
        }

        if (isset($this->statusLabels[$status])) {
            return $status;
        }

        foreach ($this->statusLabels as $slug => $label) {
            if (strtolower($label) === $status) {
                return $slug;
            }
        }

        return match ($status) {
            'pending review' => 'review',
            'approved'       => 'approved',
            'published'      => 'published',
            'archived'       => 'archived',
            default          => 'draft',
        };
    }

    private function resolvePublishedAt(string $status, ?array $existing): ?string
    {
        if ($status === 'published') {
            return $existing['published_at'] ?? utc_now();
        }

        return $existing['published_at'] ?? null;
    }

    private function reviewTimeline(int $entryId): array
    {
        return $this->reviews
            ->select('content_entry_reviews.*, au.display_name, au.username')
            ->join('app_users AS au', 'au.id = content_entry_reviews.reviewer_id', 'left')
            ->where('entry_id', $entryId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    private function can(string $permission): bool
    {
        $rbac = $this->rbac();
        return $rbac !== null && $rbac->hasPermission($permission);
    }

    private function applyRoleAwareVisibility($builder, ?string $statusFilter, ?int $currentUser): void
    {
        $rbac = $this->rbac();
        if ($rbac === null) {
            return;
        }

        $canReview  = $rbac->hasPermission('approve_blog');
        $canPublish = $rbac->hasPermission('publish_blog');

        if (! $canReview && ! $canPublish && $currentUser > 0) {
            $builder->where('created_by', $currentUser);
        }
    }

    private function withNormalizedStatus(array $entry): array
    {
        $slug = $this->sanitizeStatus($entry['status'] ?? 'draft');
        $entry['status_slug']  = $slug;
        $entry['status_label'] = $this->statusLabels[$slug] ?? ucfirst($slug);

        return $entry;
    }
}
