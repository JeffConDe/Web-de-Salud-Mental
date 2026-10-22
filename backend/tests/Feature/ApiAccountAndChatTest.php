<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiAccountAndChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_update_profile_and_exchange_private_chat_messages(): void
    {
        $registration = $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ANA@example.com',
            'password' => 'A-secure-password-123',
            'password_confirmation' => 'A-secure-password-123',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('A-secure-password-123', $user->password));
        $token = $registration->json('token');

        $this->postJson('/api/login', [
            'email' => 'ANA@example.com',
            'password' => 'A-secure-password-123',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.name', 'Ana');

        $this->withToken($token)->putJson('/api/profile', [
            'name' => 'Ana María',
            'email' => 'ANA@example.com',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Ana María')
            ->assertJsonPath('user.email', 'ana@example.com');

        $this->withToken($token)->getJson('/api/chat')
            ->assertOk()
            ->assertExactJson(['messages' => []]);

        $this->withToken($token)->postJson('/api/chat/messages', [
            'content' => 'Quiero expresar cómo me siento.',
        ])->assertCreated()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.sender', 'user')
            ->assertJsonPath('messages.1.sender', 'assistant');

        $this->withToken($token)->getJson('/api/chat')
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.content', 'Quiero expresar cómo me siento.');

        $this->app['auth']->forgetGuards();
        $secondUser = User::factory()->create();
        $secondToken = $secondUser->createToken('test')->plainTextToken;
        $this->withToken($secondToken)->getJson('/api/chat')
            ->assertOk()
            ->assertExactJson(['messages' => []]);

        $this->app['auth']->forgetGuards();
        $tokenId = (int) strtok($token, '|');
        $this->assertNotNull(PersonalAccessToken::find($tokenId));

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();
        $this->assertNull(PersonalAccessToken::find($tokenId));

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_chat_and_profile_require_authentication_and_chat_messages_are_validated(): void
    {
        $this->getJson('/api/chat')->assertUnauthorized();
        $this->putJson('/api/profile', [])->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/chat/messages', ['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'El correo o la contraseña no son correctos.');
    }
}
