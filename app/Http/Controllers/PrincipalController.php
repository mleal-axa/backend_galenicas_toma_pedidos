<?php

namespace App\Http\Controllers;

use App\Models\ListaPrecio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrincipalController extends Controller
{

    public function getListasPrecios()
    {
        return response()->json(array(
            "data" => ListaPrecio::select('netsuite_id AS value', 'nombre AS label')->where('estado', 1)->orderBy('id')->get()
        ));
    }

    public function getCatalogo($lista)
    {
        $retorno = array();
        $datos = DB::select("SELECT c.id, c.producto_netsuite_id, c.producto, c.ean, c.linea, c.embalaje,
        c.precio_venta, c.iva, c.precion_con_iva, c.tipo, c.es_lote, c.isinactive
        FROM catalogos AS c
        WHERE c.lista_precio_id = $lista");
        if(count($datos) > 0) {
            foreach ($datos as $key => $value) {
                $retorno[] = array(
                    'id' => $value->id,
                    'product_id' => $value->producto_netsuite_id,
                    'producto' => $value->producto,
                    'ean' => $value->ean,
                    'linea' => $value->linea,
                    'embalaje' => $value->embalaje,
                    'tipo' => $value->tipo,
                    'es_lote' => $value->es_lote,
                    'precio_unitario' => "$" . number_format($value->precio_venta, 0, ",", "."),
                    'iva' => $value->iva,
                    'precio' => "$" . number_format($value->precion_con_iva, 0, ",", "."),
                );
            }
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function getInventario($lista, $ubicacion)
    {
        $retorno = array();
        $datos = DB::select("SELECT c.id, c.producto_netsuite_id, c.producto, c.ean,
        CASE WHEN c.tipo LIKE '%K%'
        THEN ik.disponible ELSE i.disponible END AS disponible
        FROM catalogos AS c
        LEFT JOIN inventarios AS i ON c.producto_netsuite_id = i.producto_netsuite_id AND i.id_ubicacion = $ubicacion
        LEFT JOIN inventario_kits AS ik ON c.producto_netsuite_id = ik.producto_netsuite_id AND ik.id_ubicacion = $ubicacion
        WHERE c.lista_precio_id = $lista");
        if(count($datos) > 0) {
            foreach ($datos as $key => $value) {
                $retorno[] = array(
                    'id' => $value->id,
                    'product_id' => $value->producto_netsuite_id,
                    'producto' => $value->producto,
                    'ean' => $value->ean,
                    'inventario' => $value->disponible
                );
            }
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

}
