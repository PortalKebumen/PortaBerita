<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndonesianMessagesTest extends TestCase
{
    public function test_application_uses_indonesian_authentication_and_validation_messages(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Email atau kata sandi yang Anda masukkan salah.', __('auth.failed'));
        $this->assertSame('Email yang Anda masukkan tidak terdaftar.', __('passwords.user'));

        $validator = Validator::make(['email' => 'invalid', 'password' => 'abc'], [
            'email' => ['email'],
            'password' => ['min:8', 'confirmed'],
            'name' => ['required'],
        ]);
        $this->assertSame('Email harus berupa alamat email yang valid.', $validator->errors()->first('email'));
        $this->assertSame('Nama wajib diisi.', $validator->errors()->first('name'));
        $this->assertContains('Panjang Kata sandi harus minimal 8 karakter.', $validator->errors()->get('password'));
        $this->assertContains('Konfirmasi Kata sandi tidak cocok.', $validator->errors()->get('password'));
    }

    public function test_all_builtin_validation_rules_have_translations(): void
    {
        $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $indonesian = require lang_path('id/validation.php');
        unset($english['custom'], $english['attributes']);
        foreach ($english as $rule => $messages) {
            $this->assertArrayHasKey($rule, $indonesian);
            if (is_array($messages)) {
                foreach (array_keys($messages) as $type) {
                    $this->assertArrayHasKey($type, $indonesian[$rule]);
                }
            }
        }
    }
}
