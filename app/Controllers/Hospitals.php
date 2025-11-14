<?php

namespace App\Controllers;

use App\Controllers\Traits\AuthorizationTrait;
use App\Services\HospitalRequestFlowService;
use CodeIgniter\HTTP\ResponseInterface;

class Hospitals extends BaseController
{
    use AuthorizationTrait;

    private HospitalRequestFlowService $flow;

    public function __construct(?HospitalRequestFlowService $flow = null)
    {
        $this->flow = $flow ?? new HospitalRequestFlowService();
    }

    public function index(): string
    {
        return view('hospitals', [
            'pageinfo' => [
                'apptitle'    => 'Hospital Network',
                'appdashname' => 'MPPGCL',
                'frmmsg'      => 'Hospital Requests',
            ],
        ]);
    }

    public function request(): string|ResponseInterface
    {
        if (! $this->isUserLoggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $userId    = $this->currentUserId();
        $userTable = $this->resolveUserTable();

        $summary = $this->flow->userSummary($userId, $userTable);

        return view('hospitals/request', [
            'requests' => $summary['requests'],
            'stats'    => $summary['stats'],
        ]);
    }

    public function checkDuplicate(): ResponseInterface
    {
        $userId    = $this->currentUserId();
        $userTable = $this->resolveUserTable();

        if ($userId === null) {
            return $this->response->setStatusCode(401)->setJSON([
                'message' => 'Please log in to continue.',
            ]);
        }

        $payload = $this->request->getJSON(true) ?? (array) $this->request->getPost();
        $result  = $this->flow->checkDuplicate($payload, $userId, $userTable ?? 'tmusers');

        return $this->response->setStatusCode($result['status'])->setJSON($result['body']);
    }

    public function states(): ResponseInterface
    {
        return $this->response->setJSON($this->flow->getStates());
    }

    public function cities(int $stateId): ResponseInterface
    {
        return $this->response->setJSON($this->flow->getNetworkCities($stateId));
    }

    public function requestCities(int $stateId): ResponseInterface
    {
        $state = $this->flow->fetchState($stateId);
        if ($state === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'message' => 'Selected state was not found.',
            ]);
        }

        return $this->response->setJSON($this->flow->getRequestCities($stateId));
    }

    public function list(): ResponseInterface
    {
        $request = $this->request;

        $query = [
            'state_id'     => $request->getGet('state_id'),
            'city_id'      => $request->getGet('city_id'),
            'draw'         => $request->getGet('draw'),
            'start'        => $request->getGet('start'),
            'length'       => $request->getGet('length'),
            'search'       => $request->getGet('search')['value'] ?? '',
            'order_column' => $request->getGet('order')[0]['column'] ?? 1,
            'order_dir'    => $request->getGet('order')[0]['dir'] ?? 'asc',
        ];

        $result = $this->flow->listing($query);

        return $this->response->setStatusCode($result['status'])->setJSON($result['body']);
    }

    public function storeRequest(): ResponseInterface
    {
        $userId    = $this->currentUserId();
        $userTable = $this->resolveUserTable();

        if ($userId === null) {
            return $this->response->setStatusCode(401)->setJSON([
                'message'   => 'Please log in to submit a hospital request.',
                'redirect'  => site_url('login'),
                'csrfToken' => csrf_hash(),
            ]);
        }

        $payload = $this->request->getJSON(true) ?? (array) $this->request->getPost();
        $result  = $this->flow->store($payload, $userId, $userTable ?? 'tmusers');

        return $this->response->setStatusCode($result['status'])->setJSON($result['body']);
    }
}
