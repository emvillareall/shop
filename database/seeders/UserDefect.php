<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
class UserDefect extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('clientes')->insert([
        'nombres_clientes' => 'No',
        'apellidos_clientes' => 'ingresado',
        'cedula_clientes' => '',
        'telefono_clientes' => '',
        'ciudad_clientes' => '',
        'direccion_clientes' => '',
        ]);
    }
}
