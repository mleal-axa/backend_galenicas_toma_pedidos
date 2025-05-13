<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{

    public function get()
    {
        return response()->json(array(
            "data" => Ubicacion::select('netsuite_id AS value', 'nombre AS label', 'ciudad')->where('isinactive', 0)->orderBy('id')->get(),
        ));
    }

}
