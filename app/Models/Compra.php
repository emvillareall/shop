<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CompraItem;

/**
 * Class Compra
 *
 * @property $id
 * @property $codigo_compra
 * @property $descripcion_compra
 * @property $envio_compra
 * @property $total_pesos_compra
 * @property $total_dolares_compra
 * @property $importacion_compra
 * @property $total_final_compra
 * @property $proveedor_id
 * @property $created_at
 * @property $estado_compra
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Compra extends Model
{
    
    static $rules = [
        'id',
		'codigo_compra' => 'required',
		'descripcion_compra' => 'required',
		'envio_compra' => 'required',
		'total_pesos_compra',
        'total_dolares_compra',
        'importacion_compra',
        'total_final_compra',
		'proveedor_id' => 'required',
        'estado_compra'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['codigo_compra','descripcion_compra','envio_compra','importacion_compra','estado_compra','estado_recepcion','proveedor_id'];

    public function items()
    {
        return $this->hasMany(CompraItem::class, 'compra_id');
    }

    public function recepciones()
    {
        return $this->hasMany(CompraRecepcion::class, 'compra_id');
    }

    public function reclamosProveedor()
    {
        return $this->hasMany(ProveedorReclamo::class, 'compra_id');
    }

    public function tieneRecepciones(): bool
    {
        if (array_key_exists('recepciones_count', $this->attributes)) {
            return (int) $this->attributes['recepciones_count'] > 0;
        }

        return $this->recepciones()->exists();
    }

    public function puedeAnularse(): bool
    {
        return strtoupper(trim((string) $this->estado_compra)) !== 'ANULADA'
            && (string) $this->estado_recepcion === 'EN_TRANSITO'
            && !$this->tieneRecepciones();
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedore::class, 'proveedor_id');
    }


}
