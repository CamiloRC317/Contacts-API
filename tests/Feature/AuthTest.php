<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

const AUTH_TEST_EMAIL = 'camilo@taskflow.test';
const AUTH_TEST_PASSWORD = 'secreta123';

test('entrega un token con credenciales validas', function () {
    $user = User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => ['id', 'name', 'email'],
        ])
        ->assertJsonPath('user.email', AUTH_TEST_EMAIL)
        ->assertJsonMissingPath('user.password');

    $this->assertSame(1, $user->tokens()->count());
});

test('rechaza una contrasena incorrecta con 422', function () {
    User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => AUTH_TEST_EMAIL,
        'password' => 'otra',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('devuelve el mismo mensaje si el correo no existe', function () {
    User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'nadie@taskflow.test',
        'password' => AUTH_TEST_PASSWORD,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Las credenciales no son correctas.');
});

test('devuelve 422 si un campo obligatorio esta vacio', function (string $field) {
    $this->postJson('/api/v1/auth/login', [
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
        $field => '',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'email' => ['email'],
    'password' => ['password'],
]);

test('rechaza a un usuario inactivo', function () {
    $user = User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
        'is_active' => false,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ])
        ->assertForbidden()
        ->assertJsonPath('message', 'La cuenta está desactivada.');

    $this->assertSame(0, $user->tokens()->count());
});

test('devuelve 401 si no se proporciona token para el perfil', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('devuelve 401 en json si falta la cabecera accept', function () {
    $this->get('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

test('devuelve el perfil del usuario autenticado', function () {
    $user = User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', AUTH_TEST_EMAIL)
        ->assertJsonMissingPath('data.password');
});

test('cierra sesion y revoca el token', function () {
    $user = User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);
    $token = $user->createToken('pruebas')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertSame(0, $user->tokens()->count());
});

test('limita a cinco los intentos de inicio de sesion por minuto', function () {
    User::factory()->create([
        'email' => AUTH_TEST_EMAIL,
        'password' => AUTH_TEST_PASSWORD,
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => AUTH_TEST_EMAIL,
            'password' => 'otra',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => AUTH_TEST_EMAIL,
        'password' => 'otra',
    ])->assertTooManyRequests();
});
