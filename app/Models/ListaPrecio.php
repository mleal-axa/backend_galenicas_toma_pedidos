<?php

namespace App\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaPrecio extends Model
{
    use HasFactory, MassUpdatable;

    protected $fillable = [
        'netsuite_id',
        'nombre',
        'estado'
    ];
}
