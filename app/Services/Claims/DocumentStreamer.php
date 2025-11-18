<?php

namespace App\Services\Claims;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Claims as ClaimsConfig;
use Config\Database;
use Config\Services;
use RuntimeException;

class DocumentStreamer
{
    private ClaimsConfig $config;
    private ConnectionInterface $db;

    public function __construct(?ClaimsConfig $config = null, ?ConnectionInterface $connection = null)
    {
        $this->config = $config ?? config('Claims');
        $this->db     = $connection ?? Database::connect();
    }

    public function stream(array $claim, array $document, bool $isAdmin = false, array $context = []): ResponseInterface
    {
        $response = Services::response();

        $storage = $document['storage'] ?? [];
        $disk    = strtolower((string) ($storage['disk'] ?? ''));

        if ($disk === '' || ! in_array($disk, array_map('strtolower', $this->config->allowedDocumentDisks), true)) {
            return $this->failure(ResponseInterface::HTTP_NOT_FOUND, 'Document storage is not available.');
        }

        $relativePath = ltrim((string) ($storage['path'] ?? ''), "/\\");
        if ($relativePath === '') {
            return $this->failure(ResponseInterface::HTTP_NOT_FOUND, 'Document path missing.');
        }

        try {
            if ($disk === 'local') {
                $localPath = $this->resolveLocalPath($relativePath);
            } else {
                $localPath = $this->fetchFromFtp($relativePath);
            }
        } catch (RuntimeException $exception) {
            log_message('error', '[DocumentStreamer] Failed to locate document: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return $this->failure(ResponseInterface::HTTP_NOT_FOUND, 'Document could not be located.');
        }

        if (! is_file($localPath) || ! is_readable($localPath)) {
            return $this->failure(ResponseInterface::HTTP_NOT_FOUND, 'Document file is unavailable.');
        }

        $channel = $isAdmin ? 'admin' : 'beneficiary';
        if ($channel === 'beneficiary') {
            $claimBeneficiaryId   = $claim['beneficiary']['id'] ?? ($claim['beneficiary_id'] ?? null);
            $requestingBeneficiary = $context['beneficiary_id'] ?? null;
            if ($claimBeneficiaryId !== null && $requestingBeneficiary !== null && (int) $claimBeneficiaryId !== (int) $requestingBeneficiary) {
                log_message('warning', '[DocumentStreamer] Beneficiary mismatch for claim #{claimId} (expected {expected}, got {actual})', [
                    'claimId'  => $claim['id'] ?? null,
                    'expected' => $claimBeneficiaryId,
                    'actual'   => $requestingBeneficiary,
                ]);
                return $this->failure(ResponseInterface::HTTP_FORBIDDEN, 'Document belongs to another beneficiary.');
            }
        } else {
            $companyScope = $context['company_ids'] ?? null;
            if (is_array($companyScope)) {
                $claimCompanyId = $claim['company']['id'] ?? ($claim['company_id'] ?? null);
                if ($claimCompanyId !== null) {
                    $companyScopeInts = array_map('intval', array_filter($companyScope, static fn ($id) => $id !== null));
                    if (! in_array((int) $claimCompanyId, $companyScopeInts, true)) {
                        log_message('warning', '[DocumentStreamer] Company scope restriction for claim #{claimId}', [
                            'claimId'      => $claim['id'] ?? null,
                            'claimCompany' => $claimCompanyId,
                            'userScope'    => $companyScopeInts,
                        ]);
                        return $this->failure(ResponseInterface::HTTP_FORBIDDEN, 'Document access restricted for your scope.');
                    }
                }
            }
        }

        $checksum = $storage['checksum'] ?? null;
        if ($checksum === null || $checksum === '') {
            log_message('warning', '[DocumentStreamer] Missing checksum for claim #{claimId} document #{documentId}', [
                'claimId'    => $claim['id'] ?? null,
                'documentId' => $document['id'] ?? null,
            ]);
        } else {
            $hash = hash_file('sha256', $localPath);
            if (! hash_equals(strtolower($checksum), strtolower($hash))) {
                log_message('error', '[DocumentStreamer] Checksum mismatch for claim #{claimId} document #{documentId}', [
                    'claimId'    => $claim['id'] ?? null,
                    'documentId' => $document['id'] ?? null,
                ]);
                return $this->failure(ResponseInterface::HTTP_CONFLICT, 'Document integrity check failed.');
            }
        }

        $downloadName = $this->buildDownloadName($document);
        $mimeType     = $storage['mime_type'] ?? null;

        $downloadResponse = Services::response()->download($localPath, null);
        $downloadResponse->setFileName($downloadName);

        if ($mimeType) {
            $downloadResponse->setHeader('Content-Type', $mimeType);
        }

        if ($disk !== 'local') {
            $this->registerCleanup($localPath);
        }
        $this->logDocumentAccess($claim, $document, $channel, $context);
        return $downloadResponse;
    }

    private function failure(int $status, string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode($status)
            ->setBody($message);
    }

    private function resolveLocalPath(string $relativePath): string
    {
        $baseDir   = trim($this->config->documentBasePath, '/\\');
        $root      = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $baseDir;
        $fullPath  = $root . DIRECTORY_SEPARATOR . $relativePath;

        $rootReal = realpath($root) ?: $root;
        $fileReal = realpath($fullPath);

        if ($fileReal === false || strpos($fileReal, $rootReal) !== 0) {
            throw new RuntimeException('Local document path is outside the permitted directory.');
        }

        return $fileReal;
    }

    private function fetchFromFtp(string $relativePath): string
    {
        if (! function_exists('ftp_connect')) {
            throw new RuntimeException('FTP extension is not available.');
        }

        $cfg = $this->config->ftp;
        $connection = @ftp_connect($cfg['host'] ?? '127.0.0.1', (int) ($cfg['port'] ?? 21), (int) ($cfg['timeout'] ?? 90));
        if (! $connection) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        try {
            if (! @ftp_login($connection, (string) ($cfg['username'] ?? ''), (string) ($cfg['password'] ?? ''))) {
                throw new RuntimeException('FTP authentication failed.');
            }

            if (! empty($cfg['passive'])) {
                @ftp_pasv($connection, true);
            }

            $baseDir = trim($this->config->documentBasePath, '/\\');
            $remote  = ($baseDir !== '' ? $baseDir . '/' : '') . $relativePath;
            $remote  = str_replace('\\', '/', $remote);

            $tempFile = tempnam(sys_get_temp_dir(), 'claimdoc_');
            if ($tempFile === false) {
                throw new RuntimeException('Unable to allocate temporary file for document download.');
            }

            $handle = fopen($tempFile, 'wb');
            if ($handle === false) {
                @unlink($tempFile);
                throw new RuntimeException('Unable to open temporary file for writing.');
            }

            $success = @ftp_fget($connection, $handle, $remote, FTP_BINARY);
            fclose($handle);

            if (! $success) {
                @unlink($tempFile);
                throw new RuntimeException('Failed to download document from FTP storage.');
            }

            return $tempFile;
        } finally {
            @ftp_close($connection);
        }
    }

    private function buildDownloadName(array $document): string
    {
        $title    = trim((string) ($document['title'] ?? 'document'));
        $path     = $document['storage']['path'] ?? '';
        $ext      = pathinfo($path, PATHINFO_EXTENSION);

        if ($ext && ! str_ends_with(strtolower($title), '.' . strtolower($ext))) {
            $title .= '.' . $ext;
        }

        return $title !== '' ? $title : ('document_' . ($document['id'] ?? 'download'));
    }

    private function registerCleanup(string $tempPath): void
    {
        register_shutdown_function(static function () use ($tempPath): void {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        });
    }

    private function logDocumentAccess(array $claim, array $document, string $channel, array $context): void
    {
        if (! $this->db->tableExists('claim_document_access_log')) {
            return;
        }

        $data = [
            'claim_id'      => $claim['id'] ?? null,
            'document_id'   => $document['id'] ?? null,
            'user_id'       => $context['user_id'] ?? null,
            'user_type'     => $context['user_type'] ?? null,
            'access_channel'=> $channel,
            'client_ip'     => $context['ip_address'] ?? null,
            'user_agent'    => $context['user_agent'] ?? null,
            'downloaded_at' => utc_now(),
        ];

        try {
            $this->db->table('claim_document_access_log')->insert($data);
        } catch (\Throwable $exception) {
            log_message('warning', '[DocumentStreamer] Unable to log document access: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
