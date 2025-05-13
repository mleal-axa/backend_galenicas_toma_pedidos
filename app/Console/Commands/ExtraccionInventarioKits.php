<?php

namespace App\Console\Commands;

use App\Models\InventarioKitsNetsuite;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtraccionInventarioKits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:inventario-kits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información de inventario de los kits, desde el ERP Netsuite. Esta tarea se ejecuta cadda 10 minutos en horario laboral.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearchaxa_bus_com_tp_invkits_3', 0, 1);
            if(intval($resultado_cantidad) > 0){

                //vaciar tabla
                DB::table('inventario_kits_netsuites')->truncate();

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2174, null, $start, $end);
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

                                $producto_netsuite_id               = $value["values"]["GROUP(internalid)"][0]["text"];
                                $producto                           = $value["values"]["GROUP(itemid)"];
                                $ean                                = $value["values"]["GROUP(upccode)"];
                                $id_ubicacion                       = $value["values"]["GROUP(memberItem.inventorylocation)"][0]["value"];
                                $ubicacion                          = $value["values"]["GROUP(memberItem.inventorylocation)"][0]["text"];
                                $disponible                         = intval($value["values"]["MIN(formulatext)"]);

                                $array_insertar[] = array(
                                    'producto_netsuite_id' => $producto_netsuite_id,
                                    'producto' => $producto,
                                    'ean' => $ean,
                                    'id_ubicacion' => $id_ubicacion,
                                    'ubicacion' => $ubicacion,
                                    'disponible' => $disponible,
                                    'created_at' => Date('Y-m-d\TH:i:s')
                                );

                            }

                            //insertar en la tabla
                            InventarioKitsNetsuite::insert($array_insertar);

                        }
                    }
                }

                //validar informacion
                $consulta_2 = InventarioKitsNetsuite::select('id')->count();
                $validar_consulta_2 = $consulta_2 - $cantidad_busqueda;
                if($validar_consulta_2 <= 100){

                    //actualizar la tabla principal
                    DB::table('inventario_kits')->truncate();
                    DB::select("INSERT INTO inventario_kits SELECT * FROM inventario_kits_netsuites");

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
        GenerateLogs::generateLogScheduleTask("EXTRACCION INVENTARIO KITS", "inventario_kits", $error, $cantidad_busqueda, $cantidad_busqueda, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
