<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\LogsNetsuites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministradorController extends Controller
{

    public function getPedido($id)
    {
        $retorno = array();
        $data = DB::select("SELECT p.id, u.name AS usuario, CONCAT(c.documento, ' - ', c.nombre) AS cliente,
        dc.direccion_corta AS direccion, p.cantidad_items, p.cantidad_items_solicitado,
        p.total, p.metodo_pago, p.estado, p.netsuite_id
        FROM pedidos AS p
        LEFT JOIN users AS u ON p.user_id = u.id
        LEFT JOIN clientes AS c ON p.cliente_id = c.netsuite_id
        LEFT JOIN direcciones_clientes AS dc ON p.direccion_id = dc.netsuite_id
        WHERE p.id = $id");

        if(count($data) > 0){
            foreach ($data as $key => $value) {

                $retorno[] = array(
                    'id' => $value->id,
                    'netsuite_id' => $value->netsuite_id,
                    'comercial' => $value->usuario,
                    'cliente' => $value->cliente,
                    'direccion' => $value->direccion,
                    'cantidad_items' => $value->cantidad_items,
                    'cantidad_items_solicitado' => $value->cantidad_items_solicitado,
                    'total' => "$" . number_format($value->total, 0, ",", "."),
                    'metodo_pago' => Helper::getMetodoPago($value->metodo_pago),
                    'estado' => Helper::getEstadoPedido($value->estado),
                    'estado_numero' => $value->estado
                );

            }
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function allPedidos(Request $request)
    {
        $retorno = array();
        $sql = "SELECT p.id, u.name AS usuario, CONCAT(c.documento, ' - ', c.nombre) AS cliente,
        dc.direccion_corta AS direccion, p.cantidad_items, p.cantidad_items_solicitado,
        p.total, p.metodo_pago, p.estado, p.netsuite_id
        FROM pedidos AS p
        LEFT JOIN users AS u ON p.user_id = u.id
        LEFT JOIN clientes AS c ON p.cliente_id = c.netsuite_id
        LEFT JOIN direcciones_clientes AS dc ON p.direccion_id = dc.netsuite_id
        WHERE p.fecha BETWEEN '$request->fecha_inicio' AND '$request->fecha_fin'";

        if($request->comercial){
            $sql .= " AND p.user_id = $request->comercial";
        }

        if($request->cliente) {
            $sql .= " AND p.cliente_id = $request->cliente";
        }

        if($request->estado) {
            $sql .= " AND p.estado = $request->estado";
        }

        $data = DB::select($sql);
        if(count($data) > 0){
            foreach ($data as $key => $value) {

                $retorno[] = array(
                    'id' => $value->id,
                    'netsuite_id' => $value->netsuite_id,
                    'comercial' => $value->usuario,
                    'cliente' => $value->cliente,
                    'direccion' => $value->direccion,
                    'cantidad_items' => $value->cantidad_items,
                    'cantidad_items_solicitado' => $value->cantidad_items_solicitado,
                    'total' => "$" . number_format($value->total, 0, ",", "."),
                    'metodo_pago' => Helper::getMetodoPago($value->metodo_pago),
                    'estado' => Helper::getEstadoPedido($value->estado),
                    'estado_numero' => $value->estado
                );

            }
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function getErrorNs($id)
    {
        return response()->json(array(
            "data" => LogsNetsuites::where('tipo', 'FACTURA')->where('respuesta_id', $id)->orderBy('id', 'DESC')->first()
        ));
    }

}
