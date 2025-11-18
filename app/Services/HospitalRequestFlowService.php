<?php

namespace App\Services;

use Config\Services;

class HospitalRequestFlowService
{
    private HospitalRequestService $requests;

    public function __construct(?HospitalRequestService $requests = null)
    {
        $this->requests = $requests ?? new HospitalRequestService();
    }

    public function userSummary(?int $userId, ?string $userTable): array
    {
        if ($userId === null) {
            return [
                'requests' => [],
                'stats'    => [
                    'total'    => 0,
                    'pending'  => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
            ];
        }

        return $this->requests->userRequests($userId, $userTable);
    }

    public function checkDuplicate(array $payload, int $userId, string $userTable): array
    {
        $validation = Services::validation();
        $rules = [
            'state_id'      => 'required|is_natural_no_zero',
            'city_id'       => 'required|is_natural_no_zero',
            'hospital_name' => 'required|min_length[3]',
        ];

        if (! $validation->setRules($rules)->run($payload)) {
            log_message('debug', '[HospitalRequest] Duplicate check validation failed for user {user}', ['user' => $userId]);
            return [
                'status' => 422,
                'body'   => ['errors' => $validation->getErrors()],
            ];
        }

        $stateId      = (int) $payload['state_id'];
        $cityId       = (int) $payload['city_id'];
        $hospitalName = trim((string) $payload['hospital_name']);

        $state = $this->requests->fetchState($stateId);
        $city  = $this->requests->fetchCity($stateId, $cityId);

        if ($state === null || $city === null) {
            log_message('notice', '[HospitalRequest] Duplicate check invalid location state={state} city={city}', [
                'state' => $stateId,
                'city'  => $cityId,
            ]);
            return [
                'status' => 404,
                'body'   => ['message' => 'Selected state or city could not be found.'],
            ];
        }

        if (! $this->requests->cityAllowsRequest($state, $city)) {
            log_message('notice', '[HospitalRequest] Requests not allowed for state={state} city={city}', [
                'state' => $stateId,
                'city'  => $cityId,
            ]);
            return [
                'status' => 403,
                'body'   => ['message' => 'Requests are not permitted for this city.'],
            ];
        }

        $existingHospital = $this->requests->hospitalExistsInNetwork($cityId, $hospitalName);
        $existingRequest  = $this->requests->findExistingUserRequest($cityId, $hospitalName, $userId, $userTable);

        $message = null;
        if ($existingHospital) {
            $message = 'This hospital is already listed in the selected city.';
        } elseif ($existingRequest !== null) {
            $message = sprintf(
                'You already have a request for this hospital (reference %s) with status %s.',
                $existingRequest['reference_number'],
                strtoupper($existingRequest['status'])
            );
        }

        return [
            'status' => 200,
            'body'   => [
                'existingHospital' => $existingHospital,
                'existingRequest'  => $existingRequest,
                'message'          => $message,
            ],
        ];
    }

    public function store(array $payload, int $userId, string $userTable): array
    {
        $validation = Services::validation();
        $rules = [
            'state_id'      => 'required|is_natural_no_zero',
            'city_id'       => 'required|is_natural_no_zero',
            'hospital_name' => 'required|min_length[3]',
            'contact_email' => 'permit_empty|valid_email',
            'contact_phone' => [
                'rules'  => 'permit_empty|regex_match[/^[0-9]{10}$/]',
                'errors' => [
                    'regex_match' => 'Contact number must be exactly 10 digits (numbers only).',
                ],
            ],
        ];

        if (! $validation->setRules($rules)->run($payload)) {
            log_message('debug', '[HospitalRequest] Store validation failed for user {user}', ['user' => $userId]);
            return [
                'status' => 422,
                'body'   => [
                    'errors'    => $validation->getErrors(),
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        $stateId      = (int) $payload['state_id'];
        $cityId       = (int) $payload['city_id'];
        $hospitalName = trim((string) $payload['hospital_name']);

        $state = $this->requests->fetchState($stateId);
        if ($state === null) {
            log_message('notice', '[HospitalRequest] Store invalid state {state} user {user}', [
                'state' => $stateId,
                'user'  => $userId,
            ]);
            return [
                'status' => 422,
                'body'   => [
                    'errors'    => ['state_id' => 'Selected state was not found.'],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        $city = $this->requests->fetchCity($stateId, $cityId);
        if ($city === null) {
            log_message('notice', '[HospitalRequest] Store invalid city {city} for state {state} user {user}', [
                'city'  => $cityId,
                'state' => $stateId,
                'user'  => $userId,
            ]);
            return [
                'status' => 422,
                'body'   => [
                    'errors'    => ['city_id' => 'Selected city was not found in the chosen state.'],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        if (! $this->requests->cityAllowsRequest($state, $city)) {
            return [
                'status' => 422,
                'body'   => [
                    'errors'    => ['city_id' => 'Requests are not permitted for this city.'],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        $requesterUniqueRef = $this->requests->lookupUniqueRefNumber($userId, $userTable);
        if ($requesterUniqueRef === null) {
            log_message('warning', '[HospitalRequest] Missing unique reference for user {user}', ['user' => $userId]);
            return [
                'status' => 422,
                'body'   => [
                    'errors'    => [
                        'unique_ref' => 'Unique reference number for your profile is missing. Please contact the scheme administrator.',
                    ],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        if ($this->requests->hospitalExistsInNetwork($cityId, $hospitalName)) {
            log_message('debug', '[HospitalRequest] Existing hospital prevented new request city={city} hospital={hospital}', [
                'city'     => $cityId,
                'hospital' => $hospitalName,
            ]);
            return [
                'status' => 409,
                'body'   => [
                    'errors'    => ['hospital_name' => 'This hospital already exists in the selected city.'],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        if ($this->requests->findExistingUserRequest($cityId, $hospitalName, $userId, $userTable)) {
            log_message('debug', '[HospitalRequest] Duplicate user request prevented user={user} city={city} hospital={hospital}', [
                'user'     => $userId,
                'city'     => $cityId,
                'hospital' => $hospitalName,
            ]);
            return [
                'status' => 409,
                'body'   => [
                    'errors'    => ['hospital_name' => 'A request for this hospital in the selected city is already in progress.'],
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        $insert   = $this->requests->buildRequestInsert($payload, $state, $city, $userId, $userTable, $requesterUniqueRef);
        $insertId = $this->requests->insertRequest($insert);

        if (! $insertId) {
            log_message('error', '[HospitalRequest] Failed to insert request for user={user}', ['user' => $userId]);
            return [
                'status' => 500,
                'body'   => [
                    'message'   => 'Unable to submit request right now. Please try again.',
                    'csrfToken' => csrf_hash(),
                ],
            ];
        }

        $stored = $this->requests->findRequestById($insertId) ?? $insert;
        log_message('info', '[HospitalRequest] Request submitted user={user} requestId={requestId}', [
            'user'      => $userId,
            'requestId' => $insertId,
        ]);

        return [
            'status' => 201,
            'body'   => [
                'message'          => 'Request submitted successfully. Our team will review it shortly.',
                'requestId'        => $insertId,
                'referenceNumber'  => $insert['reference_number'],
                'status'           => $stored['status'] ?? 'pending',
                'createdAt'        => $stored['created_at'] ?? utc_now(),
                'createdAtDisplay' => $this->requests->formatTimestamp($stored['created_at'] ?? null),
                'csrfToken'        => csrf_hash(),
            ],
        ];
    }

    public function listing(array $query): array
    {
        $columns = [
            0 => 'n.CAREPROVIDERCODE',
            1 => 'n.CARENAME',
            2 => 'n.CAREPROVIDERCODE',
            3 => 's.state_name',
            4 => 'c.city_name',
            5 => 'n.CAREPHONE',
            6 => 'n.CAREEMAIL',
            7 => 'n.PPN',
        ];

        $orderColumn = $columns[(int) ($query['order_column'] ?? 1)] ?? 'n.CARENAME';
        $orderDir    = strtolower($query['order_dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        $length = (int) ($query['length'] ?? 0);
        if ($length <= 0) {
            $length = 500;
        }
        $length = max(10, min($length, 1000));

        $result = $this->requests->buildHospitalListing([
            'state_id'     => isset($query['state_id']) && $query['state_id'] !== '' ? (int) $query['state_id'] : null,
            'city_id'      => isset($query['city_id']) && $query['city_id'] !== '' ? (int) $query['city_id'] : null,
            'search'       => trim((string) ($query['search'] ?? '')),
            'order_column' => $orderColumn,
            'order_dir'    => $orderDir,
            'start'        => (int) ($query['start'] ?? 0),
            'length'       => $length,
        ]);

        return [
            'status' => 200,
            'body'   => [
                'draw'            => (int) ($query['draw'] ?? 0),
                'recordsTotal'    => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data'            => $result['data'],
            ],
        ];
    }

    public function getStates(): array
    {
        return $this->requests->getStates();
    }

    public function getNetworkCities(int $stateId): array
    {
        return $this->requests->getNetworkCities($stateId);
    }

    public function getRequestCities(int $stateId): array
    {
        return $this->requests->getRequestCities($stateId);
    }

    public function fetchState(int $stateId): ?array
    {
        return $this->requests->fetchState($stateId);
    }

    public function fetchCity(int $stateId, int $cityId): ?array
    {
        return $this->requests->fetchCity($stateId, $cityId);
    }

    public function cityAllowsRequest(array $state, array $city): bool
    {
        return $this->requests->cityAllowsRequest($state, $city);
    }
}
