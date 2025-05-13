<?php

namespace App\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalogo extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'producto_netsuite_id',
        'producto',
        'ean',
        'tipo',
        'lista_precio_id',
        'linea',
        'precio_venta',
        'tasa_iva',
        'iva',
        'precion_con_iva',
        'cantidad_maxima',
        'cantidad_minima',
        'embalaje',
        'es_lote',
        'es_controlado',
        'isinactive'
    ];
}
