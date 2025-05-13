<?php

namespace Database\Seeders;

use App\Models\Transportadoras;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportadoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Transportadoras::insert([
            [
                'netsuite_id' => 1,
                'nombre' => "Servientrega S.A",
                'estado' => 1
            ],
            [
                'netsuite_id' => 2,
                'nombre' => "Deprisa",
                'estado' => 1
            ],
            [
                'netsuite_id' => 3,
                'nombre' => "TCC",
                'estado' => 1
            ],
            [
                'netsuite_id' => 4,
                'nombre' => "Interrapidisimo",
                'estado' => 1
            ],
            [
                'netsuite_id' => 5,
                'nombre' => "Vueltap",
                'estado' => 1
            ],
            [
                'netsuite_id' => 6,
                'nombre' => "Alianza logistica",
                'estado' => 1
            ],
            [
                'netsuite_id' => 7,
                'nombre' => "Transporte de AXA",
                'estado' => 1
            ],
            [
                'netsuite_id' => 8,
                'nombre' => "Acuerdo cliente",
                'estado' => 1
            ],
            [
                'netsuite_id' => 9,
                'nombre' => "Otro",
                'estado' => 1
            ],
            [
                'netsuite_id' => 10,
                'nombre' => "Recoge en bodega",
                'estado' => 1
            ],
        ]);
    }
}
