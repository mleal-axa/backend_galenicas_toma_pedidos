<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioKits extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_netsuite_id',
        'producto',
        'ean',
        'id_ubicacion',
        'ubicacion',
        'disponible'
    ];
}
