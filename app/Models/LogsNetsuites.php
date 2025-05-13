<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsNetsuites extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'respuesta_id',
        'fecha',
        'error',
        'mensaje'
    ];
}
