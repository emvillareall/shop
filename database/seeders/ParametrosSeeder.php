<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametro;
use DB;

class ParametrosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('parametros')->insert([

            'nombre_parametro' => 'recargo_paypal',
            'valor_parametro' => 0.0525
        ]);
        
        DB::table('parametros')->insert([
            'nombre_parametro' => 'cambio_moneda',
            'valor_parametro' => 0.0624
        ]);
    }
}
