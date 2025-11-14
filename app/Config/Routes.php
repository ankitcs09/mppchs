<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index');
$routes->get('benefits', 'Home::benefits');
$routes->get('coverage', 'Home::coverage');
$routes->get('contribution', 'Home::contribution');
$routes->get('hospitals', 'Home::hospitals');
$routes->get('faq', 'Home::faq');
$routes->get('stats', 'Home::stats');
$routes->get('api/stats', 'Home::stats');
$routes->get('contact', 'Home::contact');
$routes->post('contact/request', 'ContactController::submit');
$routes->get('stories', 'StoriesController::index');
$routes->get('stories/(:segment)', 'StoriesController::show/$1');
$routes->get('testimonials', 'TestimonialsController::index');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/v2', 'Dashboard::index');
$routes->match(['GET', 'POST'], 'user/change-password', 'AccountSecurity::changePassword');

$routes->get('cashless_form_view_readonly_datatable', 'CashlessFormController::show', ['filter' => 'permission:view_beneficiary_profile_full']);
$routes->get('dashboard/cashless-form', 'CashlessFormController::show', ['filter' => 'permission:view_beneficiary_profile_full']);
$routes->get('dashboard/cashless-form/edit', 'CashlessFormController::edit', ['filter' => 'permission:edit_beneficiary_profile']);
$routes->post('dashboard/cashless-form/update', 'CashlessFormController::update', ['filter' => 'permission:edit_beneficiary_profile']);

$routes->group('enrollment', static function ($routes) {
    $routes->get('edit', 'BeneficiaryEditController::edit', ['filter' => 'permission:edit_beneficiary_profile']);
    $routes->post('edit/preview', 'BeneficiaryEditController::preview', ['filter' => 'permission:edit_beneficiary_profile']);
    $routes->post('edit/confirm', 'BeneficiaryEditController::confirm', ['filter' => 'permission:edit_beneficiary_profile']);
    $routes->get('change-requests', 'BeneficiaryChangeRequestsController::index', ['filter' => 'permission:edit_beneficiary_profile']);
    $routes->get('change-requests/(:num)', 'BeneficiaryChangeRequestsController::show/$1', ['filter' => 'permission:edit_beneficiary_profile']);
});

$routes->group('hospitals', static function ($routes) {
    $routes->get('/', 'Hospitals::index');
    $routes->get('request', 'Hospitals::request', ['filter' => 'permission:create_hospital_request']);
    $routes->get('states', 'Hospitals::states');
    $routes->get('cities/(:num)', 'Hospitals::cities/$1');
    $routes->get('request-cities/(:num)', 'Hospitals::requestCities/$1');
    $routes->get('list', 'Hospitals::list');
    $routes->post('check-duplicate', 'Hospitals::checkDuplicate', ['filter' => 'permission:create_hospital_request']);
    $routes->post('store', 'Hospitals::storeRequest', ['filter' => 'permission:create_hospital_request']);
});

$routes->group('claims', static function ($routes) {
    $routes->get('/', 'ClaimsController::index', ['filter' => 'permission:view_claims']);
    $routes->get('(:num)', 'ClaimsController::show/$1', ['filter' => 'permission:view_claims']);
    $routes->get('(:num)/documents/(:num)', 'ClaimsController::document/$1/$2', ['filter' => 'permission:download_claim_documents']);
    $routes->get('export', 'ClaimsController::export', ['filter' => 'permission:view_claims']);
    $routes->get('export/pdf', 'ClaimsController::exportPdf', ['filter' => 'permission:view_claims']);
});

$routes->group('helpdesk', static function ($routes) {
    $routes->get('beneficiaries', 'Helpdesk\BeneficiariesController::index', ['filter' => 'permission:search_beneficiaries']);
    $routes->get('beneficiaries/(:num)', 'Helpdesk\BeneficiariesController::show/$1', ['filter' => 'permission:view_beneficiary_profile_full']);
    $routes->get('beneficiaries/(:num)/pdf', 'Helpdesk\BeneficiariesController::download/$1', ['filter' => 'permission:download_beneficiary_pdf']);
    $routes->post('beneficiaries/(:num)/request-edit', 'Helpdesk\BeneficiariesController::requestEdit/$1', ['filter' => 'permission:search_beneficiaries']);
});

$routes->get('login', 'Login::index');
$routes->post('login', 'Login::useraccess', ['filter' => 'throttle:10,60']);
$routes->get('logout', 'Login::logout');
$routes->get('login/handoff', 'Login::handoff');
$routes->post('login/handoff/confirm', 'Login::handoffConfirm');
$routes->post('login/handoff/cancel', 'Login::handoffCancel');

$routes->get('password/forgot', 'PasswordResetController::request');
$routes->post('password/forgot', 'PasswordResetController::request');
$routes->get('password/reset/(:segment)/(:segment)', 'PasswordResetController::reset/$1/$2');
$routes->post('password/reset/(:segment)/(:segment)', 'PasswordResetController::reset/$1/$2');

$routes->group('login/otp', static function ($routes) {
    $routes->get('/', 'OtpController::index');
    $routes->post('/', 'OtpController::sendOtp', ['filter' => 'throttle:3,60']);
    $routes->get('verify', 'OtpController::verifyForm');
    $routes->post('verify', 'OtpController::verifyOtp', ['filter' => 'throttle:5,60']);
    $routes->post('resend', 'OtpController::resend', ['filter' => 'throttle:3,120']);
});

$routes->group('admin', ['filter' => 'permission:manage_users_company'], static function ($routes) {
    $routes->get('users', 'Admin\UsersController::index');
    $routes->get('users/create', 'Admin\UsersController::create');
    $routes->post('users', 'Admin\UsersController::store');
    $routes->get('users/(:num)/edit', 'Admin\UsersController::edit/$1');
    $routes->post('users/(:num)/update', 'Admin\UsersController::update/$1');
    $routes->post('users/(:num)/force-reset', 'Admin\UsersController::forceReset/$1');
});

$routes->group('admin/change-requests', static function ($routes) {
    $routes->get('/', 'Admin\ChangeRequestsController::index', ['filter' => 'permission:review_profile_update']);
    $routes->get('(:num)', 'Admin\ChangeRequestsController::show/$1', ['filter' => 'permission:review_profile_update']);
    $routes->post('(:num)/approve', 'Admin\ChangeRequestsController::approve/$1', ['filter' => 'permission:approve_profile_update']);
    $routes->post('(:num)/reject', 'Admin\ChangeRequestsController::reject/$1', ['filter' => 'permission:approve_profile_update']);
    $routes->post('(:num)/needs-info', 'Admin\ChangeRequestsController::needsInfo/$1', ['filter' => 'permission:review_profile_update']);
});

$routes->group('admin/change-requests', static function ($routes) {
    $routes->post('(:num)/items/(:num)', 'Admin\ChangeRequestsController::reviewItem/$1/$2', ['filter' => 'permission:approve_profile_update']);
});

$routes->group('admin/claims', static function ($routes) {
    $routes->get('/', 'Admin\ClaimsController::index', ['filter' => 'permission:manage_claims']);
    $routes->get('(:num)', 'Admin\ClaimsController::show/$1', ['filter' => 'permission:manage_claims']);
    $routes->get('(:num)/documents/(:num)', 'Admin\ClaimsController::document/$1/$2', ['filter' => 'permission:download_claim_documents']);
    $routes->get('export', 'Admin\ClaimsController::export', ['filter' => 'permission:manage_claims']);
    $routes->get('export/pdf', 'Admin\ClaimsController::exportPdf', ['filter' => 'permission:manage_claims']);
    $routes->get('batches', 'Admin\ClaimAuditController::batches', ['filter' => 'permission:manage_claims']);
    $routes->get('batches/(:num)', 'Admin\ClaimAuditController::batch/$1', ['filter' => 'permission:manage_claims']);
    $routes->get('downloads', 'Admin\ClaimAuditController::downloads', ['filter' => 'permission:manage_claims']);
});

$routes->group('admin/content', ['filter' => 'permission:submit_blog|edit_blog|approve_blog|publish_blog'], static function ($routes) {
    $routes->get('/', 'Admin\ContentController::index');
    $routes->get('create', 'Admin\ContentController::create');
    $routes->post('/', 'Admin\ContentController::store');
    $routes->get('(:num)/edit', 'Admin\ContentController::edit/$1');
    $routes->post('(:num)/update', 'Admin\ContentController::update/$1');
    $routes->post('(:num)/archive', 'Admin\ContentController::archive/$1');
    $routes->post('(:num)/publish', 'Admin\ContentController::publish/$1');
    $routes->post('(:num)/review', 'Admin\ContentController::review/$1');
    $routes->post('(:num)/withdraw', 'Admin\ContentController::withdraw/$1');
});

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->post('claims/import', 'ClaimsIngestController::import');
    $routes->get('beneficiaries', 'BeneficiariesExportController::index');
});

if (file_exists(APPPATH . 'Config/Routes/' . ENVIRONMENT . '.php')) {
    require APPPATH . 'Config/Routes/' . ENVIRONMENT . '.php';
}
