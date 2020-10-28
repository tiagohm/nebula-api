<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        $reportPath = storage_path('report.json');
        $reportData = file_exists($reportPath) ?
            json_decode(file_get_contents($reportPath)) :
            [];

        foreach ($data as $item) {
            $names = array_key_exists('names', $item) ? $item['names'] : NULL;

            if (empty($names)) {
                $strNames = NULL;
            } else {
                $strNames = array_reduce($names, $transformName, '');
            }

            $item['names'] = $strNames;
            $reported = array_search($item['id'], $reportData) !== false;
            $item['reported'] = $reported;

            DB::table('deepsky')->insert($item);
        }
    }
}
