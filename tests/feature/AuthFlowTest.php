<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\FeatureTestCase;

class AuthFlowTest extends FeatureTestCase
{
    use FeatureTestTrait;

    public function testLoginPageLoads(): void
    {
        $this->get('login')->assertStatus(200);
    }

}
