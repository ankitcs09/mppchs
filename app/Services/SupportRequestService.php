<?php

namespace App\Services;

class SupportRequestService
{
    public function record(array $payload): void
    {
        $directory = WRITEPATH . 'support-requests';
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $entry = [
            'received_at' => date(DATE_ATOM),
            'name'        => $payload['name'] ?? null,
            'email'       => $payload['email'] ?? null,
            'phone'       => $payload['phone'] ?? null,
            'subject'     => $payload['subject'] ?? null,
            'message'     => $payload['message'] ?? null,
            'ip'          => $payload['ip'] ?? null,
            'user_agent'  => $payload['userAgent'] ?? null,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents($directory . '/' . date('Ymd') . '.log', $line, FILE_APPEND);

        log_message('info', '[Contact] Support request captured from {email}', ['email' => $entry['email']]);
    }
}
