<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\LogScheduleTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduledTasksController extends Controller
{

    public function getErrores(Request $request)
    {
        return response()->json(array(
            "data" => LogScheduleTask::where('tabla', $request->id)
                ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                ->orderBy('id')
                ->get()
        ));
    }

    public function ejecutar($id)
    {
        $comando = Helper::getComandoExtraccion($id);
        $exitCode = Artisan::call($comando);
        if ($exitCode === 0) {
            return response()->json(array(
                "success" => true,
                "mensaje" => "Tarea Programada ejecutada con exito."
            ));
        } else {
            return response()->json(array(
                "success" => false,
                "mensaje" => "Ocurrio un problema al ejecutar la tarea programada!"
            ));
        }
    }

}
