<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Apaga a tabela.

        DB::table('users')->delete();

        DB::table('users')->insert([
            'name' => 'Tiago',
            'api_token' => env('API_TOKEN')
        ]);
    }
}
