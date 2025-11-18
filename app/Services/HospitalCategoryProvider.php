<?php

namespace App\Services;

/**
 * Temporary placeholder provider for hospital category metadata.
 *
 * Once the authoritative data source is available (database or API),
 * this service can be updated to fetch real mappings without touching
 * the controllers or views.
 */
class HospitalCategoryProvider
{
    /**
     * @var array<string,array{label:string,description:string,rates:string,copay:string,note:?string}>
     */
    private array $definitions = [
        'category-1' => [
            'label'       => 'Category 1',
            'description' => 'Hospitals contracted for cashless treatment at CGHS (Plus) rates.',
            'rates'       => 'Treatment / Investigation Rates: CGHS (Plus) Rates*',
            'copay'       => 'Copay (Partial Payment) Rate: 0%',
            'note'        => 'Special rates are prescribed for certain treatments, while CGHS rates apply to the remaining treatments.',
        ],
        'category-2' => [
            'label'       => 'Category 2',
            'description' => 'Government, Charitable, and Public Sector Undertaking hospitals.',
            'rates'       => 'Treatment / Investigation Rates: Hospital Rates',
            'copay'       => 'Copay (Partial Payment) Rate: 0%',
            'note'        => 'Maximum reimbursable amount per beneficiary: ₹5,000 / ₹12,000 / ₹24,000 for Options 1 / 2 / 3.',
        ],
        'category-3' => [
            'label'       => 'Category 3',
            'description' => 'Hospitals contracted with Power Company for cashless treatment at Non-CGHS rates.',
            'rates'       => 'Treatment / Investigation Rates: Contracted Hospital Rates',
            'copay'       => 'Copay (Partial Payment) Rate: 25%',
            'note'        => null,
        ],
        'category-4' => [
            'label'       => 'Category 4',
            'description' => 'Hospitals contracted with ISA’s Group Company for cashless treatment at Non-CGHS rates.',
            'rates'       => 'Treatment / Investigation Rates: Contracted Hospital Rates',
            'copay'       => 'Copay (Partial Payment) Rate: 30%',
            'note'        => null,
        ],
        'category-5' => [
            'label'       => 'Category 5',
            'description' => 'Hospitals providing Ayurveda, Yoga, and Naturopathy treatment.',
            'rates'       => 'Treatment / Investigation Rates: Contracted Hospital Rates',
            'copay'       => 'Copay (Partial Payment) Rate: 50% (maximum reimbursable limit ₹5,000 / ₹12,000 / ₹24,000 per beneficiary for Options 1 / 2 / 3 respectively)',
            'note'        => null,
        ],
        'non-network' => [
            'label'       => 'Non-Network',
            'description' => 'Hospitals outside the empanelled network (reimbursement only).',
            'rates'       => 'Treatment / Investigation Rates: CGHS (Plus) Rates',
            'copay'       => 'Copay (Partial Payment) Rate: Not applicable',
            'note'        => null,
        ],
    ];

    /**
     * Returns the category metadata for a known key.
     *
     * @return array|null
     */
    public function definition(string $key): ?array
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * Placeholder assignment while real hospital mappings are pending.
     *
     * Uses a deterministic hash so the same hospital consistently receives
     * the same placeholder category.
     *
     * @return array{key:string|null,definition:?array}
     */
    public function placeholderForProvider(string $providerCode): array
    {
        if ($providerCode === '') {
            return ['key' => null, 'definition' => null];
        }

        $keys = array_keys($this->definitions);
        if ($keys === []) {
            return ['key' => null, 'definition' => null];
        }

        $index = crc32($providerCode) % count($keys);
        $key   = $keys[$index];

        return [
            'key'        => $key,
            'definition' => $this->definitions[$key] ?? null,
        ];
    }
}
