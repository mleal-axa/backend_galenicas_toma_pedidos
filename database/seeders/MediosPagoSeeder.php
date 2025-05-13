<?php

namespace Database\Seeders;

use App\Models\MediosPagos;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MediosPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MediosPagos::insert([
            [
                'netsuite_id' => 1,
                'nombre' => "DATAFONO",
                'estado' => 1
            ],
            [
                'netsuite_id' => 2,
                'nombre' => "PAYULATAM",
                'estado' => 1
            ],
            [
                'netsuite_id' => 3,
                'nombre' => "CONSIGNACION",
                'estado' => 1
            ],
            [
                'netsuite_id' => 4,
                'nombre' => "EFECTIVO",
                'estado' => 1
            ],
            [
                'netsuite_id' => 5,
                'nombre' => "TRANSFERENCIA",
                'estado' => 1
            ],
            [
                'netsuite_id' => 6,
                'nombre' => "MERCADO PAGO",
                'estado' => 1
            ],
            [
                'netsuite_id' => 7,
                'nombre' => "LINIO",
                'estado' => 1
            ],
            [
                'netsuite_id' => 8,
                'nombre' => "DAFITI",
                'estado' => 1
            ],
            [
                'netsuite_id' => 9,
                'nombre' => "MARKET RCN",
                'estado' => 1
            ],
            [
                'netsuite_id' => 10,
                'nombre' => "TARJETA DE CREDITO",
                'estado' => 1
            ],
            [
                'netsuite_id' => 11,
                'nombre' => "PSE",
                'estado' => 1
            ],
        ]);
    }
}
