<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoIntegracion extends Model
{
    use HasFactory;

    protected $fillable = [
        'integracion_id',
        'producto_id',
        'active'
    ];
}
