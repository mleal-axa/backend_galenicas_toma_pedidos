<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\CategoriaPromocion;
use App\Models\ItemsPromociones;
use App\Models\Promociones;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;

class ExtraccionPromociones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:promociones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información de las promociones, desde el ERP Netsuite. Esta tarea se ejecuta una vez por dia.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_promotions_tp_galen', 0, 1);
            if(intval($resultado_cantidad) > 0){

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2177, null, $start, $end);
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
                            $id_prmociones = array();

                            //recorrer datos
                            foreach ($data["response"] as $key => $value) {

                                $netsuite_id                    = $value["values"]["MAX(internalid)"];
                                $nombre                         = $value["values"]["GROUP(name)"];
                                $codigo                         = $value["values"]["GROUP(code)"];
                                $fecha_inicio                   = $value["values"]["MAX(startdate)"];
                                $fecha_fin                      = $value["values"]["MAX(enddate)"];
                                $descuento                      = $value["values"]["MAX(discountrate)"];
                                $id_busqueda_items              = empty($value["values"]["GROUP(itemssavedsearch)"]) ? null : $value["values"]["GROUP(itemssavedsearch)"][0]["value"];
                                $nombre_busqueda_items          = empty($value["values"]["GROUP(itemssavedsearch)"]) ? null : $value["values"]["GROUP(itemssavedsearch)"][0]["text"];
                                $descripcion                    = $value["values"]["MAX(description)"];
                                $categorias                     = empty($value["values"]["MAX(customercategory)"]) ? '' : $value["values"]["MAX(customercategory)"];
                                $ubicaciones                    = $value["values"]["MAX(location)"];

                                //arreglar
                                $fecha_inicio = Helper::arreglarFecha($fecha_inicio);
                                $fecha_fin = Helper::arreglarFecha($fecha_fin);
                                $descuento = abs(intval($descuento));
                                $estado = Helper::checkFechaRango($fecha_inicio, $fecha_fin, date('Y-m-d'));
                                $categorias = explode(",", $categorias);

                                $informacion = array(
                                    'netsuite_id' => $netsuite_id,
                                    'nombre' => $nombre,
                                    'codigo' => $codigo,
                                    'fecha_inicio' => $fecha_inicio,
                                    'fecha_fin' => $fecha_fin,
                                    'descuento' => $descuento,
                                    'id_busqueda_items' => $id_busqueda_items,
                                    'nombre_busqueda_items' => $nombre_busqueda_items,
                                    'descripcion' => $descripcion,
                                    'ubicaciones' => $ubicaciones,
                                    'isinactive' => $estado,
                                );

                                $validar_registro = Promociones::where('netsuite_id', $netsuite_id)->exists();
                                if($validar_registro){
                                    $informacion['updated_at'] = Date('Y-m-d\TH:i:s');
                                    if(Promociones::where('netsuite_id', $netsuite_id)->update($informacion)){
                                        $promocion = Promociones::where('netsuite_id', $netsuite_id)->first();
                                    }
                                    $valores_actualizados++;
                                } else {
                                    $informacion['created_at'] = Date('Y-m-d\TH:i:s');
                                    $promocion = Promociones::create($informacion);
                                    $valores_creados++;
                                }

                                if($promocion){
                                    $id_prmociones[] = $promocion->id;
                                    CategoriaPromocion::where('promocion_id', $promocion->id)->delete();
                                    if(count($categorias) > 0) {
                                        foreach ($categorias as $key => $category) {
                                            $array_categoria = array(
                                                'promocion_id' => $promocion->id,
                                                'nombre' => trim($category)
                                            );
                                            CategoriaPromocion::create($array_categoria);
                                        }
                                    }
                                }

                            }

                            //inactivar las promociones que no vienen en la busqueda guarda
                            Promociones::whereNotIn('id', $id_prmociones)->update(array('isinactive' => 1));
                        }
                    }

                }

                $promociones_activas = Promociones::where('isinactive', 0)->get();
                if(count($promociones_activas) > 0){
                    foreach ($promociones_activas as $key => $val_promocion) {
                        $this->ejecutarItemsPromociones($val_promocion);
                    }
                }

            }

        } catch (\Throwable $e) {
            $error = 1;
            $mensaje_error = $e;
        }

        //por ultimo, generamos log
        GenerateLogs::generateLogScheduleTask("EXTRACCION PROMOCIONES", "promociones", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }

    private function ejecutarItemsPromociones($promocion)
    {
        $result_cant = Netsuite::get(2097, $promocion->id_busqueda_items, 0, 1);
        if(intval($result_cant) > 0){

            ItemsPromociones::where('promocion_id', $promocion->id)->delete();

            $start_prom = 0;
            $end_prom = 0;
            $cantidad_for_prom = ceil($result_cant/1000)*1000;
            $cantidad_dividir_prom = intval($cantidad_for_prom / 1000);

            for ($i=0; $i < $cantidad_dividir_prom ; $i++) {

                $start_prom = ($i == 0) ? 0 : $start_prom + 1000;
                $end_prom = $end_prom + 1000;
                $start_prom= str_pad($start_prom, mb_strlen($end_prom), "0", STR_PAD_LEFT);

                //consultar a netsuite
                $resultado_prom = Netsuite::get(2178, $promocion->id_busqueda_items, $start_prom, $end_prom);
                $data_prom = json_decode($resultado_prom, true);

                //recorrer datos
                foreach ($data_prom as $key => $value) {

                    $act_items = array(
                        'promocion_id' => $promocion->id,
                        'promocion_netsuite_id' => $promocion->netsuite_id,
                        'producto_netsuite_id' => $value["id"],
                        'producto' => $value["values"]["itemid"]
                    );
                    ItemsPromociones::create($act_items);

                }
            }
        }
    }
}
