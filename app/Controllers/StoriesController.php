<?php

namespace App\Controllers;

use App\Models\ContentEntryModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class StoriesController extends BaseController
{
    private ContentEntryModel $entries;

    public function __construct()
    {
        $this->entries = new ContentEntryModel();
    }

    public function index(): string
    {
        $stories = $this->entries
            ->where('type', 'blog')
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->paginate(6, 'stories');

        $stories = array_map(fn ($entry) => $this->withExcerpt($entry), $stories ?? []);

        return view('site/stories/index', [
            'stories'   => $stories,
            'pager'     => $this->entries->pager,
            'activeNav' => 'insights',
        ]);
    }

    public function show(string $slug): string
    {
        $story = $this->entries->findPublishedBySlug($slug);
        if (! $story || $story['type'] !== 'blog') {
            throw PageNotFoundException::forPageNotFound();
        }

        $moreStories = $this->entries
            ->where('type', 'blog')
            ->where('status', 'published')
            ->where('id !=', $story['id'])
            ->orderBy('published_at', 'DESC')
            ->findAll(4);

        $moreStories = array_map(fn ($entry) => $this->withExcerpt($entry, 140), $moreStories ?? []);

        return view('site/stories/show', [
            'story'       => $story,
            'moreStories' => $moreStories,
            'activeNav'   => 'insights',
        ]);
    }

    private function withExcerpt(array $entry, int $limit = 160): array
    {
        $source = $entry['summary'] ?? strip_tags((string) ($entry['body'] ?? ''));
        $source = trim((string) $source);

        if ($source !== '' && mb_strlen($source) > $limit) {
            $source = rtrim(mb_substr($source, 0, $limit - 3)) . '...';
        }

        $entry['excerpt'] = $source;

        return $entry;
    }
}
