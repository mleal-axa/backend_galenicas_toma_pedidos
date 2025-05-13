<?php

namespace App\Services;

use App\Models\LogScheduleTask;
use DateTime;

class GenerateLogs {

    static public function generateLogScheduleTask($proceso, $tabla, $error, $cantidad_registros, $valores_creados, $valores_actualizados, $fecha_inicio_log, $mensaje_error)
    {
        $fechaInicio = new DateTime($fecha_inicio_log);
        $fechaFin = new DateTime(Date('Y-m-d\TH:i:s'));
        $intervalo = $fechaInicio->diff($fechaFin);

        $array = array(
            'proceso' => $proceso,
            'tabla' => $tabla,
            'error' => $error,
            'cant_registro' => $cantidad_registros,
            'cant_insertados' => $valores_creados,
            'cant_actualizados' => $valores_actualizados,
            'fecha' => date('Y-m-d'),
            'fecha_inicio' => $fecha_inicio_log,
            'fecha_fin' => Date('Y-m-d\TH:i:s'),
            'tiempo' => $intervalo->h . " horas, " .$intervalo->i . " minutos y " . $intervalo->s . " segundos",
            'mensaje_error' => $mensaje_error
        );

        return (LogScheduleTask::create($array)) ? true : false;
    }

}

?>
