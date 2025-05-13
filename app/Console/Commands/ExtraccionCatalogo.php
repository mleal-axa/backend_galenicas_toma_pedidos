<?php

namespace App\Console\Commands;

use App\Models\Catalogo;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Console\Command;

class ExtraccionCatalogo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:catalogo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de la información del catalogo, desde el ERP Netsuite. Esta tarea se ejecuta una vez por dia.';

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
            $resultado_cantidad = Netsuite::get(2097, 'customsearch_ecomerx_catalogo_bi_galen', 0, 1);
            if(intval($resultado_cantidad) > 0){

                $start = 0;
                $end = 0;

                $cantidad_for = ceil($resultado_cantidad/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);

                    $resultado = Netsuite::get(2175, null, $start, $end);
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

                                $producto_netsuite_id               = $value["id"];
                                $producto                           = $value["values"]["itemid"];
                                $ean                                = $value["values"]["upccode"];
                                $tipo                               = $value["values"]["type"][0]["text"];
                                $lista_precio_id                    = $value["values"]["pricing.pricelevel"][0]["value"];
                                $linea                              = $value["values"]["custitem_nso_axa_field_item_linea"];
                                $precio_venta                       = empty($value["values"]["pricing.unitprice"]) ? 0 : $value["values"]["pricing.unitprice"];
                                $tasa_iva                           = empty($value["values"]["taxschedule"]) ? null : $value["values"]["taxschedule"][0]["text"];
                                $iva                                = empty($value["values"]["custitem_ks_tarifa_de_iva"]) ? 0 : $value["values"]["custitem_ks_tarifa_de_iva"][0]["text"];
                                $precion_con_iva                    = empty($value["values"]["formulanumeric"]) ? 0 : $value["values"]["formulanumeric"];
                                $cantidad_maxima                    = 0;//empty($value["values"]["pricing.maximumquantity"]) ? 0 : $value["values"]["pricing.maximumquantity"];
                                $cantidad_minima                    = 0;//empty($value["values"]["pricing.minimumquantity"]) ? 0 : $value["values"]["pricing.minimumquantity"];
                                $embalaje                           = empty($value["values"]["custitem_nso_axa_field_item_fromalm"]) ? 0 : $value["values"]["custitem_nso_axa_field_item_fromalm"];
                                $es_lote                            = $value["values"]["islotitem"];
                                $es_controlado                      = $value["values"]["custitem_nso_axa_field_item_medcont"];
                                $isinactive                         = $value["values"]["isinactive"];

                                //validar
                                $iva = intval(str_replace('%', '', $iva));
                                $es_lote = ($es_lote == false) ? 0 : 1;
                                $es_controlado = ($es_controlado == false) ? 0 : 1;
                                $isinactive = ($isinactive == false) ? 0 : 1;

                                $sql_validar = Catalogo::select('id')->where([
                                    ['producto_netsuite_id', '=', $producto_netsuite_id],
                                    ['lista_precio_id', '=', $lista_precio_id]
                                ])->get();
                                if(count($sql_validar) > 0){
                                    $array_actualizar[] = array(
                                        'producto_netsuite_id' => $producto_netsuite_id,
                                        'producto' => $producto,
                                        'ean' => $ean,
                                        'tipo' => $tipo,
                                        'lista_precio_id' => $lista_precio_id,
                                        'linea' => $linea,
                                        'precio_venta' => $precio_venta,
                                        'tasa_iva' => $tasa_iva,
                                        'iva' => $iva,
                                        'precion_con_iva' => $precion_con_iva,
                                        'cantidad_maxima' => $cantidad_maxima,
                                        'cantidad_minima' => $cantidad_minima,
                                        'embalaje' => $embalaje,
                                        'es_lote' => $es_lote,
                                        'es_controlado' => $es_controlado,
                                        'isinactive' => $isinactive,
                                        'updated_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_actualizados++;
                                } else {
                                    $array_crear[] = array(
                                        'producto_netsuite_id' => $producto_netsuite_id,
                                        'producto' => $producto,
                                        'ean' => $ean,
                                        'tipo' => $tipo,
                                        'lista_precio_id' => $lista_precio_id,
                                        'linea' => $linea,
                                        'precio_venta' => $precio_venta,
                                        'tasa_iva' => $tasa_iva,
                                        'iva' => $iva,
                                        'precion_con_iva' => $precion_con_iva,
                                        'cantidad_maxima' => $cantidad_maxima,
                                        'cantidad_minima' => $cantidad_minima,
                                        'embalaje' => $embalaje,
                                        'es_lote' => $es_lote,
                                        'es_controlado' => $es_controlado,
                                        'isinactive' => $isinactive,
                                        'created_at' => Date('Y-m-d\TH:i:s')
                                    );
                                    $valores_creados++;
                                }

                            }

                            //validamos si existe para crear
                            if(count($array_crear) > 0){
                                Catalogo::insert($array_crear);
                            }

                            //validamos si existe para actualizar
                            if(count($array_actualizar) > 0){
                                Catalogo::massUpdate(
                                    values: $array_actualizar,
                                    uniqueBy: ['producto_netsuite_id', 'lista_precio_id']
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
        GenerateLogs::generateLogScheduleTask("EXTRACCION CATALOGO", "catalogo", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
