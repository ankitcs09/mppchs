<?php

namespace App\Listeners;

use App\Events\ChangeRequestEvent;
use App\Models\BeneficiaryChangeAuditModel;
use App\Services\CompanyDashboardAssembler;
use CodeIgniter\Config\Factories;
use CodeIgniter\Events\Events;
use Config\Cache;

class ChangeRequestListener
{
    public function onChange(ChangeRequestEvent $event): void
    {
        $this->recordAudit($event);
        $this->refreshCaches($event);
    }

    private function recordAudit(ChangeRequestEvent $event): void
    {
        $actions = [
            'submitted'  => 'submitted',
            'approved'   => 'approved',
            'rejected'   => 'rejected',
            'needs_info' => 'needs_info',
        ];

        if (! isset($actions[$event->type])) {
            return;
        }

        $audit = Factories::models(BeneficiaryChangeAuditModel::class);
        $audit->insert([
            'change_request_id' => $event->requestId,
            'action'            => $actions[$event->type],
            'actor_id'          => $event->actorId,
            'notes'             => $event->context['note'] ?? null,
            'created_at'        => utc_now(),
        ]);
    }

    private function refreshCaches(ChangeRequestEvent $event): void
    {
        CompanyDashboardAssembler::invalidateCache();

        if ($event->beneficiaryId > 0) {
            cache()->delete('beneficiary_change_requests_list_' . $event->beneficiaryId);
        }
    }
}
