<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemsPromociones extends Model
{
    use HasFactory;

    protected $fillable = [
        'promocion_id',
        'promocion_netsuite_id',
        'producto_netsuite_id',
        'producto'
    ];
}
