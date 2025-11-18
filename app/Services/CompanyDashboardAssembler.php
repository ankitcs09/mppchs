<?php

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Config\Database;

class CompanyDashboardAssembler
{
    private const CACHE_VERSION_KEY = 'dashboard_company_version';

    private ConnectionInterface $db;
    private int $cacheTtl = 180;

    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->db = $connection ?? Database::connect();
    }

    public function summarize(array $context, ?string $requestedScope = null): array
    {
        $cacheKey = $this->makeCacheKey($context, $requestedScope);

        if ($cacheKey === null) {
            return $this->buildDashboard($context, $requestedScope);
        }

        return cache()->remember($cacheKey, $this->cacheTtl, function () use ($context, $requestedScope) {
            return $this->buildDashboard($context, $requestedScope);
        });
    }

    public static function invalidateCache(): void
    {
        $cache   = cache();
        $current = (int) $cache->get(self::CACHE_VERSION_KEY);
        $cache->save(self::CACHE_VERSION_KEY, $current + 1, 0);
    }

    private function buildDashboard(array $context, ?string $requestedScope): array
    {
        helper('url');

        $scopeInfo = $this->resolveScope($context, $requestedScope);
        $scope     = $scopeInfo['filter'];
        $scopeMeta = $scopeInfo['meta'];
        $generated = Time::now()->toDateTimeString();

        $summary       = $this->buildSummaryCards($scope);
        $claims        = $this->buildClaimsInsights($scope);
        $queues        = $this->buildActionQueues($scope);
        $network       = $this->buildNetworkInsights($scope);
        $beneficiaries = $this->buildBeneficiaryPulse($scope);
        $pulse         = $this->buildLivePulse($scope);
        $exceptions    = $this->buildExceptionFeed($scope);
        $utilisation   = $this->buildUtilisationSnapshot($scope);
        $uploads       = $this->buildBulkUploadMonitor($scope);
        $support       = $this->buildSupportSignals($scope);
        $notes         = $this->buildRoleNotes($context, $summary, $claims, $support);

        $summary['cards'] = $this->injectCardMeta($summary['cards'] ?? [], $claims);
        $narrative        = $this->buildNarrative($summary, $claims, $queues);

        return [
            'meta' => [
                'generatedAt' => $generated,
                'scopeLabel'  => $scopeMeta['label'],
                'scope'       => $scopeMeta,
                'narrative'   => $narrative,
            ],
            'summary'       => $summary,
            'claims'        => $claims,
            'actionQueues'  => $queues,
            'network'       => $network,
            'beneficiary'   => $beneficiaries,
            'pulse'         => $pulse,
            'exceptions'    => $exceptions,
            'utilisation'   => $utilisation,
            'uploads'       => $uploads,
            'support'       => $support,
            'notes'         => $notes,
        ];
    }

    private function makeCacheKey(array $context, ?string $requestedScope): ?string
    {
        $userId = (int) ($context['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $scopeSignature = [
            'global'    => (bool) ($context['has_global_scope'] ?? false),
            'companies' => array_values(array_unique(array_map(static fn ($id): int => (int) $id, $context['company_ids'] ?? []))),
            'requested' => $requestedScope ?? 'all',
        ];

        $version = (int) cache()->get(self::CACHE_VERSION_KEY) ?: 1;

        $signatureHash = md5(json_encode($scopeSignature));

        return sprintf(
            'dashboard_company_%d_%d_%s',
            $version,
            $userId,
            $signatureHash
        );
    }

    private function resolveScope(array $context, ?string $requested): array
    {
        $hasGlobal  = (bool) ($context['has_global_scope'] ?? false);
        $companyMap = $context['company_map'] ?? [];
        $allowedIds = array_values(array_unique(array_filter(array_map(static function ($value): ?int {
            $id = is_numeric($value) ? (int) $value : null;
            return $id !== null && $id > 0 ? $id : null;
        }, $context['company_ids'] ?? []))));

        $filter   = null;
        $label    = 'All Companies';
        $selected = 'all';
        $options  = [];

        if ($hasGlobal) {
            $options[] = ['value' => 'all', 'label' => 'All Companies'];
            foreach ($companyMap as $id => $row) {
                $options[] = [
                    'value' => (string) $id,
                    'label' => ($row['name'] ?? ('Company #' . $id)) . (isset($row['code']) ? ' (' . $row['code'] . ')' : ''),
                ];
            }

            if ($requested !== null && $requested !== '' && $requested !== 'all') {
                $candidate = (int) $requested;
                if (isset($companyMap[$candidate])) {
                    $filter   = [$candidate];
                    $selected = (string) $candidate;
                    $label    = $companyMap[$candidate]['name'] ?? ('Company #' . $candidate);
                }
            }
        } else {
            if ($allowedIds === []) {
                return [
                    'filter' => [],
                    'meta'   => [
                        'label'     => 'No Company Scope Assigned',
                        'selected'  => 'none',
                        'options'   => [],
                        'canFilter' => false,
                    ],
                ];
            }

            if (count($allowedIds) === 1) {
                $id    = $allowedIds[0];
                $row   = $companyMap[$id] ?? null;
                $filter   = [$id];
                $label    = $row['name'] ?? ('Company #' . $id);
                $selected = (string) $id;
                $options[] = [
                    'value' => (string) $id,
                    'label' => $label . (isset($row['code']) ? ' (' . $row['code'] . ')' : ''),
                ];
            } else {
                $filter   = $allowedIds;
                $label    = 'My Companies';
                $selected = 'scope';

                $options[] = ['value' => 'scope', 'label' => 'My Companies'];
                foreach ($allowedIds as $id) {
                    $row = $companyMap[$id] ?? null;
                    $options[] = [
                        'value' => (string) $id,
                        'label' => ($row['name'] ?? ('Company #' . $id)) . (isset($row['code']) ? ' (' . $row['code'] . ')' : ''),
                    ];
                }

                if ($requested !== null && $requested !== '' && $requested !== 'scope') {
                    $candidate = (int) $requested;
                    if (in_array($candidate, $allowedIds, true)) {
                        $filter   = [$candidate];
                        $selected = (string) $candidate;
                        $label    = $companyMap[$candidate]['name'] ?? ('Company #' . $candidate);
                    }
                }
            }
        }

        $meta = [
            'label'     => $label,
            'selected'  => $selected,
            'options'   => $options,
            'canFilter' => count($options) > 1,
        ];

        return [
            'filter' => $filter,
            'meta'   => $meta,
        ];
    }

    private function buildSummaryCards(?array $scope): array
    {
        $beneficiaries    = $this->countBeneficiaries($scope);
        $dependents       = $this->countDependents($scope);
        $activeClaims     = $this->countActiveClaims($scope);
        $pendingClaims    = $this->countPendingClaims($scope);
        $hospitals        = $this->countHospitals();
        $slaAlerts        = $this->countSlaAlerts($scope);
        $openClaimAmount  = $this->sumOpenClaimAmount($scope);
        $pendingApprovals = $this->countPendingApprovals($scope);

        return [
            'cards' => [
                [
                    'id'       => 'beneficiaries',
                    'label'    => 'Active Beneficiaries',
                    'value'    => $beneficiaries,
                    'caption'  => 'Users with enabled access in scope',
                ],
                [
                    'id'       => 'dependents',
                    'label'    => 'Health Dependents',
                    'value'    => $dependents,
                    'caption'  => 'Dependents mapped to these members',
                ],
                [
                    'id'       => 'claims-active',
                    'label'    => 'Open Claims',
                    'value'    => $activeClaims,
                    'caption'  => 'Claims not yet settled or closed',
                    'meta'     => $openClaimAmount,
                ],
                [
                    'id'       => 'claims-pending',
                    'label'    => 'Queue Attention',
                    'value'    => $pendingClaims,
                    'caption'  => 'Claims awaiting pre-auth or processing',
                ],
                [
                    'id'       => 'approvals-pending',
                    'label'    => 'Approvals Pending',
                    'value'    => $pendingApprovals,
                    'caption'  => 'Items requiring operational action',
                ],
                [
                    'id'       => 'hospitals',
                    'label'    => 'Empanelled Hospitals',
                    'value'    => $hospitals['total'],
                    'caption'  => sprintf('%d states / %d cities', $hospitals['states'], $hospitals['cities']),
                ],
                [
                    'id'       => 'sla-alerts',
                    'label'    => 'SLA Alerts',
                    'value'    => $slaAlerts,
                    'caption'  => 'Claims breaching 7 day turnaround',
                ],
            ],
        ];
    }

    private function injectCardMeta(array $cards, array $claims): array
    {
        foreach ($cards as &$card) {
            if (($card['id'] ?? '') === 'claims-active') {
                if (isset($card['meta'])) {
                    $card['metaFormatted'] = $card['meta'];
                } elseif (isset($claims['financials']['claimed'])) {
                    $card['metaFormatted'] = $claims['financials']['claimed'];
                }
            }
        }
        unset($card);

        return $cards;
    }

    private function buildClaimsInsights(?array $scope): array
    {
        $statusCodes = [
            'registered',
            'preauth_pending',
            'preauth_approved',
            'query_raised',
            'processing',
            'approved',
            'partially_approved',
            'settled',
            'rejected',
            'closed',
        ];

        $breakdown = array_fill_keys($statusCodes, 0);

        $builder = $this->db->table('claims c')
            ->select('s.code, COUNT(*) AS total', false)
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->groupBy('s.code');

        $this->applyClaimScope($builder, $scope);

        foreach ($builder->get()->getResultArray() as $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $breakdown[$code] = (int) ($row['total'] ?? 0);
        }

        return [
            'status'      => $breakdown,
            'financials'  => $this->fetchClaimFinancials($scope),
            'highlights'  => $this->fetchClaimHighlights($scope),
            'trend'       => $this->buildClaimTrend($scope),
            'nearBreach'  => $this->fetchSlaNearBreaches($scope),
        ];
    }

    private function buildActionQueues(?array $scope): array
    {
        $changePending   = $this->countChangeRequests($scope, ['pending']);
        $needsInfo       = $this->countChangeRequests($scope, ['needs_info']);
        $hospitalPending = $this->countHospitalRequests($scope, ['pending', 'in_review']);
        $oldestChange    = $this->oldestChangeRequestAge($scope);
        $oldestHospital  = $this->oldestHospitalRequestAge($scope);

        return [
            'items' => [
                [
                    'id'          => 'change-pending',
                    'label'       => 'Change requests awaiting review',
                    'count'       => $changePending,
                    'priority'    => $changePending > 0 ? 'high' : 'normal',
                    'href'        => site_url('admin/change-requests?status=pending'),
                    'description' => $oldestChange !== null
                        ? sprintf('Oldest pending request is %d days old.', $oldestChange)
                        : 'No beneficiary change requests pending review.',
                ],
                [
                    'id'          => 'change-needs-info',
                    'label'       => 'Requests needing beneficiary inputs',
                    'count'       => $needsInfo,
                    'priority'    => $needsInfo > 0 ? 'medium' : 'normal',
                    'href'        => site_url('admin/change-requests?status=needs_info'),
                    'description' => 'Waiting on clarifications from members.',
                ],
                [
                    'id'          => 'hospital-requests',
                    'label'       => 'Hospital empanelment reviews',
                    'count'       => $hospitalPending,
                    'priority'    => $hospitalPending > 0 ? 'medium' : 'normal',
                    'href'        => site_url('hospitals/request'),
                    'description' => $oldestHospital !== null
                        ? sprintf('Oldest request is %d days old.', $oldestHospital)
                        : 'No pending empanelment requests.',
                ],
            ],
        ];
    }

    private function buildNetworkInsights(?array $scope): array
    {
        $pending = $this->fetchPendingHospitalRequests();

        return [
            'totals' => $this->countHospitals(),
            'pendingRequests' => [
                'total' => count($pending),
                'items' => $pending,
            ],
        ];
    }

    private function buildBeneficiaryPulse(?array $scope): array
    {
        $pendingReview = $this->countBeneficiariesByFlag($scope, 'pending_review', 1);
        $otpPending    = $this->countBeneficiariesOtpPending($scope);
        $noPolicyCard  = $this->countBeneficiariesWithoutPolicyCard($scope);

        return [
            'profile' => [
                'pendingReviewer' => $pendingReview,
                'otpPending'      => $otpPending,
                'noPolicyCard'    => $noPolicyCard,
            ],
        ];
    }

    private function buildLivePulse(?array $scope): array
    {
        $windowStart = Time::now('UTC')->subHours(24)->toDateTimeString();

        $claimsBuilder = $this->db->table('claims c')
            ->select('COUNT(*) AS total', false)
            ->groupStart()
                ->where('c.created_at >=', $windowStart)
                ->orWhere('c.received_at >=', $windowStart)
            ->groupEnd();
        $this->applyClaimScope($claimsBuilder, $scope);
        $newClaims = (int) ($claimsBuilder->get()->getRowArray()['total'] ?? 0);

        $approvedBuilder = $this->db->table('claims c')
            ->select('COUNT(*) AS total', false)
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->whereIn('s.code', ['approved', 'partially_approved', 'settled'])
            ->where('c.updated_at >=', $windowStart);
        $this->applyClaimScope($approvedBuilder, $scope);
        $approved = (int) ($approvedBuilder->get()->getRowArray()['total'] ?? 0);

        $beneficiaryBuilder = $this->db->table('beneficiaries_v2 b')
            ->select('COUNT(*) AS total', false)
            ->where('b.created_at >=', $windowStart);
        if ($scope !== null) {
            $beneficiaryBuilder
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($beneficiaryBuilder, $scope);
        }
        $newBeneficiaries = (int) ($beneficiaryBuilder->get()->getRowArray()['total'] ?? 0);

        $otpBuilder = $this->db->table('beneficiaries_v2 b')
            ->select('COUNT(*) AS total', false)
            ->where('b.otp_verified_at >=', $windowStart);
        if ($scope !== null) {
            $otpBuilder
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($otpBuilder, $scope);
        }
        $otpCompleted = (int) ($otpBuilder->get()->getRowArray()['total'] ?? 0);

        $changeBuilder = $this->db->table('beneficiary_change_requests r')
            ->select('COUNT(*) AS total', false)
            ->where('r.requested_at >=', $windowStart);
        if ($scope !== null) {
            $changeBuilder
                ->join('beneficiaries_v2 b', 'b.id = r.beneficiary_v2_id', 'inner')
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($changeBuilder, $scope);
        }
        $changeSubmitted = (int) ($changeBuilder->get()->getRowArray()['total'] ?? 0);

        $hospitalBuilder = $this->db->table('hospital_requests hr')
            ->select('COUNT(*) AS total', false)
            ->where('hr.created_at >=', $windowStart);
        if ($scope !== null) {
            $hospitalBuilder
                ->join('app_users u', 'u.id = hr.requester_user_id AND hr.requester_user_table = \'app_users\'', 'inner');
            $this->applyUserCompanyScope($hospitalBuilder, $scope);
        }
        $hospitalNew = (int) ($hospitalBuilder->get()->getRowArray()['total'] ?? 0);

        return [
            'windowLabel' => 'Last 24 hours',
            'metrics'     => [
                ['id' => 'claims-new',      'label' => 'Claims registered',   'value' => $newClaims],
                ['id' => 'claims-approved', 'label' => 'Approvals issued',    'value' => $approved],
                ['id' => 'beneficiaries-new','label' => 'New beneficiaries',  'value' => $newBeneficiaries],
                ['id' => 'otp-completed',   'label' => 'OTP verified',        'value' => $otpCompleted],
                ['id' => 'change-submitted','label' => 'Change requests received', 'value' => $changeSubmitted],
                ['id' => 'hospital-submitted','label' => 'Hospital nominations', 'value' => $hospitalNew],
            ],
        ];
    }

    private function buildExceptionFeed(?array $scope): array
    {
        $items = [];

        foreach (array_slice($this->fetchSlaNearBreaches($scope), 0, 4) as $row) {
            $items[] = [
                'type'      => 'sla',
                'title'     => 'SLA nearing breach',
                'reference' => $row['reference'] ?? '-',
                'context'   => $row['hospital'] ?? 'Hospital pending',
                'badge'     => ($row['ageDays'] ?? 0) . ' d',
                'url'       => $row['url'] ?? null,
            ];
        }

        $changeRows = $this->db->table('beneficiary_change_requests r')
            ->select(['r.reference_number', 'r.status', 'r.requested_at'])
            ->whereIn('LOWER(r.status)', ['pending', 'needs_info'])
            ->where('r.requested_at IS NOT NULL', null, false)
            ->where('r.requested_at <', Time::now('UTC')->subDays(5)->toDateTimeString())
            ->orderBy('r.requested_at', 'ASC')
            ->limit(3);
        if ($scope !== null) {
            $changeRows
                ->join('beneficiaries_v2 b', 'b.id = r.beneficiary_v2_id', 'inner')
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($changeRows, $scope);
        }

        foreach ($changeRows->get()->getResultArray() as $row) {
            $items[] = [
                'type'      => 'change',
                'title'     => 'Change request ageing',
                'reference' => $row['reference_number'] ?? '-',
                'context'   => 'Waiting ' . $this->calculateAgeDays($row['requested_at'] ?? null) . ' days',
                'badge'     => strtoupper($row['status'] ?? ''),
                'url'       => site_url('admin/change-requests?search=' . urlencode((string) ($row['reference_number'] ?? ''))),
            ];
        }

        $hospitalRows = $this->db->table('hospital_requests hr')
            ->select(['hr.reference_number', 'hr.hospital_name', 'hr.status', 'hr.created_at'])
            ->whereIn('LOWER(hr.status)', ['pending', 'in_review'])
            ->where('hr.created_at <', Time::now('UTC')->subDays(5)->toDateTimeString())
            ->orderBy('hr.created_at', 'ASC')
            ->limit(3);
        if ($scope !== null) {
            $hospitalRows
                ->join('app_users u', 'u.id = hr.requester_user_id AND hr.requester_user_table = \'app_users\'', 'inner');
            $this->applyUserCompanyScope($hospitalRows, $scope);
        }

        foreach ($hospitalRows->get()->getResultArray() as $row) {
            $items[] = [
                'type'      => 'hospital',
                'title'     => 'Hospital empanelment ageing',
                'reference' => $row['reference_number'] ?? '-',
                'context'   => trim(($row['hospital_name'] ?? '-') . ' · ' . $this->calculateAgeDays($row['created_at'] ?? null) . ' days'),
                'badge'     => ucfirst((string) ($row['status'] ?? 'pending')),
                'url'       => site_url('hospitals/request?search=' . urlencode((string) ($row['reference_number'] ?? ''))),
            ];
        }

        return ['items' => $items];
    }

    private function buildUtilisationSnapshot(?array $scope): array
    {
        $rangeStart = date('Y-m-d', strtotime('-30 days'));

        $builder = $this->db->table('claims c')
            ->select([
                "COALESCE(NULLIF(TRIM(c.hospital_state), ''), 'Unassigned') AS bucket",
                'COUNT(*) AS total_claims',
                'SUM(c.claimed_amount) AS total_amount',
            ])
            ->where('COALESCE(c.claim_date, c.received_at, c.created_at) >=', $rangeStart)
            ->groupBy('bucket')
            ->orderBy('total_claims', 'DESC')
            ->limit(6);
        $this->applyClaimScope($builder, $scope);

        $rows = $builder->get()->getResultArray();

        $totalClaims = 0;
        $totalAmount = 0.0;
        $topStates = array_map(static function (array $row) use (&$totalClaims, &$totalAmount): array {
            $claims = (int) ($row['total_claims'] ?? 0);
            $amount = (float) ($row['total_amount'] ?? 0.0);
            $totalClaims += $claims;
            $totalAmount += $amount;

            return [
                'label'  => $row['bucket'] ?? 'Unassigned',
                'claims' => $claims,
                'amount' => $amount,
            ];
        }, $rows);

        return [
            'rangeLabel' => 'Last 30 days',
            'states'     => $topStates,
            'totals'     => [
                'claims' => $totalClaims,
                'amount' => $totalAmount,
            ],
        ];
    }

    private function buildBulkUploadMonitor(?array $scope): array
    {
        $rows = $this->db->table('claim_ingest_batches b')
            ->select([
                'b.batch_reference',
                'b.claims_received',
                'b.claims_success',
                'b.claims_failed',
                'b.company_ids',
                'b.processed_at',
            ])
            ->orderBy('b.processed_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $filtered = array_values(array_filter($rows, function (array $row) use ($scope): bool {
            if ($scope === null) {
                return true;
            }
            if ($scope === []) {
                return false;
            }

            $raw = trim((string) ($row['company_ids'] ?? ''));
            if ($raw === '' || $raw === '0') {
                return true;
            }

            $ids = array_filter(array_map(static function ($value): ?int {
                $value = trim((string) $value);
                return is_numeric($value) ? (int) $value : null;
            }, explode(',', $raw)));

            return $ids === [] || array_intersect($ids, $scope) !== [];
        }));

        return [
            'items' => array_map(static function (array $row): array {
                $processed = $row['processed_at'] ?? null;
                return [
                    'reference'    => $row['batch_reference'] ?? 'Batch',
                    'received'     => (int) ($row['claims_received'] ?? 0),
                    'success'      => (int) ($row['claims_success'] ?? 0),
                    'failed'       => (int) ($row['claims_failed'] ?? 0),
                    'processedAt'  => $processed,
                    'processedAgo' => $processed ? Time::parse($processed)->humanize() : null,
                ];
            }, $filtered),
        ];
    }

    private function buildSupportSignals(?array $scope): array
    {
        $changePending   = $this->countChangeRequests($scope, ['pending']);
        $changeInfo      = $this->countChangeRequests($scope, ['needs_info']);
        $changeRejected  = $this->countChangeRequests($scope, ['rejected']);
        $oldestChange    = $this->oldestChangeRequestAge($scope);

        $hospitalPending = $this->countHospitalRequests($scope, ['pending', 'in_review']);
        $hospitalOldest  = $this->oldestHospitalRequestAge($scope);

        $approvedWindow = $this->db->table('hospital_requests hr')
            ->select('COUNT(*) AS total', false)
            ->where('LOWER(hr.status)', 'approved')
            ->where('hr.updated_at >=', Time::now('UTC')->subHours(24)->toDateTimeString());
        if ($scope !== null) {
            $approvedWindow
                ->join('app_users u', 'u.id = hr.requester_user_id AND hr.requester_user_table = \'app_users\'', 'inner');
            $this->applyUserCompanyScope($approvedWindow, $scope);
        }
        $approved24h = (int) ($approvedWindow->get()->getRowArray()['total'] ?? 0);

        return [
            'changeRequests' => [
                'pending'   => $changePending,
                'needsInfo' => $changeInfo,
                'rejected'  => $changeRejected,
                'oldestAge' => $oldestChange,
            ],
            'hospitalRequests' => [
                'pending'     => $hospitalPending,
                'oldestAge'   => $hospitalOldest,
                'approved24h' => $approved24h,
            ],
        ];
    }

    private function buildRoleNotes(array $context, array $summary, array $claims, array $support): array
    {
        $permissions = $context['permission_set'] ?? [];
        $items       = [];

        $financials = $claims['financials'] ?? [];
        $claimed    = (float) ($financials['claimed'] ?? 0.0);
        $approved   = (float) ($financials['approved'] ?? 0.0);
        $gap        = $claimed - $approved;

        if (isset($permissions['view_financial_reports'])) {
            $items[] = [
                'label'   => 'Claim payout gap',
                'value'   => $gap,
                'caption' => 'Difference between claimed and approved amounts',
            ];
        }

        $beneficiariesCount = 0;
        $dependentsCount    = 0;
        foreach ($summary['cards'] ?? [] as $card) {
            if (($card['id'] ?? '') === 'beneficiaries') {
                $beneficiariesCount = (int) ($card['value'] ?? 0);
            }
            if (($card['id'] ?? '') === 'dependents') {
                $dependentsCount = (int) ($card['value'] ?? 0);
            }
        }
        if ($beneficiariesCount > 0 && $dependentsCount > 0) {
            $items[] = [
                'label'   => 'Dependent coverage ratio',
                'value'   => $dependentsCount / max($beneficiariesCount, 1),
                'caption' => 'Dependents per active beneficiary',
            ];
        }

        $changePending = $support['changeRequests']['pending'] ?? 0;
        if ($changePending > 0) {
            $items[] = [
                'label'   => 'Operations attention',
                'value'   => $changePending,
                'caption' => 'Change requests awaiting clearance',
            ];
        }

        if ($items === []) {
            return [];
        }

        return [
            'headline' => 'Leadership notes',
            'items'    => $items,
        ];
    }

    private function fetchClaimFinancials(?array $scope): array
    {
        $builder = $this->db->table('claims c')
            ->selectSum('c.claimed_amount', 'claimed')
            ->selectSum('c.approved_amount', 'approved')
            ->selectSum('c.cashless_amount', 'cashless')
            ->selectSum('c.copay_amount', 'copay')
            ->selectSum('c.non_payable_amount', 'nonPayable');

        $this->applyClaimScope($builder, $scope);

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'claimed'    => (float) ($row['claimed'] ?? 0.0),
            'approved'   => (float) ($row['approved'] ?? 0.0),
            'cashless'   => (float) ($row['cashless'] ?? 0.0),
            'copay'      => (float) ($row['copay'] ?? 0.0),
            'nonPayable' => (float) ($row['nonPayable'] ?? 0.0),
        ];
    }

    private function fetchClaimHighlights(?array $scope): array
    {
        $builder = $this->db->table('claims c')
            ->select([
                'c.id',
                'c.claim_reference',
                'c.claimed_amount',
                'c.approved_amount',
                'c.cashless_amount',
                'c.received_at',
                'c.claim_date',
                'c.hospital_name',
                's.label AS status_label',
                'b.first_name',
                'b.last_name',
            ])
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->join('beneficiaries_v2 b', 'b.id = c.beneficiary_id', 'left')
            ->where('s.is_terminal', 0)
            ->orderBy('c.claimed_amount', 'DESC')
            ->limit(5);

        $this->applyClaimScope($builder, $scope);

        $rows = $builder->get()->getResultArray();

        return array_map(function (array $row): array {
            $nameParts = array_filter([
                $row['first_name'] ?? null,
                $row['last_name'] ?? null,
            ]);

            $displayName = $nameParts !== [] ? implode(' ', $nameParts) : '—';
            $received    = $row['received_at'] ?? $row['claim_date'] ?? null;

            return [
                'id'          => (int) ($row['id'] ?? 0),
                'reference'   => $row['claim_reference'] ?? '—',
                'beneficiary' => $displayName,
                'hospital'    => $row['hospital_name'] ?? '—',
                'claimed'     => (float) ($row['claimed_amount'] ?? 0.0),
                'approved'    => (float) ($row['approved_amount'] ?? 0.0),
                'cashless'    => (float) ($row['cashless_amount'] ?? 0.0),
                'status'      => $row['status_label'] ?? 'In progress',
                'ageDays'     => $this->calculateAgeDays($received),
                'url'         => site_url('admin/claims/' . (int) ($row['id'] ?? 0)),
            ];
        }, $rows);
    }

    private function buildClaimTrend(?array $scope): array
    {
        $startDate = date('Y-m-d', strtotime('-8 weeks'));

        $builder = $this->db->table('claims c')
            ->select([
                "YEARWEEK(COALESCE(c.claim_date, c.received_at), 3) AS bucket",
                "MIN(COALESCE(c.claim_date, c.received_at)) AS bucket_start",
                "MAX(COALESCE(c.claim_date, c.received_at)) AS bucket_end",
                'COUNT(*) AS total_claims',
                'SUM(c.claimed_amount) AS total_amount',
            ])
            ->where('COALESCE(c.claim_date, c.received_at) IS NOT NULL', null, false)
            ->where('COALESCE(c.claim_date, c.received_at) >=', $startDate)
            ->groupBy('bucket')
            ->orderBy('bucket', 'ASC');

        $this->applyClaimScope($builder, $scope);

        $rows = $builder->get()->getResultArray();

        $trend = [];
        foreach ($rows as $row) {
            $start = $row['bucket_start'] ?? null;
            $end   = $row['bucket_end'] ?? null;
            if ($start && $end) {
                $label = date('d M', strtotime($start)) . ' - ' . date('d M', strtotime($end));
            } elseif ($start) {
                $label = date('d M', strtotime($start));
            } else {
                $label = 'Week';
            }

            $trend[] = [
                'bucket' => (string) ($row['bucket'] ?? ''),
                'label'  => $label,
                'claims' => (int) ($row['total_claims'] ?? 0),
                'amount' => (float) ($row['total_amount'] ?? 0.0),
            ];
        }

        return $trend;
    }

    private function fetchSlaNearBreaches(?array $scope): array
    {
        $builder = $this->db->table('claims c')
            ->select([
                'c.id',
                'c.claim_reference',
                'c.claimed_amount',
                'c.approved_amount',
                'c.hospital_name',
                'c.received_at',
                's.label AS status_label',
                'TIMESTAMPDIFF(DAY, c.received_at, NOW()) AS age_days',
            ])
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->where('c.received_at IS NOT NULL', null, false)
            ->where('s.is_terminal', 0)
            ->where('TIMESTAMPDIFF(DAY, c.received_at, NOW()) >=', 5, false)
            ->where('TIMESTAMPDIFF(DAY, c.received_at, NOW()) <', 7, false)
            ->orderBy('c.received_at', 'ASC')
            ->limit(5);

        $this->applyClaimScope($builder, $scope);

        $rows = $builder->get()->getResultArray();

        return array_map(function (array $row): array {
            return [
                'reference'   => $row['claim_reference'] ?? '-',
                'hospital'    => $row['hospital_name'] ?? '-',
                'claimed'     => (float) ($row['claimed_amount'] ?? 0.0),
                'approved'    => (float) ($row['approved_amount'] ?? 0.0),
                'status'      => $row['status_label'] ?? 'In progress',
                'ageDays'     => (int) ($row['age_days'] ?? 0),
                'url'         => site_url('admin/claims/' . (int) ($row['id'] ?? 0)),
            ];
        }, $rows);
    }

    private function fetchPendingHospitalRequests(): array
    {
        $rows = $this->db->table('hospital_requests')
            ->select([
                'reference_number',
                'hospital_name',
                'city_name',
                'state_name',
                'status',
                'created_at',
            ])
            ->whereIn('status', ['pending', 'in_review'])
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return array_map(function (array $row): array {
            return [
                'reference'   => $row['reference_number'] ?? '-',
                'hospital'    => $row['hospital_name'] ?? '-',
                'location'    => trim(($row['city_name'] ?? '') . ', ' . ($row['state_name'] ?? '')),
                'status'      => ucfirst(strtolower((string) ($row['status'] ?? 'pending'))),
                'submittedAt' => $row['created_at'] ?? null,
                'ageDays'     => $this->calculateAgeDays($row['created_at'] ?? null),
            ];
        }, $rows);
    }

    private function countChangeRequests(?array $scope, array $statuses): int
    {
        $statuses = array_map('strtolower', $statuses);

        $builder = $this->db->table('beneficiary_change_requests r')
            ->whereIn('LOWER(r.status)', $statuses);

        if ($scope !== null) {
            if ($scope === []) {
                return 0;
            }

            $builder
                ->join('beneficiaries_v2 b', 'b.id = r.beneficiary_v2_id', 'inner')
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($builder, $scope);
        }

        return $builder->countAllResults();
    }

    private function countHospitalRequests(?array $scope, array $statuses): int
    {
        $statuses = array_map('strtolower', $statuses);

        $builder = $this->db->table('hospital_requests hr')
            ->whereIn('LOWER(hr.status)', $statuses);

        if ($scope !== null) {
            if ($scope === []) {
                return 0;
            }

            $builder
                ->join('app_users u', 'u.id = hr.requester_user_id AND hr.requester_user_table = \'app_users\'', 'inner');
            $this->applyUserCompanyScope($builder, $scope);
        }

        return $builder->countAllResults();
    }

    private function oldestChangeRequestAge(?array $scope): ?int
    {
        $builder = $this->db->table('beneficiary_change_requests r')
            ->select('MIN(r.requested_at) AS requested_at')
            ->where('LOWER(r.status)', 'pending');

        if ($scope !== null) {
            if ($scope === []) {
                return null;
            }

            $builder
                ->join('beneficiaries_v2 b', 'b.id = r.beneficiary_v2_id', 'inner')
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($builder, $scope);
        }

        $row = $builder->get()->getRowArray();
        if (! $row || empty($row['requested_at'])) {
            return null;
        }

        return $this->calculateAgeDays($row['requested_at']);
    }

    private function oldestHospitalRequestAge(?array $scope): ?int
    {
        $builder = $this->db->table('hospital_requests hr')
            ->select('MIN(hr.created_at) AS created_at')
            ->whereIn('LOWER(hr.status)', ['pending', 'in_review']);

        if ($scope !== null) {
            if ($scope === []) {
                return null;
            }

            $builder
                ->join('app_users u', 'u.id = hr.requester_user_id AND hr.requester_user_table = \'app_users\'', 'inner');
            $this->applyUserCompanyScope($builder, $scope);
        }

        $row = $builder->get()->getRowArray();
        if (! $row || empty($row['created_at'])) {
            return null;
        }

        return $this->calculateAgeDays($row['created_at']);
    }

    private function buildNarrative(array $summary, array $claims, array $queues): string
    {
        $parts = [];

        $cardsById = [];
        foreach ($summary['cards'] ?? [] as $card) {
            $cardsById[$card['id'] ?? ''] = $card;
        }

        if (isset($cardsById['claims-active']['value'])) {
            $parts[] = sprintf(
                '%s open claims in progress.',
                number_format((float) $cardsById['claims-active']['value'])
            );
        }

        $queueHot = null;
        foreach ($queues['items'] ?? [] as $item) {
            if (($item['count'] ?? 0) > 0 && ($queueHot === null || $item['count'] > $queueHot['count'])) {
                $queueHot = $item;
            }
        }

        if ($queueHot !== null) {
            $parts[] = sprintf(
                '%s pending in %s.',
                number_format((float) $queueHot['count']),
                strtolower($queueHot['label'] ?? 'the queue')
            );
        }

        $trend = $claims['trend'] ?? [];
        $trendCount = count($trend);
        if ($trendCount >= 2) {
            $latest   = $trend[$trendCount - 1];
            $previous = $trend[$trendCount - 2];
            $delta    = (int) (($latest['claims'] ?? 0) - ($previous['claims'] ?? 0));
            if ($delta > 0) {
                $parts[] = sprintf('%s more claims than last week.', number_format($delta));
            } elseif ($delta < 0) {
                $parts[] = sprintf('%s fewer claims week over week.', number_format(abs($delta)));
            } else {
                $parts[] = 'Claim inflow matched the prior week.';
            }
        }

        if ($parts === []) {
            return 'No major shifts detected across queues or claims this week.';
        }

        return implode(' ', $parts);
    }

    private function countBeneficiaries(?array $scope): int
    {
        if ($scope === []) {
            return 0;
        }

        if ($scope === null) {
            $row = $this->db->table('beneficiaries_v2')
                ->select('COUNT(*) AS total', false)
                ->get()
                ->getRowArray();

            return $row ? (int) $row['total'] : 0;
        }

        $builder = $this->db->table('app_users u')
            ->select('COUNT(DISTINCT u.beneficiary_v2_id) AS total', false)
            ->whereIn('u.status', ['active', 'Active', 'ACTIVE'])
            ->where('u.beneficiary_v2_id IS NOT NULL', null, false);
        $this->applyUserCompanyScope($builder, $scope);

        $row = $builder->get()->getRowArray();

        return $row ? (int) $row['total'] : 0;
    }

    private function countDependents(?array $scope): int
    {
        if ($scope === []) {
            return 0;
        }

        $builder = $this->db->table('beneficiary_dependents_v2 d')
            ->select('COUNT(DISTINCT d.id) AS total', false)
            ->where('d.deleted_at IS NULL', null, false)
            ->where('d.is_active', 1);

        if ($scope === null) {
            $row = $builder->get()->getRowArray();
            return $row ? (int) $row['total'] : 0;
        }

        $builder
            ->join('beneficiaries_v2 b', 'b.id = d.beneficiary_id', 'inner')
            ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
            ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
        $this->applyUserCompanyScope($builder, $scope);

        $row = $builder->get()->getRowArray();
        return $row ? (int) $row['total'] : 0;
    }

    private function countActiveClaims(?array $scope): int
    {
        $builder = $this->db->table('claims c')
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->where('s.is_terminal', 0);

        $this->applyClaimScope($builder, $scope);

        return $builder->countAllResults();
    }

    private function countPendingClaims(?array $scope): int
    {
        $attentionStatuses = ['preauth_pending', 'query_raised', 'processing'];

        $builder = $this->db->table('claims c')
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->whereIn('s.code', $attentionStatuses);

        $this->applyClaimScope($builder, $scope);

        return $builder->countAllResults();
    }

    private function countHospitals(): array
    {
        $totalRow = $this->db->table('network_list_medsave')
            ->select('COUNT(*) AS total', false)
            ->get()
            ->getRowArray();

        $statesRow = $this->db->table('network_list_medsave')
            ->select('COUNT(DISTINCT CARESTATE) AS total', false)
            ->where('CARESTATE IS NOT NULL', null, false)
            ->get()
            ->getRowArray();

        $citiesRow = $this->db->table('network_list_medsave')
            ->select('COUNT(DISTINCT city_id) AS total', false)
            ->where('city_id IS NOT NULL', null, false)
            ->get()
            ->getRowArray();

        return [
            'total'  => $totalRow ? (int) $totalRow['total'] : 0,
            'states' => $statesRow ? (int) $statesRow['total'] : 0,
            'cities' => $citiesRow ? (int) $citiesRow['total'] : 0,
        ];
    }

    private function countSlaAlerts(?array $scope): int
    {
        $threshold = Time::now('UTC')->subDays(7)->toDateTimeString();

        $builder = $this->db->table('claims c')
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->where('s.is_terminal', 0)
            ->where('c.received_at IS NOT NULL', null, false)
            ->where('c.received_at <', $threshold);

        $this->applyClaimScope($builder, $scope);

        return $builder->countAllResults();
    }

    private function sumOpenClaimAmount(?array $scope): float
    {
        $builder = $this->db->table('claims c')
            ->selectSum('c.claimed_amount', 'total')
            ->join('claim_statuses s', 's.id = c.status_id', 'left')
            ->where('s.is_terminal', 0);

        $this->applyClaimScope($builder, $scope);

        $row = $builder->get()->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function countPendingApprovals(?array $scope): int
    {
        $changeQueues   = $this->countChangeRequests($scope, ['pending', 'needs_info']);
        $hospitalQueues = $this->countHospitalRequests($scope, ['pending', 'in_review']);

        return $changeQueues + $hospitalQueues;
    }

    private function countBeneficiariesByFlag(?array $scope, string $column, int $expected): int
    {
        if ($scope === []) {
            return 0;
        }

        $builder = $this->db->table('beneficiaries_v2 b')
            ->where("b.{$column}", $expected);

        if ($scope !== null) {
            $builder
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($builder, $scope);
        }

        return $builder->countAllResults();
    }

    private function countBeneficiariesOtpPending(?array $scope): int
    {
        if ($scope === []) {
            return 0;
        }

        $builder = $this->db->table('beneficiaries_v2 b')
            ->where('b.otp_verified_at IS NULL', null, false);

        if ($scope !== null) {
            $builder
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($builder, $scope);
        }

        return $builder->countAllResults();
    }

    private function countBeneficiariesWithoutPolicyCard(?array $scope): int
    {
        if ($scope === []) {
            return 0;
        }

        $subQuery = $this->db->table('beneficiary_policy_cards')
            ->select('beneficiary_id')
            ->where('beneficiary_id IS NOT NULL', null, false);

        $builder = $this->db->table('beneficiaries_v2 b')
            ->select('COUNT(*) AS total', false)
            ->whereNotIn('b.id', $subQuery, false);

        if ($scope !== null) {
            $builder
                ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'inner')
                ->whereIn('u.status', ['active', 'Active', 'ACTIVE']);
            $this->applyUserCompanyScope($builder, $scope);
        }

        $row = $builder->get()->getRowArray();

        return $row ? (int) $row['total'] : 0;
    }

    private function applyClaimScope(BaseBuilder $builder, ?array $scope): void
    {
        if ($scope === null) {
            return;
        }

        if ($scope === []) {
            $builder->where('1 = 0');
            return;
        }

        $builder->whereIn('c.company_id', $scope);
    }

    private function applyUserCompanyScope(BaseBuilder $builder, ?array $scope): void
    {
        if ($scope === null) {
            return;
        }

        if ($scope === []) {
            $builder->where('1 = 0');
            return;
        }

        $builder
            ->groupStart()
                ->whereIn('u.company_id', $scope)
                ->orWhere('u.company_id IS NULL', null, false)
            ->groupEnd();
    }

    private function calculateAgeDays(?string $timestamp): int
    {
        if (! $timestamp) {
            return 0;
        }

        try {
            $time = Time::parse($timestamp);
            return max(0, (int) $time->difference(Time::now())->getDays());
        } catch (\Throwable $exception) {
            $seconds = max(0, time() - strtotime($timestamp));
            return (int) floor($seconds / 86400);
        }
    }
}
