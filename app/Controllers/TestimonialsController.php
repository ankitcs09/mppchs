<?php

namespace App\Controllers;

use App\Models\ContentEntryModel;

class TestimonialsController extends BaseController
{
    private ContentEntryModel $entries;

    public function __construct()
    {
        $this->entries = new ContentEntryModel();
    }

    public function index(): string
    {
        $voices = $this->entries
            ->where('type', 'testimonial')
            ->where('status', 'published')
            ->orderBy('display_order', 'ASC')
            ->orderBy('published_at', 'DESC')
            ->findAll();

        return view('site/testimonials/index', [
            'voices'    => $voices,
            'activeNav' => 'voices',
        ]);
    }
}
