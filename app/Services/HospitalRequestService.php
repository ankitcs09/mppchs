<?php

namespace App\Services;

use App\Models\CityModel;
use App\Models\HospitalModel;
use App\Models\HospitalRequestModel;
use App\Models\StateModel;
use CodeIgniter\I18n\Time;
use App\Services\HospitalCategoryProvider;

class HospitalRequestService
{
    private StateModel $states;
    private CityModel $cities;
    private HospitalModel $hospitals;
    private HospitalRequestModel $requests;
    private HospitalCategoryProvider $categories;

    public function __construct(
        ?StateModel $states = null,
        ?CityModel $cities = null,
        ?HospitalModel $hospitals = null,
        ?HospitalRequestModel $requests = null,
        ?HospitalCategoryProvider $categories = null
    ) {
        $this->states     = $states ?? new StateModel();
        $this->cities     = $cities ?? new CityModel();
        $this->hospitals  = $hospitals ?? new HospitalModel();
        $this->requests   = $requests ?? new HospitalRequestModel();
        $this->categories = $categories ?? new HospitalCategoryProvider();
    }

    /**
     * Returns the request list and status counts for a user.
     *
     * @return array{requests:array<int,array>, stats:array<string,int>}
     */
    public function userRequests(int $userId, string $userTable): array
    {
        log_message(
            'debug',
            '[HospitalService] userRequests user={user} table={table}',
            [
                'user'  => $userId,
                'table' => $userTable,
            ]
        );

        $requests = $this->requests
            ->select('reference_number, hospital_name, city_name, state_name, status, created_at')
            ->where('requester_user_id', $userId)
            ->where('requester_user_table', $userTable)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $requests = array_map(function (array $row): array {
            $row['created_at_display'] = $this->formatTimestamp($row['created_at'] ?? null);
            return $row;
        }, $requests);

        $stats = [
            'total'    => count($requests),
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($requests as $request) {
            $status = strtolower((string) ($request['status'] ?? 'pending'));
            if ($status === 'approved') {
                $stats['approved']++;
            } elseif ($status === 'rejected') {
                $stats['rejected']++;
            } else {
                $stats['pending']++;
            }
        }

        return ['requests' => $requests, 'stats' => $stats];
    }

    public function fetchState(int $stateId): ?array
    {
        return $this->states->find($stateId);
    }

    public function fetchCity(int $stateId, int $cityId): ?array
    {
        return $this->cities
            ->select('city_id, city_name, is_request_enabled')
            ->where('city_id', $cityId)
            ->where('state_id', $stateId)
            ->first();
    }

    public function cityAllowsRequest(array $state, array $city): bool
    {
        if ((int) ($state['allow_unrestricted_cities'] ?? 0) === 1) {
            return true;
        }

        return (int) ($city['is_request_enabled'] ?? 0) === 1;
    }

    public function hospitalExistsInNetwork(int $cityId, string $hospitalName): bool
    {
        return $this->hospitals->builder()
            ->where('city_id', $cityId)
            ->where('UPPER(CARENAME)', strtoupper($hospitalName))
            ->countAllResults() > 0;
    }

    public function findExistingUserRequest(int $cityId, string $hospitalName, int $userId, string $userTable): ?array
    {
        return $this->requests->builder()
            ->select('reference_number, status, created_at')
            ->where('city_id', $cityId)
            ->where('UPPER(hospital_name)', strtoupper($hospitalName))
            ->where('requester_user_id', $userId)
            ->where('requester_user_table', $userTable)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getFirstRow('array');
    }

    public function getStates(): array
    {
        return $this->states
            ->select('state_id, state_name, allow_unrestricted_cities')
            ->orderBy('state_name', 'ASC')
            ->findAll();
    }

    public function getNetworkCities(int $stateId): array
    {
        $hospitalTable = $this->hospitals->tableName();

        return db_connect()
            ->table($hospitalTable . ' n')
            ->select('c.city_id, c.city_name')
            ->join('cities c', 'c.city_id = n.city_id', 'inner')
            ->where('c.state_id', $stateId)
            ->groupBy('c.city_id, c.city_name')
            ->orderBy('c.city_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getRequestCities(int $stateId): array
    {
        $state = $this->states->find($stateId);
        if ($state === null) {
            return [];
        }

        $builder = $this->cities->builder()
            ->select('city_id, city_name, is_request_enabled')
            ->where('state_id', $stateId)
            ->orderBy('city_name', 'ASC');

        if ((int) ($state['allow_unrestricted_cities'] ?? 0) === 0) {
            $builder->where('is_request_enabled', 1);
        }

        $cities = $builder->get()->getResultArray();

        return array_map(static function (array $row): array {
            unset($row['is_request_enabled']);
            return $row;
        }, $cities);
    }

    /**
     * Builds the payload for the DataTables list endpoint.
     *
     * @return array{data:array<int,array>, recordsTotal:int, recordsFiltered:int}
     */
    public function buildHospitalListing(array $params): array
    {
        log_message(
            'debug',
            '[HospitalService] buildHospitalListing state={state} city={city} search="{search}" start={start} length={length}',
            [
                'state'  => $params['state_id'] ?? null,
                'city'   => $params['city_id'] ?? null,
                'search' => $params['search'] ?? '',
                'start'  => $params['start'] ?? 0,
                'length' => $params['length'] ?? 0,
            ]
        );

        $stateId = $params['state_id'] ?? null;
        $cityId  = $params['city_id'] ?? null;
        $search  = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 'n.CARENAME';
        $orderDir    = strtoupper($params['order_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $start       = $params['start'] ?? 0;
        $length      = $params['length'] ?? 10;

        $builder = $this->hospitals
            ->datatableBuilder($stateId, $cityId, $search)
            ->orderBy($orderColumn, $orderDir);

        if ($length > 0) {
            $builder->limit($length, $start);
        }

        $rows = $builder->get()->getResultArray();
        $data = [];

        foreach ($rows as $row) {
            $category   = $this->categories->placeholderForProvider((string) ($row['CAREPROVIDERCODE'] ?? ''));
            $definition = $category['definition'] ?? null;

            $data[] = [
                'CAREPROVIDERCODE' => $row['CAREPROVIDERCODE'],
                'CARENAME'         => $row['CARENAME'],
                'state'            => $row['state_name'] ?? '-',
                'city'             => $row['city_name'] ?? '-',
                'CAREPHONE'        => $row['CAREPHONE'],
                'CAREEMAIL'        => $row['CAREEMAIL'],
                'PPN'              => $row['PPN'],
                'category_code'    => $category['key'],
                'category_label'   => $definition['label'] ?? null,
                'category_desc'    => $definition['description'] ?? null,
                'category_rates'   => $definition['rates'] ?? null,
                'category_copay'   => $definition['copay'] ?? null,
                'category_note'    => $definition['note'] ?? null,
            ];
        }

        return [
            'data'            => $data,
            'recordsTotal'    => $this->hospitals->total($stateId, $cityId),
            'recordsFiltered' => $this->hospitals->filtered($stateId, $cityId, $search),
        ];
    }

    public function lookupUniqueRefNumber(int $userId, string $userTable): ?string
    {
        $db = db_connect();

        if ($userTable === 'app_users') {
            $row = $db->table('app_users u')
                ->select('b.reference_number')
                ->join('beneficiaries_v2 b', 'b.id = u.beneficiary_v2_id', 'left')
                ->where('u.id', $userId)
                ->get()
                ->getFirstRow('array');

            return $row['reference_number'] ?? null;
        }

        $row = $db->table('tmusers u')
            ->select('b.unique_ref_number')
            ->join('beneficiaries b', 'b.id = u.beneficiary_id', 'left')
            ->where('u.id', $userId)
            ->get()
            ->getFirstRow('array');

        return $row['unique_ref_number'] ?? null;
    }

    public function buildRequestInsert(
        array $payload,
        array $state,
        array $city,
        int $userId,
        string $userTable,
        string $uniqueRef
    ): array {
        log_message(
            'debug',
            '[HospitalService] buildRequestInsert user={user} state={state} city={city} provider="{name}"',
            [
                'user'   => $userId,
                'state'  => $state['state_name'] ?? null,
                'city'   => $city['city_name'] ?? null,
                'name'   => trim($payload['hospital_name'] ?? ''),
            ]
        );

        return [
            'state_id'              => (int) $payload['state_id'],
            'state_name'            => $state['state_name'],
            'city_id'               => (int) $payload['city_id'],
            'city_name'             => $city['city_name'],
            'hospital_name'         => trim($payload['hospital_name']),
            'address'               => $payload['address'] ?? null,
            'contact_person'        => $payload['contact_person'] ?? null,
            'contact_phone'         => $payload['contact_phone'] ?? null,
            'contact_email'         => $payload['contact_email'] ?? null,
            'notes'                 => $payload['notes'] ?? null,
            'status'                => 'pending',
            'requester_user_id'     => $userId,
            'requester_user_table'  => $userTable,
            'requester_unique_ref'  => $uniqueRef,
            'reference_number'      => $this->requests->generateReferenceNumber(),
        ];
    }

    public function insertRequest(array $data): int
    {
        log_message(
            'debug',
            '[HospitalService] insertRequest provider="{name}" city={city} state={state}',
            [
                'name'  => $data['hospital_name'] ?? null,
                'city'  => $data['city_id'] ?? null,
                'state' => $data['state_id'] ?? null,
            ]
        );

        return (int) $this->requests->insert($data);
    }

    public function findRequestById(int $id): ?array
    {
        return $this->requests->find($id);
    }

    public function formatTimestamp(?string $timestamp): string
    {
        if (! $timestamp) {
            return '-';
        }

        try {
            $timezone = config('App')->appTimezone ?? date_default_timezone_get();
            return Time::parse($timestamp)->setTimezone($timezone)->toLocalizedString('dd MMM yyyy, hh:mm a');
        } catch (\Throwable $exception) {
            return date('d M Y, h:i A', strtotime($timestamp));
        }
    }
}
