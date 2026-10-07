<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraRecepcion extends Model
{
    protected $table = 'compra_recepciones';

    protected $fillable = [
        'compra_id',
        'user_id',
        'fecha_recepcion',
        'observacion',
        'idempotency_key',
    ];

    protected $casts = [
        'fecha_recepcion' => 'datetime',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(CompraRecepcionItem::class, 'compra_recepcion_id');
    }
}
