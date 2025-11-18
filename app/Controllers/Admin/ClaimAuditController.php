<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Traits\AuthorizationTrait;
use App\Services\Claims\ClaimAuditService;
use CodeIgniter\HTTP\ResponseInterface;

class ClaimAuditController extends BaseController
{
    use AuthorizationTrait;

    private ClaimAuditService $audit;

    public function __construct(?ClaimAuditService $service = null)
    {
        $this->audit = $service ?? ClaimAuditService::new();
    }

    public function batches(): string
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $filters = $this->request->getGet() ?? [];
        $page    = (int) ($filters['page'] ?? 1);
        $page    = max(1, $page);

        $listing = $this->audit->listIngestBatches(
            $filters,
            $this->companyScopeIds(),
            $page,
            20
        );

        return view('admin/claims/batches', [
            'activeNav' => 'admin-claims',
            'pageinfo'  => [
                'apptitle'    => 'Ingestion Batches',
                'appdashname' => $this->session->get('bname') ?? 'Admin',
            ],
            'filters' => $filters,
            'listing' => $listing,
        ]);
    }

    public function batch(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $batch = $this->audit->getIngestBatch($id);
        if (! $batch) {
            return redirect()->to(site_url('admin/claims/batches'))
                ->with('warning', 'Batch not found.');
        }

        return view('admin/claims/batch_show', [
            'activeNav' => 'admin-claims',
            'pageinfo'  => [
                'apptitle'    => 'Batch #' . $batch['id'],
                'appdashname' => $this->session->get('bname') ?? 'Admin',
            ],
            'batch'    => $batch,
        ]);
    }

    public function downloads()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $filters = $this->request->getGet() ?? [];
        $page    = (int) ($filters['page'] ?? 1);
        $page    = max(1, $page);

        $listing = $this->audit->listDocumentDownloads(
            $filters,
            $this->companyScopeIds(),
            $page,
            25
        );

        return view('admin/claims/downloads', [
            'activeNav' => 'admin-claims',
            'pageinfo'  => [
                'apptitle'    => 'Document Downloads',
                'appdashname' => $this->session->get('bname') ?? 'Admin',
            ],
            'filters' => $filters,
            'listing' => $listing,
        ]);
    }
}
