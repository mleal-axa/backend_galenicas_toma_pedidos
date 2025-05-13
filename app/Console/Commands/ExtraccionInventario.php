<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\InventarioNetsuite;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtraccionInventario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:inventario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información de inventario, desde el ERP Netsuite. Esta tarea se ejecuta cadda 10 minutos en horario laboral.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_invent_galenica_tp', 0, 1);
            if(intval($resultado_cantidad) > 0){

                //vaciar tabla
                DB::table('inventario_netsuites')->truncate();

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2173, null, $start, $end);
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
                            $array_insertar = array();

                            //recorrer datos
                            foreach ($data["response"] as $key => $value) {

                                $producto_netsuite_id               = $value["values"]["internalid"][0]["text"];
                                $producto                           = $value["values"]["itemid"];
                                $ean                                = $value["values"]["upccode"];
                                $id_ubicacion                       = $value["values"]["inventoryLocation.internalid"][0]["text"];
                                $ubicacion                          = $value["values"]["inventoryLocation.name"];
                                $disponible                         = $value["values"]["locationquantityavailable"];
                                $es_producto_numerado_por_lote      = $value["values"]["islotitem"];

                                $es_lote = ($es_producto_numerado_por_lote == false) ? 0 : 1;

                                $array_insertar[] = array(
                                    'producto_netsuite_id' => $producto_netsuite_id,
                                    'producto' => $producto,
                                    'ean' => $ean,
                                    'id_ubicacion' => $id_ubicacion,
                                    'ubicacion' => $ubicacion,
                                    'disponible' => $disponible,
                                    'es_producto_numerado_por_lote' => $es_lote,
                                    'created_at' => Date('Y-m-d\TH:i:s')
                                );

                            }

                            //insertar en la tabla
                            InventarioNetsuite::insert($array_insertar);

                        }
                    }
                }

                //validar informacion
                $consulta_2 = InventarioNetsuite::select('id')->count();
                $validar_consulta_2 = $consulta_2 - $cantidad_busqueda;
                if($validar_consulta_2 <= 100){

                    //actualizar la tabla principal
                    DB::table('inventarios')->truncate();
                    DB::select("INSERT INTO inventarios SELECT * FROM inventario_netsuites");

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
        GenerateLogs::generateLogScheduleTask("EXTRACCION INVENTARIO", "inventario", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
