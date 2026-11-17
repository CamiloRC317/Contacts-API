<?php

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('crea un contacto válido', function () {
    // Crear usuario
    $user = User::factory()->create();

    // Autenticar usuario
    Sanctum::actingAs($user);

    // Realizar petición
    $response = $this->postJson('/api/v1/contacts/create', [
        'name' => 'Ana Torres',
        'phone_number' => '3001234567',
    ]);

    // Comprobar respuesta HTTP y JSON
    $response->assertCreated()
        ->assertJsonPath('contact.name', 'Ana Torres')
        ->assertJsonPath('contact.phone_number', '3001234567');

    // Comprobar que se guardó en la base de datos
    $this->assertDatabaseHas('contacts', [
        'user_id' => $user->id,
        'name' => 'Ana Torres',
        'phone_number' => '3001234567',
        'email' => null,
    ]);
});

test('devuelve 404 para un contacto ajeno', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $contact = Contact::factory()->for($owner)->create([
        'name' => 'Contacto privado',
    ]);
    Sanctum::actingAs($visitor);
    $this->getJson('/api/v1/contacts/'.$contact->id)
        ->assertNotFound()
        ->assertJsonMissing(['name' => 'Contacto privado']);

});

test('devuelve los contactos del usuario autenticado sin mostrar los de otros usuarios', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Contact::factory()->for($user)->create(['name' => 'Contacto propio']);
    Contact::factory()->for($otherUser)->create(['name' => 'Contacto privado']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/contacts/me')
        ->assertOk()
        ->assertJsonPath('0.name', 'Contacto propio')
        ->assertJsonMissing(['name' => 'Contacto privado']);
});

test('devuelve 404 cuando el usuario autenticado no tiene contactos', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/contacts/me')
        ->assertNotFound()
        ->assertJsonPath('message', 'No tiene contactos');
});

test('devuelve los datos de un contacto propio', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create([
        'name' => 'Ana Torres',
        'phone_number' => '3001234567',
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/contacts/'.$contact->id)
        ->assertOk()
        ->assertJsonPath('id', $contact->id)
        ->assertJsonPath('name', 'Ana Torres')
        ->assertJsonPath('phone_number', '3001234567');
});

test('elimina un contacto propio', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/contacts/'.$contact->id)
        ->assertOk()
        ->assertJsonPath('message', 'Contacto eliminado correctamente');

    $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
});

test('devuelve 404 y conserva un contacto ajeno al intentar eliminarlo', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $contact = Contact::factory()->for($owner)->create();
    Sanctum::actingAs($visitor);

    $this->deleteJson('/api/v1/contacts/'.$contact->id)
        ->assertNotFound()
        ->assertJsonPath('message', 'Contacto no encontrado');

    $this->assertModelExists($contact);
});

test('actualiza los datos de un contacto propio', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create([
        'name' => 'Nombre anterior',
        'email' => 'anterior@example.test',
        'phone_number' => '3001111111',
    ]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/contacts/'.$contact->id, [
        'name' => 'Nombre actualizado',
        'email' => 'actualizado@example.test',
        'phone_number' => '3002222222',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Contacto actualizado correctamente');

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Nombre actualizado',
        'email' => 'actualizado@example.test',
        'phone_number' => '3002222222',
    ]);
});

test('devuelve 404 al actualizar un contacto inexistente', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/contacts/999999', [
        'name' => 'Nombre actualizado',
        'phone_number' => '3002222222',
    ])
        ->assertNotFound()
        ->assertJsonPath('message', 'Contacto no encontrado');
});

test('devuelve 409 al actualizar con un correo que ya pertenece a otro contacto', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create([
        'name' => 'Contacto original',
        'email' => 'original@example.test',
        'phone_number' => '3001111111',
    ]);
    Contact::factory()->for($user)->create(['email' => 'ocupado@example.test']);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/contacts/'.$contact->id, [
        'name' => 'No debe cambiar',
        'email' => 'ocupado@example.test',
        'phone_number' => '3002222222',
    ])
        ->assertConflict()
        ->assertJsonPath('message', 'El correo o número de teléfono del contacto ya existe');

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Contacto original',
        'email' => 'original@example.test',
    ]);
});

test('devuelve 409 al actualizar con un teléfono que ya pertenece a otro contacto', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create([
        'name' => 'Contacto original',
        'phone_number' => '3001111111',
    ]);
    Contact::factory()->for($user)->create(['phone_number' => '3003333333']);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/contacts/'.$contact->id, [
        'name' => 'No debe cambiar',
        'phone_number' => '3003333333',
    ])
        ->assertConflict()
        ->assertJsonPath('message', 'El correo o número de teléfono del contacto ya existe');

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Contacto original',
        'phone_number' => '3001111111',
    ]);
});

test('devuelve 409 al crear un contacto con un correo duplicado', function () {
    $user = User::factory()->create();
    Contact::factory()->for($user)->create(['email' => 'ocupado@example.test']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/contacts/create', [
        'name' => 'Nuevo contacto',
        'email' => 'ocupado@example.test',
        'phone_number' => '3004444444',
    ])
        ->assertConflict()
        ->assertJsonPath('message', 'El correo o número de teléfono del contacto ya existe');

    $this->assertDatabaseMissing('contacts', ['phone_number' => '3004444444']);
});

test('devuelve 409 al crear un contacto con un teléfono duplicado', function () {
    $user = User::factory()->create();
    Contact::factory()->for($user)->create(['phone_number' => '3005555555']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/contacts/create', [
        'name' => 'Nuevo contacto',
        'email' => 'nuevo@example.test',
        'phone_number' => '3005555555',
    ])
        ->assertConflict()
        ->assertJsonPath('message', 'El correo o número de teléfono del contacto ya existe');

    $this->assertDatabaseMissing('contacts', ['email' => 'nuevo@example.test']);
});

test('devuelve 401 al consultar contactos sin autenticación', function () {
    $this->getJson('/api/v1/contacts/me')->assertUnauthorized();
});
