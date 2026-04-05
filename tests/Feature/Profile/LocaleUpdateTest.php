<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_user_locale(): void
    {
        $user = User::factory()->create(['locale' => 'pt']);

        $this->actingAs($user)
            ->patch('/profile/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_it_validates_locale_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }

    public function test_it_requires_authentication(): void
    {
        $this->patch('/profile/locale', ['locale' => 'en'])
            ->assertRedirect('/login');
    }
}
