<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallesPedidos extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'producto_netsuite_id',
        'producto_nombre',
        'producto_ean',
        'tipo_producto',
        'cantidad',
        'precio_unitario',
        'iva',
        'precio',
        'promocion_netsuite_id',
        'promocion',
        'porcentaje_descuento',
        'valor_descuento',
        'subtotal',
        'total',
        'es_lote',
        'active'
    ];
}
