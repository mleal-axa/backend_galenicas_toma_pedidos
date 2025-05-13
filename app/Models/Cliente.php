<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Iksaku\Laravel\MassUpdate\MassUpdatable;

class Cliente extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'netsuite_id',
        'tipo',
        'nombre',
        'nombre_compania',
        'documento',
        'correo_electronico',
        'telefono',
        'cupo',
        'cupo_feria',
        'categoria_id',
        'categoria',
        'mp_credito',
        'mp_contado',
        'lista_precio_id',
        'isinactive'
    ];

    public function listaPrecio(){
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id', 'netsuite_id');
    }
}
