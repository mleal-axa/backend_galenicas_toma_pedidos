<?php

namespace App\Console\Commands;

use App\Models\Medicos;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;

class ExtraccionMedicos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:medicos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información de los medicos, desde el ERP Netsuite. Esta tarea se ejecuta una vez por dia.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_medicos_tp_galenica', 0, 1);
            if(intval($resultado_cantidad) > 0){

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2180, null, $start, $end);
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
                                $name                       = $value["values"]["name"];
                                $isinactive                 = $value["values"]["isinactive"];

                                //validar
                                $isinactive = ($isinactive == false) ? 0 : 1;

                                $sql_validar = Medicos::select('id')->where('netsuite_id', $netsuite_id)->get();
                                if(count($sql_validar) > 0){
                                    $array_actualizar[] = array(
                                        'netsuite_id' => $netsuite_id,
                                        'medico' => $name,
                                        'isinactive' => $isinactive,
                                        'updated_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_actualizados++;
                                } else {
                                    $array_crear[] = array(
                                        'netsuite_id' => $netsuite_id,
                                        'medico' => $name,
                                        'isinactive' => $isinactive,
                                        'created_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_creados++;
                                }

                            }

                            //validamos si existe para crear
                            if(count($array_crear) > 0){
                                Medicos::insert($array_crear);
                            }

                            //validamos si existe para actualizar
                            if(count($array_actualizar) > 0){
                                Medicos::massUpdate(
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
        GenerateLogs::generateLogScheduleTask("EXTRACCION MEDICOS", "medicos", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
