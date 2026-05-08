<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clientes')->insert([
            'nombres_clientes' => 'Booty',
            'apellidos_clientes' => 'Fitness',
            'cedula_clientes' => '1725042871',  // tu cédula dada
            'telefono_clientes' => '0999999999',
            'ciudad_clientes' => 'Quito',
            'direccion_clientes' => 'Av. Ejemplo 123',
            'email_clientes' => 'Admin_2025@ejemplo.com',
            'estado_clientes' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
