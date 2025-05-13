<?php

namespace App\Http\Controllers;

use App\Models\Medicos;
use Illuminate\Http\Request;

class MedicosController extends Controller
{

    public function get()
    {
        return response()->json(array(
            "data" => Medicos::select('netsuite_id AS value', 'medico AS label')->where('isinactive', 0)->orderBy('id')->get()
        ));
    }

}
