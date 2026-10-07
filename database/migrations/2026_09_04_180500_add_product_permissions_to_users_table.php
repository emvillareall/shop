<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('acceso_administrativo_completo')->default(false)->after('estado_user');
            $table->boolean('puede_ver_productos')->default(false)->after('acceso_administrativo_completo');
            $table->boolean('puede_crear_productos')->default(false)->after('puede_ver_productos');
            $table->boolean('puede_editar_productos')->default(false)->after('puede_crear_productos');
        });

        // Conserva el acceso que tenían las cuentas existentes antes de introducir permisos.
        DB::table('users')->update([
            'acceso_administrativo_completo' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'acceso_administrativo_completo',
                'puede_ver_productos',
                'puede_crear_productos',
                'puede_editar_productos',
            ]);
        });
    }
};
