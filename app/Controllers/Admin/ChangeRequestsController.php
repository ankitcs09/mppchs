<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Traits\AuthorizationTrait;
use App\Models\BeneficiaryChangeRequestModel;
use App\Services\BeneficiaryChangeRequestService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use RuntimeException;
use const JSON_THROW_ON_ERROR;

class ChangeRequestsController extends BaseController
{
    use AuthorizationTrait;

    private BeneficiaryChangeRequestModel $requests;
    private BeneficiaryChangeRequestService $service;

    public function __construct(
        ?BeneficiaryChangeRequestModel $requests = null,
        ?BeneficiaryChangeRequestService $service = null
    ) {
        $this->requests = $requests ?? new BeneficiaryChangeRequestModel();
        $this->service  = $service ?? service('beneficiaryChangeRequest');
    }

    public function index()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('review_profile_update');

        $statusFilter = $this->request->getGet('status') ?? 'pending';
        $statusScope = $statusFilter === 'all'
            ? []
            : [$statusFilter];

        $builder = $this->requests
            ->select('beneficiary_change_requests.*, app_users.username, app_users.display_name')
            ->join('app_users', 'app_users.id = beneficiary_change_requests.user_id', 'left')
            ->orderBy('beneficiary_change_requests.created_at', 'DESC');

        if (! empty($statusScope)) {
            $builder->whereIn('beneficiary_change_requests.status', $statusScope);
        }

        $requests = $builder->paginate(25);
        $pager    = $this->requests->pager;

        return view('admin/change_requests/index', [
            'requests' => $requests,
            'pager'    => $pager,
            'status'   => $statusFilter,
            'activeNav'=> 'manage-change-requests',
        ]);
    }

    public function show(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('review_profile_update');

        $request = $this->requests->find($id);
        if (! $request) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $payloadBefore = $this->decodePayload($request['payload_before'] ?? null);
        $payloadAfter  = $this->decodePayload($request['payload_after'] ?? null);
        $summary       = $this->decodePayload($request['summary_diff'] ?? null) ?: [];

        $diff = $this->calculateDiff(
            $payloadBefore['beneficiary'] ?? [],
            $payloadAfter['beneficiary'] ?? [],
            $payloadBefore['dependents'] ?? [],
            $payloadAfter['dependents'] ?? []
        );

        return view('admin/change_requests/show', [
            'request' => $request,
            'before'  => $payloadBefore,
            'after'   => $payloadAfter,
            'summary' => array_merge([
                'beneficiary_changes' => count($diff['beneficiary']),
                'dependent_adds'      => $diff['counts']['adds'],
                'dependent_updates'   => $diff['counts']['updates'],
                'dependent_removals'  => $diff['counts']['removals'],
            ], $summary),
            'diff'    => [
                'beneficiary' => $diff['beneficiary'],
                'dependents'  => $diff['dependents'],
            ],
            'items'        => $this->service->listItems($id),
            'itemCounts'   => $this->service->getItemStats($id),
            'activeNav'=> 'manage-change-requests',
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('approve_profile_update');
        $reviewerId = (int) $this->session->get('id');

        try {
            $this->service->approve($id, $reviewerId, (string) $this->request->getPost('comment'));
            return redirect()->to(site_url('admin/change-requests/' . $id))
                ->with('success', 'Change request approved and applied.');
        } catch (RuntimeException $exception) {
            return redirect()->back()
                ->with('error', $exception->getMessage());
        }
    }

    public function reject(int $id): RedirectResponse
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('approve_profile_update');
        $reviewerId = (int) $this->session->get('id');

        $comment = (string) $this->request->getPost('comment');
        if ($comment === '') {
            return redirect()->back()->with('warning', 'Please provide a reason when rejecting a request.');
        }

        try {
            $this->service->reject($id, $reviewerId, $comment);
            return redirect()->to(site_url('admin/change-requests/' . $id))
                ->with('success', 'Change request rejected.');
        } catch (RuntimeException $exception) {
            return redirect()->back()
                ->with('error', $exception->getMessage());
        }
    }

    public function reviewItem(int $requestId, int $itemId): RedirectResponse
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('approve_profile_update');

        $status = strtolower((string) $this->request->getPost('status'));
        $note   = trim((string) $this->request->getPost('note'));
        $reviewerId = (int) $this->session->get('id');

        if (in_array($status, ['rejected', 'needs_info'], true) && $note === '') {
            return redirect()->back()->with('warning', 'Please provide a note for rejected or needs-info decisions.');
        }

        try {
            $this->service->reviewItem($requestId, $itemId, $status, $reviewerId, $note === '' ? null : $note);
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('admin/change-requests/' . $requestId))
            ->with('success', 'Field decision recorded.');
    }

    public function needsInfo(int $id): RedirectResponse
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('review_profile_update');
        $reviewerId = (int) $this->session->get('id');

        $comment = (string) $this->request->getPost('comment');
        if ($comment === '') {
            return redirect()->back()->with('warning', 'Please describe what additional information is needed.');
        }

        try {
            $this->service->requestMoreInfo($id, $reviewerId, $comment);
            return redirect()->to(site_url('admin/change-requests/' . $id))
                ->with('success', 'The request has been sent back for more information.');
        } catch (RuntimeException $exception) {
            return redirect()->back()
                ->with('error', $exception->getMessage());
        }
    }

    private function decodePayload(?string $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            log_message('error', '[ChangeRequest] Failed decoding payload: {message}', ['message' => $exception->getMessage()]);
            return [];
        }
    }

    private function calculateDiff(array $beforeBeneficiary, array $afterBeneficiary, array $beforeDependents, array $afterDependents): array
    {
        $beneficiaryDiff = [];
        foreach ($afterBeneficiary as $field => $value) {
            $before = $beforeBeneficiary[$field] ?? null;
            if ($value !== $before) {
                $beneficiaryDiff[$field] = [
                    'before' => $before,
                    'after'  => $value,
                ];
            }
        }

        $dependentDiffs = [];
        $beforeMap = [];
        foreach ($beforeDependents as $row) {
            if (! empty($row['id'])) {
                $beforeMap['id:' . $row['id']] = $row;
            }
        }

        $afterMap = [];
        foreach ($afterDependents as $row) {
            $key = ! empty($row['id'])
                ? 'id:' . $row['id']
                : ('temp:' . ($row['temp_id'] ?? spl_object_id((object) $row)));
            $afterMap[$key] = $row;
        }

        $adds = 0;
        $updates = 0;
        $removals = 0;

        foreach ($beforeMap as $key => $row) {
            if (
                ! isset($afterMap[$key]) ||
                (! empty($afterMap[$key]['action']) && $afterMap[$key]['action'] === 'remove') ||
                (! empty($afterMap[$key]['is_deleted']))
            ) {
                $dependentDiffs[] = [
                    'action'       => 'remove',
                    'dependent_id' => $row['id'] ?? null,
                    'before'       => $row,
                    'after'        => null,
                ];
                $removals++;
            }
        }

        foreach ($afterMap as $key => $row) {
            $action = $row['action'] ?? null;
            if ($action === 'remove' || ! empty($row['is_deleted'])) {
                if (! empty($row['is_deleted']) && ! empty($row['id'])) {
                    // already handled in removal loop
                }
                continue;
            }

            if (str_starts_with($key, 'temp:') || $action === 'add' || empty($row['id'])) {
                $dependentDiffs[] = [
                    'action' => 'add',
                    'before' => null,
                    'after'  => $row,
                ];
                $adds++;
                continue;
            }

            $before = $beforeMap[$key] ?? null;
            if (! $before) {
                $dependentDiffs[] = [
                    'action' => 'add',
                    'before' => null,
                    'after'  => $row,
                ];
                $adds++;
                continue;
            }

            $changes = [];
            foreach (['relationship', 'dependant_order', 'is_alive', 'is_health_dependant', 'first_name', 'gender', 'blood_group_id', 'date_of_birth'] as $field) {
                if (($before[$field] ?? null) != ($row[$field] ?? null)) {
                    $changes[$field] = [
                        'before' => $before[$field] ?? null,
                        'after'  => $row[$field] ?? null,
                    ];
                }
            }

            if (! empty($changes)) {
                $dependentDiffs[] = [
                    'action'       => 'update',
                    'dependent_id' => $before['id'] ?? null,
                    'before'       => $before,
                    'after'        => $row,
                    'changes'      => $changes,
                ];
                $updates++;
            }
        }

        return [
            'beneficiary' => $beneficiaryDiff,
            'dependents'  => $dependentDiffs,
            'counts'      => [
                'adds'     => $adds,
                'updates'  => $updates,
                'removals' => $removals,
            ],
        ];
    }
}
