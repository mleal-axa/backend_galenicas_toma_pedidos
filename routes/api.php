<?php

use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ColasController;
use App\Http\Controllers\JobExecutionController;
use App\Http\Controllers\MedicosController;
use App\Http\Controllers\MediosPagosController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\ScheduledTasksController;
use App\Http\Controllers\TransportadoraController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Iniciar Sesión
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function() {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //Cerrar Sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    //princpales
    Route::controller(PrincipalController::class)
        ->prefix('/')->group(function () {
        Route::get('/listas-de-precios/get', 'getListasPrecios');
        Route::get('/catalogo/get/{lista}', 'getCatalogo');
        Route::get('/inventario/get/{lista}/{ubicacion}', 'getInventario');
    });

    //usuarios
    Route::controller(UserController::class)
        ->prefix('/users')->group(function () {
        Route::get('/', 'get');
    });

    //transportadora
    Route::controller(TransportadoraController::class)
        ->prefix('/transportadora')->group(function () {
        Route::get('/', 'get');
    });

    //medios de pago
    Route::controller(MediosPagosController::class)
        ->prefix('/medios-de-pagos')->group(function () {
        Route::get('/', 'get');
    });

    //ubicaciones
    Route::controller(UbicacionController::class)
        ->prefix('/ubicaciones')->group(function () {
        Route::get('/', 'get');
    });

    //medicos
    Route::controller(MedicosController::class)
        ->prefix('/medicos')->group(function () {
        Route::get('/', 'get');
    });

    //Clientes
    Route::controller(ClienteController::class)
        ->prefix('/clientes')->group(function () {
        Route::get('/', 'all');
        Route::get('/{id}', 'get');

        Route::get('/direcciones/{id}', 'getDirecciones');
    });

    //Pedidos
    Route::controller(PedidoController::class)
        ->prefix('/pedido')->group(function () {
        Route::get('/get/valores-envios', 'getValoresEnvios');
        Route::get('/get/catalogo/{lista}/{ubicacion}', 'getCatalogo');
        Route::get('/get/promociones/{producto}/{categoria}', 'getPromociones');
        Route::post('/create', 'create');
        Route::get('/mis-pedidos/{fecha_inicio?}/{fecha_fin?}', 'getPedidosUser');
        Route::get('/get/{id}', 'get');
        Route::get('/cancel/{id}', 'cancel');
        Route::get('/editar/get/{id}', 'getEditar');
        Route::post('/update', 'update');
        Route::get('/estados', 'getEstados');
        Route::get('/send-netsuite/{id}', 'enviarPedidoNetsuite');
        Route::post('/update/estado', 'updateEstado');
    });

    //administrador
    Route::controller(AdministradorController::class)
        ->prefix('/administrador')->group(function () {
        Route::get('/pedido/get/{id}', 'getPedido');
        Route::post('/pedido/all', 'allPedidos');
        Route::get('/pedido/get/error/{id}', 'getErrorNs');
    });

    //colas de procesos
    Route::controller(ColasController::class)
        ->prefix('/colas-procesos')->group(function () {
        Route::get('/get/en-proceso', 'getEnProceso');
        Route::get('/get/pendientes/{fecha}', 'getPendientes');
        Route::get('/get/errores/{fecha}', 'getErrores');
    });

    //tareas programadas
    Route::controller(ScheduledTasksController::class)
        ->prefix('/tareas-programadas')->group(function () {
        Route::post('/get/errores', 'getErrores');
        Route::get('/ejecutar/{id}', 'ejecutar');
    });

});

Route::post('/SendOrder', [JobExecutionController::class, 'SendOrder']);

