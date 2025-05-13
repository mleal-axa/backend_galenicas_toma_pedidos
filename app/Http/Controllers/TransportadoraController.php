<?php

namespace App\Http\Controllers;

use App\Models\Transportadoras;
use Illuminate\Http\Request;

class TransportadoraController extends Controller
{

    public function get()
    {
        return response()->json(array(
            "data" => Transportadoras::select('netsuite_id AS value', 'nombre AS label')->where('estado', 1)->orderBy('id')->get()
        ));
    }

}
