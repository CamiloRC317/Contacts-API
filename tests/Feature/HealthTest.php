<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_devuelve_el_json_del_estado_de_salud(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('status', '0.1.0');

        $response->assertOk()
            ->assertJsonPath('app', config('app.name'));

        $response->assertOk()
            ->assertJsonPath('name', 'contact-api');
    }
}
