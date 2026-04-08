<?php

declare(strict_types=1);

namespace Tests\Unit\Lang;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LangHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('pt');
    }

    public function test_it_translates_a_key_correctly(): void
    {
        $this->assertSame('Entrar', __('Login'));
    }

    public function test_it_returns_key_when_translation_missing(): void
    {
        $this->assertSame('non.existent.key', __('non.existent.key'));
    }

    public function test_it_switches_locale_correctly(): void
    {
        App::setLocale('en');
        $this->assertSame('Login', __('Login'));

        App::setLocale('es');
        $this->assertSame('Iniciar sesión', __('Login'));
    }
}
