<?php

namespace Config;

use App\Services\RbacService;
use App\Services\NavigationService;
use App\Services\BeneficiaryChangeRequestService;
use App\Services\BeneficiaryExportService;
use App\Services\Claims\ClaimRepositoryService;
use App\Services\Claims\ClaimsIngestionService;
use App\Services\Claims\DocumentStreamer;
use App\Services\Reports\PdfExportService;
use App\Services\Reports\SpreadsheetExportService;
use App\Services\SensitiveDataService;
use CodeIgniter\Config\BaseService;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

/**
 * Services Configuration file.
 *
 * Application specific service factories live here. The system versions
 * remain untouched so framework upgrades remain painless.
 */
class Services extends BaseService
{
    /**
     * Shared RBAC service loader.
     *
     * @param bool                        $getShared Retrieve shared instance.
     * @param ConnectionInterface|null    $connection Optional database connection.
     */
    public static function rbac(?ConnectionInterface $connection = null, bool $getShared = true): RbacService
    {
        if ($getShared) {
            return static::getSharedInstance('rbac', $connection);
        }

        $db = $connection instanceof ConnectionInterface ? $connection : \Config\Database::connect();

        return new RbacService($db);
    }

    public static function navigation(bool $getShared = true): NavigationService
    {
        if ($getShared) {
            return static::getSharedInstance('navigation');
        }

        return new NavigationService();
    }

    public static function beneficiaryChangeRequest(bool $getShared = true): BeneficiaryChangeRequestService
    {
        if ($getShared) {
            return static::getSharedInstance('beneficiaryChangeRequest');
        }

        return new BeneficiaryChangeRequestService();
    }

    public static function claimRepository(?ConnectionInterface $connection = null, bool $getShared = true): ClaimRepositoryService
    {
        if ($getShared) {
            return static::getSharedInstance('claimRepository', $connection);
        }

        $db = $connection instanceof ConnectionInterface ? $connection : Database::connect();

        return new ClaimRepositoryService($db);
    }

    public static function claimsDocumentStreamer(bool $getShared = true): DocumentStreamer
    {
        if ($getShared) {
            return static::getSharedInstance('claimsDocumentStreamer');
        }

        return new DocumentStreamer(config('Claims'), Database::connect());
    }

    public static function claimsIngestion(?ConnectionInterface $connection = null, bool $getShared = true): ClaimsIngestionService
    {
        if ($getShared) {
            return static::getSharedInstance('claimsIngestion', $connection);
        }

        $db = $connection instanceof ConnectionInterface ? $connection : Database::connect();

        return new ClaimsIngestionService($db, config('Claims'));
    }

    public static function sensitiveData(bool $getShared = true): SensitiveDataService
    {
        if ($getShared) {
            return static::getSharedInstance('sensitiveData');
        }

        return new SensitiveDataService();
    }

    public static function beneficiaryExport(?ConnectionInterface $connection = null, bool $getShared = true): BeneficiaryExportService
    {
        if ($getShared) {
            return static::getSharedInstance('beneficiaryExport', $connection);
        }

        $db = $connection instanceof ConnectionInterface ? $connection : Database::connect();

        return new BeneficiaryExportService($db, static::sensitiveData(false));
    }

    public static function reportSpreadsheet(bool $getShared = true): SpreadsheetExportService
    {
        if ($getShared) {
            return static::getSharedInstance('reportSpreadsheet');
        }

        return new SpreadsheetExportService();
    }

    public static function reportPdf(bool $getShared = true): PdfExportService
    {
        if ($getShared) {
            return static::getSharedInstance('reportPdf');
        }

        return new PdfExportService();
    }
}
