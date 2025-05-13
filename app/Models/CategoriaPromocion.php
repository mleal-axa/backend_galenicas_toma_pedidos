<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaPromocion extends Model
{
    use HasFactory;

    protected $fillable = [
        'promocion_id',
        'nombre',
    ];
}
