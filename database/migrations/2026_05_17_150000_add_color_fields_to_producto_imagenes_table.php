<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('producto_imagenes')) {
            return;
        }

        Schema::table('producto_imagenes', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_imagenes', 'color_id')) {
                $table->unsignedBigInteger('color_id')->nullable()->after('producto_id');
            }
            if (!Schema::hasColumn('producto_imagenes', 'color_nombre')) {
                $table->string('color_nombre', 120)->nullable()->after('color_id');
            }
            if (!Schema::hasColumn('producto_imagenes', 'color_normalizado')) {
                $table->string('color_normalizado', 120)->nullable()->after('color_nombre');
            }
            if (!Schema::hasColumn('producto_imagenes', 'es_principal')) {
                $table->boolean('es_principal')->default(false)->after('orden');
            }
            if (!Schema::hasColumn('producto_imagenes', 'activo')) {
                $table->boolean('activo')->default(true)->after('es_principal');
            }
            if (!Schema::hasColumn('producto_imagenes', 'nombre_original')) {
                $table->string('nombre_original', 255)->nullable()->after('ruta');
            }
            if (!Schema::hasColumn('producto_imagenes', 'mime_type')) {
                $table->string('mime_type', 80)->nullable()->after('nombre_original');
            }
            if (!Schema::hasColumn('producto_imagenes', 'peso_original')) {
                $table->unsignedBigInteger('peso_original')->nullable()->after('mime_type');
            }
            if (!Schema::hasColumn('producto_imagenes', 'peso_optimizado')) {
                $table->unsignedBigInteger('peso_optimizado')->nullable()->after('peso_original');
            }
            if (!Schema::hasColumn('producto_imagenes', 'ancho')) {
                $table->unsignedInteger('ancho')->nullable()->after('peso_optimizado');
            }
            if (!Schema::hasColumn('producto_imagenes', 'alto')) {
                $table->unsignedInteger('alto')->nullable()->after('ancho');
            }
        });

        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->index(['producto_id', 'orden'], 'idx_producto_imagenes_producto_orden');
            $table->index(['producto_id', 'color_id'], 'idx_producto_imagenes_producto_color');
            $table->index(['producto_id', 'color_normalizado'], 'idx_producto_imagenes_producto_color_norm');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('producto_imagenes')) {
            return;
        }

        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropIndex('idx_producto_imagenes_producto_orden');
            $table->dropIndex('idx_producto_imagenes_producto_color');
            $table->dropIndex('idx_producto_imagenes_producto_color_norm');
        });
    }
};

