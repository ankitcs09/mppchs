<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Services\Claims\ClaimsIngestionService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Claims as ClaimsConfig;
use RuntimeException;

class ClaimsIngestController extends BaseController
{
    use ResponseTrait;

    private ClaimsIngestionService $service;
    private ClaimsConfig $config;

    public function __construct(
        ?ClaimsIngestionService $service = null,
        ?ClaimsConfig $config = null
    ) {
        $this->service = $service ?? service('claimsIngestion');
        $this->config  = $config ?? config('Claims');
    }

    public function import(): ResponseInterface
    {
        if ($this->config->ingestRequireHttps && ! $this->request->isSecure()) {
            return $this->fail('Ingestion endpoint requires HTTPS.', ResponseInterface::HTTP_FORBIDDEN);
        }

        if (! $this->authorizeRequest()) {
            return $this->fail('Unauthorized', ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->fail('Invalid JSON payload.', ResponseInterface::HTTP_BAD_REQUEST);
        }

        $context = [
            'source_ip'  => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
        ];

        try {
            $result = $this->service->ingestBatch($payload, $context);
        } catch (RuntimeException $exception) {
            return $this->fail($exception->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            log_message('error', '[ClaimsIngest] Unexpected failure: {message}', ['message' => $exception->getMessage()]);
            return $this->fail('Unexpected server error processing claims.', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Claims processed.',
            'data'    => $result,
        ]);
    }

    private function authorizeRequest(): bool
    {
        $clientIp = $this->request->getIPAddress();
        if (! empty($this->config->ingestAllowedIPs)) {
            $allowed = array_map('trim', $this->config->ingestAllowedIPs);
            if (! in_array($clientIp, $allowed, true)) {
                log_message('warning', '[ClaimsIngest] IP {ip} not permitted.', ['ip' => $clientIp]);
                return false;
            }
        }

        $configuredKey = trim((string) $this->config->ingestApiKey);
        if ($configuredKey === '') {
            // No API key configured -> treat as open (typically in dev)
            return true;
        }

        $headerKey = trim((string) $this->request->getHeaderLine('X-API-Key'));
        if ($headerKey === '') {
            return false;
        }

        return hash_equals($configuredKey, $headerKey);
    }
}
