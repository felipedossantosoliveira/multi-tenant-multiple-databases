<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->get('/'. $user->currentTenant->slug . '/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->patch('/'. $user->currentTenant->slug . '/profile', [
                'name' => 'Test User',
                'username' => 'test.user',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/'. $user->currentTenant->slug . '/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test.user', $user->username);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->patch('/'. $user->currentTenant->slug . '/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/'. $user->currentTenant->slug . '/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->delete('/'. $user->currentTenant->slug . '/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->from('/'. $user->currentTenant->slug . '/profile')
            ->delete('/'. $user->currentTenant->slug . '/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/'. $user->currentTenant->slug . '/profile');

        $this->assertNotNull($user->fresh());
    }
}
