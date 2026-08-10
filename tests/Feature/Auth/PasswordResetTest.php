<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El middleware IdentifyCondominium aborta 404 si no hay condominio para el host.
        // Creamos uno que coincida con el host de prueba por defecto.
        \App\Models\Condominium::factory()->create([
            'name' => 'Condominio Demo',
            'subdomain' => 'condo_demo',
        ]);

        // Inicializar sesión y token CSRF para las peticiones POST.
        $this->app['session']->start();
        $this->app['session']->regenerateToken();
    }

    /**
     * Helper para realizar POST con token CSRF válido en este entorno de prueba.
     */
    private function postWithCsrf(string $uri, array $data)
    {
        return $this->post($uri, array_merge($data, [
            '_token' => $this->app['session']->token(),
        ]));
    }

    public function test_forgot_password_page_renders()
    {
        $this->get(route('password.forgot'))
            ->assertStatus(200)
            ->assertSee('Recuperar contraseña');
    }

    public function test_forgot_password_sends_email_for_existing_user()
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postWithCsrf(route('password.forgot.send'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        $this->assertDatabaseHas('password_resets', [
            'email' => $user->email,
        ]);
    }

    public function test_forgot_password_does_not_reveal_missing_user()
    {
        Mail::fake();

        $response = $this->postWithCsrf(route('password.forgot.send'), [
            'email' => 'missing@example.com',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_reset_password_page_renders_with_valid_token()
    {
        $user = User::factory()->create();
        $plainToken = 'valid-token-123';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $this->get(route('password.reset', ['token' => $plainToken, 'email' => $user->email]))
            ->assertStatus(200)
            ->assertSee('Restablecer contraseña');
    }

    public function test_reset_password_page_redirects_with_invalid_token()
    {
        $this->get(route('password.reset', ['token' => 'invalid-token', 'email' => 'test@example.com']))
            ->assertRedirect(url('/password/forgot'))
            ->assertSessionHasErrors('email');
    }

    public function test_reset_password_updates_password_and_deletes_token()
    {
        $user = User::factory()->create();
        $plainToken = 'reset-token-123';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $newPassword = 'NewSecurePass1';

        $response = $this->postWithCsrf(route('password.reset.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(url('/login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        $this->assertDatabaseMissing('password_resets', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_fails_with_expired_token()
    {
        $user = User::factory()->create();
        $plainToken = 'expired-token-123';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->postWithCsrf(route('password.reset.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'NewSecurePass1',
            'password_confirmation' => 'NewSecurePass1',
        ]);

        $response->assertRedirect(url('/password/forgot'))
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('NewSecurePass1', $user->fresh()->password));
    }

    public function test_reset_password_validates_password_rules()
    {
        $user = User::factory()->create();
        $plainToken = 'token-123';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->postWithCsrf(route('password.reset.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
