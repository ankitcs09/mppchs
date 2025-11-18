<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $maxRequests = isset($arguments[0]) ? (int) $arguments[0] : 60;
        $timeWindow  = isset($arguments[1]) ? (int) $arguments[1] : 60;

        $throttler = Services::throttler();
        $identifier = md5($request->getIPAddress() . '|' . $request->getPath());

        if (! $throttler->check($identifier, $maxRequests, $timeWindow)) {
            return Services::response()
                ->setStatusCode(429)
                ->setBody('Too Many Requests');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
