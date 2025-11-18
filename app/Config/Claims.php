<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Claims extends BaseConfig
{
    /**
     * Default pagination sizes for beneficiary and admin views.
     */
    public int $beneficiaryPageSize = 20;
    public int $adminPageSize       = 25;

    /**
     * Document storage configuration.
     */
    public string $documentStorageDisk = 'ftp';
    public string $documentBasePath    = 'claims';
    public array $allowedDocumentDisks = ['ftp', 'local'];

    /**
     * Optional FTP configuration placeholder for document retrieval.
     */
    public array $ftp = [
        'host'     => '127.0.0.1',
        'port'     => 21,
        'username' => '',
        'password' => '',
        'passive'  => true,
        'timeout'  => 90,
    ];

    /**
     * Signed download link lifetime (seconds).
     */
    public int $documentLinkTtl = 900;

    /**
     * Ingestion API configuration.
     */
    public string $ingestApiKey = '';
    public array $ingestAllowedIPs = [];
    public bool $ingestRequireHttps = false;
}
