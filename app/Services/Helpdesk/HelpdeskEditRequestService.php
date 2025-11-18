<?php

namespace App\Services\Helpdesk;

use App\Models\HelpdeskEditRequestModel;
use CodeIgniter\I18n\Time;

class HelpdeskEditRequestService
{
    public function __construct(private readonly HelpdeskEditRequestModel $model = new HelpdeskEditRequestModel())
    {
    }

    public function createRequest(
        int $beneficiaryId,
        int $helpdeskUserId,
        string $notes,
        ?int $companyId,
        array $attachments = []
    ): array {
        $payload = [
            'beneficiary_id'   => $beneficiaryId,
            'helpdesk_user_id' => $helpdeskUserId,
            'company_id'       => $companyId,
            'notes'            => $notes,
            'attachments'      => json_encode($attachments, JSON_THROW_ON_ERROR),
            'status'           => 'pending',
            'created_at'       => Time::now('UTC')->toDateTimeString(),
        ];

        $id = $this->model->insert($payload, true);
        $payload['id'] = $id;

        log_message('info', '[Helpdesk] edit request submitted request_id={id} beneficiary={beneficiary} by user={user}', [
            'id'          => $id,
            'beneficiary' => $beneficiaryId,
            'user'        => $helpdeskUserId,
        ]);

        return $payload;
    }
}
