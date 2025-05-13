<?php

namespace App\Console\Commands;

use App\Models\Kit;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;

class ExtraccionKits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:kits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información de los kits, desde el ERP Netsuite. Esta tarea se ejecuta una vez por dia.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_tp_kits_galenica', 0, 1);
            if(intval($resultado_cantidad) > 0){

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2176, null, $start, $end);
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
                            $array_actualizar = array();

                            //recorrer datos
                            foreach ($data["response"] as $key => $value) {

                                $netsuite_id                = $value["id"];
                                $nombre                     = $value["values"]["itemid"];
                                $ean                        = $value["values"]["memberItem.upccode"];
                                $linea                      = $value["values"]["custitem_nso_axa_field_item_linea"];
                                $centro_de_costo            = empty($value["values"]["department"]) ? '' : $value["values"]["department"][0]["text"];
                                $componente_id              = empty($value["values"]["memberitem"]) ? null : $value["values"]["memberitem"][0]["value"];
                                $componente                 = empty($value["values"]["memberitem"]) ? '' : $value["values"]["memberitem"][0]["text"];
                                $cantidad_kit               = $value["values"]["memberquantity"];
                                $cantidad_regular           = intval($value["values"]["custitem_axa_q_regular_combos_pjcl"]);
                                $cantidad_regalo            = intval($value["values"]["custitem_axa_q_obsequio_oferta_pjcl"]);
                                $isinactive                 = $value["values"]["isinactive"];

                                //validar
                                $isinactive = ($isinactive == false) ? 0 : 1;

                                $sql_validar = Kit::select('id')->where('netsuite_id', $netsuite_id)->get();
                                if(count($sql_validar) > 0){
                                    $array_actualizar[] = array(
                                        'netsuite_id' => $netsuite_id,
                                        'nombre' => $nombre,
                                        'ean' => $ean,
                                        'linea' => $linea,
                                        'centro_de_costo' => $centro_de_costo,
                                        'componente_id' => $componente_id,
                                        'componente' => $componente,
                                        'cantidad_kit' => $cantidad_kit,
                                        'cantidad_regular' => $cantidad_regular,
                                        'cantidad_regalo' => $cantidad_regalo,
                                        'updated_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_actualizados++;
                                } else {
                                    $array_crear[] = array(
                                        'netsuite_id' => $netsuite_id,
                                        'nombre' => $nombre,
                                        'ean' => $ean,
                                        'linea' => $linea,
                                        'centro_de_costo' => $centro_de_costo,
                                        'componente_id' => $componente_id,
                                        'componente' => $componente,
                                        'cantidad_kit' => $cantidad_kit,
                                        'cantidad_regular' => $cantidad_regular,
                                        'cantidad_regalo' => $cantidad_regalo,
                                        'created_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_creados++;
                                }

                            }

                            //validamos si existe para crear
                            if(count($array_crear) > 0){
                                Kit::insert($array_crear);
                            }

                            //validamos si existe para actualizar
                            if(count($array_actualizar) > 0){
                                Kit::massUpdate(
                                    values: $array_actualizar,
                                    uniqueBy: ['netsuite_id']
                                );
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
        GenerateLogs::generateLogScheduleTask("EXTRACCION KITS", "kits", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
