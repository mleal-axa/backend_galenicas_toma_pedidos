<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedidos extends Model
{
    use HasFactory;

    protected $fillable = [
        'netsuite_id',
        'numero_factura',
        'user_id',
        'cliente_id',
        'direccion_id',
        'ubicacion_id',
        'lista_precio_id',
        'fecha',
        'nota',
        'transportadora_id',
        'medio_pago_id',
        'medico_id',
        'metodo_pago',
        'estado',
        'cantidad_items',
        'cantidad_items_solicitado',
        'impuesto',
        'subtotal',
        'envio',
        'descuento',
        'total',
        'user_id_canceled',
        'user_id_updated',
        'fecha_respuesta_netsuite'
    ];

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'netsuite_id');
    }

    public function direccion() {
        return $this->belongsTo(DireccionesCliente::class, 'direccion_id', 'netsuite_id');
    }

    public function ubicacion() {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id', 'netsuite_id');
    }

    public function listaPrecio() {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id', 'netsuite_id');
    }

    public function transportadora() {
        return $this->belongsTo(Transportadoras::class, 'transportadora_id', 'netsuite_id');
    }

    public function medioPago() {
        return $this->belongsTo(MediosPagos::class, 'medio_pago_id', 'netsuite_id');
    }

    public function medico() {
        return $this->belongsTo(Medicos::class, 'medico_id', 'netsuite_id');
    }

    public function usuario() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productos() {
        return $this->hasMany(DetallesPedidos::class, 'pedido_id');
    }

}
