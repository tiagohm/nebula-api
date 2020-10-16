<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhotoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        ini_set('memory_limit', '2G');

        DB::table('photos')->delete();

        $contents = file_get_contents(__DIR__ . '/../../../data/photos/metadata.json');
        $data = json_decode($contents, true);

        foreach ($data as $key => $item) {
            $id = intval($key);
            $item['dso'] = $id;
            DB::table('photos')->insert($item);
        }
    }
}
