<?php

use App\Models\User;
use App\Models\Contact;
use Laravel\Sanctum\Sanctum;

test('crea un contacto válido', function () {
    // Crear usuario
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $datos = [
        'name' => $contact->name,
    ];
    // Autenticar usuario
    Sanctum::actingAs($user);

    // Realizar petición
    $response = $this->postJson('/api/v1/contacts/create', [
        'name' => 'Ana Torres',
        'phone_number' => '3001234567',
    ]);
    $response->dump();
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