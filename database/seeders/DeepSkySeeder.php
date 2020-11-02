<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeepSkySeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        ini_set('memory_limit', '2G');

        // Apaga a tabela.

        DB::table('deepsky')->delete();

        // Popula a tabela com a lista de DSOs.

        $contents = file_get_contents(storage_path('catalog.json'));
        $data = json_decode($contents, true);

        $transformName = function ($a, $b) {
            return $a . "[$b]";
        };

        foreach ($data as $item) {
            $names = array_key_exists('names', $item) ? $item['names'] : NULL;

            if (empty($names)) {
                $strNames = NULL;
            } else {
                $strNames = array_reduce($names, $transformName, '');
            }

            $item['names'] = $strNames;

            DB::table('deepsky')->insert($item);
        }
    }
}
