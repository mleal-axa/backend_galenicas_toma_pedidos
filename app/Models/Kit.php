<?php

namespace App\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kit extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'netsuite_id',
        'nombre',
        'ean',
        'linea',
        'centro_de_costo',
        'componente_id',
        'componente',
        'cantidad_kit',
        'cantidad_regular',
        'cantidad_regalo',
        'isinactive'
    ];
}
