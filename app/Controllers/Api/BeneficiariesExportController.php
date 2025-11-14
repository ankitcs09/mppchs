<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\BeneficiaryExportService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Claims as ClaimsConfig;

class BeneficiariesExportController extends BaseController
{
    use ResponseTrait;

    public function __construct(
        private ?BeneficiaryExportService $exportService = null,
        private ?ClaimsConfig $claimsConfig = null
    ) {
        $this->exportService = $exportService ?? service('beneficiaryExport');
        $this->claimsConfig  = $claimsConfig ?? config('Claims');
    }

    public function index(): ResponseInterface
    {
        if (! $this->authorizeRequest()) {
            return $this->fail('Unauthorized', ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $filters = [
            'company_code'  => $this->request->getGet('company_code'),
            'updated_after' => $this->request->getGet('updated_after'),
            'reference'     => $this->request->getGet('reference'),
            'limit'         => $this->request->getGet('limit'),
            'page'          => $this->request->getGet('page'),
        ];

        try {
            $result = $this->exportService->export($filters);
        } catch (\Throwable $exception) {
            log_message('error', '[BeneficiaryExport] Failure: {message}', ['message' => $exception->getMessage()]);
            return $this->fail($exception->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Export successful.',
            'data'    => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }

    private function authorizeRequest(): bool
    {
        $configuredKey = trim((string) $this->claimsConfig->ingestApiKey);
        if ($configuredKey === '') {
            return true;
        }

        $headerKey = trim((string) $this->request->getHeaderLine('X-API-Key'));
        if ($headerKey === '') {
            return false;
        }

        return hash_equals($configuredKey, $headerKey);
    }
}

