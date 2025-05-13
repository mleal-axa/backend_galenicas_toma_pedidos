<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallesInventarios extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'ean',
        'nombre_item',
        'id_lote',
        'lote',
        'id_bin',
        'bin',
        'ubicacion_id',
        'ubicacion',
        'estado',
        'saldo',
        'disponible',
        'es_producto_numerado_por_lote',
        'fecha_vencimiento',
        'inactivo'
    ];
}
