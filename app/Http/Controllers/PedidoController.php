<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Jobs\SendOrder;
use App\Models\Catalogo;
use App\Models\Cliente;
use App\Models\DetallesPedidos;
use App\Models\Pedidos;
use App\Models\ProductoIntegracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    private $abbott_url;
    public function __construct()
    {
        $abbott = config('services.Abbott');
        $this->abbott_url = $abbott['URL'];
    }

    public function getValoresEnvios()
    {
        return response()->json(array(
            "data" => [
                array(
                    'value' => 3500,
                    'label' => 'Envio Galenica - $3.500'
                ),
                array(
                    'value' => 5000,
                    'label' => 'Envio Galenica - $5.000'
                ),
            ]
        ));
    }

    public function getCatalogo($lista, $ubicacion)
    {
        $retorno = array();
        $datos = DB::select("SELECT c.id, c.producto_netsuite_id, c.producto, c.ean, c.linea, c.embalaje,
        CASE WHEN c.tipo LIKE '%K%'
        THEN ik.disponible ELSE i.disponible END AS disponible,
        c.precio_venta, c.iva, c.precion_con_iva, c.tipo, c.es_lote
        FROM catalogos AS c
        LEFT JOIN inventarios AS i ON c.producto_netsuite_id = i.producto_netsuite_id AND i.id_ubicacion = $ubicacion
        LEFT JOIN inventario_kits AS ik ON c.producto_netsuite_id = ik.producto_netsuite_id AND ik.id_ubicacion = $ubicacion
        WHERE c.lista_precio_id = $lista AND c.isinactive = 0 AND c.precion_con_iva > 0");
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
                    'inventario' => ($value->disponible == null) ? 0 : $value->disponible,
                    'precio_unitario' => "$" . number_format($value->precio_venta, 0, ",", "."),
                    'precio_unitario_calculable' => $value->precio_venta,
                    'iva' => $value->iva,
                    'precio' => "$" . number_format($value->precion_con_iva, 0, ",", "."),
                    'precio_calculable' => $value->precion_con_iva
                );
            }
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function getPromociones($producto, $categoria)
    {
        $retorno = DB::select("SELECT p.netsuite_id AS code, p.nombre AS name, p.descuento FROM items_promociones AS ip
        LEFT JOIN promociones AS p ON ip.promocion_id = p.id
        LEFT JOIN categoria_promocions AS cp ON p.id = cp.promocion_id
        WHERE ip.producto_netsuite_id = $producto AND p.isinactive = 0 AND cp.nombre = '$categoria'");
        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'cliente' => 'required|numeric',
            'direccion' => 'required|numeric',
            'ubicacion' => 'required|numeric',
            'lista_precio' => 'required|numeric',
            'fecha' => 'required|date',
            'medio_pago' => 'required|numeric',
            'metodo_pago' => 'required|numeric',
            'cantidad_items' => 'required|numeric|min:1',
            'cantidad_items_solicitado' => 'required|numeric|min:1',
            'subtotal' => 'required|numeric|min:1',
            'total' => 'required|numeric|min:1',
            'envio' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
        ], [
            'items.min' => 'Lo sentimos, debes tener agregado al menos un item para guardar el pedido!'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'data' => []
            ]);
        }

        $crear_header = array(
            'user_id' => Auth::user()->id,
            'cliente_id' => $request->cliente,
            'direccion_id' => $request->direccion,
            'ubicacion_id' => $request->ubicacion,
            'lista_precio_id' => $request->lista_precio,
            'fecha' => $request->fecha,
            'nota' => $request->nota,
            'transportadora_id' => $request->transportadora,
            'medio_pago_id' => $request->medio_pago,
            'medico_id' => empty($request->medico) ? null : intval($request->medico),
            'metodo_pago' => $request->metodo_pago,
            'estado' => 1,
            'cantidad_items' => $request->cantidad_items,
            'cantidad_items_solicitado' => $request->cantidad_items_solicitado,
            'impuesto' => intval($request->impuesto),
            'subtotal' => intval($request->subtotal),
            'descuento' => intval($request->descuento),
            'envio' => intval($request->envio),
            'total' => intval($request->total)
        );

        $pedido_id = null;
        $array_product_abbott = [];
        try {
            if($result_crete = Pedidos::create($crear_header)) {

                $pedido_id = $result_crete->id;
                if(count($request->items) > 0){
                    foreach ($request->items as $key => $item) {

                        $array_detalle = array(
                            'pedido_id' => $pedido_id,
                            'producto_netsuite_id' => $item["producto_id"],
                            'producto_nombre' => $item["producto"],
                            'producto_ean' => $item["ean"],
                            'tipo_producto' => $item["tipo"],
                            'cantidad' => $item["cantidad"],
                            'precio_unitario' => $item["precio_unitario"],
                            'iva' => $item["iva"],
                            'precio' => intval($item["precio_final"]),
                            'promocion_netsuite_id' => $item["promocion_id"],
                            'promocion' => $item["promocion"],
                            'porcentaje_descuento' => $item["porcentaje_descuento"],
                            'valor_descuento' => intval($item["descuento"]),
                            'subtotal' => intval($item["subtotal"]),
                            'total' => intval($item["total"]),
                            'es_lote' => $item["es_lote"],
                            'active' => 1
                        );
                        DetallesPedidos::create($array_detalle);

                        $product = Catalogo::where('producto_netsuite_id', intval($item["producto_id"]))->first();
                        

                        
                        $exist = ProductoIntegracion::where('producto_id', $product->id)->exists();
                        
                        
                        if ($exist) {
                            $array_product_abbott[] = [
                                'ean' => $item["ean"],
                                'quantity' => $item["cantidad"]
                            ];
                        }                           
                    }
                }

                if($request->type == 2) {

                    $pedido_nuevo = Pedidos::findOrFail($pedido_id);
                    $pedido_nuevo->estado = 3;
                    $pedido_nuevo->save();

                    $sendOrder = SendOrder::dispatch($pedido_id)->onQueue('tp_galenica')->onConnection('tp_galenica_project');

                    if($sendOrder){
                        
                        if (!empty($array_product_abbott)) {
                            $responseTransaction = '';
                            
                            $responseBody = $this->sendValidateClientAbbot($request->cliente,$request->ubicacion);
                            
                            if ($responseBody['status'] == 202 && $responseBody['info']['Status']['Code'] == 0 ) {
                                $reponseProduct = $this->sendProductsAbbott($array_product_abbott, $responseBody, $request->cliente);
                                
                                if ( $reponseProduct['status'] == 202 && isset($reponseProduct['info']['Status']['Code']) && $reponseProduct['info']['Status']['Code'] == 0 ) {
                                    $responseTransaction = $this->sendValidatePurchaseAbbot($reponseProduct);
                                    if ( $responseTransaction['status'] == 202 && isset($responseTransaction['info']['Status']['Code']) && $responseTransaction['info']['Status']['Code'] == 0 ) {
                                        $error = 0;
                                    }
                                }
                            }
                        }
                    }                    
                }

            } else {
                return response()->json([
                    'success' => false,
                    'errors' => array(
                        "error" => "Error, se presento un problema al crear el pedido!"
                    ),
                    'data' => []
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json($th);
        }

        return response()->json([
            'success' => true,
            'errors' => array(),
            'data' => $pedido_id
        ]);
    }

    public function sendValidateClientAbbot($clienteId,$ubicacion_id){
        try {
            $cliente = Cliente::where('netsuite_id',$clienteId)->first();
            $url = $this->abbott_url.'client-validation';
            
            $data = [
                'validateClient' =>json_encode( [
                    'document' => (string) $cliente->documento,
                    'code_warehouse' => '01GALBTA',
                    'employee' => 'toma_pedido',
                    'warehouse' => $ubicacion_id,
                ])
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($url, $data);

            return $response->json();
        
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'info' => 'Opss, Ocurrió un error en el método sendValidateClientAbbot del controller CarroController',
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendProductsAbbott($products, $clientValidate, $clienteId){
        try {
            $cliente = Cliente::where('netsuite_id',$clienteId)->first();
            $url = $this->abbott_url.'quotation-transaction';

            $data = [
                'products' => $products,
                'code_warehouse' => $clientValidate['warehouse'],
                'document' => (string) $cliente->documento,
                'token' => $clientValidate['info']['Data']['Token']
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($url, $data);
            
            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'info' => 'Opss, Ocurrió un error en el método sendProductsAbbott del controller CarroController',
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendValidatePurchaseAbbot($transaction){
        try {

            $url = $this->abbott_url.'confirm-quotation';
            $data = [
                'transaction_id' => $transaction['transaction']['original']['transaction_id']
            ];
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($url, $data);
            
            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'info' => 'Opss, Ocurrió un error en el método sendValidatePurchaseAbbot del controller CarroController',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getPedidosUser($fecha_inicio = null, $fecha_fin = null)
    {

        if($fecha_inicio == 'null' || $fecha_fin == 'null') {
            $dayOfWeek = date('w', strtotime(date('Y-m-d')));
            $fecha_inicio = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', strtotime(date('Y-m-d'))));
            $fecha_fin = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days', strtotime(date('Y-m-d'))));
        }

        $user_id = Auth::user()->id;
        $retorno = array();
        $data = DB::select("SELECT p.id, u.name AS usuario, CONCAT(c.documento, ' - ', c.nombre) AS cliente,
        dc.direccion_corta AS direccion, p.cantidad_items, p.cantidad_items_solicitado,
        p.total, p.metodo_pago, p.estado, p.netsuite_id
        FROM pedidos AS p
        LEFT JOIN users AS u ON p.user_id = u.id
        LEFT JOIN clientes AS c ON p.cliente_id = c.netsuite_id
        LEFT JOIN direcciones_clientes AS dc ON p.direccion_id = dc.netsuite_id
        WHERE p.user_id = $user_id AND p.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");

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

    public function get($id)
    {
        $retorno = array();
        $pedido = Pedidos::find($id);
        if($pedido) {

            $items = array();
            if(count($pedido->productos) > 0){
                foreach ($pedido->productos as $key => $producto) {

                    $items[] = array(
                        'producto_id' => $producto->producto_netsuite_id,
                        'producto' => $producto->producto_nombre,
                        'ean' => $producto->producto_ean,
                        'cantidad' => $producto->cantidad,
                        'precio_unitario' => "$" . number_format($producto->precio_unitario, 0, ",", "."),
                        'iva' => $producto->iva,
                        'precio_final' => "$" . number_format($producto->precio, 0, ",", "."),
                        'promocion' => $producto->promocion,
                        'porcentaje_descuento' => ($producto->porcentaje_descuento == 0) ? '' : $producto->porcentaje_descuento,
                        'descuento' => ($producto->valor_descuento == 0) ? '' : "$" . number_format($producto->valor_descuento, 0, ",", "."),
                        'subtotal' => "$" . number_format($producto->subtotal, 0, ",", "."),
                        'total' => "$" . number_format($producto->total, 0, ",", "."),
                    );

                }
            }

            $retorno = array(
                'cliente_nombre' => ($pedido->cliente) ? $pedido->cliente->documento . ' - ' . $pedido->cliente->nombre : '',
                'direccion' => ($pedido->direccion) ? $pedido->direccion->direccion_corta : '',
                'fecha' => $pedido->fecha,
                'ubicacion' => ($pedido->ubicacion) ? $pedido->ubicacion->nombre : '',
                'lista_precio' => ($pedido->listaPrecio) ? $pedido->listaPrecio->nombre : '',
                'nota' => $pedido->nota,
                'items' => $pedido->cantidad_items,
                'items_solicitados' => $pedido->cantidad_items_solicitado,
                'impuesto' => "$" . number_format($pedido->impuesto, 0, ",", "."),
                'subtotal' => "$" . number_format($pedido->subtotal, 0, ",", "."),
                'descuento' => "$" . number_format($pedido->descuento, 0, ",", "."),
                'envio' => "$" . number_format($pedido->envio, 0, ",", "."),
                'total' => "$" . number_format($pedido->total, 0, ",", "."),
                'cliente' => ($pedido->cliente) ? array(
                    'nombre' => $pedido->cliente->nombre,
                    'categoria' => $pedido->cliente->categoria,
                    'email' => $pedido->cliente->correo_electronico,
                    'documento' => $pedido->cliente->documento,
                    'telefono' => $pedido->telefono
                ) : array(),
                'transportadora' => ($pedido->transportadora) ? $pedido->transportadora->nombre : '',
                'medio_pago' => ($pedido->medioPago) ? $pedido->medioPago->nombre : '',
                'metodo_pago' => Helper::getMetodoPago($pedido->metodo_pago),
                'medico' => ($pedido->medico) ? $pedido->medico->medico : '',
                'productos' => $items
            );
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function cancel($id)
    {
        $succes = true;
        $errors = "";
        $data = array();

        $pedido = Pedidos::find($id);
        if($pedido) {

            $actualizar = array(
                'estado' => 2,
                'user_id_canceled' => Auth::user()->id
            );
            Pedidos::findOrFail($id)->update($actualizar);

            $pedido_nuevo = Pedidos::find($id);
            $data = array(
                'id' => $pedido_nuevo->id,
                'estado' => Helper::getEstadoPedido($pedido_nuevo->estado),
                'estado_numero' => $pedido_nuevo->estado
            );

        } else {
            $succes = false;
            $errors = "Error, no pudimos encontrar el Pedido #$id, para poder cancelarlo!";
        }

        return response()->json([
            'success' => $succes,
            'errors' => $errors,
            'data' => $data
        ]);
    }

    public function getEditar($id)
    {
        $retorno = array();
        $pedido = Pedidos::find($id);
        if($pedido) {

            $items = array();
            if(count($pedido->productos) > 0){
                foreach ($pedido->productos as $key => $producto) {

                    $val_catalogo = DB::select("SELECT c.id,
                    CASE WHEN c.tipo LIKE '%K%'
                    THEN ik.disponible ELSE i.disponible END AS disponible, c.tipo, c.es_lote
                    FROM catalogos AS c
                    LEFT JOIN inventarios AS i ON c.producto_netsuite_id = i.producto_netsuite_id AND i.id_ubicacion = $pedido->ubicacion_id
                    LEFT JOIN inventario_kits AS ik ON c.producto_netsuite_id = ik.producto_netsuite_id AND ik.id_ubicacion = $pedido->ubicacion_id
                    WHERE c.lista_precio_id = $pedido->lista_precio_id AND c.isinactive = 0 AND c.precion_con_iva > 0
                    AND c.producto_netsuite_id = $producto->producto_netsuite_id");

                    if(count($val_catalogo) > 0) {
                        $items[] = array(
                            'id' => $val_catalogo[0]->id,
                            'producto_id' => $producto->producto_netsuite_id,
                            'producto' => $producto->producto_nombre,
                            'ean' => $producto->producto_ean,
                            'tipo' => $val_catalogo[0]->tipo,
                            'es_lote' => $val_catalogo[0]->es_lote,
                            'cantidad' => $producto->cantidad,
                            'precio_unitario' => $producto->precio_unitario,
                            'iva' => $producto->iva,
                            'precio_final' => $producto->precio,
                            'inventario' => $val_catalogo[0]->disponible,
                            'promocion_id' => empty($producto->promocion_netsuite_id) ? null : $producto->promocion_netsuite_id,
                            'promocion' => empty($producto->promocion) ? '' : $producto->promocion,
                            'porcentaje_descuento' => $producto->porcentaje_descuento,
                            'descuento' => $producto->valor_descuento,
                            'subtotal' => $producto->subtotal,
                            'total' => $producto->total
                        );
                    }

                }
            }

            $metodos_pagos = array();
            if($pedido->cliente->mp_contado == 1){
                $metodos_pagos[] = array(
                    "value" => 1,
                    "label" => "CONTADO"
                );
            } if($pedido->cliente->mp_credito == 1){
                $metodos_pagos[] = array(
                    "value" => 2,
                    "label" => "CREDITO"
                );
            }

            $valor_envio = array();
            if(intval($pedido->envio) == 3500) {
                $valor_envio = array(
                    "value" => 3500,
                    "label" => 'Envio Galenica - $3.500'
                );
            } else if(intval($pedido->envio) == 5000) {
                $valor_envio = array(
                    "value" => 5000,
                    "label" => 'Envio Galenica - $5.000'
                );
            }

            $retorno = array(
                'id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'cliente_nombre' => ($pedido->cliente) ? $pedido->cliente->documento . ' - ' . $pedido->cliente->nombre : '',
                'direccion_id' => $pedido->direccion_id,
                'direccion' => ($pedido->direccion) ? $pedido->direccion->direccion_corta : '',
                'fecha' => $pedido->fecha,
                'ubicacion' => ($pedido->ubicacion) ? $pedido->ubicacion->nombre : '',
                'ubicacion_id' => $pedido->ubicacion_id,
                'lista_precio' => ($pedido->listaPrecio) ? $pedido->listaPrecio->nombre : '',
                'lista_id' => $pedido->lista_precio_id,
                'nota' => $pedido->nota,
                'cliente' => ($pedido->cliente) ? array(
                    'nombre' => $pedido->cliente->nombre,
                    'categoria' => $pedido->cliente->categoria,
                    'email' => $pedido->cliente->correo_electronico,
                    'documento' => $pedido->cliente->documento,
                    'telefono' => $pedido->telefono
                ) : array(),
                'transportadora' => $pedido->transportadora_id,
                'medio_pago' => $pedido->medio_pago_id,
                'metodo_pago' => $pedido->metodo_pago,
                'medico' => $pedido->medico_id,
                'metodos_pagos' => $metodos_pagos,
                'envio' => $pedido->envio,
                'valor_envio' => $valor_envio,
                'productos' => $items
            );
        }

        return response()->json(array(
            "data" => $retorno
        ));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'medio_pago' => 'required|numeric',
            'metodo_pago' => 'required|numeric',
            'cantidad_items' => 'required|numeric|min:1',
            'cantidad_items_solicitado' => 'required|numeric|min:1',
            'subtotal' => 'required|numeric|min:1',
            'total' => 'required|numeric|min:1',
            'items' => 'required|array|min:1',
        ], [
            'items.min' => 'Lo sentimos, debes tener agregado al menos un item para guardar el pedido!'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'data' => []
            ]);
        }

        $pedido = Pedidos::find($request->id);
        if($pedido) {

            $actualizar = array(
                'nota' => $request->nota,
                'transportadora_id' => $request->transportadora,
                'medio_pago_id' => $request->medio_pago,
                'metodo_pago' => $request->metodo_pago,
                'medico_id' => $request->medico,
                'estado' => 1,
                'cantidad_items' => $request->cantidad_items,
                'cantidad_items_solicitado' => $request->cantidad_items_solicitado,
                'impuesto' => $request->impuesto,
                'subtotal' => $request->subtotal,
                'descuento' => $request->descuento,
                'envio' => $request->envio,
                'total' => $request->total,
                'user_id_updated' => Auth::user()->id
            );

            if(Pedidos::findOrFail($request->id)->update($actualizar)) {

                $pedido_id = $request->id;
                if(count($request->items) > 0){
                    DetallesPedidos::where('pedido_id', $request->id)->delete();
                    foreach ($request->items as $key => $item) {
                        $array_detalle = array(
                            'pedido_id' => $pedido_id,
                            'producto_netsuite_id' => $item["producto_id"],
                            'producto_nombre' => $item["producto"],
                            'producto_ean' => $item["ean"],
                            'tipo_producto' => $item["tipo"],
                            'cantidad' => $item["cantidad"],
                            'precio_unitario' => $item["precio_unitario"],
                            'iva' => $item["iva"],
                            'precio' => $item["precio_final"],
                            'promocion_netsuite_id' => $item["promocion_id"],
                            'promocion' => $item["promocion"],
                            'porcentaje_descuento' => $item["porcentaje_descuento"],
                            'valor_descuento' => $item["descuento"],
                            'subtotal' => $item["subtotal"],
                            'total' => $item["total"],
                            'es_lote' => $item["es_lote"],
                            'active' => 1
                        );
                        DetallesPedidos::create($array_detalle);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'errors' => array(),
                'data' => null
            ]);

        } else {
            return response()->json([
                'success' => false,
                'errors' => array('error' => 'Se presento un error, no pudimos encontrar el pedido!'),
                'data' => []
            ]);
        }
    }

    public function getEstados()
    {
        $data = array();
        foreach (Helper::getDataEstadoPedido() as $key => $value) {
            $data[] = array(
                "value" => $key,
                "label" => $value
            );
        }
        return response()->json(array(
            "data" => $data
        ));
    }

    public function enviarPedidoNetsuite($id)
    {
        $succes = true; $errors = '';
        $data = array();
        if($pedido = Pedidos::find($id)) {

            $pedido->estado = 3;
            $pedido->save();

            $data = array(
                'id' => $id,
                'estado' => Helper::getEstadoPedido(3),
                'estado_numero' => 3
            );

            if(!SendOrder::dispatch($id)->onQueue('tp_galenica')->onConnection('tp_galenica_project')){
                $succes = false;
                $errors = 'Error, se presento un problema en el JOB para enviar el pedido!';
            }else{
                $array_product_abbott = [];
                $detallesPedido = DetallesPedidos::where('pedido_id', $id)->get();
                foreach($detallesPedido as $detallePedido){
                    
                    $product = Catalogo::where('producto_netsuite_id', intval($detallePedido->producto_netsuite_id))->first();
                    $exist = ProductoIntegracion::where('producto_id', $product->id)->exists();
                    
                    if ($exist) {
                        $array_product_abbott[] = [
                            'ean' => $detallePedido->producto_ean,
                            'quantity' => $detallePedido->cantidad
                        ];
                    }      
                }

                if (!empty($array_product_abbott)) {
                    $responseTransaction = '';
                    
                    $responseBody = $this->sendValidateClientAbbot($pedido->cliente_id,$pedido->ubicacion_id);
                    
                    if ($responseBody['status'] == 202 && $responseBody['info']['Status']['Code'] == 0 ) {
                        $reponseProduct = $this->sendProductsAbbott($array_product_abbott, $responseBody, $pedido->cliente_id);
                        
                        if ( $reponseProduct['status'] == 202 && isset($reponseProduct['info']['Status']['Code']) && $reponseProduct['info']['Status']['Code'] == 0 ) {
                            $responseTransaction = $this->sendValidatePurchaseAbbot($reponseProduct);
                            
                            if ( $responseTransaction['status'] == 202 && isset($responseTransaction['info']['Status']['Code']) && $responseTransaction['info']['Status']['Code'] == 0 ) {
                                $error = 0;
                            }
                        }
                    }
                }
            }

        } else {
            $succes = false;
            $errors = 'Error, no pudimos encontrar el pedido para enviarlo!';
        }

        return response()->json([
            'success' => $succes,
            'errors' => $errors,
            'data' => $data
        ]);
    }

    public function updateEstado(Request $request)
    {
        $succes = true;
        $errors = "";
        $data = array();

        $pedido = Pedidos::find($request->id);
        if($pedido) {

            $actualizar = array(
                'estado' => $request->estado,
                'user_id_updated' => Auth::user()->id
            );
            if($request->id_netsuite) {
                $actualizar["netsuite_id"] = $request->id_netsuite;
            }
            Pedidos::findOrFail($request->id)->update($actualizar);

            $pedido_nuevo = Pedidos::find($request->id);
            $data = array(
                'id' => $pedido_nuevo->id,
                'estado' => Helper::getEstadoPedido($pedido_nuevo->estado),
                'estado_numero' => $pedido_nuevo->estado
            );

        } else {
            $succes = false;
            $errors = "Error, no pudimos encontrar el Pedido #$request->id, para poder actualizar el estado!";
        }

        return response()->json([
            'success' => $succes,
            'errors' => $errors,
            'data' => $data
        ]);
    }

}
