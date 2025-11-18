<?php

namespace App\Services\Helpdesk;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class BeneficiaryDirectoryService
{
    public function __construct(private ?ConnectionInterface $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Search beneficiaries within the supplied company scope.
     *
     * @param list<int>|null $companyScope Null => global, [] => none.
     */
    public function search(?array $companyScope, array $filters = []): array
    {
        $query    = trim((string) ($filters['query'] ?? ''));
        $page     = max(1, (int) ($filters['page'] ?? 1));
        $perPage  = max(5, min(50, (int) ($filters['per_page'] ?? 20)));
        $offset   = ($page - 1) * $perPage;

        if ($companyScope === [] ) {
            return [
                'rows'       => [],
                'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'pages' => 0],
            ];
        }

        $builder = $this->baseBuilder();
        $this->applyCompanyScope($builder, $companyScope);

        if ($query !== '') {
            $this->applySearchTerm($builder, $query);
        }

        $countBuilder = clone $builder;
        $total = (int) ($countBuilder
            ->select('COUNT(DISTINCT b.id) AS total', false)
            ->get()
            ->getRow('total') ?? 0);

        if ($total === 0) {
            return [
                'rows'       => [],
                'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'pages' => 0],
            ];
        }

        $rows = $builder
            ->select([
                'b.id',
                'b.reference_number',
                'b.legacy_reference',
                'b.first_name',
                'b.last_name',
                'b.city',
                'b.state_id',
                'b.primary_mobile_masked',
                'b.updated_at',
                'c.name AS company_name',
                'c.code AS company_code',
            ])
            ->groupBy('b.id')
            ->orderBy('b.updated_at', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'rows'       => $rows,
            'pagination' => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'pages'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Ensure the beneficiary belongs to the supplied company scope.
     */
    public function findWithinCompany(?array $companyScope, int $beneficiaryId): ?array
    {
        if ($beneficiaryId <= 0) {
            return null;
        }

        if ($companyScope === []) {
            return null;
        }

        $builder = $this->baseBuilder()
            ->select([
                'b.id',
                'b.reference_number',
                'b.first_name',
                'b.last_name',
                'b.primary_mobile_masked',
                'c.name AS company_name',
                'c.code AS company_code',
            ])
            ->where('b.id', $beneficiaryId)
            ->groupBy('b.id');

        $this->applyCompanyScope($builder, $companyScope);

        return $builder->get()->getRowArray() ?: null;
    }

    private function baseBuilder(): BaseBuilder
    {
        return $this->db->table('beneficiaries_v2 b')
            ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
            ->join('companies c', 'c.id = u.company_id', 'left')
            ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
    }

    private function applyCompanyScope(BaseBuilder $builder, ?array $companyScope): void
    {
        if ($companyScope === null) {
            return;
        }

        $builder->groupStart()
            ->whereIn('u.company_id', $companyScope)
            ->orWhere('u.company_id IS NULL', null, false)
            ->groupEnd();
    }

    private function applySearchTerm(BaseBuilder $builder, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $isWildcard = str_contains($term, '*');
        $pattern    = $isWildcard ? str_replace('*', '%', $term) : $term;
        $side       = $isWildcard ? 'none' : 'both';
        $escape     = $isWildcard ? false : null;

        $builder->groupStart();
        foreach ([
            'b.first_name',
            'b.last_name',
            'b.reference_number',
            'b.legacy_reference',
            'b.ppo_number_masked',
            'b.primary_mobile_masked',
            'b.samagra_masked',
        ] as $column) {
            if ($isWildcard) {
                $builder->orWhere("{$column} LIKE", $pattern, false);
            } else {
                $builder->orLike($column, $pattern, $side);
            }
        }
        $builder->groupEnd();
    }
}
