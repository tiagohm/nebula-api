<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // DeepSky.

        ini_set('memory_limit', '2G');

        DB::table('deepsky')->delete();

        $contents = file_get_contents(__DIR__ . '/catalog.json');
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
