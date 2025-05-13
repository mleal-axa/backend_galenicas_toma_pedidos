<?php

namespace App\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DireccionesCliente extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'cliente_netsuite_id',
        'netsuite_id',
        'nombre_sucursal',
        'direccion_completa',
        'direccion_corta',
        'barrio',
        'pais',
        'departamento',
        'ciudad',
        'isinactive'
    ];
}
