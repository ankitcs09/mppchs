<?php

namespace App\Models;

use CodeIgniter\Model;

class ContentEntryModel extends Model
{
    protected $table            = 'content_entries';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
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
        'is_featured',
        'display_order',
        'published_at',
        'created_by',
        'updated_by',
        'reviewed_by',
        'review_note',
        'meta',
    ];
    protected $validationRules  = [
        'type'   => 'required|string|max_length[32]',
        'title'  => 'required|string|max_length[190]',
        'slug'   => 'permit_empty|string|max_length[190]',
        'status' => 'permit_empty|string|max_length[32]',
    ];
    protected $validationMessages = [];

    /**
     * Returns the most recent published entries of a given type.
     *
     * @return array<int,array>
     */
    public function latestPublished(string $type, int $limit = 3): array
    {
        return $this->where('type', $type)
            ->where('status', 'published')
            ->orderBy('is_featured', 'DESC')
            ->orderBy('published_at', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll($limit);
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
            ->where('status', 'published')
            ->where('deleted_at', null)
            ->first();
    }

    public function setPublished(int $id, bool $published): bool
    {
        $data = [
            'status'       => $published ? 'published' : 'draft',
            'published_at' => $published ? utc_now() : null,
        ];

        return (bool) $this->update($id, $data);
    }
}
