<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\FeatureTestCase;

class OtpFlowTest extends FeatureTestCase
{
    use FeatureTestTrait;

    public function testOtpRequestPageLoads(): void
    {
        $response = $this->get('login/otp');
        $response->assertStatus(200);
    }
}