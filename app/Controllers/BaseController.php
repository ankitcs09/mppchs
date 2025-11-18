<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'beneficiary', 'navigation'];
    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
        $this->applyLocale($request);
    }

    private function applyLocale(RequestInterface $request): void
    {
        $appConfig = config('App');
        $supported = $appConfig->supportedLocales ?? ['en'];

        $requested = $request->getGet('lang');
        if ($requested && in_array($requested, $supported, true)) {
            $this->session->set('app.locale', $requested);
        }

        $locale = $this->session->get('app.locale') ?? $request->getLocale();
        if (! in_array($locale, $supported, true)) {
            $locale = $appConfig->defaultLocale;
        }

        service('request')->setLocale($locale);
    }
}
