<?php

namespace App\Console\Commands;

use App\Models\Catalogo;
use App\Models\IntegracionProveedor;
use App\Models\ProductoIntegracion;
use App\Services\GenerateLogs;
use App\Services\Netsuite;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class ExtraccionCatalogoAbbott extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extraccion:catalogo-abbott';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta la extracción del catálogo de Abbott';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //Busqueda DB INTEGRACION ECOMERX | CATALOGO ABBOTT PRODUCTOS
        $error = 0;
        $mensaje_error = '';

        //establecer variables
        $busqueda_guardada = "customsearch_db_int_ecox_catalogo_prod_2"; //produccion

        $program = IntegracionProveedor::where('nombre','ABBOTT')->first();        
        $fecha_inicio_log = Date('Y-m-d\TH:i:s');
        $valores_actualizados = 0;
        $valores_creados = 0;
        $cantidad_busqueda = 0;
        
        try {

            //primera consulta a netsuite, consultar cantidades
            $cantidad_busqueda = Netsuite::get(2097, $busqueda_guardada, 0, 1);
            
            if(intval($cantidad_busqueda) > 0){
                
                //vaciar tabla
                DB::table('producto_integracions')
                    ->where('integracion_id', $program->id)
                    ->delete();
                
                $start = 0; $end = 0;

                $cantidad_for = ceil($cantidad_busqueda/1000)*1000;
                $cantidad_dividir = intval($cantidad_for / 1000);
                for ($i=0; $i < $cantidad_dividir ; $i++) {

                    $start = ($i == 0) ? 0 : $start + 1000;
                    $end = $end + 1000;
                    $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT);
                    
                    $resultado = Netsuite::get(2442, null, $start, $end); //produccion
                    
                    $data = json_decode($resultado, true);
                    if(isset($data['error'])){
                        $error = 1;
                        $mensaje_error = json_encode($data);
                    } else {
                        if($data["message"] == 'error'){
                            $error = 1;
                            $mensaje_error = $data["response"];
                        } else {
                            $insertar = [];
                            foreach ($data["response"] as $key => $value) {                             
                                
                                $netsuite_id  = empty($value["values"]["internalid"]) ? null : $value["values"]["internalid"][0]["value"];
                                $ean          = empty($value["values"]["upccode"]) ? null : $value["values"]["upccode"];
                                $canje        = empty($value["values"]["custitem_axa_id_corres_com_"]) ? null : $value["values"]["custitem_axa_id_corres_com_"];
                                $product = Catalogo::where('producto_netsuite_id',intval($netsuite_id))->first();
                                
                                if($product && !isset($canje)){
                                    $insertar[] = [
                                        'integracion_id' => $program->id,
                                        'producto_id' => $product->id,
                                        'active'     => 1,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                    
                                    $valores_creados++;
                                }         
                            }        
                                            
                            // Insertar nuevos clientes
                            if (!empty($insertar)) {
                                ProductoIntegracion::insert($insertar);
                            }                                                            
                        }
                    }
                }

            } else {
                $error = 1;
                $mensaje_error = 'Se presento un error al consultar la busqueda "'.$busqueda_guardada.'"! No se pudo extraer nada. Error: ' . json_encode($cantidad_busqueda);
            }

        } catch (\Throwable $th) {
            $error = 1;
            $mensaje_error = $th;
        }

        //por ultimo, generamos log
        GenerateLogs::generateLogScheduleTask("ACTUALIZACIÓN PRODUCTOS ABBOTT", "producto_integracions", $error, $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error);
    }
}
