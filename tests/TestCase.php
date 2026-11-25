<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Kode 2FA saat ini untuk akun uji (UserFactory memakai kunci tetap). */
    protected function twoFactorCode(string $secret = \Database\Factories\UserFactory::TWO_FACTOR_SECRET): string
    {
        return (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
    }

    //
}
