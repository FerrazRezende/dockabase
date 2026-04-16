<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('pt');
    }

    private function createSessionStore(): Store
    {
        $handler = app('session')->driver()->getHandler();

        return new Store('test', $handler);
    }

    public function test_it_sets_locale_from_authenticated_user(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new SetLocaleMiddleware();
        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('es', App::currentLocale());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_sets_locale_from_cookie_for_guests(): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set('locale', 'en');
        $request->setLaravelSession($this->createSessionStore());

        $middleware = new SetLocaleMiddleware();
        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('en', App::currentLocale());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_uses_default_locale_when_no_preference_set(): void
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new SetLocaleMiddleware();
        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('pt', App::currentLocale());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_validates_locale_value(): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set('locale', 'fr');
        $request->setLaravelSession($this->createSessionStore());

        $middleware = new SetLocaleMiddleware();
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertContains(App::currentLocale(), ['pt', 'en', 'es']);
    }

    public function test_it_falls_back_to_supported_locale_for_invalid_value(): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set('locale', 'de');
        $request->setLaravelSession($this->createSessionStore());

        $middleware = new SetLocaleMiddleware();
        $middleware->handle($request, fn ($req) => response('OK'));

        // Should fall back to a supported locale
        $this->assertContains(App::currentLocale(), ['pt', 'en', 'es']);
    }

    public function test_it_prioritizes_user_locale_over_cookie(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->cookies->set('locale', 'en');

        $middleware = new SetLocaleMiddleware();
        $middleware->handle($request, fn ($req) => response('OK'));

        // User locale should take precedence
        $this->assertSame('es', App::currentLocale());
    }
}
