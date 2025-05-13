<?php

namespace App\Http\Controllers;

use App\Models\MediosPagos;
use Illuminate\Http\Request;

class MediosPagosController extends Controller
{

    public function get()
    {
        return response()->json(array(
            "data" => MediosPagos::select('netsuite_id AS value', 'nombre AS label')->where('estado', 1)->orderBy('id')->get()
        ));
    }

}
