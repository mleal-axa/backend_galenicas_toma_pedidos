<?php

namespace App\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promociones extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'netsuite_id',
        'nombre',
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'descuento',
        'id_busqueda_items',
        'nombre_busqueda_items',
        'descripcion',
        'ubicaciones',
        'isinactive'
    ];

    public function categorias(){
        return $this->belongsToMany(CategoriaPromocion::class, 'promocion_id');
    }
}
