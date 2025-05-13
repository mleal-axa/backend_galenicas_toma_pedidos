<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'netsuite_id' => 1,
            'name' => 'Administrador',
            'email' => 'brayan.rodriguez@axa.com.co',
            'password' => Hash::make('123456789'),
            'password_text' => '123456789',
            'categoria' => 'ADMINISTRADOR',
            'active' => 1
        ]);
    }
}
