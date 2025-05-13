<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\DetallesInventarios;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtraccionDetallesInventarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:detalles-inventarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $error = 0;
        $mensaje_error = '';

        //establecer variables
        $fecha_inicio_log = Date('Y-m-d\TH:i:s');
        $valores_actualizados = 0;
        $valores_creados = 0;
        $cantidad_busqueda = 0;

        try {

            //primera consulta a netsuite, consultar cantidades
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_detalle_invt_tp_gal', 0, 1, 'InventoryBalance');
            if(intval($resultado_cantidad) > 0){

                //vaciar tabla
                DB::table('detalles_inventarios')->truncate();

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2181, null, $start, $end);
                    $data = json_decode($resultado, true);
                    if(isset($data['error'])){
                        $error = 1;
                        $mensaje_error = json_encode($data);
                    } else {
                        if($data["message"] == 'error'){
                            $error = 1;
                            $mensaje_error = $data["response"];
                        } else {

                            $cantidad_busqueda = $cantidad_busqueda + count($data["response"]);
                            $array_crear = array();
                            //$array_actualizar = array();

                            //recorrer datos
                            foreach ($data["response"] as $key => $value) {

                                $item_id                                = $value["values"]["item.internalid"][0]["text"];
                                $ean                                    = $value["values"]["item.upccode"];
                                $nombre_item                            = $value["values"]["item"][0]["text"];
                                $id_lote                                = empty($value["values"]["inventoryNumber.internalid"]) ? null : $value["values"]["inventoryNumber.internalid"][0]["text"];
                                $lote                                   = empty($value["values"]["inventorynumber"]) ? null : $value["values"]["inventorynumber"][0]["text"];
                                $id_bin                                 = empty($value["values"]["binNumber.internalid"]) ? null : $value["values"]["binNumber.internalid"][0]["text"];
                                $bin                                    = empty($value["values"]["binnumber"]) ? null : $value["values"]["binnumber"][0]["text"];
                                $ubicacion_id                           = empty($value["values"]["location"]) ? null : $value["values"]["location"][0]["value"];
                                $ubicacion                              = empty($value["values"]["location"]) ? null : $value["values"]["location"][0]["text"];
                                $estado                                 = empty($value["values"]["status"]) ? '' : $value["values"]["status"][0]["text"];
                                $saldo                                  = empty($value["values"]["onhand"]) ? 0 : $value["values"]["onhand"];
                                $disponible                             = empty($value["values"]["available"]) ? 0 : $value["values"]["available"];
                                $es_producto_numerado_por_lote          = ($value["values"]["item.islotitem"] == true) ? 'Si' : 'No';
                                $fecha_vencimiento                      = empty($value["values"]["inventoryNumber.expirationdate"]) ? null : Helper::arreglarFecha($value["values"]["inventoryNumber.expirationdate"]);
                                $inactivo                               = ($value["values"]["item.isinactive"] == true) ? 'Si' : 'No';

                                $array_crear[] = array(
                                    'item_id' => $item_id,
                                    'ean' => $ean,
                                    'nombre_item' => $nombre_item,
                                    'id_lote' => $id_lote,
                                    'lote' => $lote,
                                    'id_bin' => $id_bin,
                                    'bin' => $bin,
                                    'ubicacion_id' => $ubicacion_id,
                                    'ubicacion' => $ubicacion,
                                    'estado' => $estado,
                                    'saldo' => $saldo,
                                    'disponible' => $disponible,
                                    'es_producto_numerado_por_lote' => $es_producto_numerado_por_lote,
                                    'fecha_vencimiento' => $fecha_vencimiento,
                                    'inactivo' => $inactivo,
                                    'created_at' => Date('Y-m-d\TH:i:s')
                                );

                            }

                            //validamos si existe para crear o actualizar
                            if(count($array_crear) > 0){
                                DetallesInventarios::insert($array_crear);
                            }
                        }
                    }
                }
            } else {
                $error = 1;
                $mensaje_error = 'Se presento un error al consultar la busqueda! No se pudo extraer nada.';
            }

        } catch (\Throwable $e) {
            $error = 1;
            $mensaje_error = $e;
        }

        //por ultimo, generamos log
        GenerateLogs::generateLogScheduleTask("EXTRACCION DETALLE INVENTARIO", "detalles_inventarios", $error, $cantidad_busqueda, $cantidad_busqueda, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
