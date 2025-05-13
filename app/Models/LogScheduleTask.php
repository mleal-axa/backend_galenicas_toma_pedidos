<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogScheduleTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'proceso',
        'tabla',
        'error',
        'cant_registro',
        'cant_insertados',
        'cant_actualizados',
        'fecha',
        'fecha_inicio',
        'fecha_fin',
        'tiempo',
        'mensaje_error'
    ];
}
