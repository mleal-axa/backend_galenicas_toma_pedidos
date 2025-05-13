<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{

    public function all(){
        return response()->json(array(
            "data" => DB::select("SELECT netsuite_id AS value, CONCAT(documento, ' - ', nombre) AS label FROM clientes WHERE isinactive = 0 ORDER BY documento")
        ));
    }

    public function getDirecciones($id)
    {
        return response()->json(array(
            "data" => DB::select("SELECT netsuite_id AS value, nombre_sucursal AS label FROM direcciones_clientes WHERE isinactive = 0 AND cliente_netsuite_id = $id ORDER BY netsuite_id")
        ));
    }

    public function get($id)
    {
        $cliente = Cliente::where('netsuite_id', $id)->first();
        if($cliente){

            $metodos_pagos = array();
            if($cliente->mp_contado == 1){
                $metodos_pagos[] = array(
                    "value" => 1,
                    "label" => "CONTADO"
                );
            } if($cliente->mp_credito == 1){
                $metodos_pagos[] = array(
                    "value" => 2,
                    "label" => "CREDITO"
                );
            }

            return response()->json(array(
                "data" => array(
                    "netsuite_id" => $cliente->netsuite_id,
                    "nombre" => $cliente->nombre,
                    "categoria" => $cliente->categoria,
                    "email" => $cliente->correo_electronico,
                    "documento" => $cliente->documento,
                    "telefono" => $cliente->telefono,
                    "lista_precio_id" => $cliente->lista_precio_id,
                    "lista_precio" => ($cliente->listaPrecio) ? $cliente->listaPrecio->nombre : '',
                    "metodos_pagos" => $metodos_pagos
                )
            ));
        } else {
            return response()->json(array(
                "data" => array()
            ));
        }
    }

}
