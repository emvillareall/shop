<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'ruta',
        'color_id',
        'color_nombre',
        'color_normalizado',
        'orden',
        'es_principal',
        'activo',
        'nombre_original',
        'mime_type',
        'peso_original',
        'peso_optimizado',
        'ancho',
        'alto',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
