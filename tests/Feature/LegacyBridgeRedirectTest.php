<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyBridgeRedirectTest extends TestCase
{
    public function test_legacy_public_routes_are_not_available_anymore(): void
    {
        $this->get('/tienda')->assertNotFound();
        $this->get('/carrito')->assertNotFound();
        $this->get('/catalogo/1/1')->assertNotFound();
        $this->get('/lineas')->assertNotFound();
        $this->get('/store/lineas/1/categorias')->assertNotFound();
        $this->get('/catalogo-general')->assertNotFound();
        $this->get('/catalogo-pdf')->assertNotFound();
    }

    public function test_legacy_variant_endpoint_is_not_available(): void
    {
        $this->get('/id_colores/1')->assertNotFound();
    }
}
