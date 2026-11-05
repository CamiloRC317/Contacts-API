<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const EMAIL = 'camilo@taskflow.test';

    private const PASSWORD = 'secreta123';

    private function crearUsuario(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ], $attributes));
    }

    private function datosInicioSesion(array $changes = []): array
    {
        return array_merge([
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ], $changes);
    }

    public function test_entrega_un_token_con_credenciales_validas(): void
    {
        $user = $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion())
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'url_img', 'residence', 'phone_number'],
            ])
            ->assertJsonPath('user.email', self::EMAIL)
            ->assertJsonMissingPath('user.password');

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_rechaza_una_contrasena_incorrecta_con_422(): void
    {
        $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion(['password' => 'otra']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_devuelve_el_mismo_mensaje_si_el_correo_no_existe(): void
    {
        $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion(['email' => 'nadie@taskflow.test']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Las credenciales no son correctas.');
    }

    #[DataProvider('camposObligatoriosInicioSesion')]
    public function test_devuelve_422_si_un_campo_obligatorio_esta_vacio(string $field): void
    {
        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion([$field => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }

    public static function camposObligatoriosInicioSesion(): array
    {
        return [
            'email' => ['email'],
            'password' => ['password'],
        ];
    }

    public function test_rechaza_a_un_usuario_inactivo(): void
    {
        $user = $this->crearUsuario(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion())
            ->assertForbidden()
            ->assertJsonPath('message', 'La cuenta está desactivada.');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_devuelve_401_si_no_se_proporciona_token_para_el_perfil(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_devuelve_401_en_json_si_falta_la_cabecera_accept(): void
    {
        $this->get('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_devuelve_el_perfil_del_usuario_autenticado(): void
    {
        $user = $this->crearUsuario();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', self::EMAIL)
            ->assertJsonMissingPath('data.password');
    }

    public function test_cierra_sesion_y_revoca_el_token(): void
    {
        $user = $this->crearUsuario();
        $token = $user->createToken('pruebas')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_limita_a_cinco_los_intentos_de_inicio_de_sesion_por_minuto(): void
    {
        $this->crearUsuario();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', $this->datosInicioSesion(['password' => 'otra']))
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', $this->datosInicioSesion(['password' => 'otra']))
            ->assertTooManyRequests();
    }
}