<?php

namespace App\Jobs;

use App\Models\Catalogo;
use App\Models\DetallesInventarios;
use App\Models\Kit;
use App\Models\LogsNetsuites;
use App\Models\Pedidos;
use App\Services\Netsuite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pedido_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pedido_id)
    {
        $this->pedido_id = $pedido_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->send($this->pedido_id);
    }

    private function send($id)
    {
        $pedido = Pedidos::findOrFail($id);

        //agregar los items
        $items = array();
        $promociones_ids = array();
        if(count($pedido->productos) > 0){
            foreach ($pedido->productos as $key => $producto) {

                //validar si es kit
                if($producto->tipo_producto == 'Kit/Package') {
                    $producto_kit = Kit::where('netsuite_id', $producto->producto_netsuite_id)->first();
                    if($producto_kit) {
                        $producto_catalogo = Catalogo::where([
                            ['producto_netsuite_id', '=', $producto_kit->componente_id],
                            ['lista_precio_id', '=', $pedido->lista_precio_id]
                        ])->first();
                        if($producto_catalogo) {

                            //productos regular
                            $array_lotes = $this->obtenerLotes($producto_catalogo->producto_netsuite_id, $pedido->ubicacion_id, $producto_kit->cantidad_regular, $producto_catalogo->es_lote);
                            $items[] = array(
                                "id" => $producto_catalogo->producto_netsuite_id,
                                "cantidad" => $producto_kit->cantidad_regular,
                                "precio" => $producto_catalogo->precio_venta,
                                "ubicacion" => $pedido->ubicacion_id,
                                "es_lote" => $producto_catalogo->es_lote,
                                "items_lotes" => $array_lotes,
                                "es_promocion" => 0
                            );

                            if(!empty($producto->promocion_netsuite_id) && $producto->porcentaje_descuento > 0) {
                                $items[] = array(
                                    "id" => 322283,
                                    "cantidad" => 1,
                                    "precio" => "-".$producto->porcentaje_descuento."%",
                                    "ubicacion" => $pedido->ubicacion_id,
                                    "es_lote" => 0,
                                    "es_promocion" => 1
                                );
                            }

                            //productos regalo
                            $array_lotes = $this->obtenerLotes($producto_catalogo->producto_netsuite_id, $pedido->ubicacion_id, $producto_kit->cantidad_regalo, $producto_catalogo->es_lote);
                            $items[] = array(
                                "id" => $producto_catalogo->producto_netsuite_id,
                                "cantidad" => $producto_kit->cantidad_regalo,
                                "precio" => 0,
                                "ubicacion" => $pedido->ubicacion_id,
                                "es_lote" => $producto_catalogo->es_lote,
                                "items_lotes" => $array_lotes,
                                "es_promocion" => 0
                            );

                        }
                    }
                } else {

                    $array_lotes = $this->obtenerLotes($producto->producto_netsuite_id, $pedido->ubicacion_id, $producto->cantidad, $producto->es_lote);
                    $items[] = array(
                        "id" => $producto->producto_netsuite_id,
                        "cantidad" => $producto->cantidad,
                        "precio" => $producto->precio_unitario,
                        "ubicacion" => $pedido->ubicacion_id,
                        "es_lote" => $producto->es_lote,
                        "items_lotes" => $array_lotes,
                        "es_promocion" => 0
                    );

                    if(!empty($producto->promocion_netsuite_id) && $producto->porcentaje_descuento > 0) {
                        $items[] = array(
                            "id" => 322283,
                            "cantidad" => 1,
                            "precio" => "-".$producto->porcentaje_descuento."%",
                            "ubicacion" => $pedido->ubicacion_id,
                            "es_lote" => 0,
                            "es_promocion" => 1
                        );
                    }

                }

            }
        }

        //agregando el header de informacion
        $array_json = array(
            "customform" => 407,
            "cliente" => $pedido->cliente_id,
            "direccion_id" => $pedido->direccion_id,
            "comentarios" => $pedido->nota,
            "numero_pedido" => $pedido->id,
            "metodo_pago" => $pedido->metodo_pago,
            "clase" => 1707, // ECOMERX SAS : GALENICA
            "ubicacion" => $pedido->ubicacion_id,
            "vendedor_id" => ($pedido->usuario) ? $pedido->usuario->netsuite_id : null,
            "medio_pago" => $pedido->medio_pago_id,
            "tipo_envio" => 322182,//304543, // Envio Galenica
            "valor_envio" => intval($pedido->envio),
            "transportadora" => $pedido->transportadora_id,
            "medico_id" => $pedido->medico_id,
            "items" => $items
        );

        //configurando para enviar a netsuite
        $json = json_encode($array_json);
        $resultado_service = Netsuite::post(2182, $json);
        $decodificado = json_decode($resultado_service, true);
        if($decodificado["message"] == 'success'){

            $id_netsuite = $decodificado["response"]["id"];
            $num_factura = $decodificado["response"]["num"];

            $pedido->netsuite_id = $id_netsuite;
            $pedido->numero_factura = $num_factura;
            $pedido->estado = 4;
            $pedido->fecha_respuesta_netsuite = date('Y-m-d h:i:s a');
            $pedido->save();

            //generar log
            $mensaje = 'CREACION FACTURA #'. $pedido->id . ', Mensaje: Factura creada correctamente, generado con el ID NETSUITE #' . $id_netsuite . ' y NUMERO FACTURA #' . $num_factura;
            $agg_info = array(
                'tipo' => 'FACTURA',
                'respuesta_id' => $pedido->id,
                'fecha' => date('Y-m-d'),
                'error' => 'Si',
                'mensaje' => $mensaje
            );
            LogsNetsuites::create($agg_info);

        } else {

            $pedido->estado = 5;
            $pedido->save();

            //generar log
            $mensaje = 'ERROR CREACION FACTURA #'. $pedido->id . ', Mensaje: ' .$decodificado["response"];
            $agg_info = array(
                'tipo' => 'FACTURA',
                'respuesta_id' => $pedido->id,
                'fecha' => date('Y-m-d'),
                'error' => 'Si',
                'mensaje' => $mensaje
            );
            LogsNetsuites::create($agg_info);
        }
    }

    private function obtenerLotes($producto_id, $ubicacion_id, $cantidad, $es_lote)
    {
        $array_lotes = array();
        $cantidad_restante = $cantidad;
        if($es_lote == 1){
            $verificacion = DetallesInventarios::where([
                ['item_id', '=', $producto_id],
                ['ubicacion_id', '=', $ubicacion_id]
            ])
            ->whereNotNull('id_lote')
            ->whereNotNull('id_bin')
            ->orderBy('disponible', 'desc')
            ->get();
            if(count($verificacion) > 0){
                foreach ($verificacion as $key => $val_veri) {
                    if($val_veri->disponible >= $cantidad){
                        $array_lotes[] = array(
                            "id_lote" => ($val_veri->id_lote == 0) ? null : $val_veri->id_lote,
                            "id_bin" => ($val_veri->id_bin == 0) ? null : $val_veri->id_bin,
                            "cantidad_disponible" => $cantidad
                        );
                        break;
                    } else {
                        if($cantidad_restante < 0){
                            //print("numero negativo: " . $cantidad_restante . "<br>");
                        } else {
                            if($cantidad_restante > $val_veri->disponible){
                                $array_lotes[] = array(
                                    "id_lote" => ($val_veri->id_lote == 0) ? null : $val_veri->id_lote,
                                    "id_bin" => ($val_veri->id_bin == 0) ? null : $val_veri->id_bin,
                                    "cantidad_disponible" => $val_veri->disponible
                                );

                                $cantidad_restante = $cantidad_restante - $val_veri->disponible;
                            } else {
                                $array_lotes[] = array(
                                    "id_lote" => ($val_veri->id_lote == 0) ? null : $val_veri->id_lote,
                                    "id_bin" => ($val_veri->id_bin == 0) ? null : $val_veri->id_bin,
                                    "cantidad_disponible" => $cantidad_restante
                                );

                                $cantidad_restante = $cantidad_restante - $val_veri->disponible;
                            }

                        }
                    }
                }
            }
        }

        return $array_lotes;
    }

}
