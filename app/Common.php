<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see https://codeigniter.com/user_guide/extending/common.html
 */

use CodeIgniter\I18n\Time;
use Config\App;

if (! function_exists('utc_now')) {
    /**
     * Returns the current UTC timestamp formatted for DB storage.
     */
    function utc_now(): string
    {
        return Time::now('UTC')->toDateTimeString();
    }
}

if (! function_exists('format_display_time')) {
    /**
     * Formats a UTC timestamp into the configured display timezone.
     */
    function format_display_time(?string $timestamp, string $pattern = 'dd MMM yyyy, hh:mm a'): ?string
    {
        if (! $timestamp) {
            return null;
        }

        try {
            $time = Time::parse($timestamp, 'UTC');
        } catch (\Throwable $exception) {
            return $timestamp;
        }

        $displayTz = config(App::class)->displayTimezone ?? 'UTC';

        return $time->setTimezone($displayTz)->toLocalizedString($pattern);
    }
}

if (! function_exists('site_nav_links')) {
    /**
     * Default navigation links for public/auth layouts.
     *
     * @return array<int,array<string,string>>
     */
    function site_nav_links(): array
    {
        return [
            ['id' => 'home', 'label' => 'Home', 'href' => site_url('/')],
            ['id' => 'benefits', 'label' => 'Benefits', 'href' => site_url('benefits')],
            ['id' => 'coverage', 'label' => 'Coverage', 'href' => site_url('coverage')],
            ['id' => 'contribution', 'label' => 'Contribution', 'href' => site_url('contribution')],
            ['id' => 'hospitals', 'label' => 'Hospitals', 'href' => site_url('hospitals')],
            ['id' => 'faq', 'label' => 'FAQ', 'href' => site_url('faq')],
            ['id' => 'voices', 'label' => 'Voices', 'href' => site_url('testimonials')],
            ['id' => 'insights', 'label' => 'Leadership Insights', 'href' => site_url('stories')],
            ['id' => 'contact', 'label' => 'Contact', 'href' => site_url('contact')],
        ];
    }
}
